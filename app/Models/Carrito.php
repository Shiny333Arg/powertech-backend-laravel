<?php

namespace App\Models;

use App\Enums\EstadoCarrito;
use App\Enums\ModoEntrega;
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
 *
 * <b>El carrito es tambien el checkout en curso.</b> mp_preference_id,
 * modo_entrega y observaciones se guardan aca y no en la sesion porque el
 * webhook de Mercado Pago llega sin sesion: es el unico camino que esta
 * garantizado, y necesita esos datos para crear la venta.
 */
class Carrito extends Model
{
    protected $table = 'carritos';

    protected $fillable = [
        'user_id', 'session_token', 'estado',
        'mp_preference_id', 'modo_entrega', 'observaciones',
    ];

    protected $casts = [
        'estado'       => EstadoCarrito::class,
        'modo_entrega' => ModoEntrega::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function lineas(): HasMany
    {
        return $this->hasMany(CarritoLinea::class, 'carrito_id');
    }

    /** Solo el estado `activo`, literal. */
    public function scopeActivo(Builder $query): Builder
    {
        return $query->where('estado', EstadoCarrito::Activo->value);
    }

    /**
     * El carrito que el cliente todavia puede usar: el que esta en la vitrina
     * y el que esta en el checkout.
     *
     * <b>Por que existe aparte de scopeActivo().</b> Son dos preguntas
     * distintas y confundirlas vacia carritos. `activo` es un valor del enum;
     * "abierto" es el carrito vigente del cliente, que durante el checkout
     * esta en `en_checkout` y sigue siendo el suyo. Buscar el carrito del
     * cliente con scopeActivo() no encuentra nada mientras esta pagando, y
     * quien lo busque creara uno nuevo vacio.
     *
     * `convertido` y `abandonado` son finales: esos no se reabren.
     */
    public function scopeAbierto(Builder $query): Builder
    {
        return $query->whereIn('estado', [
            EstadoCarrito::Activo->value,
            EstadoCarrito::EnCheckout->value,
        ]);
    }
}
