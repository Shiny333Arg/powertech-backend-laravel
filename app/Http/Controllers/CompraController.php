<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Support\CompraDelCliente;
use Illuminate\View\View;

/**
 * "Mis compras": el historial del cliente y el seguimiento de cada pedido.
 *
 * Solo lectura. El cliente no cancela ni modifica desde aca: cancelar una
 * venta pagada libera stock comprometido y, si ya tiene factura con CAE,
 * exige nota de credito. Eso es una decision de la empresa, no una accion de
 * autogestion.
 *
 * <b>Muestra los dos canales.</b> Una compra en el mostrador tambien es una
 * compra del cliente, y aparece con su etiqueta. Lo que NO aparece son los
 * presupuestos ni los presupuestos vencidos: un presupuesto no es una compra,
 * es un precio ofertado. Si mas adelante se quiere que el cliente vea sus
 * presupuestos para venir a pagarlos, es una seccion aparte con otra
 * semantica, no una fila mas de esta lista.
 *
 * <b>Las reglas viven en App\Support\CompraDelCliente.</b> Que cuenta como
 * compra, de quien es cada pedido y como se deriva el seguimiento estaban aca
 * como metodos privados, y Api\V1\CompraController necesita exactamente los
 * mismos: si el circuito cambia, el historial de la web y el de la app tienen
 * que cambiar juntos. Esta clase solo consulta y pasa a la vista.
 */
class CompraController extends Controller
{
    /**
     * Historial, lo mas reciente primero.
     *
     * Un cliente sin ficha (`clientes`) no tiene ventas posibles: se le
     * muestra la lista vacia en lugar de un error.
     *
     * <b>El caso nulo no se puede dejar en where('cliente_id', null).</b> Ese
     * where no filtra por "ninguna": traduce a "cliente_id is null", y en
     * `ventas` esas son las ventas de mostrador a consumidor final, que son
     * de otra gente. La columna es nullable justamente para eso
     * (create_ventas_table). El 0 no existe como id, asi que la lista sale
     * vacia, que es lo que este metodo siempre quiso hacer.
     */
    public function index(): View
    {
        $cliente = request()->user()->cliente;

        $compras = Venta::query()
            ->where('cliente_id', $cliente?->id ?? 0)
            ->whereNotIn('estado', CompraDelCliente::valoresQueNoSonCompras())
            ->withCount('lineas')
            ->latest('fecha')
            ->latest('id')
            ->paginate(10);

        return view('compras.index', [
            'compras' => $compras,
        ]);
    }

    /**
     * Detalle y seguimiento de un pedido.
     *
     * El detalle sale de `venta_lineas` y no del catalogo: descripcion, precio
     * y alicuota estan congelados ahi, y es lo que se le cobro. Si el producto
     * se renombro o cambio de precio despues, el pedido tiene que seguir
     * mostrando lo que el cliente compro.
     *
     * 404 y no 403 cuando el pedido es de otro cliente: un 403 confirmaria que
     * ese numero de pedido existe. Un presupuesto recibe el mismo 404, no se
     * accede por esta puerta ni escribiendo la URL a mano.
     */
    public function show(Venta $venta): View
    {
        abort_unless(
            CompraDelCliente::esVisiblePara($venta, request()->user()->cliente),
            404,
        );

        $venta->load([
            'lineas.producto:id,imagen,nombre,activo,publicado_app',
            'pagos',
            'comprobantes.tipoComprobante',
            'comprobantes.puntoVenta',
        ]);

        return view('compras.show', [
            'venta'       => $venta,
            'seguimiento' => CompraDelCliente::seguimiento($venta),

            // Factura vigente: la de signo positivo que no fue rechazada. El
            // criterio, y por que el casteo de `signo` es imprescindible,
            // estan en CompraDelCliente::facturaVigente().
            'factura' => CompraDelCliente::facturaVigente($venta),
        ]);
    }
}
