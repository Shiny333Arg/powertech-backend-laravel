<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Categoria como opcion de filtro del catalogo.
 *
 * Solo lo que la app necesita para armar el selector y para pedir
 * /productos?categoria={id}. El `slug` viaja porque es estable y sin acentos:
 * sirve para deep links y para que la app recuerde una seccion sin depender
 * de un id que podria cambiar entre entornos.
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
        ];
    }
}