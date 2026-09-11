<?php

namespace App\Models;

use App\Enums\CanalPromocion;
use App\Enums\CanalVenta;
use App\Enums\TipoPromocion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * F.1 promociones. UNICA FUENTE DE DESCUENTO DEL SISTEMA: por requerimiento
 * se elimino el descuento manual sobre las ventas.
 *
 * `prioridad` y `acumulable` definen que pasa cuando dos promociones alcanzan
 * el mismo producto. Sin ellos el resultado depende del orden en que el
 * motor devuelva las filas, que es impredecible y no reproducible ante un
 * reclamo del cliente.
 */
class Promocion extends Model
{
    use SoftDeletes;

    // Sin esto Laravel buscaria `promocions`.
    protected $table = 'promociones';

    protected $fillable = [
        'nombre', 'descripcion', 'tipo', 'valor', 'banner', 'canal',
        'prioridad', 'acumulable', 'vigencia_desde', 'vigencia_hasta',
        'activo', 'creado_por',
    ];

    protected function casts(): array
    {
        return [
            'tipo'           => TipoPromocion::class,
            'canal'          => CanalPromocion::class,
            'valor'          => 'decimal:2',
            'prioridad'      => 'integer',
            'acumulable'     => 'boolean',
            'vigencia_desde' => 'datetime',
            'vigencia_hasta' => 'datetime',
            'activo'         => 'boolean',
        ];
    }

    public function productos(): BelongsToMany
    {
        return $this->belongsToMany(
            Producto::class, 'promocion_productos', 'promocion_id', 'producto_id'
        );
    }

    /** Deja responsabilidad sobre los descuentos, que son dinero. */
    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function aplicaciones(): HasMany
    {
        return $this->hasMany(PromocionAplicada::class, 'promocion_id');
    }

    public function scopeVigentes(Builder $q): Builder
    {
        return $q->where('activo', true)
                 ->where('vigencia_desde', '<=', now())
                 ->where('vigencia_hasta', '>=', now());
    }

    public function scopeParaCanal(Builder $q, CanalVenta $canal): Builder
    {
        return $q->whereIn('canal', [CanalPromocion::AMBOS->value, $canal->value]);
    }

    /**
     * El orden de resolucion cuando varias promociones alcanzan un producto.
     * Mayor prioridad primero; a igual prioridad, la mas reciente.
     */
    public function scopeEnOrdenDeAplicacion(Builder $q): Builder
    {
        return $q->orderByDesc('prioridad')->orderByDesc('vigencia_desde');
    }

    public function descuentoSobre(float $precioUnitario): float
    {
        return round(match ($this->tipo) {
            TipoPromocion::PORCENTAJE      => $precioUnitario * ((float) $this->valor / 100),
            TipoPromocion::MONTO_FIJO      => min((float) $this->valor, $precioUnitario),
            TipoPromocion::PRECIO_ESPECIAL => max(0, $precioUnitario - (float) $this->valor),
        }, 2);
    }
}
