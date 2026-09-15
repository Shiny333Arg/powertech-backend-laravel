<?php

namespace App\Models;

use App\Enums\EstadoOrdenCompra;
use App\Enums\OrigenOrdenCompra;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class OrdenCompra extends Model
{
    protected $table = 'ordenes_compra';

    protected $fillable = [
        'numero', 'proveedor_id', 'estado', 'origen', 'total_estimado',
        'usuario_creo_id', 'usuario_aprobo_id', 'fecha_aprobacion',
        'fecha_envio', 'fecha_entrega_estimada', 'observaciones',
    ];

    protected $casts = [
        'estado'                 => EstadoOrdenCompra::class,
        'origen'                 => OrigenOrdenCompra::class,
        'total_estimado'         => 'decimal:2',
        'fecha_aprobacion'       => 'datetime',
        'fecha_envio'            => 'datetime',
        'fecha_entrega_estimada' => 'date',
    ];

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

    public function movimientosStock(): MorphMany
    {
        return $this->morphMany(MovimientoStock::class, 'origen');
    }

    /** Antes de generar una orden automatica: el faltante ya puede estar pedido. */
    public function scopeAbiertas(Builder $query): Builder
    {
        return $query->whereIn('estado', EstadoOrdenCompra::abiertas());
    }

    public function scopePendientesDeAprobacion(Builder $query): Builder
    {
        return $query->where('estado', EstadoOrdenCompra::Borrador->value);
    }
}
