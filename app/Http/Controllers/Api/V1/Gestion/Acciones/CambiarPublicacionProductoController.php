<?php

namespace App\Http\Controllers\Api\V1\Gestion\Acciones;

use App\Exceptions\TransicionNoPermitidaException;
use App\Http\Controllers\Controller;
use App\Http\Resources\Gestion\ProductoResource;
use App\Models\Producto;
use Illuminate\Http\Request;

/**
 * PATCH /api/v1/gestion/productos/{producto}/publicacion
 *
 * Publicar o retirar de la tienda.
 *
 *   { "publicado_app": true }    se ve en la vitrina y en Flutter
 *   { "publicado_app": false }   deja de verse; se sigue vendiendo en mostrador
 *
 * <b>Es una pregunta distinta de la baja, y tiene permiso propio</b>
 * (`producto.publicar`, que el vendedor y el cajero no tienen). Un producto
 * activo que se vende en mostrador y no se publica —reservado, de exhibicion,
 * a pedido— es un caso normal del rubro, asi que el estado y la publicacion no
 * pueden ser la misma bandera ni compartir endpoint.
 *
 * <b>La regla:</b> un producto dado de baja no se puede publicar. Al reves si:
 * despublicar siempre esta permitido, porque sacar algo de la vitrina nunca
 * puede quedar bloqueado. El panel resuelve esto con un mensaje de error en la
 * pantalla; aca sale como 422 con el texto del dominio, que es como el render
 * de bootstrap/app.php trata las transiciones no permitidas.
 */
class CambiarPublicacionProductoController extends Controller
{
    public function __invoke(Request $request, Producto $producto): ProductoResource
    {
        $datos = $request->validate([
            'publicado_app' => ['required', 'boolean'],
        ], [
            'publicado_app.required' => 'Indicá si el producto se publica en la tienda o no.',
        ]);

        if ($datos['publicado_app'] && ! $producto->activo) {
            throw TransicionNoPermitidaException::porque(
                'Un producto dado de baja no se puede publicar. Reactivalo primero.'
            );
        }

        $producto->update(['publicado_app' => $datos['publicado_app']]);

        return new ProductoResource(
            $producto->refresh()->load(['categoria:id,nombre', 'proveedor:id,razon_social,activo', 'alicuotaIva:id,nombre,porcentaje'])
        );
    }
}
