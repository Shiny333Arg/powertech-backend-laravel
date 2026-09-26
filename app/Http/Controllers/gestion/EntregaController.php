<?php

namespace App\Http\Controllers\gestion;

use App\Enums\EstadoVenta;
use App\Exceptions\StockInsuficienteException;
use App\Exceptions\TransicionNoPermitidaException;
use App\Models\Venta;
use App\Services\EntregaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * El deposito.
 *
 * Es el unico controlador del sistema que provoca una baja de stock fisico, y
 * lo hace en un solo punto: la salida de la mercaderia. Ni el cobro ni la
 * factura mueven el fisico.
 */
class EntregaController extends Controller
{
    public function __construct(private readonly EntregaService $entregas)
    {
    }

    /** Cola del deposito: lo que hay para preparar, despachar o entregar. */
    public function index(): View
    {
        Gate::authorize('viewAny', Venta::class);

        $enCurso = Venta::query()
            ->whereIn('estado', [
                EstadoVenta::EnPreparacion->value,
                EstadoVenta::ListaRetiro->value,
                EstadoVenta::Despachada->value,
            ])
            ->with(['cliente:id,razon_social', 'lineas.producto:id,codigo,nombre'])
            ->orderBy('fecha')
            ->paginate(25);

        // Preventas facturadas esperando reposicion, por antiguedad: quien
        // pago primero se entrega primero.
        $pendientesDeStock = Venta::query()
            ->pendientesDeStock()
            ->with(['cliente:id,razon_social', 'lineas.producto:id,codigo,nombre,stock_fisico'])
            ->limit(50)
            ->get();

        return view('gestion.entregas.index', compact('enCurso', 'pendientesDeStock'));
    }

    /** Marca el pedido armado y esperando al cliente en el local. */
    public function listaRetiro(Venta $venta): RedirectResponse
    {
        Gate::authorize('entregar', $venta);

        return $this->ejecutar(
            fn () => $this->entregas->listaParaRetiro($venta),
            'El pedido quedó listo para retirar.'
        );
    }

    /** Despacho de un envio: acá sale la mercadería. */
    public function despachar(Venta $venta): RedirectResponse
    {
        Gate::authorize('entregar', $venta);

        return $this->ejecutar(
            fn () => $this->entregas->despachar($venta, request()->user()),
            'Envío despachado. Se descontó el stock físico.'
        );
    }

    /** Entrega al cliente. */
    public function entregar(Venta $venta): RedirectResponse
    {
        Gate::authorize('entregar', $venta);

        return $this->ejecutar(
            fn () => $this->entregas->entregar($venta, request()->user()),
            'Venta entregada.'
        );
    }

    /**
     * Traduce las dos excepciones esperables a un mensaje de pantalla.
     *
     * StockInsuficienteException no es un bug: es el sistema impidiendo que el
     * stock fisico quede en negativo. Significa que la mercaderia no esta.
     */
    private function ejecutar(callable $accion, string $exito): RedirectResponse
    {
        try {
            $accion();
        } catch (StockInsuficienteException $e) {
            return back()->withErrors(['stock' => $e->getMessage()]);
        } catch (TransicionNoPermitidaException $e) {
            return back()->withErrors(['estado' => $e->getMessage()]);
        }

        return back()->with('status', $exito);
    }
}
