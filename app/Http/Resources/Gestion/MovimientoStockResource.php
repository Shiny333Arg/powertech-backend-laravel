<?php

namespace App\Http\Resources\Gestion;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un renglon del kardex.
 *
 * <b>Solo existe para gestion: la tienda no tiene kardex.</b> Un movimiento
 * cuenta cuanto habia y cuanto quedo, y quien lo hizo; nada de eso tiene
 * sentido —ni deberia viajar— del lado del cliente.
 *
 * Cada fila trae los dos deltas y los dos saldos resultantes porque el kardex
 * se lee como un extracto bancario: la columna de saldo es la que permite
 * reconstruir la historia sin volver a sumar, y es tambien la que delata una
 * inconsistencia si alguna vez aparece.
 *
 * `cantidad_comprometido` es el otro saldo, el de las unidades reservadas por
 * ventas confirmadas y todavia no entregadas. Una reserva no cambia el fisico
 * —la mercaderia sigue en el deposito— pero si lo disponible, y por eso viajan
 * los dos.
 *
 * @mixin \App\Models\MovimientoStock
 */
class MovimientoStockResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'   => $this->id,
            'tipo' => $this->tipo->value,

            // La etiqueta la define el enum y es la que ya muestra el panel:
            // asi las dos interfaces nombran igual cada movimiento.
            'tipo_etiqueta' => $this->tipo->etiqueta(),

            'cantidad_fisico'       => $this->cantidad_fisico,
            'cantidad_comprometido' => $this->cantidad_comprometido,

            'stock_fisico_resultante'       => $this->stock_fisico_resultante,
            'stock_comprometido_resultante' => $this->stock_comprometido_resultante,

            // El motivo es obligatorio en el ajuste manual —el unico movimiento
            // sin documento que lo explique— y en la recepcion lleva el remito.
            'motivo' => $this->motivo,

            // De donde salio el movimiento. null en el ajuste manual: ahi la
            // explicacion es el motivo. En los demas apunta a la venta, la
            // orden de compra o el comprobante que lo origino.
            'origen' => $this->origen_type ? [
                'tipo' => class_basename($this->origen_type),
                'id'   => $this->origen_id,
            ] : null,

            // Viaja solo en el kardex global, donde cada fila puede ser de un
            // producto distinto. En el kardex de un producto seria repetir en
            // cada renglon algo que ya dice la cabecera.
            'producto' => $this->whenLoaded('producto', fn () => [
                'id'     => $this->producto->id,
                'codigo' => $this->producto->codigo,
                'nombre' => $this->producto->nombre,
            ]),

            // Quien lo registro. null en los movimientos que dispara el
            // sistema, como la reposicion automatica.
            'usuario' => $this->whenLoaded('usuario', fn () => $this->usuario ? [
                'id'     => $this->usuario->id,
                'nombre' => $this->usuario->nombre_completo,
            ] : null),

            'registrado_el' => $this->created_at?->toIso8601String(),
        ];
    }
}
