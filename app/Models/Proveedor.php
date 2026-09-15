<?php

namespace App\Models;

use App\Enums\CanalPedido;
use App\Models\Concerns\ProtegeContraBorrado;
use App\Models\Concerns\TieneBajaLogica;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/** Los proveedores no son usuarios: no tienen login ni interactuan con el sistema. */
class Proveedor extends Model
{
    use ProtegeContraBorrado;
    use TieneBajaLogica;

    protected $table = 'proveedores';

    protected $fillable = [
        'razon_social', 'cuit', 'condicion_iva_id', 'email', 'telefono',
        'contacto', 'canal_pedido', 'portal_url', 'plazo_entrega_dias', 'activo',
    ];

    protected $casts = [
        'canal_pedido'       => CanalPedido::class,
        'plazo_entrega_dias' => 'integer',
        'activo'             => 'boolean',
    ];

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
