<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * I.2 comprobante_lineas. No se reutiliza venta_lineas porque una nota de
 * credito parcial tiene lineas propias que no coinciden con las de la venta,
 * y porque el comprobante debe quedar congelado con exactamente los importes
 * que se informaron al organismo.
 */
class ComprobanteLinea extends Model
{
    protected $table = 'comprobante_lineas';

    public $timestamps = false;

    protected $fillable = [
        'comprobante_id', 'producto_id', 'descripcion', 'cantidad',
        'precio_unitario', 'alicuota_iva', 'neto', 'iva', 'total',
    ];

    protected function casts(): array
    {
        return [
            'cantidad'        => 'integer',
            'precio_unitario' => 'decimal:2',
            'alicuota_iva'    => 'decimal:2',
            'neto'            => 'decimal:2',
            'iva'             => 'decimal:2',
            'total'           => 'decimal:2',
        ];
    }

    public function comprobante(): BelongsTo
    {
        return $this->belongsTo(Comprobante::class, 'comprobante_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
