<?php

namespace App\Services;

use App\Enums\OrigenOrdenCompra;
use App\Models\OrdenCompra;
use App\Models\Producto;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Decide SI hay que reponer. No sabe armar una orden: eso es OrdenCompraService.
 *
 * Separarlos no es ceremonia. La decision es lo que se prueba con casos borde
 * (disponible negativo, umbral recien subido, orden ya abierta) y lo que se va
 * a querer ajustar cuando los umbrales resulten mal calibrados; el armado del
 * documento no cambia. Ademas, la orden manual usa OrdenCompraService sin pasar
 * por acá.
 *
 * <b>Los dos caminos que llegan hasta este servicio</b>
 *
 *   reactivo : StockService emite MovimientoStockRegistrado -> EvaluarReposicion
 *              evalua ESE producto. Responde en el momento del faltante.
 *   barrido  : el comando compras:revisar-reposicion recorre todo el catalogo.
 *              Cubre lo que el reactivo no puede ver: que alguien SUBA el punto
 *              de reposicion de un producto que ya estaba por debajo, productos
 *              cargados en falta, y eventos perdidos por un worker caido.
 *
 * Los dos terminan en evaluarProducto(), y evaluarProducto() es idempotente:
 * correrlo dos veces seguidas no genera dos pedidos ni infla el pedido que ya
 * cubre el faltante.
 */
class ReposicionService
{
    /**
     * Cuanto espera el lock por producto antes de rendirse.
     *
     * Si otra evaluacion del mismo producto esta en curso, la segunda no tiene
     * nada que aportar —va a leer el mismo saldo y encontrar la orden que la
     * primera acaba de crear—, asi que espera poco y se va.
     */
    private const ESPERA_LOCK_SEGUNDOS = 5;

    public function __construct(
        private readonly OrdenCompraService $ordenes,
    ) {}

    // ------------------------------------------------------------------
    // Evaluacion
    // ------------------------------------------------------------------

    /**
     * Evalua un producto y, si corresponde, lo deja pedido por la cantidad que
     * hace falta HOY.
     *
     * Devuelve la orden que quedo cubriendolo si hubo algun cambio, o null si
     * no habia nada que hacer. Que devuelva null es el caso normal: la enorme
     * mayoria de los movimientos de stock no dispara ninguna reposicion.
     *
     * <b>El lock no es opcional.</b> Dos ventas simultaneas del mismo producto
     * emiten dos eventos, los toman dos procesos, y los dos ven "no hay orden
     * abierta" antes de que ninguno la haya creado: sin el lock salen dos
     * ordenes por el mismo faltante, o peor, la segunda encuentra el borrador
     * que la primera acaba de crear y cae en la rama acumuladora de
     * agregarLinea(), duplicando la cantidad pedida. Es exactamente la carrera
     * que la tabla no puede impedir sola, porque no hay indice unico que
     * exprese "un producto no puede estar en dos ordenes abiertas".
     *
     * <b>Todo el cuerpo va DENTRO del closure de block(), a proposito.</b> El
     * lock se sostiene mientras el closure corre y Laravel lo libera al salir.
     * Dejar afuera la lectura de ordenAbiertaDe() —aunque el lock se hubiera
     * tomado unas lineas antes— es lo mismo que no tener lock: la decision de
     * pedir o ampliar se toma sobre un estado que otro proceso puede estar
     * cambiando en ese mismo instante.
     */
    public function evaluarProducto(Producto $producto): ?OrdenCompra
    {
        try {
            return Cache::lock("reposicion:producto:{$producto->getKey()}", 30)
                ->block(self::ESPERA_LOCK_SEGUNDOS, function () use ($producto) {
                    // Releido bajo el lock: entre que se emitio el evento y que
                    // este job se ejecuto pudo entrar una recepcion de compra y
                    // el faltante ya no existe. Decidir sobre el saldo del
                    // evento seria pedir de mas.
                    $producto->refresh();

                    if (! $this->necesitaReposicion($producto)) {
                        return null;
                    }

                    $abierta = $this->ordenAbiertaDe($producto);

                    // Sin orden abierta: se pide de cero.
                    if ($abierta === null) {
                        return $this->ordenes->pedir(
                            $producto,
                            $this->cantidadSugerida($producto),
                            OrigenOrdenCompra::Automatica,
                        );
                    }

                    // Hay una orden abierta, pero eso no significa que alcance.
                    //
                    // El faltante pudo CRECER despues de emitirla: varias ventas
                    // seguidas del mismo producto, cada una empujando el disponible
                    // mas abajo. Dar el faltante por cubierto solo porque existe un
                    // pedido deja al producto pedido de menos, y cuando la
                    // mercaderia llega no destraba las preventas que la esperaban.
                    //
                    // Mientras sea BORRADOR se amplia: nadie lo aprobo y nada
                    // salio, asi que no hay documento que respetar.
                    if ($abierta->esBorrador()) {
                        return $this->ordenes->ampliar(
                            $abierta,
                            $producto,
                            $this->cantidadSugerida($producto),
                        );
                    }

                    // Aprobada, enviada o recibida parcial: el documento esta
                    // cerrado. Cambiarle la cantidad seria reescribir lo que el
                    // administrativo aprobo y lo que el proveedor ya recibio. Se
                    // deja como esta, y el faltante que quedo descubierto se avisa
                    // en la pantalla del pedido (OrdenCompraLinea::cubreElFaltante()).
                    // Lo que sobre se pide en el ciclo siguiente, cuando esta orden
                    // se cierre.
                    return null;
                });
        } catch (LockTimeoutException) {
            // Otra evaluacion del mismo producto esta corriendo. La nuestra es
            // redundante por definicion: va a leer el mismo saldo y encontrar
            // la orden que la otra acaba de crear. No hay nada que reintentar,
            // asi que se devuelve null en vez de dejar fallar el job.
            return null;
        }
    }

    /**
     * Barrido completo del catalogo.
     *
     * @param  bool  $simular  true = informa sin escribir nada
     * @return array{evaluados:int, pedidos:int, sin_proveedor:int, ordenes: list<string>}
     */
    public function evaluarTodos(bool $simular = false): array
    {
        $resumen = [
            'evaluados'     => 0,
            'pedidos'       => 0,
            'sin_proveedor' => $this->sinProveedor()->count(),
            'ordenes'       => [],
        ];

        $this->candidatos()->chunkById(200, function (Collection $productos) use (&$resumen, $simular) {
            foreach ($productos as $producto) {
                $resumen['evaluados']++;

                if ($simular) {
                    $prevision = $this->preverAccion($producto);

                    if ($prevision !== null) {
                        $resumen['pedidos']++;
                        $resumen['ordenes'][] = $prevision;
                    }

                    continue;
                }

                $orden = $this->evaluarProducto($producto);

                if ($orden !== null) {
                    $resumen['pedidos']++;
                    $resumen['ordenes'][] = "{$producto->codigo} → {$orden->numero}";
                }
            }
        });

        return $resumen;
    }

    /**
     * Que haria evaluarProducto() sin escribir nada. Solo para el --dry-run.
     *
     * Replica las mismas ramas a proposito: un dry-run que use otra logica
     * miente, y el punto de la opcion es poder confiar en lo que informa.
     */
    private function preverAccion(Producto $producto): ?string
    {
        if (! $this->necesitaReposicion($producto)) {
            return null;
        }

        $cantidad = $this->cantidadSugerida($producto);
        $abierta  = $this->ordenAbiertaDe($producto);

        if ($abierta === null) {
            return sprintf(
                '%s — disponible %d, umbral %d, pediria %d',
                $producto->codigo,
                $producto->stock_disponible,
                $producto->punto_reposicion,
                $cantidad,
            );
        }

        if (! $abierta->esBorrador()) {
            return null; // documento cerrado: no se toca
        }

        $pedida = $abierta->lineaDe($producto->getKey())?->cantidad_pedida ?? 0;

        if ($cantidad <= $pedida) {
            return null; // el borrador ya cubre el faltante
        }

        return sprintf(
            '%s — disponible %d, umbral %d, ampliaria %s de %d a %d',
            $producto->codigo,
            $producto->stock_disponible,
            $producto->punto_reposicion,
            $abierta->numero,
            $pedida,
            $cantidad,
        );
    }

    // ------------------------------------------------------------------
    // Reglas
    // ------------------------------------------------------------------

    /**
     * Las cuatro condiciones, en orden de costo creciente.
     *
     * <b>El umbral se compara contra el DISPONIBLE</b>, no contra el fisico.
     * Contra el fisico, un producto con todas sus unidades ya vendidas y
     * pendientes de entrega pareceria abastecido y nunca se repondria.
     *
     * <b>Se usa <= y no <</b>: "quedan 5 y el punto de reposicion es 5" ya es
     * el momento de reponer. El umbral es el piso al que no se quiere bajar, no
     * el valor al que se puede llegar.
     */
    public function necesitaReposicion(Producto $producto): bool
    {
        return $producto->activo
            && $producto->reposicion_automatica
            && $producto->proveedor_id !== null
            && $producto->stock_disponible <= $producto->punto_reposicion;
    }

    /**
     * Cuanto pedir: lo que falta para volver al umbral, mas el lote de compra.
     *
     * El faltante se calcula contra el disponible, que <b>puede ser negativo</b>
     * por preventa. Es intencional: con disponible -3 y umbral 5, el faltante
     * es 8, no 5. Lo que se le debe a clientes que ya pagaron entra en el
     * pedido, que es justamente lo que destraba esas ventas.
     *
     * Nunca devuelve menos de 1: llegar acá significa que hay algo que pedir.
     */
    public function cantidadSugerida(Producto $producto): int
    {
        $faltante = $producto->punto_reposicion - $producto->stock_disponible;

        return max(1, $faltante + $producto->cantidad_reposicion);
    }

    /**
     * La orden abierta que ya cubre este faltante, si existe.
     *
     * Abierta = borrador, aprobada, enviada o recibida_parcial. Una recibida
     * parcial sigue contando: el saldo pendiente esta en camino.
     *
     * Devuelve la orden y no un booleano porque lo que se hace con ella depende
     * de su estado: un borrador se amplia, un documento cerrado se respeta.
     */
    public function ordenAbiertaDe(Producto $producto): ?OrdenCompra
    {
        return OrdenCompra::query()
            ->abiertas()
            ->conProducto($producto->getKey())
            ->latest('id')
            ->first();
    }

    /** Se conserva por comodidad de lectura en pantallas y condiciones simples. */
    public function tieneOrdenAbierta(Producto $producto): bool
    {
        return $this->ordenAbiertaDe($producto) !== null;
    }

    // ------------------------------------------------------------------
    // Consultas de apoyo (dashboard y barrido)
    // ------------------------------------------------------------------

    /** Los que el barrido mira: activos, con reposicion automatica y con proveedor. */
    public function candidatos()
    {
        return Producto::query()
            ->activos()
            ->conReposicionAutomatica()
            ->bajoPuntoReposicion();
    }

    /**
     * El agujero silencioso: reposicion automatica activada y sin proveedor.
     *
     * ProductoRequest ya no deja guardar esa combinacion, pero se llega igual
     * por dos caminos —datos previos a esa validacion, y dar de baja al
     * proveedor despues— y el resultado es un producto que se queda sin stock
     * sin que nadie se entere. Por eso va al dashboard en vez de fallar callado.
     */
    public function sinProveedor()
    {
        return Producto::query()
            ->activos()
            ->conReposicionAutomatica()
            ->whereNull('proveedor_id')
            ->orderBy('stock_disponible');
    }

    /**
     * Para el tablero del administrativo: que se repuso solo, y por que.
     *
     * No hace falta que sea exacto al instante; se usa para mirar si los
     * umbrales estan bien puestos.
     */
    public function registrarDescarte(Producto $producto, string $motivo): void
    {
        Log::info('Reposicion descartada', [
            'producto'   => $producto->codigo,
            'disponible' => $producto->stock_disponible,
            'umbral'     => $producto->punto_reposicion,
            'motivo'     => $motivo,
        ]);
    }
}
