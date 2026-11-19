<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Ajuste de inventario: diferencias de conteo, roturas, mermas.
 *
 * La cantidad va CON SIGNO porque un ajuste resta tanto como suma, y el motivo
 * es obligatorio por requerimiento: es el unico movimiento que no tiene un
 * documento de origen que lo explique.
 *
 * <b>`producto_id` faltaba y era un 500.</b> El formulario lo manda como campo
 * oculto y `StockController::ajustar()` lo lee de `validated()`, pero no estaba
 * entre las reglas: `validated()` solo devuelve lo validado, asi que la clave
 * no existia y cualquier ajuste moria con «Undefined array key». Validarlo acá
 * —y no leerlo crudo del request en el controlador— es ademas lo que garantiza
 * que el id exista antes de llegar al servicio.
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
            // Sin filtrar por activo, a proposito: un producto dado de baja
            // sigue teniendo existencias que pueden tener que corregirse.
            'producto_id' => ['required', 'integer', Rule::exists('productos', 'id')],
            'cantidad'    => ['required', 'integer', 'not_in:0', 'min:-999999', 'max:999999'],
            'motivo'      => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'producto_id.required' => 'No llegó el producto a ajustar.',
            'producto_id.exists'   => 'El producto que se quiere ajustar no existe.',
            'cantidad.required' => 'Indicá cuántas unidades ajustar.',
            'cantidad.not_in'   => 'Un ajuste de cero unidades no registra nada.',
            'cantidad.integer'  => 'La cantidad tiene que ser un número entero, positivo o negativo.',
            'motivo.required'   => 'Escribí el motivo del ajuste. Queda asentado en el historial de stock.',
        ];
    }
}
