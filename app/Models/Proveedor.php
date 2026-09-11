<?php

namespace App\Models;

use App\Enums\CanalPedido;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * E.1 proveedores. Sin ellos no hay a quien emitir la orden de compra del
 * restock automatico. No son usuarios: no tienen login.
 */
class Proveedor extends Model
{
    use SoftDeletes;

    // Sin esto Laravel buscaria la tabla `proveedors`.
    protected $table = 'proveedores';

    protected $fillable = [
        'razon_social', 'cuit', 'condicion_iva_id', 'email', 'telefono',
        'contacto', 'canal_pedido', 'portal_url', 'plazo_entrega_dias', 'activo',
    ];

    protected function casts(): array
    {
        return [
            'canal_pedido'       => CanalPedido::class,
            'plazo_entrega_dias' => 'integer',
            'activo'             => 'boolean',
        ];
    }

    public function condicionIva(): BelongsTo
    {
        return $this->belongsTo(CondicionIva::class, 'condicion_iva_id');
    }

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'proveedor_id');
    }

    public function ordenesCompra(): HasMany
    {
        return $this->hasMany(OrdenCompra::class, 'proveedor_id');
    }

    public function direcciones(): MorphMany
    {
        return $this->morphMany(Direccion::class, 'direccionable');
    }
}
