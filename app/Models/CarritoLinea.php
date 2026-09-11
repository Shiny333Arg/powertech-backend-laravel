<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * G.2 carrito_lineas. El indice unico (carrito_id, producto_id) evita la
 * linea duplicada: agregar dos veces suma cantidad en lugar de crear otra
 * fila.
 *
 * NO guarda precio a proposito: el carrito debe mostrar siempre el precio
 * vigente, incluidas las promociones activas al momento de mirarlo.
 */
class CarritoLinea extends Model
{
    protected $table = 'carrito_lineas';

    public $timestamps = false;

    protected $fillable = ['carrito_id', 'producto_id', 'cantidad'];

    protected function casts(): array
    {
        return ['cantidad' => 'integer'];
    }

    public function carrito(): BelongsTo
    {
        return $this->belongsTo(Carrito::class, 'carrito_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
