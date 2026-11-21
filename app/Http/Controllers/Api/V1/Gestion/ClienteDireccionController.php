<?php

namespace App\Http\Controllers\Api\V1\Gestion;

use App\Http\Controllers\Controller;
use App\Http\Requests\DireccionRequest;
use App\Http\Resources\Gestion\DireccionResource;
use App\Models\Cliente;
use App\Models\Direccion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * Direcciones de un cliente, por API.
 *
 * <b>Es el mismo DireccionRequest que usaba el panel</b> —las reglas no se
 * movieron de lugar—, y la misma regla de la única predeterminada por dueño y
 * por tipo. Lo que cambió es quién lo invoca.
 *
 * <b>El dueño sale SIEMPRE de la ruta, nunca del cuerpo.</b> Si el
 * `direccionable_type` llegara del formulario, cualquiera podría colgarle una
 * dirección fiscal a otro cliente. Es la misma decisión que tomaron las rutas
 * web anidadas, y la razón por la que el Resource tampoco lo publica.
 *
 * <b>Cuelgan del cliente y no de un /direcciones global</b>, por el mismo
 * motivo que el stock cuelga del producto: así el dueño está en la URL y la
 * autorización se puede pedir sobre él.
 *
 * <b>Un controlador propio y no el polimórfico del panel.</b> El web atendía
 * clientes, proveedores y empleados con un discriminador `{tipo}` en la URL;
 * acá el único que las necesita por endpoint es el cliente —el proveedor manda
 * su domicilio único anidado en el cuerpo y el empleado hace lo mismo con el
 * suyo—, así que un discriminador para un solo valor sería ceremonia, y un
 * `{tipo}` libre en la API es la clase de parámetro que después hay que
 * defender.
 *
 * <b>La dirección SÍ se borra:</b> no es un documento y no la referencia nada.
 * Los envíos copian el domicilio en lugar de apuntar acá, justamente para que
 * un despacho ya hecho no cambie de destino si alguien edita el perfil. Es la
 * excepción a la regla de "sólo baja lógica" del resto de la API, y estaba
 * explicada así en las rutas web desde siempre.
 *
 * <b>Las tres operaciones van con 'cliente.editar'</b>: la autorización se
 * delega en el dueño, como en el panel —quien puede editar al cliente puede
 * editar sus direcciones— y es lo que dice la descripción del permiso en
 * PermisoSeeder ("Editar clientes y sus direcciones").
 */
class ClienteDireccionController extends Controller
{
    /** POST /api/v1/gestion/clientes/{cliente}/direcciones — 201. */
    public function store(DireccionRequest $request, Cliente $cliente): JsonResponse
    {
        $direccion = DB::transaction(function () use ($cliente, $request) {
            $direccion = $cliente->direcciones()->create($request->validated());

            $this->resolverPredeterminada($cliente, $direccion);

            return $direccion;
        });

        return (new DireccionResource($direccion->refresh()))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /** PUT /api/v1/gestion/clientes/{cliente}/direcciones/{direccion} */
    public function update(DireccionRequest $request, Cliente $cliente, Direccion $direccion): DireccionResource
    {
        $this->exigirQuePertenezca($direccion, $cliente);

        DB::transaction(function () use ($cliente, $direccion, $request) {
            $direccion->update($request->validated());

            $this->resolverPredeterminada($cliente, $direccion);
        });

        return new DireccionResource($direccion->refresh());
    }

    /**
     * DELETE /api/v1/gestion/clientes/{cliente}/direcciones/{direccion} — 204.
     *
     * Sin cuerpo: no queda recurso del que hablar y la pantalla ya sabe cuál
     * borró.
     */
    public function destroy(Cliente $cliente, Direccion $direccion): Response
    {
        $this->exigirQuePertenezca($direccion, $cliente);

        $direccion->delete();

        return response()->noContent();
    }

    /**
     * 404 si la dirección no es de este cliente.
     *
     * <b>Es la comprobación que impide operar sobre la dirección de otro.</b>
     * El binding de `{direccion}` resuelve por id y nada más: sin esto, un id
     * de la tabla polimórfica alcanzaría para editar o borrar la dirección de
     * cualquier cliente, proveedor o empleado con sólo tener 'cliente.editar'
     * sobre uno cualquiera. Mismo criterio y mismo código que el controlador
     * web: 404 y no 403, porque desde la URL pedida ese recurso no existe.
     */
    private function exigirQuePertenezca(Direccion $direccion, Cliente $cliente): void
    {
        abort_unless(
            $direccion->direccionable_id === $cliente->getKey()
                && $direccion->direccionable_type === $cliente->getMorphClass(),
            Response::HTTP_NOT_FOUND
        );
    }

    /** Una sola predeterminada por cliente y tipo de dirección. */
    private function resolverPredeterminada(Cliente $cliente, Direccion $direccion): void
    {
        if (! $direccion->es_predeterminada) {
            return;
        }

        $cliente->direcciones()
            ->where('tipo', $direccion->tipo->value)
            ->whereKeyNot($direccion->id)
            ->update(['es_predeterminada' => false]);
    }
}
