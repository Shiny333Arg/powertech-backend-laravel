<?php

namespace App\Http\Controllers\gestion;

use App\Enums\CanalVenta;
use App\Enums\EstadoVenta;
use App\Enums\ModoEntrega;
use App\Exceptions\TransicionNoPermitidaException;
use App\Http\Requests\CancelarVentaRequest;
use App\Http\Requests\VentaRequest;
use App\Models\Venta;
use App\Services\VentaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Gestion de ventas y presupuestos del canal mostrador.
 *
 * <b>No hay destroy().</b> Una venta no se elimina ni se da de baja: su fin de
 * vida es un estado. Lo que en otro CRUD seria la D es aca `cancelar()`, que
 * libera el stock comprometido y deja el documento con su numero y su
 * historial. La ruta se declara con ->except('destroy').
 *
 * Tampoco estan aca el cobro (cajero), la entrega (deposito) ni la emision del
 * comprobante: son etapas posteriores del circuito, con sus propios permisos y
 * sus propios controladores.
 */
class VentaController extends Controller
{
    public function __construct(private readonly VentaService $ventas)
    {
    }

    // ------------------------------------------------------------------
    // Lectura
    // ------------------------------------------------------------------

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Venta::class);

        $filtros = $request->validate([
            'numero'     => ['nullable', 'integer'],
            'estado'     => ['nullable', 'string'],
            'canal'      => ['nullable', 'string'],
            'cliente_id' => ['nullable', 'integer'],
            'desde'      => ['nullable', 'date'],
            'hasta'      => ['nullable', 'date', 'after_or_equal:desde'],
        ]);

        $usuario = $request->user();

        $ventas = Venta::query()
            ->with([
                'cliente:id,razon_social',
                'usuario:id,nombres,apellido',
            ])
            ->withCount('lineas')
            ->when($filtros['numero'] ?? null, fn ($q, $numero) => $q->where('numero', $numero))
            ->when($filtros['estado'] ?? null, fn ($q, $estado) => $q->where('estado', $estado))
            ->when($filtros['canal'] ?? null, fn ($q, $canal) => $q->where('canal', $canal))
            ->when($filtros['cliente_id'] ?? null, fn ($q, $cliente) => $q->where('cliente_id', $cliente))
            ->when($filtros['desde'] ?? null, fn ($q, $desde) => $q->whereDate('fecha', '>=', $desde))
            ->when($filtros['hasta'] ?? null, fn ($q, $hasta) => $q->whereDate('fecha', '<=', $hasta))
            // El vendedor ve su propio historial; el resto depende del permiso.
            ->unless(
                $usuario->tienePermiso('venta.ver_todas'),
                fn ($q) => $q->where('usuario_id', $usuario->id)
            )
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('gestion.ventas.index', [
            'ventas'  => $ventas,
            'filtros' => $filtros,
            'estados' => EstadoVenta::cases(),
            'canales' => CanalVenta::cases(),
        ]);
    }

    public function show(Venta $venta): View
    {
        Gate::authorize('view', $venta);

        $venta->load([
            'cliente.condicionIva',
            'usuario:id,nombres,apellido',
            'usuarioValido:id,nombres,apellido',
            'lineas.producto:id,codigo,nombre,stock_disponible',
            'promocionesAplicadas.promocion:id,nombre',
            'pagos.usuario:id,nombres,apellido',
            'comprobantes.tipoComprobante',
            'comprobantes.puntoVenta',
            'envio',
        ]);

        return view('gestion.ventas.show', ['venta' => $venta]);
    }

    // ------------------------------------------------------------------
    // Alta
    // ------------------------------------------------------------------

    public function create(): View
    {
        Gate::authorize('create', Venta::class);

        return view('gestion.ventas.create', [
            'modosEntrega' => ModoEntrega::cases(),
        ]);
    }

    public function store(VentaRequest $request): RedirectResponse
    {
        Gate::authorize('create', Venta::class);

        $venta = $this->ventas->crear(
            $request->validated(),
            $request->user(),
            CanalVenta::Mostrador,
        );

        $mensaje = $venta->esPresupuesto()
            ? "Presupuesto {$venta->numeroFormateado()} generado."
            : "Venta {$venta->numeroFormateado()} confirmada. Stock comprometido.";

        return redirect()
            ->route('gestion.ventas.show', $venta)
            ->with('status', $mensaje);
    }

    // ------------------------------------------------------------------
    // Edicion
    // ------------------------------------------------------------------

    public function edit(Venta $venta): View
    {
        Gate::authorize('update', $venta);

        $venta->load('lineas.producto:id,codigo,nombre,precio,stock_disponible');

        return view('gestion.ventas.edit', [
            'venta'        => $venta,
            'modosEntrega' => ModoEntrega::cases(),
        ]);
    }

    public function update(VentaRequest $request, Venta $venta): RedirectResponse
    {
        Gate::authorize('update', $venta);

        try {
            $this->ventas->actualizar($venta, $request->validated());
        } catch (TransicionNoPermitidaException $e) {
            return back()->withInput()->withErrors(['estado' => $e->getMessage()]);
        }

        return redirect()
            ->route('gestion.ventas.show', $venta)
            ->with('status', "Presupuesto {$venta->numeroFormateado()} actualizado.");
    }

    // ------------------------------------------------------------------
    // Transiciones
    // ------------------------------------------------------------------

    /** Valida el presupuesto: lo confirma, lo habilita para el cobro y compromete el stock. */
    public function validar(Request $request, Venta $venta): RedirectResponse
    {
        Gate::authorize('validar', $venta);

        try {
            $this->ventas->validar($venta, $request->user());
        } catch (TransicionNoPermitidaException $e) {
            return back()->withErrors(['estado' => $e->getMessage()]);
        }

        return back()->with(
            'status',
            "Venta {$venta->numeroFormateado()} confirmada. Las unidades quedaron comprometidas."
        );
    }

    /** La "baja" de una venta. No borra nada: cambia el estado y libera el compromiso. */
    public function cancelar(CancelarVentaRequest $request, Venta $venta): RedirectResponse
    {
        Gate::authorize('cancelar', $venta);

        try {
            $this->ventas->cancelar($venta, $request->user(), $request->validated()['motivo']);
        } catch (TransicionNoPermitidaException $e) {
            return back()->withErrors(['estado' => $e->getMessage()]);
        }

        return back()->with(
            'status',
            "Venta {$venta->numeroFormateado()} cancelada."
        );
    }
}
