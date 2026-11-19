<?php

namespace App\Http\Resources\Gestion;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Categoria vista desde el panel.
 *
 * <b>Es un archivo distinto del CategoriaResource de la tienda, a proposito.</b>
 * Aquel es el selector de filtros del catalogo y manda tres campos (id, nombre,
 * slug); el panel necesita ademas el orden, el estado y cuantos productos la
 * referencian. Compartir uno solo obligaria a decidir por request que campos
 * salen, que es como terminan filtrandose datos de gestion a la tienda.
 *
 * <b>Los campos se enumeran uno por uno, nunca toArray() del modelo.</b> Es la
 * misma regla que siguen los resources de la tienda: un volcado completo
 * publica lo que se agregue manana a la tabla sin que nadie lo decida.
 *
 * @mixin \App\Models\Categoria
 */
class CategoriaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'     => $this->id,
            'nombre' => $this->nombre,
            'slug'   => $this->slug,
            'orden'  => (int) $this->orden,
            'activo' => (bool) $this->activo,

            // Cuantos productos la nombran. Viaja solo cuando la consulta hizo
            // el withCount(), que es el listado y el detalle: sin whenCounted
            // una respuesta sin el conteo emitiria null y la pantalla no
            // podria distinguir "no vino" de "no tiene ninguno".
            //
            // Es el dato con el que el panel sabe si DELETE va a dar 409 antes
            // de intentarlo. La regla igual la hace cumplir la FK, no esto.
            'productos_count' => $this->whenCounted('productos'),
        ];
    }
}
