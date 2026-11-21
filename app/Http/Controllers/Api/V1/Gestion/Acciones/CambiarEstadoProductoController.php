<?php

namespace App\Http\Controllers\Api\V1\Gestion\Acciones;

use App\Http\Controllers\Controller;
use App\Http\Resources\Gestion\ProductoResource;
use App\Models\Producto;
use Illuminate\Http\Request;

/**
 * PATCH /api/v1/gestion/productos/{producto}/estado
 *
 * Baja logica y reactivacion, la unica baja que admite un producto. Las dos en
 * un endpoint porque son la misma decision —y el mismo permiso,
 * `producto.baja`— mirada desde los dos lados.
 *
 *   { "activo": false }   sale de operacion: no se carga en ventas nuevas
 *   { "activo": true }    vuelve a estar disponible
 *
 * <b>No toca `publicado_app`, a proposito.</b> Un producto dado de baja no se
 * ve en la tienda cualquiera sea ese valor, porque la vitrina filtra por los
 * dos; dejarlo intacto conserva la decision de publicacion por si se reactiva.
 * Es exactamente lo que hace el panel.
 *
 * Controlador de accion unica, como el resto de las transiciones: con un solo
 * __invoke no hay forma de que la operacion quede sin su linea de
 * autorizacion. El permiso lo exige la ruta con 'can:cambiarEstado,producto'.
 */
class CambiarEstadoProductoController extends Controller
{
    public function __invoke(Request $request, Producto $producto): ProductoResource
    {
        $datos = $request->validate([
            'activo' => ['required', 'boolean'],
        ], [
            'activo.required' => 'Indicá si el producto queda en operación o no.',
        ]);

        $datos['activo']
            ? $producto->reactivar()
            : $producto->darDeBaja();

        return new ProductoResource(
            $producto->refresh()->load(['categoria:id,nombre', 'proveedor:id,razon_social,activo', 'alicuotaIva:id,nombre,porcentaje'])
        );
    }
}
