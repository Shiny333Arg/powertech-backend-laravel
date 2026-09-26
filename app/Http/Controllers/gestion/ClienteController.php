<?php

namespace App\Http\Controllers\gestion;

use App\Http\Requests\ClienteRequest;
use App\Models\Cliente;
use App\Models\CondicionIva;
use App\Models\TipoDocumento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * El cliente tiene que ser una entidad y no un texto libre dentro de la venta:
 * sin tipo y numero de documento y condicion frente al IVA no se puede
 * facturar.
 *
 * <b>No hay destroy().</b> El cliente solo se da de baja: esta referenciado
 * por ventas y comprobantes emitidos. `baja()` y `reactivar()` son la misma
 * operacion en los dos sentidos.
 */
class ClienteController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Cliente::class);

        $filtros = $request->validate([
            'q'      => ['nullable', 'string', 'max:100'],
            'activo' => ['nullable', 'boolean'],
        ]);

        $clientes = Cliente::query()
            ->with(['tipoDocumento:id,nombre', 'condicionIva:id,nombre'])
            ->when($filtros['q'] ?? null, fn ($q, $texto) => $q->where(
                fn ($sub) => $sub->where('razon_social', 'like', "%{$texto}%")
                    ->orWhere('nro_doc', 'like', "%{$texto}%")
            ))
            // Las pantallas de gestion necesitan ver lo dado de baja para
            // poder reactivarlo: por eso el filtro es explicito y no un
            // global scope.
            ->when(isset($filtros['activo']), fn ($q) => $q->where('activo', (bool) $filtros['activo']))
            ->orderBy('razon_social')
            ->paginate(25)
            ->withQueryString();

        return view('gestion.clientes.index', compact('clientes', 'filtros'));
    }

    public function create(): View
    {
        Gate::authorize('create', Cliente::class);

        return view('gestion.clientes.create', $this->catalogos());
    }

    public function store(ClienteRequest $request): RedirectResponse
    {
        Gate::authorize('create', Cliente::class);

        $cliente = Cliente::create($request->datos() + ['activo' => true]);

        return redirect()
            ->route('gestion.clientes.show', $cliente)
            ->with('status', "Cliente «{$cliente->razon_social}» creado.");
    }

    public function show(Cliente $cliente): View
    {
        Gate::authorize('view', $cliente);

        $cliente->load(['tipoDocumento', 'condicionIva', 'direcciones', 'user:id,email,activo']);

        return view('gestion.clientes.show', [
            'cliente' => $cliente,
            'ventas'  => $cliente->ventas()->latest('fecha')->limit(10)->get(),
        ]);
    }

    public function edit(Cliente $cliente): View
    {
        Gate::authorize('update', $cliente);

        return view('gestion.clientes.edit', ['cliente' => $cliente] + $this->catalogos());
    }

    public function update(ClienteRequest $request, Cliente $cliente): RedirectResponse
    {
        Gate::authorize('update', $cliente);

        $cliente->update($request->datos());

        return redirect()
            ->route('gestion.clientes.show', $cliente)
            ->with('status', 'Cliente actualizado.');
    }

    /**
     * Baja logica. Saca al cliente de la operacion de mostrador; sus compras
     * anteriores lo siguen nombrando.
     *
     * No toca el `activo` de su `user`: cortarle el acceso a la tienda es una
     * decision distinta, y por eso son dos columnas.
     */
    public function baja(Cliente $cliente): RedirectResponse
    {
        Gate::authorize('baja', $cliente);

        $cliente->update(['activo' => false]);

        return back()->with('status', 'Cliente dado de baja.');
    }

    public function reactivar(Cliente $cliente): RedirectResponse
    {
        Gate::authorize('baja', $cliente);

        $cliente->update(['activo' => true]);

        return back()->with('status', 'Cliente reactivado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function catalogos(): array
    {
        return [
            'tiposDocumento'  => TipoDocumento::orderBy('nombre')->get(),
            'condicionesIva'  => CondicionIva::orderBy('nombre')->get(),
        ];
    }
}
