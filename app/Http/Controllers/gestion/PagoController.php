<?php

namespace App\Http\Controllers\gestion;

use App\Enums\EstadoPago;
use App\Enums\EstadoVenta;
use App\Exceptions\FacturacionException;
use App\Exceptions\TransicionNoPermitidaException;
use App\Http\Requests\CobroRequest;
use App\Models\Pago;
use App\Models\Venta;
use App\Services\CobroService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * La caja.
 *
 * `pendientes()` es la cola de trabajo del cajero: ventas confirmadas y
 * validadas esperando cobro. `index()` es su arqueo e historial.
 *
 * Un pago no se edita ni se borra: si se cobro de menos se agrega otro pago,
 * si se cobro de mas se devuelve con nota de credito. Por eso no hay update
 * ni destroy.
 */
class PagoController extends Controller
{
    public function __construct(private readonly CobroService $cobros)
    {
    }

    /** Cola de cobro. */
    public function pendientes(Request $request): View
    {
        Gate::authorize('create', Pago::class);

        $ventas = Venta::query()
            ->where('estado', EstadoVenta::Confirmada->value)
            ->whereNotNull('fecha_validacion')
            ->with(['cliente:id,razon_social', 'usuario:id,nombres,apellido'])
            ->withCount('lineas')
            ->orderBy('fecha')
            ->paginate(25);

        return view('gestion.cobros.pendientes', ['ventas' => $ventas]);
    }

    /** Arqueo e historial de cobros. */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Pago::class);

        $filtros = $request->validate([
            'desde'  => ['nullable', 'date'],
            'hasta'  => ['nullable', 'date', 'after_or_equal:desde'],
            'metodo' => ['nullable', 'string'],
        ]);

        $usuario = $request->user();

        $base = Pago::query()
            ->where('estado', EstadoPago::Acreditado->value)
            ->when($filtros['desde'] ?? null, fn ($q, $d) => $q->whereDate('fecha', '>=', $d))
            ->when($filtros['hasta'] ?? null, fn ($q, $h) => $q->whereDate('fecha', '<=', $h))
            ->when($filtros['metodo'] ?? null, fn ($q, $m) => $q->where('metodo', $m))
            // El cajero ve su propio arqueo; el administrativo ve el de todos.
            ->unless(
                $usuario->tienePermiso('venta.ver_todas'),
                fn ($q) => $q->where('usuario_id', $usuario->id)
            );

        return view('gestion.cobros.index', [
            'pagos'      => (clone $base)->with(['venta:id,numero', 'usuario:id,nombres,apellido'])
                ->latest('fecha')->paginate(50)->withQueryString(),
            'totalPorMetodo' => (clone $base)->selectRaw('metodo, SUM(monto) as total, COUNT(*) as cantidad')
                ->groupBy('metodo')->get(),
            'filtros'    => $filtros,
        ]);
    }

    /** Pantalla de cobro de una venta. */
    public function create(Venta $venta): View
    {
        Gate::authorize('cobrar', $venta);

        $venta->load(['cliente.condicionIva', 'lineas.producto:id,codigo,nombre', 'pagos']);

        return view('gestion.cobros.create', [
            'venta'  => $venta,
            'saldo'  => $this->cobros->saldoPendiente($venta) / 100,
        ]);
    }

    /**
     * Registra el cobro. Si completa el total, en la misma transaccion la
     * venta pasa a pagada, se emite el comprobante y se decide si va a
     * preparacion o queda pendiente de stock.
     */
    public function store(CobroRequest $request, Venta $venta): RedirectResponse
    {
        Gate::authorize('cobrar', $venta);

        $datos = $request->validated();

        try {
            $venta = $this->cobros->cobrar(
                $venta,
                $datos['pagos'],
                $request->user(),
                (bool) ($datos['entregar_inmediato'] ?? false),
            );
        } catch (TransicionNoPermitidaException|FacturacionException $e) {
            return back()->withInput()->withErrors(['cobro' => $e->getMessage()]);
        }

        return redirect()
            ->route('gestion.ventas.show', $venta)
            ->with('status', $this->mensajeDe($venta));
    }

    private function mensajeDe(Venta $venta): string
    {
        return match ($venta->estado) {
            EstadoVenta::Confirmada     => 'Pago parcial registrado. La venta sigue con saldo pendiente.',
            EstadoVenta::PendienteStock => 'Venta cobrada y facturada. Queda pendiente de stock hasta la reposición.',
            EstadoVenta::Entregada      => 'Venta cobrada, facturada y entregada.',
            default                     => 'Venta cobrada y facturada. Pasó a preparación.',
        };
    }
}
