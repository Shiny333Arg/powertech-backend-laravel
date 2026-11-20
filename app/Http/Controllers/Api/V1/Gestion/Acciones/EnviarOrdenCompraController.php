<?php

namespace App\Http\Controllers\Api\V1\Gestion\Acciones;

use App\Exceptions\TransicionNoPermitidaException;
use App\Http\Controllers\Api\V1\Gestion\Concerns\SirveElPedido;
use App\Http\Controllers\Controller;
use App\Models\OrdenCompra;
use App\Models\User;
use App\Services\OrdenCompraService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

/**
 * POST /api/v1/gestion/compras/{orden}/envio
 *
 * Le hace llegar el pedido al proveedor. <b>El canal es el correo y no hay
 * otro</b>: el remito va como cuerpo del mail, no hay portal ni registro de
 * pedidos telefónicos. Si el proveedor no tiene canal 'email' y una casilla
 * cargada, el pedido se queda aprobado y el sistema lo dice, en vez de marcarlo
 * enviado sin que haya salido nada.
 *
 * <b>Este controlador no manda ningún correo.</b> Envuelve
 * OrdenCompraService::enviar() y ::enviarTodas(), que son los mismos métodos que
 * usa el botón del panel, con el mismo Mailable y la misma regla de que el
 * correo sale ANTES de cambiar el estado: si el envío falla, la orden sigue en
 * 'aprobada', que es la verdad.
 *
 * <b>Dos caminos, y la bifurcación vive acá y no en la policy.</b> Un pedido
 * repartido entre varios proveedores no se envía: lo que se mandan son las
 * órdenes de cada uno, una por una. OrdenCompraPolicy::enviar() mira el permiso
 * y el estado, que es lo suyo; la forma del documento la mira el controlador,
 * igual que en el panel.
 *
 * <b>Un fallo parcial NO es un error de la petición.</b> Sobre un pedido de tres
 * proveedores, que uno no tenga casilla cargada no invalida los dos remitos que
 * sí salieron, y responder 4xx dejaría al cliente creyendo que no se envió nada
 * —y reintentando, que es justamente lo que le mandaría el remito dos veces a
 * los que ya lo recibieron—. Por eso el repartido responde <b>200</b> con las
 * dos listas: 'enviadas' y 'fallidas'. Es la razón por la que
 * OrdenCompraService::enviarTodas() devuelve el resultado en lugar de lanzar
 * excepción, y reintentar alcanza solo a las que fallaron, porque cada orden
 * lleva su propio estado.
 *
 * <b>Respuestas:</b>
 *
 *   200  el detalle del pedido enviado. En un repartido, además 'envios' con
 *        las órdenes que salieron y las que no, con su motivo
 *   403  sin 'compra.enviar', o el estado no admite la transición (policy)
 *   422  el proveedor no recibe pedidos por correo, no tiene casilla cargada,
 *        el pedido repartido no tiene órdenes pendientes de envío, o hay otra
 *        operación en curso sobre el mismo pedido
 *   502  el correo no pudo salir por una falla del servicio de mail. <b>No es
 *        un 500:</b> el problema no es del cliente ni un defecto nuestro, y el
 *        pedido quedó reintentable, que es la información que el cliente
 *        necesita. Mismo criterio que PasarelaPagoException con Mercado Pago
 *   404  el pedido no existe
 */
class EnviarOrdenCompraController extends Controller
{
    use SirveElPedido;

    public function __construct(
        private readonly OrdenCompraService $ordenes,
    ) {}

    public function __invoke(Request $request, OrdenCompra $orden): JsonResponse
    {
        return $this->enTransicion($orden, function (OrdenCompra $pedido) use ($request): JsonResponse {
            return $pedido->esMadre()
                ? $this->enviarRepartido($pedido, $request->user())
                : $this->enviarAlProveedor($pedido, $request->user());
        });
    }

    /**
     * Un pedido con destinatario: un remito, un correo.
     *
     * La transición imposible y el proveedor mal configurado se dejan propagar
     * —las dos son TransicionNoPermitidaException y bootstrap/app.php ya las
     * convierte en 422 con su mensaje—. Lo que sí se atrapa es cualquier otra
     * cosa: una falla del transporte de correo no es un defecto del sistema y
     * no merece un 500.
     */
    private function enviarAlProveedor(OrdenCompra $orden, User $usuario): JsonResponse
    {
        try {
            $this->ordenes->enviar($orden, $usuario);
        } catch (TransicionNoPermitidaException $error) {
            throw $error;
        } catch (Throwable $error) {
            // report() y no un log a mano: va al mismo canal que el resto de
            // los errores del sistema, con su traza. Es lo que hace el
            // controlador del panel.
            report($error);

            $proveedor = $orden->proveedor?->razon_social ?? 'el proveedor';

            return response()->json([
                'message' => "No se pudo enviar el correo a «{$proveedor}». "
                    .'El pedido sigue aprobado y se puede reintentar.',
                'codigo'  => 'ENVIO_FALLIDO',
            ], Response::HTTP_BAD_GATEWAY);
        }

        return $this->detalle($orden)->response();
    }

    /**
     * Un pedido repartido: el remito de cada proveedor, por separado.
     *
     * enviarTodas() toma solo las hijas que siguen en 'aprobada', así que
     * reintentar es seguro: las que ya salieron no se vuelven a mandar.
     */
    private function enviarRepartido(OrdenCompra $madre, User $usuario): JsonResponse
    {
        $resultado = $this->ordenes->enviarTodas($madre, $usuario);

        /** @var \Illuminate\Support\Collection<int, OrdenCompra> $enviadas */
        $enviadas = $resultado['enviadas'];

        /** @var array<string, string> $fallidas */
        $fallidas = $resultado['fallidas'];

        // Ni una salió ni una falló: no quedaba nada pendiente de envío. Es
        // casi inalcanzable —sincronizarDesdeHijas() deja a la madre en el
        // estado de la hija más atrasada, y la policy ya rechaza una madre sin
        // nada por mandar—, pero responder 200 con las dos listas vacías
        // parecería un envío exitoso que no ocurrió.
        if ($enviadas->isEmpty() && $fallidas === []) {
            throw TransicionNoPermitidaException::porque(
                "El pedido {$madre->numero} no tiene órdenes pendientes de envío."
            );
        }

        return $this->detalle($madre)
            ->additional([
                'envios' => [
                    'enviadas' => $enviadas
                        ->map(fn (OrdenCompra $hija) => [
                            'id'        => $hija->id,
                            'numero'    => $hija->numero,
                            'proveedor' => $hija->proveedor?->razon_social,
                            'email'     => $hija->proveedor?->email,
                        ])
                        ->values()
                        ->all(),

                    // Razón social => por qué no salió. Lo típico es un
                    // proveedor sin casilla cargada, y eso se arregla en su
                    // ficha, no reintentando: por eso viaja el motivo y no un
                    // código de reintento.
                    'fallidas' => $fallidas,
                ],
            ])
            ->response();
    }
}
