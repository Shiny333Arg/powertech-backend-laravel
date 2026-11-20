<?php

namespace App\Http\Controllers\Api\V1\Gestion\Acciones;

use App\Enums\EstadoOrdenCompra;
use App\Http\Controllers\Api\V1\Gestion\Concerns\SirveElPedido;
use App\Http\Controllers\Controller;
use App\Http\Requests\MotivoOrdenCompraRequest;
use App\Models\OrdenCompra;
use App\Services\OrdenCompraService;
use Illuminate\Http\JsonResponse;

/**
 * POST /api/v1/gestion/compras/{orden}/cancelacion
 *
 * Da de baja el pedido. <b>No lo borra:</b> la orden de compra no se elimina
 * nunca —no hay DELETE en esta API y no hay método delete() en la policy—, su
 * fin de vida es un estado, igual que el de una venta. El número, el detalle y
 * el historial quedan.
 *
 * <b>Cancelar no es cosmético: es lo que vuelve a habilitar la reposición
 * automática de estos productos.</b> Mientras la orden esté abierta,
 * ReposicionService los da por pedidos y no genera nada; en cuanto pasa a
 * cancelada, el faltante vuelve a estar descubierto y el evento
 * OrdenCompraCancelada lo hace pedir en el acto, por la cantidad que haga falta
 * en ese momento. Sin ese aviso, un producto en preventa con clientes que ya
 * pagaron esperaría hasta el barrido de las 7 de la mañana.
 *
 * <b>El motivo es obligatorio</b> y queda anotado en las observaciones del
 * documento, con el sello de fecha y el usuario que canceló. Lo valida
 * MotivoOrdenCompraRequest, compartido con el cierre con faltante.
 *
 * <b>Cancelar una madre cancela sus hijas</b>: el pedido completo se da de baja.
 * Cancelar una hija sola es legítimo —el proveedor que avisa que no tiene
 * stock— y deja al resto del pedido en pie. Lo resuelve el servicio; acá se
 * cuenta cuántas cayeron, porque el cliente no tiene otra forma de saber que
 * una cancelación alcanzó a tres documentos.
 *
 * <b>Respuestas:</b>
 *
 *   200  el detalle del pedido cancelado, con el motivo ya anotado, más
 *        'hijas_canceladas'
 *   403  sin 'compra.cancelar', o el estado no admite la transición: 'recibida'
 *        y 'cancelada' son terminales (policy)
 *   422  el motivo falta o no entra en 255 caracteres, o hay otra operación en
 *        curso sobre el mismo pedido
 *   404  el pedido no existe
 */
class CancelarOrdenCompraController extends Controller
{
    use SirveElPedido;

    public function __construct(
        private readonly OrdenCompraService $ordenes,
    ) {}

    public function __invoke(MotivoOrdenCompraRequest $request, OrdenCompra $orden): JsonResponse
    {
        $cancelada = $this->enTransicion(
            $orden,
            fn (OrdenCompra $pedido) => $this->ordenes->cancelar($pedido, $request->user(), $request->motivo()),
        );

        return $this->detalle($cancelada)
            ->additional([
                // Cuántas órdenes del reparto cayeron con el pedido. Cero en
                // una orden simple y en una hija cancelada sola, que es lo
                // normal.
                'hijas_canceladas' => $cancelada->hijas()
                    ->where('estado', EstadoOrdenCompra::Cancelada->value)
                    ->count(),
            ])
            ->response();
    }
}
