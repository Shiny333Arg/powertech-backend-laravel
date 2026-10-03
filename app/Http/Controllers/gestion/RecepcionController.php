<?php

namespace App\Http\Controllers\gestion;

use App\Exceptions\StockInsuficienteException;
use App\Exceptions\TransicionNoPermitidaException;
use App\Http\Requests\RecepcionRequest;
use App\Models\OrdenCompra;
use App\Services\RecepcionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * La entrada de mercaderia contra un pedido a proveedor.
 *
 * Cuelga de la orden, igual que las devoluciones cuelgan de la venta: una
 * recepcion no existe sin el pedido contra el que entra.
 *
 * <b>Este controlador no mueve stock ni toca estados.</b> Todo eso vive en
 * RecepcionService, que es quien sabe que una entrega acumula en
 * cantidad_recibida, registra un movimiento de compra, decide si la orden quedo
 * completa y recorre la cola de preventas. Acá solo se resuelve la pantalla y
 * se traducen las dos excepciones esperables a un mensaje.
 */
class RecepcionController extends Controller
{
    public function __construct(private readonly RecepcionService $recepciones)
    {
    }

    /** El formulario de carga: un renglon por producto, con lo que falta. */
    public function create(OrdenCompra $orden): View
    {
        Gate::authorize('recibir', $orden);

        $orden->load([
            'proveedor',
            'lineas.producto',
            // El historial de entregas anteriores. No hay tabla de recepciones:
            // cada entrada es un movimiento de stock, con su fecha, su autor y
            // el remito del proveedor en 'motivo'.
            'recepciones.producto:id,codigo,nombre',
            'recepciones.usuario:id,usuario',
        ]);

        return view('gestion.compras.recibir', ['orden' => $orden]);
    }

    /**
     * Registra la entrega.
     *
     * El mensaje de vuelta dice cuantas preventas se destrabaron, que es la
     * informacion que el deposito no tiene de otra forma: la mercaderia que
     * acaba de entrar puede estar ya vendida y esperada por alguien que pago
     * hace tres semanas.
     */
    public function store(RecepcionRequest $request, OrdenCompra $orden): RedirectResponse
    {
        Gate::authorize('recibir', $orden);

        try {
            $resultado = $this->recepciones->registrar(
                $orden,
                $request->cantidades(),
                $request->user(),
                $request->remito(),
            );
        } catch (TransicionNoPermitidaException $error) {
            return back()->withInput()->withErrors(['estado' => $error->getMessage()]);
        } catch (StockInsuficienteException $error) {
            return back()->withInput()->withErrors(['stock' => $error->getMessage()]);
        } catch (InvalidArgumentException $error) {
            return back()->withInput()->withErrors(['cantidades' => $error->getMessage()]);
        }

        return redirect()
            ->route('gestion.compras.show', $orden)
            ->with('status', $this->resumen($resultado));
    }

    /**
     * Cierra una orden recibida a medias.
     *
     * Es la salida para el proveedor que avisa que el resto no viene. Sin esta
     * accion la orden se queda abierta para siempre y el restock no vuelve a
     * pedir esos productos nunca.
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

    /**
     * @param  array{unidades:int, lineas:int, cerrada:bool, ventas_liberadas:int}  $resultado
     */
    private function resumen(array $resultado): string
    {
        $unidades = $resultado['unidades'] === 1
            ? '1 unidad'
            : "{$resultado['unidades']} unidades";

        $mensaje = $resultado['cerrada']
            ? "Entraron {$unidades} y el pedido quedó recibido por completo."
            : "Entraron {$unidades}. El pedido sigue abierto por lo que falta.";

        if ($resultado['ventas_liberadas'] > 0) {
            $mensaje .= $resultado['ventas_liberadas'] === 1
                ? ' Se destrabó 1 venta que esperaba stock: ya está en preparación.'
                : " Se destrabaron {$resultado['ventas_liberadas']} ventas que esperaban stock: ya están en preparación.";
        }

        return $mensaje;
    }
}
