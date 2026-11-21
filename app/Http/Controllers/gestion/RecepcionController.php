<?php

namespace App\Http\Controllers\gestion;

use App\Enums\EstadoOrdenCompra;
use App\Exceptions\TransicionNoPermitidaException;
use App\Models\OrdenCompra;
use App\Services\RecepcionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * La entrada de mercaderia contra un pedido a proveedor.
 *
 * Cuelga de la orden, igual que las devoluciones cuelgan de la venta: una
 * recepcion no existe sin el pedido contra el que entra.
 *
 * <b>La carga de la entrega pasó a la API.</b> store() se eliminó junto con su
 * ruta: la registra ahora
 * POST /api/v1/gestion/compras/{orden}/recepciones, que es el mismo
 * RecepcionRequest y el mismo RecepcionService, con una guarda más —el 409 de
 * remito repetido— que el POST web no tenía. Lo que queda acá son las dos
 * cosas que no se migraron en esta entrega:
 *
 *   create()  la página del formulario, que sigue sirviéndola Blade.
 *   cerrar()  dar por terminado un pedido a medio recibir. Se opera desde el
 *             detalle —show.blade.php—, que es la segunda entrega.
 *
 * <b>Este controlador no mueve stock ni toca estados.</b> Todo eso vive en
 * RecepcionService, que es quien sabe que una entrega acumula en
 * cantidad_recibida, registra un movimiento de compra, decide si la orden quedo
 * completa y recorre la cola de preventas.
 */
class RecepcionController extends Controller
{
    public function __construct(private readonly RecepcionService $recepciones)
    {
    }

    /**
     * La página del formulario de carga.
     *
     * <b>Ya no trae el pedido adentro.</b> El encabezado, los renglones con lo
     * que falta de cada uno y el historial de entregas anteriores los pide
     * resources/js/gestion/compras/recepcion.js a
     * GET /api/v1/gestion/compras/{orden}, que desde E.2.b ya devuelve las
     * recepciones en 'data.recepciones' —no hay tabla de recepciones: cada
     * entrada ES un movimiento de stock con el remito en su 'motivo'—.
     *
     * <b>El Gate sigue acá y es lo que más importa de este método.</b> No es
     * sólo el permiso: recibir() exige esRecibible(), que deja afuera el
     * borrador, la aprobada sin enviar, la ya recibida, la cancelada y el
     * pedido repartido —la mercadería se recibe en la orden de cada
     * proveedor—. Así el enlace directo a la página de un pedido que no admite
     * entregas tampoco entra, y no queda a cargo del JavaScript.
     */
    public function create(OrdenCompra $orden): View
    {
        Gate::authorize('recibir', $orden);

        return view('gestion.compras.recibir', [
            'orden' => $orden,

            // El color del badge de estado, tal como lo define el enum: el
            // encabezado de esta pantalla lo pinta el JavaScript y el del
            // detalle sigue siendo Blade. Una sola paleta, en el enum.
            'colores' => EstadoOrdenCompra::colores(),
        ]);
    }

    /**
     * Cierra una orden recibida a medias.
     *
     * Es la salida para el proveedor que avisa que el resto no viene. Sin esta
     * accion la orden se queda abierta para siempre y el restock no vuelve a
     * pedir esos productos nunca.
     *
     * Se opera desde el detalle, que no se migró en esta entrega. Su endpoint
     * —POST /api/v1/gestion/compras/{orden}/cierre— ya existe desde E.2.b.
     */
    public function cerrar(Request $request, OrdenCompra $orden): RedirectResponse
    {
        Gate::authorize('cerrar', $orden);

        $datos = $request->validate(
            ['motivo' => ['required', 'string', 'max:255']],
            ['motivo.required' => 'Escribí por qué se da por cerrado el pedido con faltante.'],
        );

        try {
            $this->recepciones->cerrar($orden, $request->user(), $datos['motivo']);
        } catch (TransicionNoPermitidaException $error) {
            return back()->withErrors(['estado' => $error->getMessage()]);
        }

        return back()->with(
            'status',
            "Se cerró el pedido {$orden->numero} con lo recibido. "
            .'Lo que quedó sin cubrir vuelve a entrar en la reposición automática.'
        );
    }
}
