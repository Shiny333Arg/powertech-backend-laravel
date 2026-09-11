<?php

namespace App\Models;

use App\Enums\EstadoOrdenCompra;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * E.2 ordenes_compra. El restock automatico se materializa en un documento y
 * no en una notificacion suelta: el job detecta que el stock DISPONIBLE cayo
 * bajo el punto de reposicion, arma la orden en borrador y el administrativo
 * la aprueba.
 *
 * `numero` sale de la serie 'orden_compra' de numeradores, con el mismo
 * mecanismo que el correlativo de la venta. Es la segunda serie del sistema
 * y la razon por la que el contador es una tabla generica.
 */
class OrdenCompra extends Model
{
    // Sin esto Laravel buscaria `orden_compras`.
    protected $table = 'ordenes_compra';

    protected $fillable = [
        'numero', 'proveedor_id', 'estado', 'origen', 'total_estimado',
        'usuario_creo_id', 'usuario_aprobo_id', 'fecha_aprobacion',
        'fecha_envio', 'fecha_entrega_estimada', 'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'estado'                 => EstadoOrdenCompra::class,
            'total_estimado'         => 'decimal:2',
            'fecha_aprobacion'       => 'datetime',
            'fecha_envio'            => 'datetime',
            'fecha_entrega_estimada' => 'date',
        ];
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function lineas(): HasMany
    {
        return $this->hasMany(OrdenCompraLinea::class, 'orden_compra_id');
    }

    public function usuarioCreo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_creo_id');
    }

    public function usuarioAprobo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_aprobo_id');
    }

    /** Las recepciones impactan como movimientos de tipo `compra`. */
    public function movimientosStock(): MorphMany
    {
        return $this->morphMany(MovimientoStock::class, 'origen');
    }

    public function scopeAbiertas(Builder $q): Builder
    {
        return $q->whereIn('estado', array_column(EstadoOrdenCompra::abiertas(), 'value'));
    }

    /** Dashboard del administrativo: pendientes de aprobacion. */
    public function scopePendientesDeAprobacion(Builder $q): Builder
    {
        return $q->where('estado', EstadoOrdenCompra::BORRADOR);
    }

    /**
     * Guarda del job de reposicion. Sin esta verificacion, cada corrida
     * generaria un pedido nuevo por el mismo faltante, porque la orden
     * pendiente todavia no sumo nada al stock.
     */
    public static function existeAbiertaPara(int $productoId): bool
    {
        return static::abiertas()
            ->whereHas('lineas', fn (Builder $q) => $q->where('producto_id', $productoId))
            ->exists();
    }

    public function estaCompletamenteRecibida(): bool
    {
        return ! $this->lineas()
            ->whereColumn('cantidad_recibida', '<', 'cantidad_pedida')
            ->exists();
    }
}
