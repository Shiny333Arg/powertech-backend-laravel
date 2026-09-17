<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Alta y edicion de productos.
 *
 * Tres columnas de la tabla NO estan acá, y la ausencia es la decision:
 *
 *   stock_fisico / stock_comprometido : no se escriben desde un formulario.
 *       Se mueven con StockService, que deja el movimiento correspondiente.
 *       En el alta se acepta 'stock_inicial', que NO es la columna: es la
 *       cantidad del movimiento de tipo 'inicial'.
 *   stock_disponible : columna generada, de solo lectura.
 *   destacado : lo escribe un job por volumen de venta. Por requerimiento no
 *       es editable a mano, asi que ni siquiera se acepta el campo.
 */
class ProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // la autorizacion real vive en ProductoPolicy
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'reposicion_automatica' => $this->boolean('reposicion_automatica'),
            'publicado_app'         => $this->boolean('publicado_app'),
            'activo'                => $this->boolean('activo'),
        ]);
    }

    public function rules(): array
    {
        $id     = $this->route('producto')?->id;
        $esAlta = $id === null;

        return [
            'codigo' => [
                'required', 'string', 'max:30',
                Rule::unique('productos', 'codigo')->ignore($id),
            ],
            'nombre'      => ['required', 'string', 'max:150'],
            'marca'       => ['nullable', 'string', 'max:80'],
            'descripcion' => ['nullable', 'string', 'max:65535'],

            // exists sin filtrar por activo, a proposito: exigir categoria
            // activa acá bloquearia editar un producto cuya categoria se dio
            // de baja despues. El desplegable del alta sí filtra por activas.
            'categoria_id'    => ['required', 'integer', Rule::exists('categorias', 'id')],
            'alicuota_iva_id' => ['required', 'integer', Rule::exists('alicuotas_iva', 'id')],
            'proveedor_id'    => ['nullable', 'integer', Rule::exists('proveedores', 'id')],

            'reposicion_automatica' => ['boolean'],

            'precio' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'costo'  => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],

            'punto_reposicion'    => ['required', 'integer', 'min:0', 'max:999999'],
            'cantidad_reposicion' => ['required', 'integer', 'min:0', 'max:999999'],

            'peso_gramos' => ['nullable', 'integer', 'min:0', 'max:999999'],

            // Una sola foto por producto, por requerimiento.
            'imagen' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],

            'publicado_app' => ['boolean'],
            'activo'        => ['boolean'],

            // Solo en el alta: despues, el stock se mueve con movimientos.
            'stock_inicial' => [$esAlta ? 'nullable' : 'prohibited', 'integer', 'min:0', 'max:999999'],
        ];
    }

    /**
     * Sin proveedor no hay a quien emitirle la orden de reposicion, asi que la
     * reposicion automatica quedaria activada sin poder disparar nada. Es una
     * combinacion de dos campos y por eso no entra en rules().
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->boolean('reposicion_automatica') && ! $this->filled('proveedor_id')) {
                $validator->errors()->add(
                    'reposicion_automatica',
                    'Para reponer automáticamente hay que indicar el proveedor al que se le emite la orden.'
                );
            }
        });
    }

    /** Lo que va al modelo. stock_inicial queda afuera: no es una columna. */
    public function datosDelProducto(): array
    {
        return collect($this->validated())
            ->except(['stock_inicial', 'imagen'])
            ->all();
    }

    public function stockInicial(): int
    {
        return (int) $this->validated('stock_inicial', 0);
    }

    public function messages(): array
    {
        return [
            'codigo.required'  => 'El producto necesita un código.',
            'codigo.unique'    => 'Ya hay un producto con ese código.',
            'nombre.required'  => 'Escribí el nombre del producto.',
            'categoria_id.required'    => 'Elegí una categoría.',
            'alicuota_iva_id.required' => 'Elegí la alícuota de IVA. Sin ella no se puede discriminar el IVA en la factura.',
            'precio.required'  => 'Indicá el precio de venta.',
            'precio.numeric'   => 'El precio tiene que ser un número.',
            'punto_reposicion.required'    => 'Indicá a partir de qué stock disponible hay que reponer.',
            'cantidad_reposicion.required' => 'Indicá cuántas unidades pedir al reponer.',
            'imagen.image'     => 'El archivo tiene que ser una imagen.',
            'imagen.max'       => 'La imagen no puede pesar más de 2 MB.',
            'stock_inicial.prohibited' => 'El stock no se edita desde el producto: registrá un ajuste de inventario.',
        ];
    }
}
