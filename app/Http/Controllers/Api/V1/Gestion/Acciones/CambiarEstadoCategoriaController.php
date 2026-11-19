<?php

namespace App\Http\Controllers\Api\V1\Gestion\Acciones;

use App\Http\Controllers\Controller;
use App\Http\Resources\Gestion\CategoriaResource;
use App\Models\Categoria;
use Illuminate\Http\Request;

/**
 * PATCH /api/v1/gestion/categorias/{categoria}/estado
 *
 * Baja logica y reactivacion. Las dos en un endpoint porque son la misma
 * decision —y el mismo permiso, categoria.baja— mirada desde los dos lados:
 * el cuerpo trae el estado al que se quiere llegar.
 *
 *   { "activo": false }   sale de operacion, reversible, la fila sigue
 *   { "activo": true }    vuelve a estar disponible para productos nuevos
 *
 * <b>Controlador de accion unica, y no un metodo mas del CRUD.</b> Es la forma
 * del proyecto para las transiciones de estado: con un solo __invoke no hay
 * manera de que la operacion quede sin su linea de autorizacion, que es
 * exactamente el agujero que F1 cerro en el panel —authorizeResource() cubre
 * los siete metodos de recurso y deja las acciones afuera—.
 *
 * <b>PATCH y no PUT</b>: cambia un campo, no reemplaza el recurso.
 *
 * El permiso lo exige la ruta con 'can:cambiarEstado,categoria'. Se usan
 * darDeBaja()/reactivar() del trait TieneBajaLogica y no un update() suelto,
 * para que la baja siga siendo una sola operacion en todo el sistema.
 */
class CambiarEstadoCategoriaController extends Controller
{
    public function __invoke(Request $request, Categoria $categoria): CategoriaResource
    {
        $datos = $request->validate([
            'activo' => ['required', 'boolean'],
        ], [
            'activo.required' => 'Indicá si la categoría queda en operación o no.',
        ]);

        $datos['activo']
            ? $categoria->reactivar()
            : $categoria->darDeBaja();

        return new CategoriaResource($categoria->refresh()->loadCount('productos'));
    }
}
