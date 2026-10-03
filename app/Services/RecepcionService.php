<?php

namespace App\Services;

use App\Enums\EstadoOrdenCompra;
use App\Events\OrdenCompraCerrada;
use App\Exceptions\TransicionNoPermitidaException;
use App\Models\OrdenCompra;
use App\Models\OrdenCompraLinea;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * La entrada de mercaderia contra un pedido a proveedor.
 *
 * Es la otra mitad del requerimiento 6: el restock emite los pedidos, esto es
 * lo que vuelve. Tres cosas pasan en cada recepcion, y el orden entre ellas no
 * es casual:
 *
 *   1. Se acumula en orden_compra_lineas.cantidad_recibida y entra un
 *      movimiento de stock de tipo 'compra' con origen en la orden. Suma solo
 *      al fisico; el disponible sube solo, porque es columna generada.
 *   2. La orden pasa a 'recibida_parcial' o a 'recibida' segun el acumulado de
 *      TODOS sus renglones, no segun el tamaño de esta entrega.
 *   3. Recien despues del commit se recorre la cola de preventas de cada
 *      producto que entro, por antiguedad, y se habilitan para preparacion las
 *      ventas que ya se puedan entregar.
 *
 * <b>Por que el punto 3 va despues del commit y no adentro.</b> La cola decide
 * mirando el stock fisico. Adentro de la transaccion, ese saldo todavia no es
 * visible para nadie mas y, si la transaccion falla, se habrian prometido
 * entregas contra mercaderia que nunca entro. Lo que se promete tiene que
 * apoyarse en un hecho firme.
 *
 * <b>Servicio propio y no un metodo de OrdenCompraService.</b> La recepcion
 * toca tres dominios —la orden, el stock y las ventas— mientras que
 * OrdenCompraService es el armado del documento de compra y no sabe que existen
 * las ventas. Meterle la cola de preventas lo convertiria en el lugar donde
 * vive todo.
 */
class RecepcionService
{
    public function __construct(
        private readonly StockService $stock,
        private readonly EntregaService $entregas,
    ) {}

    // ------------------------------------------------------------------
    // Recepcion
    // ------------------------------------------------------------------

    /**
     * Carga una entrega de mercaderia contra la orden.
     *
     * @param  array<int|string, int>  $cantidades  id de la linea => unidades que entran AHORA.
     *                                              Las lineas que no vinieron se omiten o van en cero.
     * @param  string|null  $remito  Numero de remito del proveedor. Se guarda en el
     *                               'motivo' de cada movimiento de stock: el modelo de datos
     *                               decidio no tener tabla de recepciones, asi que el movimiento
     *                               ES el registro de la entrada, y el remito tiene que viajar
     *                               con ella y no con la orden —una orden puede recibir tres
     *                               remitos distintos.
     * @return array{orden: OrdenCompra, unidades: int, lineas: int, cerrada: bool, ventas_liberadas: int}
     *
     * @throws TransicionNoPermitidaException
     * @throws \App\Exceptions\StockInsuficienteException
     */
    public function registrar(OrdenCompra $orden, array $cantidades, User $usuario, ?string $remito = null): array
    {
        $cantidades = $this->limpiar($cantidades);

        if ($cantidades === []) {
            throw new InvalidArgumentException('No se cargó ninguna cantidad: no hay nada que recibir.');
        }

        /** @var Collection<int, Producto> $tocados */
        [$orden, $tocados, $unidades] = DB::transaction(function () use ($orden, $cantidades, $usuario, $remito) {

            // Bloqueo de la orden: dos personas cargando la misma entrega desde
            // dos pantallas se serializan acá. Sin esto las dos leen el mismo
            // acumulado, las dos concluyen que la orden quedo completa y la
            // segunda choca con una transicion desde 'recibida', que es
            // terminal.
            $orden = OrdenCompra::whereKey($orden->getKey())->lockForUpdate()->firstOrFail();

            $this->exigirRecepcionPosible($orden);

            $lineas = $orden->lineas()
                ->with('producto')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $tocados  = collect();
            $unidades = 0;

            foreach ($cantidades as $lineaId => $cantidad) {
                /** @var OrdenCompraLinea|null $linea */
                $linea = $lineas->get($lineaId);

                if ($linea === null) {
                    throw TransicionNoPermitidaException::porque(
                        "El renglón #{$lineaId} no pertenece al pedido {$orden->numero}."
                    );
                }

                // increment() es un UPDATE relativo, igual que los saldos de
                // stock: nunca se lee en PHP para reescribir un absoluto.
                $linea->increment('cantidad_recibida', $cantidad);

                // Suma al fisico y deja el movimiento con el remito. Si esto
                // explota —no deberia: una compra solo suma y no puede ser
                // rechazada por el motor— la transaccion entera se va atras y
                // cantidad_recibida vuelve al valor anterior.
                $this->stock->recibirCompra($linea->producto, $cantidad, $orden, $usuario, $remito);

                $tocados->put($linea->producto_id, $linea->producto);
                $unidades += $cantidad;
            }

            // El estado sale del acumulado de todos los renglones, no de lo que
            // acaba de entrar. Releidos frescos: los increment() de arriba no
            // actualizan la coleccion en memoria.
            $orden->setRelation('lineas', $orden->lineas()->get());

            $this->transicionar($orden, $orden->estadoTrasRecepcion());

            return [$orden, $tocados, $unidades];
        });

        // ---- Ya commiteado: a partir de acá el fisico es un hecho firme ----

        $liberadas = $this->liberarPreventas($tocados);

        if ($orden->estado === EstadoOrdenCompra::Recibida) {
            // El faltante pudo crecer despues de aprobar el pedido, y entonces
            // recibirlo completo no alcanza. Cerrada la orden, ese saldo vuelve
            // a estar descubierto y hay que volver a evaluarlo: el movimiento
            // de compra no lo hace, porque EvaluarReposicion descarta todo lo
            // que SUBE el disponible.
            OrdenCompraCerrada::dispatch($orden);
        }

        return [
            'orden'            => $orden,
            'unidades'         => $unidades,
            'lineas'           => $tocados->count(),
            'cerrada'          => $orden->estado === EstadoOrdenCompra::Recibida,
            'ventas_liberadas' => $liberadas,
        ];
    }

    // ------------------------------------------------------------------
    // Cierre con faltante
    // ------------------------------------------------------------------

    /**
     * Da por terminada una orden que quedo a medio recibir.
     *
     * <b>Por que hace falta una accion explicita.</b> El proveedor manda 8 de
     * 10 y avisa que el resto no viene. Sin esto la orden se queda en
     * 'recibida_parcial' para siempre, y ese es un estado ABIERTO: mientras lo
     * sea, ReposicionService da el faltante por pedido y no vuelve a pedir esos
     * productos nunca. El pedido que ya no va a llegar bloquea al que si haria
     * falta.
     *
     * Es el espejo exacto del problema que resuelve cancelar(), con la
     * diferencia de que acá parte de la mercaderia si entro, asi que cancelar
     * seria mentir sobre lo que paso.
     */
    public function cerrar(OrdenCompra $orden, User $usuario, string $motivo): OrdenCompra
    {
        $orden = DB::transaction(function () use ($orden, $usuario, $motivo) {
            $orden = OrdenCompra::whereKey($orden->getKey())->lockForUpdate()->firstOrFail();

            if (! $orden->esCerrable()) {
                throw TransicionNoPermitidaException::porque(
                    "El pedido {$orden->numero} no está recibido parcialmente: no hay nada que cerrar."
                );
            }

            $orden->load('lineas.producto');

            $this->transicionar($orden, EstadoOrdenCompra::Recibida);

            $orden->forceFill([
                'observaciones' => $this->anotar(
                    $orden->observaciones,
                    "Cerrado con faltante por {$usuario->usuario}: {$motivo}. "
                    .$this->resumenDelFaltante($orden)
                ),
            ])->save();

            return $orden;
        });

        // Mismo motivo que en registrar(), y acá con mas razon: cerrar a mano
        // no mueve stock, asi que este evento es el UNICO aviso que va a
        // recibir el restock. Sin el, los productos que quedaron cortos esperan
        // al barrido de las 7.
        OrdenCompraCerrada::dispatch($orden);

        return $orden;
    }

    // ------------------------------------------------------------------
    // Cola de preventas
    // ------------------------------------------------------------------

    /**
     * Recorre la cola de cada producto que entro.
     *
     * Producto por producto y no todo junto a proposito: la cola se ordena por
     * antiguedad de la venta DENTRO de cada producto, que es lo que pide el
     * requerimiento. Una venta con dos productos recien llegados se evalua dos
     * veces; la segunda la encuentra ya en preparacion y la saltea, porque
     * liberarCola() solo mira las que siguen en pendiente_stock.
     *
     * @param  Collection<int, Producto>  $productos
     */
    private function liberarPreventas(Collection $productos): int
    {
        $liberadas = 0;

        foreach ($productos as $producto) {
            $liberadas += $this->entregas->liberarCola($producto);
        }

        return $liberadas;
    }

    // ------------------------------------------------------------------
    // Auxiliares
    // ------------------------------------------------------------------

    /**
     * Descarta los renglones que no vinieron y normaliza a enteros.
     *
     * Un cero no es un error: es el renglon que el proveedor no mando. Lo que
     * si es un error es una cantidad negativa, porque una recepcion no
     * devuelve mercaderia —eso es una nota de credito o un ajuste.
     *
     * @param  array<int|string, mixed>  $cantidades
     * @return array<int, int>
     */
    private function limpiar(array $cantidades): array
    {
        $limpias = [];

        foreach ($cantidades as $lineaId => $cantidad) {
            $cantidad = (int) $cantidad;

            if ($cantidad < 0) {
                throw new InvalidArgumentException(
                    'Una recepción no puede ser negativa: para sacar unidades del depósito está el ajuste de inventario.'
                );
            }

            if ($cantidad > 0) {
                $limpias[(int) $lineaId] = $cantidad;
            }
        }

        return $limpias;
    }

    private function exigirRecepcionPosible(OrdenCompra $orden): void
    {
        if ($orden->esRecibible()) {
            return;
        }

        $mensaje = match ($orden->estado) {
            EstadoOrdenCompra::Borrador, EstadoOrdenCompra::Aprobada => "El pedido {$orden->numero} todavía no se le envió al proveedor.",
            EstadoOrdenCompra::Recibida  => "El pedido {$orden->numero} ya está recibido por completo.",
            EstadoOrdenCompra::Cancelada => "El pedido {$orden->numero} está cancelado.",
            default                      => "El pedido {$orden->numero} no admite recepciones en su estado actual.",
        };

        throw TransicionNoPermitidaException::porque($mensaje);
    }

    /**
     * Las transiciones de la orden pasan todas por acá, incluida
     * recibida_parcial -> recibida_parcial, que es la segunda entrega contra el
     * mismo pedido y esta declarada en el enum justamente para no tener que
     * hacerle una excepcion acá.
     */
    private function transicionar(OrdenCompra $orden, EstadoOrdenCompra $destino): void
    {
        if (! $orden->estado->puedeTransicionarA($destino)) {
            throw TransicionNoPermitidaException::entre($orden->estado, $destino);
        }

        $orden->fill(['estado' => $destino])->save();
    }

    /** Que quedo sin recibir, para que el cierre lo deje escrito. */
    private function resumenDelFaltante(OrdenCompra $orden): string
    {
        $faltantes = $orden->lineas
            ->filter(fn (OrdenCompraLinea $linea) => $linea->pendiente > 0)
            ->map(fn (OrdenCompraLinea $linea) => "{$linea->producto->codigo} x{$linea->pendiente}")
            ->values();

        if ($faltantes->isEmpty()) {
            return 'Sin unidades pendientes.';
        }

        return 'Quedó sin recibir: '.$faltantes->implode(', ').'.';
    }

    private function anotar(?string $observaciones, string $nota): string
    {
        $sello = now()->format('d/m/Y H:i');

        return trim(($observaciones ?? '')."\n[{$sello}] {$nota}");
    }
}
