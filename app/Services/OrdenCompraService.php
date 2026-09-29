<?php

namespace App\Services;

use App\Enums\CanalPedido;
use App\Enums\EstadoOrdenCompra;
use App\Enums\OrigenOrdenCompra;
use App\Exceptions\TransicionNoPermitidaException;
use App\Mail\RemitoDePedido;
use App\Models\Numerador;
use App\Models\OrdenCompra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

/**
 * Sabe COMO se arma, se aprueba y se envia una orden de compra. No decide
 * cuando: eso es ReposicionService para las automaticas y el administrativo
 * para las manuales.
 *
 * <b>Canal de pedido: solo email.</b> El pedido se le hace llegar al proveedor
 * como un remito adjunto en un correo. No hay integracion con portales ni
 * registro de pedidos telefonicos: si el proveedor no tiene canal 'email' y
 * una casilla cargada, la orden se aprueba pero no se puede enviar, y el
 * sistema lo dice en vez de marcarla enviada sin que haya salido nada.
 *
 * <b>Reglas que hace cumplir</b>
 *  - El numero sale de la serie 'orden_compra' de numeradores, con la fila
 *    bloqueada y DENTRO de la transaccion que inserta la orden. Un ROLLBACK
 *    devuelve el numero.
 *  - Las automaticas y las manuales comparten todo salvo el campo 'origen':
 *    distinguirlas es la unica forma de medir si los umbrales estan calibrados.
 *  - Una orden no se elimina. Su fin de vida es 'cancelada' o 'recibida', igual
 *    que una venta.
 *  - total_estimado se recalcula siempre desde las lineas, nunca se acumula a
 *    mano: un total que se suma incrementalmente se desincroniza en la primera
 *    edicion.
 */
class OrdenCompraService
{
    private const SERIE = 'orden_compra';

    // ------------------------------------------------------------------
    // Alta automatica
    // ------------------------------------------------------------------

    /**
     * Deja pedido un producto: lo agrega al borrador abierto del proveedor, o
     * abre uno nuevo si no hay.
     *
     * <b>Por que agrupar por proveedor.</b> Cuando entra una venta grande,
     * ocho productos del mismo proveedor cruzan el umbral en el mismo segundo.
     * Una orden por producto le deja al administrativo ocho documentos para
     * aprobar y al proveedor ocho correos por el mismo envio. Agrupar en el
     * borrador que ya existe convierte eso en un pedido con ocho renglones.
     *
     * Solo se agrupa contra un BORRADOR: una orden ya aprobada o enviada es un
     * documento cerrado y meterle un renglon cambiaria lo que el administrativo
     * aprobo y lo que el proveedor recibio.
     */
    public function pedir(Producto $producto, int $cantidad, OrigenOrdenCompra $origen = OrigenOrdenCompra::Automatica): OrdenCompra
    {
        $proveedor = $producto->proveedor;

        if ($proveedor === null) {
            throw new RuntimeException(
                "«{$producto->nombre}» tiene reposición automática pero no tiene proveedor asignado."
            );
        }

        // Lock por PROVEEDOR, no por producto: el que ReposicionService ya tomo
        // impide dos evaluaciones del mismo producto, pero no dos productos
        // distintos del mismo proveedor buscando su borrador a la vez. Sin
        // este, los dos encuentran "no hay borrador" y abren uno cada uno.
        $lock = Cache::lock("orden-compra:proveedor:{$proveedor->getKey()}", 30);

        return $lock->block(10, function () use ($producto, $proveedor, $cantidad, $origen) {
            return DB::transaction(function () use ($producto, $proveedor, $cantidad, $origen) {
                $orden = $this->borradorAbiertoDe($proveedor)
                    ?? $this->nuevoBorrador($proveedor, $origen);

                $this->agregarLinea($orden, $producto, $cantidad);

                return $orden->fresh('lineas.producto');
            });
        });
    }

    /** El borrador vigente del proveedor, si lo hay. Solo uno a la vez. */
    public function borradorAbiertoDe(Proveedor $proveedor): ?OrdenCompra
    {
        return OrdenCompra::query()
            ->where('proveedor_id', $proveedor->getKey())
            ->where('estado', EstadoOrdenCompra::Borrador->value)
            ->lockForUpdate()
            ->latest('id')
            ->first();
    }

    // ------------------------------------------------------------------
    // Alta manual
    // ------------------------------------------------------------------

    /**
     * Alta manual desde la pantalla de compras.
     *
     * @param  list<array{producto_id:int, cantidad_pedida:int, costo_unitario?:float|string|null}>  $lineas
     */
    public function crear(Proveedor $proveedor, array $lineas, User $usuario, ?string $observaciones = null): OrdenCompra
    {
        return DB::transaction(function () use ($proveedor, $lineas, $usuario, $observaciones) {
            $orden = $this->nuevoBorrador($proveedor, OrigenOrdenCompra::Manual, $usuario, $observaciones);

            $this->reemplazarLineas($orden, $lineas);

            return $orden->fresh('lineas.producto');
        });
    }

    /**
     * Edicion del detalle. Solo en borrador.
     *
     * Una orden aprobada ya paso por la decision del administrativo y una
     * enviada ya esta en manos del proveedor: cambiarles el detalle seria
     * reescribir lo que otro leyo. Lo que se hace en ese caso es cancelarla y
     * emitir otra.
     *
     * @param  list<array{producto_id:int, cantidad_pedida:int, costo_unitario?:float|string|null}>  $lineas
     */
    public function actualizar(OrdenCompra $orden, array $lineas, ?string $observaciones = null): OrdenCompra
    {
        if (! $orden->esEditable()) {
            throw TransicionNoPermitidaException::porque(
                'Solo se puede editar el detalle de una orden de compra en borrador.'
            );
        }

        return DB::transaction(function () use ($orden, $lineas, $observaciones) {
            $orden->lineas()->delete();

            $this->reemplazarLineas($orden, $lineas);

            if ($observaciones !== null) {
                $orden->observaciones = $observaciones;
                $orden->save();
            }

            return $orden->fresh('lineas.producto');
        });
    }

    // ------------------------------------------------------------------
    // Transiciones
    // ------------------------------------------------------------------

    /**
     * Aprobacion del administrativo. Es un requerimiento explicito: ninguna
     * orden sale sin que una persona la haya mirado.
     *
     * Deja registro de quien y cuando, que es el punto de pedirla.
     */
    public function aprobar(OrdenCompra $orden, User $usuario): OrdenCompra
    {
        $this->exigirTransicion($orden, EstadoOrdenCompra::Aprobada);

        if ($orden->lineas()->count() === 0) {
            throw TransicionNoPermitidaException::porque(
                'La orden no tiene renglones: no hay nada que aprobar.'
            );
        }

        $orden->fill([
            'estado'                 => EstadoOrdenCompra::Aprobada,
            'usuario_aprobo_id'      => $usuario->id,
            'fecha_aprobacion'       => now(),
            'fecha_entrega_estimada' => $orden->fecha_entrega_estimada ?? $this->entregaEstimada($orden->proveedor),
        ])->save();

        return $orden;
    }

    /**
     * Envia el remito al proveedor por correo y marca la orden como enviada.
     *
     * <b>El correo se manda ANTES de cambiar el estado, y de forma sincronica.</b>
     * Encolarlo dejaria la orden en 'enviada' con el mail todavia sin salir, y
     * si el envio despues falla nadie se entera: el administrativo ve una orden
     * enviada que el proveedor nunca recibio. Si el correo no sale, esto
     * explota y la orden se queda en 'aprobada', que es la verdad.
     *
     * El riesgo espejo —el correo sale y el UPDATE falla— existe, pero deja la
     * orden reenviable, que es el lado seguro del error.
     */
    public function enviar(OrdenCompra $orden, User $usuario): OrdenCompra
    {
        $this->exigirTransicion($orden, EstadoOrdenCompra::Enviada);

        $orden->loadMissing(['proveedor', 'lineas.producto']);

        $this->exigirCanalEmail($orden->proveedor);

        Mail::to($orden->proveedor->email)->send(new RemitoDePedido($orden));

        $orden->fill([
            'estado'      => EstadoOrdenCompra::Enviada,
            'fecha_envio' => now(),
        ])->save();

        return $orden;
    }

    /**
     * Cancela la orden. No la borra: la orden no se elimina nunca.
     *
     * Cancelar es tambien lo que <b>vuelve a habilitar la reposicion</b> de sus
     * productos: mientras la orden este abierta, ReposicionService los da por
     * pedidos y no genera nada. En cuanto pasa a cancelada, el faltante vuelve
     * a ser visible y la proxima corrida lo pide de nuevo.
     */
    public function cancelar(OrdenCompra $orden, User $usuario, string $motivo): OrdenCompra
    {
        $this->exigirTransicion($orden, EstadoOrdenCompra::Cancelada);

        $orden->fill([
            'estado'        => EstadoOrdenCompra::Cancelada,
            'observaciones' => $this->anotar($orden->observaciones, "Cancelada por {$usuario->usuario}: {$motivo}"),
        ])->save();

        return $orden;
    }

    // ------------------------------------------------------------------
    // Construccion
    // ------------------------------------------------------------------

    private function nuevoBorrador(
        Proveedor $proveedor,
        OrigenOrdenCompra $origen,
        ?User $usuario = null,
        ?string $observaciones = null,
    ): OrdenCompra {
        // usuario_creo_id queda en null en las automaticas: no las creo nadie,
        // las creo el sistema. Falsear el usuario del vendedor cuya venta
        // disparo el faltante seria atribuirle una decision que no tomo.
        $usuario ??= $origen === OrigenOrdenCompra::Manual ? auth()->user() : null;

        return OrdenCompra::create([
            'numero'                 => Numerador::proximoFormateado(self::SERIE),
            'proveedor_id'           => $proveedor->getKey(),
            'estado'                 => EstadoOrdenCompra::Borrador,
            'origen'                 => $origen,
            'total_estimado'         => 0,
            'usuario_creo_id'        => $usuario?->id,
            'fecha_entrega_estimada' => $this->entregaEstimada($proveedor),
            'observaciones'          => $observaciones ?? ($origen === OrigenOrdenCompra::Automatica
                ? 'Generada automáticamente por reposición de stock.'
                : null),
        ]);
    }

    /**
     * Suma un producto al borrador, o acumula sobre el renglon existente.
     *
     * Acumular en vez de repetir el producto evita que el proveedor reciba un
     * remito con el mismo item dos veces: pasa cuando el faltante crece entre
     * dos evaluaciones y la orden todavia no se aprobo.
     */
    public function agregarLinea(OrdenCompra $orden, Producto $producto, int $cantidad): void
    {
        $linea = $orden->lineas()->where('producto_id', $producto->getKey())->first();

        if ($linea !== null) {
            $linea->increment('cantidad_pedida', $cantidad);
        } else {
            $orden->lineas()->create([
                'producto_id'     => $producto->getKey(),
                'cantidad_pedida' => $cantidad,
                // El costo del catalogo es una ESTIMACION para que el
                // administrativo tenga una referencia al aprobar. El precio
                // real lo fija la factura del proveedor al recibir.
                'costo_unitario'  => $producto->costo ?? 0,
            ]);
        }

        $orden->recalcularTotalEstimado();
    }

    /** @param  list<array{producto_id:int, cantidad_pedida:int, costo_unitario?:float|string|null}>  $lineas */
    private function reemplazarLineas(OrdenCompra $orden, array $lineas): void
    {
        $productos = Producto::whereIn('id', array_column($lineas, 'producto_id'))->get()->keyBy('id');

        foreach ($lineas as $fila) {
            $producto = $productos->get((int) $fila['producto_id']);

            if ($producto === null) {
                throw new RuntimeException("El producto #{$fila['producto_id']} no existe.");
            }

            $orden->lineas()->create([
                'producto_id'     => $producto->id,
                'cantidad_pedida' => (int) $fila['cantidad_pedida'],
                'costo_unitario'  => $fila['costo_unitario'] ?? $producto->costo ?? 0,
            ]);
        }

        $orden->recalcularTotalEstimado();
    }

    // ------------------------------------------------------------------
    // Auxiliares
    // ------------------------------------------------------------------

    /**
     * El unico canal soportado es el correo. Se verifica acá y no en la vista
     * porque el comando programado tambien puede llegar a enviar.
     */
    private function exigirCanalEmail(Proveedor $proveedor): void
    {
        if ($proveedor->canal_pedido !== CanalPedido::Email) {
            throw TransicionNoPermitidaException::porque(
                "«{$proveedor->razon_social}» tiene canal de pedido «{$proveedor->canal_pedido->etiqueta()}». "
                .'Los pedidos se envían por correo: cambiá el canal del proveedor a email.'
            );
        }

        if (blank($proveedor->email)) {
            throw TransicionNoPermitidaException::porque(
                "«{$proveedor->razon_social}» no tiene correo cargado y el remito se envía por email."
            );
        }
    }

    private function entregaEstimada(Proveedor $proveedor): ?string
    {
        return $proveedor->plazo_entrega_dias
            ? now()->addDays($proveedor->plazo_entrega_dias)->toDateString()
            : null;
    }

    private function exigirTransicion(OrdenCompra $orden, EstadoOrdenCompra $destino): void
    {
        if (! $orden->estado->puedeTransicionarA($destino)) {
            throw TransicionNoPermitidaException::entre($orden->estado, $destino);
        }
    }

    private function anotar(?string $observaciones, string $nota): string
    {
        $sello = now()->format('d/m/Y H:i');

        return trim(($observaciones ?? '')."\n[{$sello}] {$nota}");
    }
}
