<?php

namespace App\Http\Resources\Gestion;

use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Una fila del historial de compras de la ficha del cliente.
 *
 * <b>Son exactamente las cuatro columnas que mostraba la tabla</b> —número,
 * fecha, estado y total—, y nada más. No es el recurso de la venta: el módulo
 * de ventas del panel todavía no está migrado y cuando lo esté tendrá el suyo,
 * con las líneas, los pagos, el vendedor y los netos. Acá sólo viaja lo que la
 * ficha pinta, que es el criterio de CompraResource en la tienda: mandar el
 * detalle de cada venta en una página de diez compras es mover un orden de
 * magnitud más de JSON para dibujar un renglón.
 *
 * <b>Lo que NO sale de acá:</b> `cliente_id` —el historial es siempre el del
 * cliente que dice la URL—, el vendedor, los netos y el circuito de
 * presupuestos.
 *
 * El estado viaja con valor y etiqueta, igual que el resto de los enums de la
 * API: la etiqueta la define EstadoVenta, así que la ficha nombra cada estado
 * igual que el partial `gestion.partials.estado` que usaba antes.
 *
 * @mixin Venta
 */
class ClienteVentaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            // El correlativo interno y su forma impresa. Viaja formateado
            // porque armar el padding de numeroFormateado() en el JavaScript
            // sería repetir una decisión del dominio en la pantalla.
            'numero'            => $this->numero,
            'numero_formateado' => $this->numeroFormateado(),

            'fecha' => $this->fecha?->toIso8601String(),

            'estado'          => $this->estado?->value,
            'estado_etiqueta' => $this->estado?->etiqueta(),

            'total' => round((float) $this->total, 2),
        ];
    }
}
