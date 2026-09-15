<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** cantidad_recibida habilita recepciones parciales sin tabla de recepciones. */
class OrdenCompraLinea extends Model
{
    protected $table = 'orden_compra_lineas';

    protected $fillable = [
        'orden_compra_id', 'producto_id', 'cantidad_pedida',
        'cantidad_recibida', 'costo_unitario',
    ];

    protected $casts = [
        'cantidad_pedida'   => 'integer',
        'cantidad_recibida' => 'integer',
        'costo_unitario'    => 'decimal:2',
    ];

    public function ordenCompra(): BelongsTo
    {
        return $this->belongsTo(OrdenCompra::class, 'orden_compra_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function pendiente(): int
    {
        return max(0, $this->cantidad_pedida - $this->cantidad_recibida);
    }
}
