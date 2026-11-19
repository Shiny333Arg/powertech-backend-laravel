<?php

namespace App\Http\Requests;

use App\Models\Producto;
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
 *
 * <b>Sirve a los dos clientes.</b> El formulario del panel manda el producto en
 * el cuerpo; la API lo lleva en la ruta
 * (`POST /api/v1/gestion/productos/{producto}/stock/ajustes`). Lo unifica
 * `prepareForValidation()`, de modo que la validacion, los mensajes y el
 * contrato con `StockService` sean uno solo para las dos pantallas.
 */
class AjusteStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ProductoPolicy::ajustarStock
    }

    /**
     * Cuando el producto viene en la ruta, manda la ruta.
     *
     * Dos consecuencias buscadas:
     *
     * 1. En la API el cuerpo no necesita `producto_id`, y si lo manda igual
     *    —por copiar el ejemplo del panel, o a proposito— <b>se pisa</b>: no
     *    hay forma de ajustar el stock de un producto distinto del que dice la
     *    URL. Es la misma precaucion que ya toma el kardex de un producto al
     *    no aceptar `producto_id` por query string.
     * 2. El formulario web no tiene parametro `producto` en su ruta, asi que
     *    no entra por aca y sigue exactamente igual que antes.
     *
     * El binding ya resolvio el modelo (404 si no existe), de modo que a la
     * regla `exists` le llega siempre un id real; la regla se conserva porque
     * es la que cubre el camino del formulario.
     */
    protected function prepareForValidation(): void
    {
        $producto = $this->route('producto');

        if ($producto instanceof Producto) {
            $this->merge(['producto_id' => $producto->getKey()]);
        }
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
