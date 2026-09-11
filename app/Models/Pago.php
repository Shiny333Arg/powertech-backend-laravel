<?php

namespace App\Models;

use App\Enums\EstadoPago;
use App\Enums\MetodoPago;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * H.3 pagos. Un booleano de "cobrada" no dice como, cuando ni en cuantas
 * partes se cobro. Esta tabla habilita el pago mixto, el parcial y el arqueo
 * del cajero.
 *
 * IDEMPOTENCIA DE MERCADO PAGO: `mp_payment_id` tiene indice unico. MP
 * reenvia la misma notificacion varias veces; sin ese indice se acredita el
 * mismo pago dos veces y se emiten dos comprobantes por la misma venta, o
 * sea dos numeros fiscales quemados que despues hay que anular con notas de
 * credito. Es el error clasico de esta integracion.
 */
class Pago extends Model
{
    protected $table = 'pagos';

    public $timestamps = false;

    protected $fillable = [
        'venta_id', 'metodo', 'monto', 'comision', 'neto_acreditado', 'cuotas',
        'estado', 'mp_preference_id', 'mp_payment_id', 'mp_merchant_order_id',
        'mp_status', 'mp_status_detail', 'mp_payment_type', 'usuario_id', 'fecha',
    ];

    protected function casts(): array
    {
        return [
            'metodo'          => MetodoPago::class,
            'estado'          => EstadoPago::class,
            'monto'           => 'decimal:2',
            'comision'        => 'decimal:2',
            'neto_acreditado' => 'decimal:2',
            'cuotas'          => 'integer',
            'fecha'           => 'datetime',
        ];
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    /** El cajero. Null en la venta online. */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function scopeAcreditados(Builder $q): Builder
    {
        return $q->where('estado', EstadoPago::ACREDITADO);
    }

    /**
     * Puerta de entrada del webhook. El indice unico es la garantia real;
     * esto solo evita el viaje de ida cuando la notificacion ya se proceso.
     */
    public static function yaProcesado(string $mpPaymentId): bool
    {
        return static::where('mp_payment_id', $mpPaymentId)->exists();
    }

    /** Lo que cobra el cliente no es lo que entra a la cuenta. */
    public function comisionReal(): float
    {
        return (float) $this->monto - (float) ($this->neto_acreditado ?? $this->monto);
    }
}
