<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Devolucion total o parcial. Llega un mapa venta_linea_id => cantidad.
 *
 * El tope por linea (`pendienteDeDevolucion()`) se verifica en el servicio,
 * dentro de la transaccion: comprobarlo aca dejaria una ventana entre la
 * validacion y la emision de la nota.
 */
class DevolucionRequest extends FormRequest
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
            'motivo'      => ['required', 'string', 'min:5', 'max:255'],
            'cantidades'  => ['required', 'array', 'min:1'],
            'cantidades.*' => ['integer', 'min:0', 'max:9999'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $total = array_sum(array_map('intval', (array) $this->input('cantidades', [])));

            if ($total <= 0) {
                $validator->errors()->add('cantidades', 'Indicá al menos una unidad a devolver.');
            }
        });
    }

    /**
     * @return array<int, int>
     */
    public function cantidades(): array
    {
        return array_map('intval', $this->validated()['cantidades']);
    }
}
