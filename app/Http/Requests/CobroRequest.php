<?php

namespace App\Http\Requests;

use App\Enums\MetodoPago;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Cobro de mostrador. Admite pago mixto: parte efectivo, parte tarjeta.
 *
 * No valida que la suma cubra el total: un pago parcial es valido y deja la
 * venta confirmada. Lo que si se rechaza —en el servicio, que es donde se
 * conoce el saldo— es cobrar de mas: el vuelto no es un pago.
 */
class CobroRequest extends FormRequest
{
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
            'pagos'            => ['required', 'array', 'min:1', 'max:5'],
            'pagos.*.metodo'   => ['required', Rule::enum(MetodoPago::class)],
            'pagos.*.monto'    => ['required', 'numeric', 'min:0.01', 'max:99999999'],
            'pagos.*.cuotas'   => ['nullable', 'integer', 'min:1', 'max:24'],
            'entregar_inmediato' => ['boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ((array) $this->input('pagos', []) as $i => $pago) {
                if (($pago['metodo'] ?? null) !== MetodoPago::Tarjeta->value && filled($pago['cuotas'] ?? null)) {
                    $validator->errors()->add(
                        "pagos.{$i}.cuotas",
                        'Las cuotas solo aplican a un pago con tarjeta.'
                    );
                }
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['entregar_inmediato' => $this->boolean('entregar_inmediato')]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'pagos.*.metodo' => 'medio de pago',
            'pagos.*.monto'  => 'importe',
            'pagos.*.cuotas' => 'cuotas',
        ];
    }
}
