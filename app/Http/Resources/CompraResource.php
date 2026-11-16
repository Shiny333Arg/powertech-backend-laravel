<?php

namespace App\Http\Resources;

use App\Support\CompraDelCliente;
use App\Support\EstadoCompraCliente;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Una compra en el historial. Equivale a una tarjeta de
 * resources/views/compras/index.blade.php.
 *
 * <b>No trae las lineas.</b> La tarjeta del historial muestra el numero, la
 * fecha, el estado, el modo de entrega, el total y cuantos productos son:
 * nada mas. Mandar el detalle de cada venta en una pagina de diez compras es
 * mover un orden de magnitud mas de JSON para pintar un contador, y el
 * detalle completo esta a un toque de distancia en /compras/{id}.
 *
 * <b>Depende de dos agregados que pone el controlador.</b> `lineas_count`
 * (withCount) y `monto_acreditado` (withSum sobre los pagos acreditados). Los
 * dos se resuelven en la misma consulta de la pagina: calcularlos aca, uno por
 * fila, seria un N+1 de libro.
 *
 * <b>Lo que NO sale de aca:</b> `cliente_id`, `usuario_id` —el vendedor—,
 * `usuario_valido_id`, `validez_hasta` y los netos. El historial es siempre el
 * de quien pregunta, asi que a quien pertenece la venta es informacion que la
 * app ya tiene; el resto es informacion de la empresa o del circuito de
 * presupuestos, que no llega a esta seccion.
 *
 * @mixin \App\Models\Venta
 */
class CompraResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            // El correlativo interno y su forma impresa. Viaja formateado
            // porque es el numero que el cliente dicta en el local, y
            // armarlo en Flutter seria repetir el padding de numeroFormateado().
            'numero'            => $this->numero,
            'numero_formateado' => $this->numeroFormateado(),

            'fecha' => $this->fecha?->toIso8601String(),

            // Una compra en el mostrador tambien es una compra del cliente y
            // aparece en el historial con su etiqueta.
            'canal' => [
                'valor'    => $this->canal->value,
                'etiqueta' => $this->canal->etiqueta(),
            ],

            // Con `tono` para que la app diferencie los estados de un vistazo
            // sin mapear cadenas. Ver EstadoCompraCliente.
            'estado' => EstadoCompraCliente::paraJson($this->estado),

            'modo_entrega' => [
                'valor'    => $this->modo_entrega->value,
                'etiqueta' => $this->modo_entrega->etiqueta(),
            ],

            'total' => round((float) $this->total, 2),

            // Lineas, no unidades: es el contador que muestra la tarjeta
            // ("3 productos"), igual que lineas_count en la vista.
            'cantidad_lineas' => (int) ($this->lineas_count ?? 0),

            // El estado del cobro, que no es el estado de la venta: una venta
            // de mostrador puede estar confirmada y cobrada a medias.
            'pago' => CompraDelCliente::resumenDePago(
                (float) $this->total,
                (float) ($this->monto_acreditado ?? 0),
            ),
        ];
    }
}
