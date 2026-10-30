<?php

namespace App\Http\Requests;

use App\Models\Producto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Alta y edicion de una linea de carrito.
 *
 * Una sola clase para las dos operaciones porque validan lo mismo: la unica
 * diferencia es que el alta necesita saber QUE producto, y la edicion ya lo
 * tiene en la linea de la URL.
 *
 * <b>No se valida contra el stock disponible, a proposito.</b> Se admite
 * preventa: pedir mas unidades de las que hay en el deposito es una operacion
 * legitima y el sistema la soporta. El tope de 99 no es stock, es un freno
 * contra el tipeo (un 11 que sale 111).
 */
class CarritoLineaRequest extends FormRequest
{
    public function rules(): array
    {
        $reglas = [
            'cantidad' => ['required', 'integer', 'min:1', 'max:99'],
        ];

        if ($this->routeIs('cliente.carrito.store')) {

            // Solo productos de la vitrina: activo Y publicado. Un producto
            // despublicado no se agrega por mas que su id llegue en el body.
            $reglas['producto_id'] = [
                'required',
                'integer',
                Rule::exists(Producto::class, 'id')
                    ->where('activo', true)
                    ->where('publicado_app', true),
            ];
        }

        return $reglas;
    }

    public function messages(): array
    {
        return [
            'cantidad.min'       => 'La cantidad tiene que ser al menos 1.',
            'cantidad.max'       => 'No podés llevar más de 99 unidades del mismo producto.',
            'producto_id.exists' => 'Ese producto ya no está disponible en la tienda.',
        ];
    }
}
