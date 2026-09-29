<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Alta y edicion manual de una orden de compra.
 *
 * Lo que NO se acepta acá, y la ausencia es la decision:
 *
 *   numero            : lo asigna el numerador dentro de la transaccion.
 *   estado            : se mueve con transiciones, no con un select.
 *   origen            : lo fija el servicio. Una orden cargada desde este
 *                       formulario es 'manual' por definicion; dejar que el
 *                       formulario lo mande permitiria disfrazar una manual de
 *                       automatica y arruinaria la unica medicion que dice si
 *                       los umbrales estan bien calibrados.
 *   total_estimado    : se recalcula desde las lineas.
 *   usuario_aprobo_id / fecha_aprobacion : los escribe aprobar().
 *
 * <b>Todos los productos tienen que ser del proveedor de la orden.</b> Un
 * pedido es un documento dirigido a UNA empresa: si se cuela un producto de
 * otro proveedor, el remito le pide algo que no vende.
 */
class OrdenCompraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // OrdenCompraPolicy
    }

    public function rules(): array
    {
        return [
            // En la edicion el proveedor no cambia: cambiar de destinatario es
            // otro pedido, no una correccion de este.
            'proveedor_id' => [
                $this->esAlta() ? 'required' : 'prohibited',
                'integer',
                Rule::exists('proveedores', 'id')->where('activo', true),
            ],

            'observaciones' => ['nullable', 'string', 'max:65535'],

            'lineas'                   => ['required', 'array', 'min:1'],
            'lineas.*.producto_id'     => ['required', 'integer', 'distinct', Rule::exists('productos', 'id')],
            'lineas.*.cantidad_pedida' => ['required', 'integer', 'min:1', 'max:999999'],

            // Opcional: si no lo escriben, el servicio toma el costo del
            // catalogo. Es una estimacion en los dos casos.
            'lineas.*.costo_unitario' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
        ];
    }

    /**
     * La regla que cruza dos campos y por eso no entra en rules(): todos los
     * productos del pedido tienen que ser del proveedor al que se le emite.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $proveedorId = $this->esAlta()
                ? (int) $this->input('proveedor_id')
                : (int) $this->route('orden')?->proveedor_id;

            if ($proveedorId === 0) {
                return;
            }

            $ajenos = \App\Models\Producto::query()
                ->whereIn('id', collect($this->input('lineas', []))->pluck('producto_id')->filter()->all())
                ->where(fn ($q) => $q->where('proveedor_id', '!=', $proveedorId)->orWhereNull('proveedor_id'))
                ->pluck('nombre');

            if ($ajenos->isNotEmpty()) {
                $validator->errors()->add(
                    'lineas',
                    'Estos productos no son de este proveedor: '.$ajenos->implode(', ')
                    .'. Un pedido va dirigido a una sola empresa.'
                );
            }
        });
    }

    /** @return list<array{producto_id:int, cantidad_pedida:int, costo_unitario:float|null}> */
    public function lineas(): array
    {
        return collect($this->validated('lineas'))
            ->map(fn (array $linea) => [
                'producto_id'     => (int) $linea['producto_id'],
                'cantidad_pedida' => (int) $linea['cantidad_pedida'],
                'costo_unitario'  => $linea['costo_unitario'] ?? null,
            ])
            ->values()
            ->all();
    }

    private function esAlta(): bool
    {
        return $this->route('orden') === null;
    }

    public function messages(): array
    {
        return [
            'proveedor_id.required'   => 'Elegí a qué proveedor se le emite el pedido.',
            'proveedor_id.prohibited' => 'El proveedor de una orden no se cambia: cancelala y emití otra.',
            'lineas.required'         => 'El pedido necesita al menos un producto.',
            'lineas.min'              => 'El pedido necesita al menos un producto.',
            'lineas.*.producto_id.distinct'   => 'Hay un producto repetido: sumá las cantidades en un solo renglón.',
            'lineas.*.cantidad_pedida.min'    => 'La cantidad a pedir tiene que ser al menos 1.',
            'lineas.*.cantidad_pedida.required' => 'Indicá cuántas unidades pedir.',
        ];
    }
}
