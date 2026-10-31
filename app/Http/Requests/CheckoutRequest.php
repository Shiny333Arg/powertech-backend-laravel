<?php

namespace App\Http\Requests;

use App\Enums\ModoEntrega;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Confirmacion del checkout.
 *
 * NO valida importes ni cantidades: el total lo calcula VentaService sobre el
 * carrito y el precio vigente, nunca sobre lo que llega del formulario. Si el
 * importe viajara en el body, el cliente podria elegir cuanto pagar.
 *
 * `modo_entrega` se acepta como campo aunque hoy tenga un solo valor posible,
 * para que el formulario no cambie de forma cuando se habiliten los envios.
 */
class CheckoutRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // Solo retiro por ahora: el circuito de envio y tracking no esta
            // implementado, y aceptar 'envio' crearia ventas que despues
            // nadie puede despachar.
            'modo_entrega' => ['required', Rule::in([ModoEntrega::Retiro->value])],

            'observaciones' => ['nullable', 'string', 'max:500'],

            // Casilla de conformidad. El pago es previo al pedido y no hay
            // vuelta atras sin nota de credito: tiene que ser un acto
            // explicito.
            'confirma' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'modo_entrega.in' => 'Por ahora solo está disponible el retiro en el local.',
            'confirma.accepted' => 'Tenés que confirmar el pedido para continuar.',
        ];
    }

    public function modoEntrega(): ModoEntrega
    {
        return ModoEntrega::from($this->string('modo_entrega')->value());
    }
}
