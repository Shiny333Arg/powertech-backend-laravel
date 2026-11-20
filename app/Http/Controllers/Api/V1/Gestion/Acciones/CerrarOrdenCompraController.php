<?php

namespace App\Http\Controllers\Api\V1\Gestion\Acciones;

use App\Http\Controllers\Api\V1\Gestion\Concerns\SirveElPedido;
use App\Http\Controllers\Controller;
use App\Http\Requests\MotivoOrdenCompraRequest;
use App\Models\OrdenCompra;
use App\Services\RecepcionService;
use Illuminate\Http\JsonResponse;

/**
 * POST /api/v1/gestion/compras/{orden}/cierre
 *
 * Da por terminado un pedido que quedó a medio recibir. Es la salida para el
 * proveedor que manda 8 de 10 y avisa que el resto no viene.
 *
 * <b>Por qué hace falta una acción explícita.</b> Sin esto, la orden se queda
 * en 'recibida_parcial' para siempre, y ese es un estado ABIERTO: mientras lo
 * sea, ReposicionService da el faltante por pedido y no vuelve a pedir esos
 * productos nunca. El pedido que ya no va a llegar bloquea al que sí haría
 * falta. Es el espejo exacto del problema que resuelve cancelar, con la
 * diferencia de que acá parte de la mercadería sí entró, así que cancelar
 * sería mentir sobre lo que pasó.
 *
 * <b>Cerrar NO mueve stock.</b> No entra mercadería: lo que cambia es que el
 * pedido deja de estar abierto. Por eso el evento OrdenCompraCerrada que emite
 * el servicio es el único aviso que va a recibir la reposición automática —el
 * listener reactivo del stock no se entera, porque no hay movimiento—. Sin ese
 * aviso, lo que quedó corto espera al barrido de las 7 de la mañana.
 *
 * <b>Mismo permiso que recibir</b>, 'compra.recibir': es la otra cara de la
 * misma tarea. La decide el depósito cuando el proveedor avisa que el resto no
 * viene, y no mueve dinero ni autoriza nada. Lo resuelve
 * OrdenCompraPolicy::cerrar(), que además exige `esCerrable()`: solo una orden
 * con destinatario y en 'recibida_parcial'. Un pedido repartido no se cierra
 * —el que avisa que el resto no viene avisa por lo suyo, y cerrar el pedido
 * entero lo marcaría recibido con los demás proveedores todavía debiendo—.
 *
 * <b>El motivo es obligatorio</b> y queda anotado en las observaciones junto
 * con el autor y el resumen de lo que no vino, que lo arma el servicio. Lo
 * valida MotivoOrdenCompraRequest, el mismo de la cancelación: ahí ya está
 * escrito el mensaje propio del cierre.
 *
 * <b>Respuestas:</b>
 *
 *   200  el detalle del pedido, ya en 'recibida', con el motivo y el faltante
 *        anotados en 'observaciones'
 *   403  sin 'compra.recibir', o el pedido no está recibido parcialmente, o es
 *        un pedido repartido (policy)
 *   422  el motivo falta o no entra en 255 caracteres, o hay otra operación en
 *        curso sobre el mismo pedido
 *   404  el pedido no existe
 */
class CerrarOrdenCompraController extends Controller
{
    use SirveElPedido;

    public function __construct(
        private readonly RecepcionService $recepciones,
    ) {}

    public function __invoke(MotivoOrdenCompraRequest $request, OrdenCompra $orden): JsonResponse
    {
        // El TTL por defecto —30 segundos— alcanza de sobra: cerrar no mueve
        // stock ni recorre la cola de preventas. El candado está igual porque
        // un cierre que corra contra una recepción del mismo pedido tiene que
        // serializarse: son dos formas de terminar el mismo documento.
        $cerrada = $this->enTransicion(
            $orden,
            fn (OrdenCompra $pedido) => $this->recepciones->cerrar($pedido, $request->user(), $request->motivo()),
        );

        return $this->detalle($cerrada)->response();
    }
}
