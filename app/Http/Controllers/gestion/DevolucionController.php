<?php

namespace App\Http\Controllers\gestion;

use App\Exceptions\FacturacionException;
use App\Exceptions\TransicionNoPermitidaException;
use App\Http\Requests\DevolucionRequest;
use App\Models\Venta;
use App\Services\DevolucionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Devolucion total o parcial de una venta entregada.
 *
 * Emite la nota de credito asociada a la factura, reingresa las unidades al
 * stock fisico y deja la venta en `devuelta` o `devuelta_parcial`. La venta no
 * se borra ni se cancela: cancelar es para lo que no llego a entregarse.
 */
class DevolucionController extends Controller
{
    public function __construct(private readonly DevolucionService $devoluciones)
    {
    }

    public function create(Venta $venta): View
    {
        Gate::authorize('devolver', $venta);

        $venta->load(['lineas.producto:id,codigo,nombre', 'comprobantes.tipoComprobante']);

        return view('gestion.devoluciones.create', ['venta' => $venta]);
    }

    public function store(DevolucionRequest $request, Venta $venta): RedirectResponse
    {
        Gate::authorize('devolver', $venta);

        try {
            $venta = $this->devoluciones->devolver(
                $venta,
                $request->cantidades(),
                $request->user(),
                $request->validated()['motivo'],
            );
        } catch (TransicionNoPermitidaException|FacturacionException $e) {
            return back()->withInput()->withErrors(['devolucion' => $e->getMessage()]);
        }

        return redirect()
            ->route('gestion.ventas.show', $venta)
            ->with('status', 'Devolución registrada. Se emitió la nota de crédito y reingresó el stock.');
    }
}
