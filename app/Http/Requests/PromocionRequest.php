<?php

namespace App\Http\Requests;

use App\Enums\CanalPromocion;
use App\Enums\TipoPromocion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Alta y edicion de promociones.
 *
 * La promocion es la UNICA fuente de descuento del sistema: por requerimiento
 * se elimino el descuento manual sobre las ventas. Todo lo que abarata una
 * linea pasa por acá.
 *
 * 'creado_por' no esta en las reglas: no lo elige quien completa el formulario,
 * lo pone el controlador con el usuario autenticado.
 */
class PromocionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // PromocionPolicy
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'acumulable' => $this->boolean('acumulable'),
            'activo'     => $this->boolean('activo'),
        ]);
    }

    public function rules(): array
    {
        return [
            'nombre'      => ['required', 'string', 'max:120'],
            'descripcion' => ['nullable', 'string', 'max:255'],

            'tipo'  => ['required', Rule::in(TipoPromocion::valores())],
            'valor' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],

            'banner'        => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'quitar_banner' => ['boolean'],

            'canal' => ['required', Rule::in(CanalPromocion::valores())],

            // Los dos campos que deciden que pasa cuando dos promociones
            // alcanzan el mismo producto. Sin ellos el resultado depende del
            // orden en que el motor devuelva las filas, que no es reproducible
            // ante un reclamo del cliente.
            'prioridad'  => ['required', 'integer', 'min:0', 'max:9999'],
            'acumulable' => ['boolean'],

            'vigencia_desde' => ['required', 'date'],
            'vigencia_hasta' => ['required', 'date', 'after:vigencia_desde'],

            'activo' => ['boolean'],

            // La promocion es de productos: sin al menos uno no abarata nada.
            'productos'   => ['required', 'array', 'min:1'],
            'productos.*' => ['integer', Rule::exists('productos', 'id')],
        ];
    }

    /**
     * El significado de 'valor' depende de 'tipo', asi que el limite no puede
     * vivir en una regla sola.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $tipo  = $this->input('tipo');
            $valor = (float) $this->input('valor');

            if ($tipo === TipoPromocion::Porcentaje->value && $valor > 100) {
                $validator->errors()->add('valor', 'Un descuento por porcentaje no puede pasar de 100.');
            }
        });
    }

    /** Solo columnas de promociones. Los productos y el banner van aparte. */
    public function datosDeLaPromocion(): array
    {
        return $this->safe()->except(['productos', 'banner', 'quitar_banner']);
    }

    /** @return list<int> */
    public function productosSeleccionados(): array
    {
        return array_map('intval', $this->validated('productos'));
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'Poné un nombre a la promoción. Es el que va a ver el cliente.',
            'tipo.required'   => 'Elegí si el descuento es un porcentaje, un monto fijo o un precio especial.',
            'valor.required'  => 'Indicá el valor del descuento.',
            'valor.min'       => 'El valor tiene que ser mayor que cero.',
            'canal.required'  => 'Indicá dónde se aplica: mostrador, tienda o los dos.',
            'prioridad.required' => 'Indicá la prioridad. Decide cuál gana si dos promociones alcanzan el mismo producto.',
            'vigencia_desde.required' => 'Indicá desde cuándo rige.',
            'vigencia_hasta.required' => 'Indicá hasta cuándo rige.',
            'vigencia_hasta.after'    => 'El fin de la vigencia tiene que ser posterior al inicio.',
            'productos.required' => 'Elegí al menos un producto. Una promoción sin productos no descuenta nada.',
            'productos.min'      => 'Elegí al menos un producto.',
            'banner.image'       => 'El banner tiene que ser una imagen.',
            'banner.max'         => 'El banner no puede pesar más de 2 MB.',
        ];
    }
}
