<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Deja registrado POR QUE una linea salio mas barata: reconstruye el precio
 * ante un reclamo y mide el rendimiento real de cada promocion.
 */
class PromocionAplicada extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'promociones_aplicadas';

    protected $fillable = ['venta_id', 'venta_linea_id', 'promocion_id', 'importe_descuento'];

    protected $casts = [
        'importe_descuento' => 'decimal:2',
        'created_at'        => 'datetime',
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function ventaLinea(): BelongsTo
    {
        return $this->belongsTo(VentaLinea::class, 'venta_linea_id');
    }

    public function promocion(): BelongsTo
    {
        return $this->belongsTo(Promocion::class, 'promocion_id');
    }
}
