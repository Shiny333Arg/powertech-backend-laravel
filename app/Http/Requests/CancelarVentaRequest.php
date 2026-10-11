<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Cancelar es la unica "baja" que admite una venta, y deja rastro: el motivo
 * se asienta en las observaciones junto con la fecha.
 */
class CancelarVentaRequest extends FormRequest
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
            'motivo' => ['required', 'string', 'min:5', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'motivo.required' => 'Indicá por qué se cancela la venta.',
            'motivo.min'      => 'El motivo tiene que explicar la cancelación.',
        ];
    }
}
