<?php

namespace App\Http\Controllers\gestion;

use App\Enums\EstadoComprobante;
use App\Exceptions\FacturacionException;
use App\Models\Comprobante;
use App\Services\FacturacionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Comprobantes fiscales: solo lectura y reintento.
 *
 * No hay alta manual —el comprobante nace de acreditar un pago o de una
 * devolucion— ni edicion ni borrado: es inmutable una vez que tiene CAE.
 */
class ComprobanteController extends Controller
{
    public function __construct(private readonly FacturacionService $facturacion)
    {
    }

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Comprobante::class);

        $filtros = $request->validate([
            'estado'         => ['nullable', 'string'],
            'punto_venta_id' => ['nullable', 'integer'],
            'desde'          => ['nullable', 'date'],
            'hasta'          => ['nullable', 'date', 'after_or_equal:desde'],
        ]);

        $comprobantes = Comprobante::query()
            ->with(['tipoComprobante:id,nombre,letra', 'puntoVenta:id,numero', 'cliente:id,razon_social', 'venta:id,numero'])
            ->when($filtros['estado'] ?? null, fn ($q, $e) => $q->where('estado', $e))
            ->when($filtros['punto_venta_id'] ?? null, fn ($q, $pv) => $q->where('punto_venta_id', $pv))
            ->when($filtros['desde'] ?? null, fn ($q, $d) => $q->whereDate('fecha_emision', '>=', $d))
            ->when($filtros['hasta'] ?? null, fn ($q, $h) => $q->whereDate('fecha_emision', '<=', $h))
            ->latest('fecha_emision')
            ->paginate(25)
            ->withQueryString();

        return view('gestion.comprobantes.index', [
            'comprobantes' => $comprobantes,
            'filtros'      => $filtros,
            'estados'      => EstadoComprobante::cases(),
        ]);
    }

    public function show(Comprobante $comprobante): View
    {
        Gate::authorize('view', $comprobante);

        $comprobante->load([
            'tipoComprobante', 'puntoVenta', 'cliente', 'tipoDocumento', 'condicionIva',
            'lineas.producto:id,codigo,nombre',
            'venta:id,numero',
            'comprobanteAsociado.tipoComprobante',
            'notasAsociadas.tipoComprobante',
            'solicitudes',
        ]);

        return view('gestion.comprobantes.show', ['comprobante' => $comprobante]);
    }

    /**
     * Reintenta la autorizacion de un comprobante rechazado o que quedo
     * pendiente porque se corto la conexion.
     *
     * Es seguro repetirlo: el servicio no vuelve a pedir CAE para uno ya
     * autorizado, y cada intento queda asentado en `afip_solicitudes` con su
     * numero de intento.
     */
    public function reintentar(Comprobante $comprobante): RedirectResponse
    {
        Gate::authorize('reintentar', $comprobante);

        try {
            $comprobante = $this->facturacion->autorizar($comprobante);
        } catch (FacturacionException $e) {
            return back()->withErrors(['facturacion' => $e->getMessage()]);
        }

        return back()->with('status', $comprobante->estaAutorizado()
            ? "Comprobante autorizado. CAE {$comprobante->cae}."
            : 'El organismo volvió a rechazar el comprobante. Revisá el detalle de la solicitud.');
    }
}
