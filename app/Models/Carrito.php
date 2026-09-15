<?php

namespace App\Models;

use App\Enums\EstadoCarrito;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * El carrito NO es una venta: no consume numeracion, no descuenta stock, no
 * genera comprobante y se abandona la mayoria de las veces.
 *
 * Y NO compromete stock: se admite preventa, asi que reservar no protege de
 * nada, y reservar en el carrito generaria comprometido fantasma por abandono.
 * El compromiso nace con la venta confirmada.
 */
class Carrito extends Model
{
    protected $table = 'carritos';

    protected $fillable = ['user_id', 'session_token', 'estado'];

    protected $casts = ['estado' => EstadoCarrito::class];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function lineas(): HasMany
    {
        return $this->hasMany(CarritoLinea::class, 'carrito_id');
    }

    public function scopeActivo(Builder $query): Builder
    {
        return $query->where('estado', EstadoCarrito::Activo->value);
    }
}
