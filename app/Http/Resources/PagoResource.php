<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un pago del pedido, visto por el cliente.
 *
 * Son varias filas y no una a proposito: `pagos` admite pago mixto y pagos
 * parciales, asi que un pedido puede tener una transferencia acreditada y un
 * saldo en efectivo pendiente. La app los lista; el resumen de cuanto falta
 * lo arma CompraDelCliente::resumenDePago().
 *
 * <b>Lo que NO sale de aca:</b> `comision`, `neto_acreditado`, `usuario_id`
 * —el cajero— y los identificadores de Mercado Pago (`mp_payment_id`,
 * `mp_preference_id`, `mp_merchant_order_id`, `mp_status`, `mp_status_detail`).
 * La comision y el neto acreditado son lo que la pasarela le cobra a la
 * empresa: el cliente pago el monto, y cuanto de eso llego a la cuenta no es
 * informacion suya. Los ids de MP son estado interno de la conciliacion y del
 * webhook.
 *
 * `estado` viaja con valor y etiqueta como el resto de los enums: la app
 * compara por valor y muestra el texto.
 *
 * @mixin \App\Models\Pago
 */
class PagoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'metodo' => [
                'valor'    => $this->metodo->value,
                'etiqueta' => $this->metodo->etiqueta(),
            ],

            'estado' => [
                'valor'    => $this->estado->value,
                'etiqueta' => $this->estado->etiqueta(),
            ],

            'monto' => round((float) $this->monto, 2),

            // Null salvo en tarjeta. La app solo lo muestra cuando viene.
            'cuotas' => $this->cuotas,

            'fecha' => $this->fecha?->toIso8601String(),
        ];
    }
}
