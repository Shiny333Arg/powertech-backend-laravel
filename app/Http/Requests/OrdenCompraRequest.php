<?php

namespace App\Http\Requests;

use App\Models\Producto;
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
 *   proveedor_id      : ya no se elige. Sale del detalle: si todos los
 *                       renglones son del mismo proveedor la orden queda
 *                       dirigida a el, y si hay varios el pedido se reparte al
 *                       aprobarse. Aceptarlo del formulario permitiria emitirle
 *                       a una empresa un pedido de productos que no vende.
 *   costo_unitario    : sale del catalogo. Se muestra para que el
 *                       administrativo vea cuanto pesa el pedido, pero el dato
 *                       tiene un solo dueño, que es la ficha del producto.
 *   total_estimado    : se recalcula desde las lineas.
 *   usuario_aprobo_id / fecha_aprobacion : los escribe aprobar().
 *
 * <b>Lo que si se exige de cada producto: que tenga a quien pedirselo.</b> Es
 * lo que reemplaza a la vieja regla de «todos del mismo proveedor». Aquella
 * existia porque el documento era uno solo; ahora el documento se reparte, y
 * lo unico que no tiene arreglo es un renglon que no es de nadie.
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
            'observaciones' => ['nullable', 'string', 'max:65535'],

            'lineas'                   => ['required', 'array', 'min:1'],
            'lineas.*.producto_id'     => [
                'required', 'integer', 'distinct',
                Rule::exists('productos', 'id')->where('activo', true),
            ],
            'lineas.*.cantidad_pedida' => ['required', 'integer', 'min:1', 'max:999999'],
        ];
    }

    /**
     * La regla que necesita ir a la base y por eso no entra en rules(): cada
     * producto del pedido tiene que tener un proveedor activo al que pedirselo.
     *
     * Son dos mensajes distintos porque son dos arreglos distintos: uno se
     * resuelve en la ficha del producto y el otro en la del proveedor.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $ids = collect($this->input('lineas', []))
                ->pluck('producto_id')
                ->filter()
                ->all();

            if ($ids === []) {
                return;
            }

            $productos = Producto::with('proveedor:id,razon_social,activo')->findMany($ids);

            $huerfanos = $productos
                ->filter(fn (Producto $p) => $p->proveedor_id === null)
                ->pluck('nombre');

            if ($huerfanos->isNotEmpty()) {
                $validator->errors()->add(
                    'lineas',
                    'Estos productos no tienen proveedor asignado y no hay a quién pedírselos: '
                    .$huerfanos->implode(', ')
                    .'. Asignáselo desde la ficha de cada producto.'
                );
            }

            $inactivos = $productos
                ->filter(fn (Producto $p) => $p->proveedor !== null && ! $p->proveedor->activo)
                ->map(fn (Producto $p) => "{$p->nombre} ({$p->proveedor->razon_social})");

            if ($inactivos->isNotEmpty()) {
                $validator->errors()->add(
                    'lineas',
                    'Estos renglones son de un proveedor dado de baja: '
                    .$inactivos->implode(', ')
                    .'. Reactivalo o cambiale el proveedor al producto.'
                );
            }
        });
    }

    /** @return list<array{producto_id:int, cantidad_pedida:int}> */
    public function lineas(): array
    {
        return collect($this->validated('lineas'))
            ->map(fn (array $linea) => [
                'producto_id'     => (int) $linea['producto_id'],
                'cantidad_pedida' => (int) $linea['cantidad_pedida'],
            ])
            ->values()
            ->all();
    }

    public function messages(): array
    {
        return [
            'lineas.required' => 'El pedido necesita al menos un producto.',
            'lineas.min'      => 'El pedido necesita al menos un producto.',

            'lineas.*.producto_id.required' => 'Hay un renglón sin producto.',
            'lineas.*.producto_id.distinct' => 'Hay un producto repetido: sumá las cantidades en un solo renglón.',
            'lineas.*.producto_id.exists'   => 'Hay un renglón con un producto que ya no está activo.',

            'lineas.*.cantidad_pedida.required' => 'Indicá cuántas unidades pedir.',
            'lineas.*.cantidad_pedida.min'      => 'La cantidad a pedir tiene que ser al menos 1.',
        ];
    }
}
