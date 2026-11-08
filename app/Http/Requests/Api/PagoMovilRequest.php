<?php

namespace App\Http\Requests\Api;

use App\Enums\ModoEntrega;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Inicio del pago desde la app.
 *
 * <b>Acá sí se pide `confirma`, y en el PATCH del paso 8 no.</b> Es la misma
 * casilla del formulario web: el acto explícito con el que el cliente acepta
 * que el pago es previo al pedido y que no hay vuelta atrás sin nota de
 * crédito. Corresponde al momento del cobro, no al de elegir cómo retirar,
 * que es lo que guarda el PATCH.
 *
 * <b>`modo_entrega` y `observaciones` son opcionales porque ya pudieron
 * guardarse.</b> La pantalla del checkout los persiste a medida que el
 * cliente elige; si vuelven acá, se reaplican —igual que hace el formulario
 * web, que manda todo junto en el submit— y si no vienen, vale lo que esté
 * guardado en el carrito. Lo que no se admite es pagar sin ningún modo de
 * entrega, y eso lo verifica el controlador contra el carrito, no acá: el
 * dato puede estar guardado aunque no venga en el cuerpo.
 *
 * <b>Ningún importe viaja.</b> Ni total, ni precios, ni descuentos. El monto
 * que se le cobra al cliente lo calcula el backend sobre el carrito y el
 * precio vigente, y después `CheckoutService::exigirImporteExacto()` lo
 * compara contra lo que la pasarela acreditó. Si el importe llegara del
 * cliente, el cliente elegiría cuánto pagar.
 */
class PagoMovilRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'confirma' => ['accepted'],

            'modo_entrega' => ['sometimes', 'required', Rule::in([ModoEntrega::Retiro->value])],

            'observaciones' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'confirma.accepted'  => 'Tenés que confirmar el pedido para continuar.',
            'modo_entrega.in'    => 'Por ahora solo está disponible el retiro en el local.',
            'observaciones.max'  => 'Las observaciones no pueden superar los 500 caracteres.',
        ];
    }
}
