<?php

namespace App\Models;

use App\Models\Concerns\ProtegeContraBorrado;
use App\Models\Concerns\TieneBajaLogica;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * user_id es nullable: el cliente de mostrador existe sin tener cuenta.
 *
 * activo es independiente del activo de su user: dar de baja el user le corta
 * el acceso a la tienda, dar de baja el cliente lo saca de la operacion del
 * mostrador. Son dos decisiones distintas.
 */
class Cliente extends Model
{
    use ProtegeContraBorrado;
    use TieneBajaLogica;

    protected $table = 'clientes';

    protected $fillable = [
        'user_id', 'razon_social', 'tipo_documento_id', 'nro_doc',
        'condicion_iva_id', 'telefono', 'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tipoDocumento(): BelongsTo
    {
        return $this->belongsTo(TipoDocumento::class, 'tipo_documento_id');
    }

    public function condicionIva(): BelongsTo
    {
        return $this->belongsTo(CondicionIva::class, 'condicion_iva_id');
    }

    public function direcciones(): MorphMany
    {
        return $this->morphMany(Direccion::class, 'direccionable');
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'cliente_id');
    }

    public function comprobantes(): HasMany
    {
        return $this->hasMany(Comprobante::class, 'cliente_id');
    }
}
