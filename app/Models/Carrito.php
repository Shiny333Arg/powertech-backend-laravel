<?php

namespace App\Models;

use App\Enums\EstadoCarrito;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * G.1 carritos. El carrito NO es una venta: no consume numeracion, no
 * descuenta stock, no genera comprobante y se abandona la mayoria de las
 * veces. Mezclarlo con `ventas` ensuciaria esa tabla con filas que nunca
 * fueron una operacion.
 *
 * EL CARRITO NO COMPROMETE STOCK, y con tres valores de stock la pregunta se
 * vuelve legitima —ahora existe donde anotar una reserva— pero la respuesta
 * sigue siendo no. Primero, se admite preventa: reservar no protege de nada,
 * porque igual se puede vender sin stock. Segundo, reservar obligaria a un
 * job de expiracion que devuelva unidades de carritos abandonados, que son
 * la mayoria, y el resultado seria comprometido fantasma bloqueando la
 * vitrina. El compromiso nace donde hay una operacion real: la venta
 * confirmada.
 */
class Carrito extends Model
{
    protected $table = 'carritos';

    protected $fillable = ['user_id', 'session_token', 'estado'];

    protected function casts(): array
    {
        return ['estado' => EstadoCarrito::class];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function lineas(): HasMany
    {
        return $this->hasMany(CarritoLinea::class, 'carrito_id');
    }

    public function scopeActivos(Builder $q): Builder
    {
        return $q->where('estado', EstadoCarrito::ACTIVO);
    }

    /** Reporte de carritos abandonados. */
    public function scopeAbandonadosDesde(Builder $q, int $dias): Builder
    {
        return $q->where('estado', EstadoCarrito::ACTIVO)
                 ->where('updated_at', '<', now()->subDays($dias));
    }

    /**
     * El total se calcula al vuelo contra el precio vigente. El carrito no
     * congela precios: eso pasa recien al convertirse en venta.
     */
    public function total(): float
    {
        return (float) $this->lineas()
            ->join('productos', 'productos.id', '=', 'carrito_lineas.producto_id')
            ->sum(DB::raw('carrito_lineas.cantidad * productos.precio'));
    }
}
