<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A.6 clientes. Entidad y no texto libre dentro de la venta: ARCA exige tipo
 * y numero de documento y condicion frente al IVA para determinar si
 * corresponde factura A o B.
 *
 * `user_id` es nullable a proposito: el cliente de mostrador existe como
 * cliente sin tener cuenta.
 */
class Cliente extends Model
{
    use SoftDeletes;

    protected $table = 'clientes';

    protected $fillable = [
        'user_id', 'razon_social', 'tipo_documento_id', 'nro_doc',
        'condicion_iva_id', 'telefono',
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

    public function direccionFiscal(): ?Direccion
    {
        return $this->direcciones()->where('tipo', 'fiscal')->first();
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'cliente_id');
    }

    public function comprobantes(): HasMany
    {
        return $this->hasMany(Comprobante::class, 'cliente_id');
    }

    /** Tiene cuenta en la tienda, o es solo un cliente de mostrador. */
    public function tieneCuenta(): bool
    {
        return $this->user_id !== null;
    }
}
