<?php

namespace App\Models;

use App\Enums\EstadoComprobante;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use LogicException;

/**
 * I.1 comprobantes. Venta y comprobante NO son la misma cosa: un presupuesto
 * es una venta sin comprobante, y una venta puede generar una factura y
 * despues una nota de credito.
 *
 * `numero` es el correlativo FISCAL, por (tipo, punto de venta), y lo
 * autoriza ARCA. No tiene ninguna relacion con ventas.numero, que es el
 * correlativo interno. Son dos numeraciones que corren en paralelo y jamas
 * se cruzan.
 *
 * INMUTABLE UNA VEZ CON CAE. Nada se borra: una anulacion se resuelve
 * emitiendo una nota de credito asociada.
 */
class Comprobante extends Model
{
    protected $table = 'comprobantes';

    protected $fillable = [
        'venta_id', 'tipo_comprobante_id', 'punto_venta_id', 'numero',
        'comprobante_asociado_id', 'cliente_id', 'tipo_documento_id', 'nro_doc',
        'condicion_iva_id', 'fecha_emision', 'neto', 'iva', 'total',
        'cae', 'cae_vencimiento', 'estado', 'motivo',
    ];

    protected function casts(): array
    {
        return [
            'estado'          => EstadoComprobante::class,
            'numero'          => 'integer',
            'fecha_emision'   => 'datetime',
            'cae_vencimiento' => 'date',
            'neto'            => 'decimal:2',
            'iva'             => 'decimal:2',
            'total'           => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (self $c) {
            // Se permite escribir el CAE una vez, que es como pasa de
            // pendiente a autorizado. Despues queda congelado.
            if ($c->getOriginal('cae') !== null) {
                throw new LogicException(
                    'Un comprobante con CAE es inmutable. Para corregirlo, '
                    . 'emiti una nota de credito asociada.'
                );
            }
        });

        static::deleting(fn () => throw new LogicException(
            'Los comprobantes no se eliminan. Una anulacion es una nota de credito.'
        ));
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function tipoComprobante(): BelongsTo
    {
        return $this->belongsTo(TipoComprobante::class, 'tipo_comprobante_id');
    }

    public function puntoVenta(): BelongsTo
    {
        return $this->belongsTo(PuntoVenta::class, 'punto_venta_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    /** Encadena la nota de credito con su factura de origen. */
    public function comprobanteAsociado(): BelongsTo
    {
        return $this->belongsTo(self::class, 'comprobante_asociado_id');
    }

    public function notasAsociadas(): HasMany
    {
        return $this->hasMany(self::class, 'comprobante_asociado_id');
    }

    public function lineas(): HasMany
    {
        return $this->hasMany(ComprobanteLinea::class, 'comprobante_id');
    }

    public function solicitudes(): HasMany
    {
        return $this->hasMany(AfipSolicitud::class, 'comprobante_id');
    }

    /** La nota de credito es el origen del movimiento de devolucion. */
    public function movimientosStock(): MorphMany
    {
        return $this->morphMany(MovimientoStock::class, 'origen');
    }

    public function estaAutorizado(): bool
    {
        return $this->estado === EstadoComprobante::AUTORIZADO && $this->cae !== null;
    }

    /** Numero fiscal completo: 0001-00000123. */
    public function numeroFormateado(): string
    {
        return sprintf('%04d-%08d', $this->puntoVenta->numero, $this->numero);
    }
}
