<?php

namespace App\Http\Controllers\Api\V1\Gestion\Acciones;

use App\Http\Controllers\Api\V1\Gestion\Concerns\SirveElPedido;
use App\Http\Controllers\Controller;
use App\Models\OrdenCompra;
use App\Services\OrdenCompraService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * POST /api/v1/gestion/compras/{orden}/aprobacion
 *
 * La firma del administrativo. Es un requerimiento explícito del sistema:
 * ninguna orden sale sin que una persona la haya mirado, y lo que queda
 * registrado es quién y cuándo.
 *
 * <b>Este controlador no aprueba nada.</b> Envuelve
 * OrdenCompraService::aprobar(), que es el mismo método que usa el botón del
 * panel. Lo propio de este archivo son tres cosas y ninguna es de negocio: el
 * candado contra la aprobación simultánea, la forma de la respuesta y nada más.
 *
 * <b>Tres efectos que no son evidentes y que la respuesta tiene que contar:</b>
 *
 *   1. <b>Un pedido mezclado se reparte acá.</b> Si el detalle tiene productos
 *      de varios proveedores, aprobarlo emite una orden hija por cada uno, ya
 *      aprobada: la decisión que el requerimiento pide que quede registrada es
 *      esta, y pedirle al administrativo que vuelva a aprobar tres documentos
 *      que acaba de autorizar sería pedirle la misma firma cuatro veces. Las
 *      hijas son las que se envían y se reciben.
 *   2. <b>Se dan de baja los borradores automáticos que este pedido deja sin
 *      sentido.</b> El restock arma borradores solo; si este pedido cubre todos
 *      sus renglones, quedarían pidiendo algo que ya se pidió. Por eso viajan
 *      en 'anulados': una anulación silenciosa es indistinguible de un
 *      documento que desapareció.
 *   3. <b>Aprobar congela las cantidades.</b> A partir de acá la reposición
 *      automática ya no las amplía: el documento pasó a ser lo que esta persona
 *      autorizó.
 *
 * <b>Separación de funciones.</b> La hace cumplir OrdenCompraPolicy::aprobar()
 * desde el `can:` de la ruta, no este archivo: quien creó el pedido no puede
 * aprobarlo salvo que tenga 'compra.aprobar_propias', que solo tiene el
 * administrador. Un administrativo que cargue un pedido por la API no va a
 * poder aprobarlo él mismo, y eso responde <b>403</b>.
 *
 * <b>Respuestas:</b>
 *
 *   200  el detalle del pedido aprobado, más 'hijas_emitidas' y 'anulados'
 *   403  sin 'compra.aprobar', o es el propio pedido, o el estado no admite la
 *        transición — las tres las decide la policy, igual que en el panel
 *   422  el pedido no tiene renglones, un renglón no tiene proveedor al que
 *        emitirle, o hay otra operación en curso sobre el mismo pedido
 *   404  el pedido no existe
 */
class AprobarOrdenCompraController extends Controller
{
    use SirveElPedido;

    public function __construct(
        private readonly OrdenCompraService $ordenes,
    ) {}

    public function __invoke(Request $request, OrdenCompra $orden): JsonResponse
    {
        /** @var array{orden: OrdenCompra, anulados: list<string>} $resultado */
        $resultado = $this->enTransicion(
            $orden,
            fn (OrdenCompra $pedido) => $this->ordenes->aprobar($pedido, $request->user()),
        );

        $aprobada = $resultado['orden'];

        return $this->detalle($aprobada)
            ->additional([
                // Cuántas órdenes emitió el reparto. Cero en un pedido de un
                // solo proveedor, que es el caso normal. Va como número y no
                // como la frase del panel: el detalle ya trae las hijas con su
                // número y su destinatario, y la pantalla escribe su propio
                // mensaje.
                'hijas_emitidas' => $aprobada->hijas()->count(),

                // Los números de los pedidos automáticos que esta aprobación
                // dejó sin sentido y que el servicio canceló en el mismo acto.
                'anulados' => $resultado['anulados'],
            ])
            ->response();
    }
}
