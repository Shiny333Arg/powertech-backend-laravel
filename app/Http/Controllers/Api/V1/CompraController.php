<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CompraDetalleResource;
use App\Http\Resources\CompraResource;
use App\Models\Venta;
use App\Support\CompraDelCliente;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * "Mis compras" para la app: el historial del cliente y el detalle de cada
 * pedido.
 *
 * <b>Solo lectura, igual que la web.</b> El cliente no cancela ni modifica
 * desde aca: cancelar una venta pagada libera stock comprometido y, si ya
 * tiene factura con CAE, exige nota de credito. Es una decision de la
 * empresa, no una accion de autogestion.
 *
 * <b>Las reglas no se duplican: viven en CompraDelCliente.</b> Que un
 * presupuesto no es una compra, que un pedido ajeno responde 404 y como se
 * deriva el seguimiento son las mismas respuestas que da la web, y las da el
 * mismo codigo. Si el circuito cambia, cambia una vez.
 *
 * <b>Sin 'api.verificado', y es deliberado.</b> routes/web.php exige
 * 'verified' en el carrito y en el checkout, y NO en "mis compras": mirar el
 * historial no necesita correo confirmado, armar un pedido si. Un cliente que
 * se registro, compro en el mostrador y todavia no confirmo el correo tiene
 * que poder ver su compra.
 */
class CompraController extends Controller
{
    /**
     * Compras por pagina. El mismo numero que la web, para que las dos
     * interfaces paginen igual.
     */
    private const POR_PAGINA = 10;

    /**
     * GET /api/v1/compras
     *
     * Historial paginado, lo mas reciente primero. Incluye los dos canales:
     * una compra en el mostrador tambien es una compra del cliente.
     *
     * <b>Dos agregados en la misma consulta.</b> `lineas_count` para el
     * contador de la tarjeta y la suma de los pagos acreditados para el estado
     * del cobro. Los dos salen como subconsulta de la pagina: resolverlos en
     * el resource seria dos consultas por fila.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $cliente = $request->user()->cliente;

        // Un cliente sin ficha en `clientes` no tiene ventas posibles, y se le
        // devuelve la lista vacia en lugar de un error.
        //
        // <b>Hay que decirlo explicito.</b> Un where('cliente_id', null) no
        // filtra nada: traduce a "cliente_id is null", y en esta tabla esas
        // son las ventas de mostrador a consumidor final —de otra gente—.
        // La columna es nullable justamente para eso
        // (create_ventas_table: cliente_id nullable).
        if ($cliente === null) {
            return CompraResource::collection(
                new LengthAwarePaginator([], 0, self::POR_PAGINA, 1, [
                    'path' => $request->url(),
                ])
            );
        }

        $compras = Venta::query()
            ->where('cliente_id', $cliente->id)
            ->whereNotIn('estado', CompraDelCliente::valoresQueNoSonCompras())
            ->withCount('lineas')
            ->withSum(
                ['pagos as monto_acreditado' => fn (Builder $query) => $query->acreditados()],
                'monto',
            )
            ->latest('fecha')
            ->latest('id')
            ->paginate(self::POR_PAGINA);

        return CompraResource::collection($compras);
    }

    /**
     * GET /api/v1/compras/{venta}
     *
     * Detalle y seguimiento de un pedido.
     *
     * <b>404 y no 403 cuando el pedido es de otro cliente.</b> Un 403
     * confirmaria que ese numero de pedido existe. Es la misma respuesta que
     * da la web, y la que cierra el agujero de cambiar el id en la URL.
     *
     * Un presupuesto entra por la misma puerta y recibe el mismo 404: no es
     * una compra, ni escribiendo la URL a mano.
     *
     * <b>Todo en una pasada.</b> Cinco relaciones con eager loading para que
     * el resource no dispare una consulta por linea, por pago ni por
     * comprobante. Del producto se traen solo las cinco columnas que la
     * pantalla usa: la imagen y lo que decide si su ficha se puede abrir.
     */
    public function show(Request $request, Venta $venta): CompraDetalleResource
    {
        abort_unless(
            CompraDelCliente::esVisiblePara($venta, $request->user()->cliente),
            404,
        );

        $venta->load([
            'lineas.producto:id,imagen,nombre,activo,publicado_app',
            'pagos',
            'envio',
            'comprobantes.tipoComprobante',
            'comprobantes.puntoVenta',
        ]);

        return new CompraDetalleResource($venta);
    }
}
