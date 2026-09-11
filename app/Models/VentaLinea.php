<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * H.2 venta_lineas. Unidad sobre la que se calcula el compromiso de stock:
 * cada linea genera su movimiento de reserva al confirmarse y de entrega al
 * salir la mercaderia.
 *
 * Se congelan descripcion, precio y alicuota. Si manana el producto se
 * renombra o cambia su alicuota, el documento emitido debe seguir mostrando
 * lo que se emitio: un comprobante es inmutable y su detalle no puede
 * depender de un join contra un catalogo vivo.
 */
class VentaLinea extends Model
{
    protected $table = 'venta_lineas';

    public $timestamps = false;

    protected $fillable = [
        'venta_id', 'producto_id', 'descripcion', 'cantidad', 'cantidad_devuelta',
        'precio_unitario', 'descuento_promocion', 'alicuota_iva',
        'costo_unitario', 'neto', 'iva', 'total',
    ];

    protected function casts(): array
    {
        return [
            'cantidad'            => 'integer',
            'cantidad_devuelta'   => 'integer',
            'precio_unitario'     => 'decimal:2',
            'descuento_promocion' => 'decimal:2',
            'alicuota_iva'        => 'decimal:2',
            'costo_unitario'      => 'decimal:2',
            'neto'                => 'decimal:2',
            'iva'                 => 'decimal:2',
            'total'               => 'decimal:2',
        ];
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function promocionesAplicadas(): HasMany
    {
        return $this->hasMany(PromocionAplicada::class, 'venta_linea_id');
    }

    /** Acota cuantas unidades puede reingresar al fisico una devolucion. */
    public function cantidadDevolvible(): int
    {
        return $this->cantidad - $this->cantidad_devuelta;
    }

    /** Margen real de la venta, con el costo congelado al momento de vender. */
    public function margen(): ?float
    {
        if ($this->costo_unitario === null) {
            return null;
        }

        return (float) $this->neto - ((float) $this->costo_unitario * $this->cantidad);
    }
}
