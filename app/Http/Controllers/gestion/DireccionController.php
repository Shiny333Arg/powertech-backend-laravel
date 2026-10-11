<?php

namespace App\Http\Controllers\gestion;

use App\Http\Requests\DireccionRequest;
use App\Models\Cliente;
use App\Models\Direccion;
use App\Models\Empleado;
use App\Models\Proveedor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

/**
 * Direcciones de clientes, proveedores y empleados. Una sola tabla, un solo
 * controlador, rutas anidadas:
 *
 *   gestion/{tipo}/{id}/direcciones
 *
 * El dueño sale SIEMPRE de la ruta, nunca del body: si el
 * `direccionable_type` llegara del formulario, cualquiera podria colgarle una
 * direccion fiscal a otro cliente.
 *
 * La autorizacion se delega en el dueño: quien puede editar al cliente puede
 * editar sus direcciones.
 */
class DireccionController extends Controller
{
    /** Los unicos tipos admitidos. Cualquier otro valor es un 404. */
    private const DUENOS = [
        'clientes'    => Cliente::class,
        'proveedores' => Proveedor::class,
        'empleados'   => Empleado::class,
    ];

    public function store(DireccionRequest $request, string $tipo, int $id): RedirectResponse
    {
        $dueno = $this->dueno($tipo, $id);
        Gate::authorize('update', $dueno);

        DB::transaction(function () use ($dueno, $request) {
            $direccion = $dueno->direcciones()->create($request->validated());
            $this->resolverPredeterminada($dueno, $direccion);
        });

        return back()->with('status', 'Dirección agregada.');
    }

    public function update(DireccionRequest $request, string $tipo, int $id, Direccion $direccion): RedirectResponse
    {
        $dueno = $this->dueno($tipo, $id);
        Gate::authorize('update', $dueno);
        $this->exigirQuePertenezca($direccion, $dueno);

        DB::transaction(function () use ($dueno, $direccion, $request) {
            $direccion->update($request->validated());
            $this->resolverPredeterminada($dueno, $direccion);
        });

        return back()->with('status', 'Dirección actualizada.');
    }

    /**
     * La direccion SI se borra: no es un documento y no la referencia nada.
     * Los envios copian el domicilio en lugar de apuntar aca, justamente para
     * que un despacho no cambie de destino porque alguien edito el perfil.
     */
    public function destroy(string $tipo, int $id, Direccion $direccion): RedirectResponse
    {
        $dueno = $this->dueno($tipo, $id);
        Gate::authorize('update', $dueno);
        $this->exigirQuePertenezca($direccion, $dueno);

        $direccion->delete();

        return back()->with('status', 'Dirección eliminada.');
    }

    private function dueno(string $tipo, int $id): Model
    {
        $clase = self::DUENOS[$tipo] ?? throw new InvalidArgumentException("Tipo de dueño inválido: {$tipo}");

        return $clase::findOrFail($id);
    }

    private function exigirQuePertenezca(Direccion $direccion, Model $dueno): void
    {
        abort_unless(
            $direccion->direccionable_id === $dueno->getKey()
                && $direccion->direccionable_type === $dueno->getMorphClass(),
            404
        );
    }

    /** Una sola predeterminada por dueño y tipo de dirección. */
    private function resolverPredeterminada(Model $dueno, Direccion $direccion): void
    {
        if (! $direccion->es_predeterminada) {
            return;
        }

        $dueno->direcciones()
            ->where('tipo', $direccion->tipo->value)
            ->whereKeyNot($direccion->id)
            ->update(['es_predeterminada' => false]);
    }
}
