<?php

namespace App\Models;

use App\Models\Concerns\ProtegeContraBorrado;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * No reutiliza venta_lineas: una nota de credito parcial tiene lineas propias
 * que no coinciden con las de la venta, y el comprobante queda congelado con
 * exactamente los importes que se informaron al organismo.
 */
class ComprobanteLinea extends Model
{
    use ProtegeContraBorrado;

    protected $table = 'comprobante_lineas';

    protected $fillable = [
        'comprobante_id', 'producto_id', 'descripcion', 'cantidad',
        'precio_unitario', 'alicuota_iva', 'neto', 'iva', 'total',
    ];

    protected $casts = [
        'cantidad'        => 'integer',
        'precio_unitario' => 'decimal:2',
        'alicuota_iva'    => 'decimal:2',
        'neto'            => 'decimal:2',
        'iva'             => 'decimal:2',
        'total'           => 'decimal:2',
    ];

    public function comprobante(): BelongsTo
    {
        return $this->belongsTo(Comprobante::class, 'comprobante_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    protected function mensajeNoEliminable(): string
    {
        return 'El detalle de un comprobante fiscal no se borra.';
    }
}
