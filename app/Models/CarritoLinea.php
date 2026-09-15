<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * No guarda precio: el carrito muestra siempre el precio vigente, con las
 * promociones activas al momento de mirarlo. El precio se congela recien
 * al convertirse en venta.
 */
class CarritoLinea extends Model
{
    protected $table = 'carrito_lineas';

    protected $fillable = ['carrito_id', 'producto_id', 'cantidad'];

    protected $casts = ['cantidad' => 'integer'];

    public function carrito(): BelongsTo
    {
        return $this->belongsTo(Carrito::class, 'carrito_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
