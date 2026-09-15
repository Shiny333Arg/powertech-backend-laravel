<?php

namespace App\Models;

use App\Enums\EstadoPago;
use App\Enums\MetodoPago;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Permite pago mixto, pagos parciales y el arqueo del cajero.
 *
 * mp_payment_id tiene indice unico: es lo que da idempotencia frente al
 * webhook de Mercado Pago, que reenvia la misma notificacion varias veces.
 * Sin eso se acredita dos veces y se emiten dos comprobantes por la misma
 * venta: dos numeros fiscales quemados.
 */
class Pago extends Model
{
    protected $table = 'pagos';

    protected $fillable = [
        'venta_id', 'metodo', 'monto', 'comision', 'neto_acreditado', 'cuotas',
        'estado', 'mp_preference_id', 'mp_payment_id', 'mp_merchant_order_id',
        'mp_status', 'mp_status_detail', 'mp_payment_type', 'usuario_id', 'fecha',
    ];

    protected $casts = [
        'metodo'          => MetodoPago::class,
        'estado'          => EstadoPago::class,
        'monto'           => 'decimal:2',
        'comision'        => 'decimal:2',
        'neto_acreditado' => 'decimal:2',
        'cuotas'          => 'integer',
        'fecha'           => 'datetime',
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    /** Cajero. Null en la venta online. */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function scopeAcreditados(Builder $query): Builder
    {
        return $query->where('estado', EstadoPago::Acreditado->value);
    }
}
