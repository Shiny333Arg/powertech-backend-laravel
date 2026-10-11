<?php

namespace App\Http\Requests;

use App\Enums\ModoEntrega;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Alta y edicion del detalle de una venta de mostrador.
 *
 * No valida precios ni descuentos: los toma el servidor del catalogo y del
 * motor de promociones. Un precio que llega del formulario es un precio que
 * el cliente puede editar.
 *
 * Tampoco valida disponibilidad de stock: la preventa esta admitida, asi que
 * cantidad > stock_disponible no es un error sino un aviso en pantalla.
 */
class VentaRequest extends FormRequest
{
    /** La autorizacion la resuelve VentaPolicy desde el controlador. */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'cliente_id' => [
                'nullable', 'integer',
                Rule::exists('clientes', 'id')->where('activo', true),
            ],
            'modo_entrega'  => ['required', Rule::enum(ModoEntrega::class)],
            'confirmar'     => ['boolean'],
            'validez_hasta' => [
                'nullable', 'date', 'after_or_equal:today',
                Rule::requiredIf(fn () => ! $this->boolean('confirmar')),
            ],
            'costo_envio'   => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'observaciones' => ['nullable', 'string', 'max:2000'],

            'lineas'                => ['required', 'array', 'min:1', 'max:200'],
            'lineas.*.producto_id'  => [
                'required', 'integer', 'distinct',
                Rule::exists('productos', 'id')->where('activo', true),
            ],
            'lineas.*.cantidad'     => ['required', 'integer', 'min:1', 'max:9999'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $modo = $this->input('modo_entrega');

            // Sin cliente identificado no hay a quien ni a donde enviar.
            if ($modo === ModoEntrega::Envio->value && ! $this->filled('cliente_id')) {
                $validator->errors()->add(
                    'cliente_id',
                    'Una venta con envío a domicilio necesita un cliente identificado.'
                );
            }

            if ($modo === ModoEntrega::Retiro->value && (float) $this->input('costo_envio', 0) > 0) {
                $validator->errors()->add(
                    'costo_envio',
                    'Una venta con retiro en local no lleva costo de envío.'
                );
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'confirmar'   => $this->boolean('confirmar'),
            'costo_envio' => $this->input('costo_envio') === '' ? null : $this->input('costo_envio'),
            'lineas'      => array_values(array_filter(
                (array) $this->input('lineas', []),
                fn ($linea) => is_array($linea) && filled($linea['producto_id'] ?? null)
            )),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'cliente_id'           => 'cliente',
            'modo_entrega'         => 'modo de entrega',
            'validez_hasta'        => 'validez del presupuesto',
            'costo_envio'          => 'costo de envío',
            'lineas'               => 'detalle',
            'lineas.*.producto_id' => 'producto',
            'lineas.*.cantidad'    => 'cantidad',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'lineas.required'               => 'La venta tiene que tener al menos un producto.',
            'lineas.*.producto_id.distinct' => 'El producto está cargado dos veces: sumá las cantidades en una sola línea.',
            'lineas.*.producto_id.exists'   => 'El producto no existe o está dado de baja.',
            'validez_hasta.required'        => 'Un presupuesto necesita una fecha de validez.',
        ];
    }
}
