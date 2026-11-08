<?php

namespace App\Http\Requests\Api;

use App\Enums\ModoEntrega;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Preferencias de entrega del checkout, desde la app.
 *
 * <b>Dos diferencias con CheckoutRequest, el del formulario web, y las dos
 * son a proposito.</b>
 *
 * Primera: no pide `confirma`. Esa casilla es el acto explicito con el que el
 * cliente acepta que el pago es previo al pedido, y en la web tiene que
 * viajar junto con el submit porque ese submit YA manda a pagar. Aca guardar
 * el modo de entrega no cobra nada: la conformidad corresponde al momento del
 * pago, que es el paso 10. Pedirla ahora la convertiria en un tramite vacio
 * y, peor, en una conformidad dada demasiado lejos del cobro.
 *
 * Segunda: los dos campos son opcionales. La pantalla de checkout de la app
 * guarda a medida que el cliente elige, no todo junto al final, asi que tiene
 * que poder mandar solo lo que cambio. El controlador aplica unicamente las
 * claves presentes.
 *
 * <b>Ningun importe viaja en el cuerpo</b>, igual que en el web: el total lo
 * calcula el backend sobre el carrito y el precio vigente. Si el importe
 * llegara del cliente, el cliente elegiria cuanto pagar.
 */
class CheckoutMovilRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            // Solo retiro, igual que en la web: el circuito de envio y
            // tracking no esta implementado, y aceptar 'envio' crearia
            // pedidos que despues nadie puede despachar. El campo existe
            // igual para que el contrato no cambie de forma cuando se
            // habiliten.
            'modo_entrega' => ['sometimes', 'required', Rule::in([ModoEntrega::Retiro->value])],

            // 500 es el largo de la columna.
            'observaciones' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'modo_entrega.in'      => 'Por ahora solo está disponible el retiro en el local.',
            'observaciones.max'    => 'Las observaciones no pueden superar los 500 caracteres.',
        ];
    }
}
