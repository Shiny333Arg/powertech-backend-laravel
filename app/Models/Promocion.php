<?php

namespace App\Models;

use App\Enums\CanalPromocion;
use App\Enums\CanalVenta;
use App\Enums\TipoPromocion;
use App\Models\Concerns\ProtegeContraBorrado;
use App\Models\Concerns\TieneBajaLogica;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Unica fuente de descuento del sistema: el descuento manual se eliminó. */
class Promocion extends Model
{
    use ProtegeContraBorrado;
    use TieneBajaLogica;

    protected $table = 'promociones';

    protected $fillable = [
        'nombre', 'descripcion', 'tipo', 'valor', 'banner', 'canal',
        'prioridad', 'acumulable', 'vigencia_desde', 'vigencia_hasta',
        'activo', 'creado_por',
    ];

    protected $casts = [
        'tipo'           => TipoPromocion::class,
        'canal'          => CanalPromocion::class,
        'valor'          => 'decimal:2',
        'prioridad'      => 'integer',
        'acumulable'     => 'boolean',
        'vigencia_desde' => 'datetime',
        'vigencia_hasta' => 'datetime',
        'activo'         => 'boolean',
    ];

    public function productos(): BelongsToMany
    {
        return $this->belongsToMany(Producto::class, 'promocion_productos', 'promocion_id', 'producto_id');
    }

    public function aplicaciones(): HasMany
    {
        return $this->hasMany(PromocionAplicada::class, 'promocion_id');
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    /** Activa y dentro de la ventana de vigencia. */
    public function scopeVigentes(Builder $query): Builder
    {
        return $query->where('activo', true)
            ->where('vigencia_desde', '<=', now())
            ->where('vigencia_hasta', '>=', now());
    }

    public function scopeDelCanal(Builder $query, CanalVenta $canal): Builder
    {
        return $query->whereIn('canal', [$canal->value, CanalPromocion::Ambos->value]);
    }
}
