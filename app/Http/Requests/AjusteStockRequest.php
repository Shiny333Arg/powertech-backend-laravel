<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Ajuste de inventario: diferencias de conteo, roturas, mermas.
 *
 * La cantidad va CON SIGNO porque un ajuste resta tanto como suma, y el motivo
 * es obligatorio por requerimiento: es el unico movimiento que no tiene un
 * documento de origen que lo explique.
 */
class AjusteStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ProductoPolicy::ajustarStock
    }

    public function rules(): array
    {
        return [
            'cantidad' => ['required', 'integer', 'not_in:0', 'min:-999999', 'max:999999'],
            'motivo'   => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'cantidad.required' => 'Indicá cuántas unidades ajustar.',
            'cantidad.not_in'   => 'Un ajuste de cero unidades no registra nada.',
            'cantidad.integer'  => 'La cantidad tiene que ser un número entero, positivo o negativo.',
            'motivo.required'   => 'Escribí el motivo del ajuste. Queda asentado en el historial de stock.',
        ];
    }
}
