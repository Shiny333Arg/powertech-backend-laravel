<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Descripcion, precio y alicuota quedan CONGELADOS al emitir: si manana el
 * producto se renombra o cambia su alicuota, el documento debe seguir
 * mostrando lo que se emitio.
 */
class VentaLinea extends Model
{
    protected $table = 'venta_lineas';

    protected $fillable = [
        'venta_id', 'producto_id', 'descripcion', 'cantidad', 'cantidad_devuelta',
        'precio_unitario', 'descuento_promocion', 'alicuota_iva', 'costo_unitario',
        'neto', 'iva', 'total',
    ];

    protected $casts = [
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

    /** Cuantas unidades de esta linea todavia pueden devolverse. */
    public function pendienteDeDevolucion(): int
    {
        return max(0, $this->cantidad - $this->cantidad_devuelta);
    }
}
