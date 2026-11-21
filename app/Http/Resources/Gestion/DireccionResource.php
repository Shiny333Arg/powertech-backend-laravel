<?php

namespace App\Http\Resources\Gestion;

use App\Models\Direccion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Una dirección de la tabla polimórfica `direcciones`, vista desde el panel.
 *
 * <b>No publica el dueño</b> —ni `direccionable_type` ni `direccionable_id`—:
 * el dueño sale siempre de la URL, nunca del cuerpo ni de la respuesta. Es la
 * misma regla que ya tenían las rutas anidadas del panel, y la razón es que si
 * el tipo viajara por el cuerpo cualquiera podría colgarle una dirección fiscal
 * a otro cliente.
 *
 * El `tipo` viaja con valor y etiqueta, igual que los enums de
 * MovimientoStockResource y ProveedorResource: el valor es el que el formulario
 * manda de vuelta y la etiqueta es la que muestra la pantalla, así que el panel
 * Blade y la API nombran cada tipo igual.
 *
 * @mixin Direccion
 */
class DireccionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'tipo'              => $this->tipo?->value,
            'tipo_etiqueta'     => $this->tipo?->etiqueta(),
            'calle'             => $this->calle,
            'numero'            => $this->numero,
            'piso_depto'        => $this->piso_depto,
            'codigo_postal'     => $this->codigo_postal,
            'localidad'         => $this->localidad,
            'provincia'         => $this->provincia,

            // Una sola predeterminada por dueño y por tipo. Lo resuelve el
            // controlador al guardar, no el modelo.
            'es_predeterminada' => (bool) $this->es_predeterminada,
        ];
    }
}
