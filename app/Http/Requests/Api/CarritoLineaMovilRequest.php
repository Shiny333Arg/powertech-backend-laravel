<?php

namespace App\Http\Requests\Api;

use App\Models\Producto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Alta y edicion de una linea de carrito desde la app.
 *
 * <b>Por que no se reutiliza CarritoLineaRequest.</b> Aquel decide si pedir
 * `producto_id` con `$this->routeIs('cliente.carrito.store')`, es decir
 * atandose al NOMBRE de una ruta web. Desde la API ese nombre nunca coincide,
 * asi que heredarlo haria que el alta no validara el producto: el campo
 * quedaria fuera de las reglas y cualquier id entraria. Reutilizarlo exigiria
 * tocar el Request del circuito web, que esta validado y fuera de alcance.
 *
 * Las REGLAS son deliberadamente identicas a las de alla. Si cambian, cambian
 * en los dos lados: son la misma politica comercial vista desde dos canales.
 *
 * <b>No se valida contra el stock disponible, a proposito.</b> Se admite
 * preventa: pedir mas unidades de las que hay en el deposito es una operacion
 * legitima. El tope de 99 no es stock, es un freno contra el tipeo.
 *
 * <b>Ningun importe se acepta del cliente.</b> El cuerpo tiene dos campos y
 * ninguno es dinero: el precio lo calcula el backend contra `productos` y el
 * MotorPromociones en cada lectura. Un `precio` que llegue en el JSON se
 * ignora porque no existe como regla, y `validated()` solo devuelve lo
 * declarado aca.
 */
class CarritoLineaMovilRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $reglas = [
            'cantidad' => ['required', 'integer', 'min:1', 'max:99'],
        ];

        if ($this->isMethod('POST')) {

            // Solo productos de la vitrina: activo Y publicado. Un producto
            // despublicado no se agrega por mas que su id llegue en el body.
            // Es el mismo criterio de Producto::scopePublicados().
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

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cantidad.required'  => 'Indicá cuántas unidades querés.',
            'cantidad.integer'   => 'La cantidad tiene que ser un número entero.',
            'cantidad.min'       => 'La cantidad tiene que ser al menos 1.',
            'cantidad.max'       => 'No podés llevar más de 99 unidades del mismo producto.',
            'producto_id.exists' => 'Ese producto ya no está disponible en la tienda.',
        ];
    }
}
