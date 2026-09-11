<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * F.3 promociones_aplicadas. Deja registrado POR QUE una linea salio mas
 * barata. Sirve para dos cosas concretas: reconstruir el precio ante un
 * reclamo, y medir el rendimiento real de cada promocion. Sin esta tabla el
 * descuento es un numero anonimo dentro del total.
 */
class PromocionAplicada extends Model
{
    // Sin esto Laravel buscaria `promocion_aplicadas`.
    protected $table = 'promociones_aplicadas';

    public const UPDATED_AT = null;

    protected $fillable = [
        'venta_id', 'venta_linea_id', 'promocion_id', 'importe_descuento',
    ];

    protected function casts(): array
    {
        return [
            'importe_descuento' => 'decimal:2',
            'created_at'        => 'datetime',
        ];
    }

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
