<?php

namespace App\Models;

use App\Enums\EstadoComprobante;
use App\Models\Concerns\ProtegeContraBorrado;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Venta y comprobante no son la misma cosa: un presupuesto es una venta sin
 * comprobante, y una venta devuelta tiene factura y nota de credito.
 *
 * Inmutable una vez que tiene CAE. Nada se borra: una anulacion se resuelve
 * emitiendo una nota de credito asociada.
 *
 * numero es el correlativo FISCAL, unico por (tipo, punto de venta).
 */
class Comprobante extends Model
{
    use ProtegeContraBorrado;

    protected $table = 'comprobantes';

    protected $fillable = [
        'venta_id', 'tipo_comprobante_id', 'punto_venta_id', 'numero',
        'comprobante_asociado_id', 'cliente_id', 'tipo_documento_id', 'nro_doc',
        'condicion_iva_id', 'fecha_emision', 'neto', 'iva', 'total',
        'cae', 'cae_vencimiento', 'estado', 'motivo',
    ];

    protected $casts = [
        'numero'          => 'integer',
        'estado'          => EstadoComprobante::class,
        'fecha_emision'   => 'datetime',
        'neto'            => 'decimal:2',
        'iva'             => 'decimal:2',
        'total'           => 'decimal:2',
        'cae_vencimiento' => 'date',
    ];

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

    /** La factura de origen, si este comprobante es una nota. */
    public function comprobanteAsociado(): BelongsTo
    {
        return $this->belongsTo(self::class, 'comprobante_asociado_id');
    }

    /** Las notas emitidas contra esta factura. */
    public function notasAsociadas(): HasMany
    {
        return $this->hasMany(self::class, 'comprobante_asociado_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    /** Datos fiscales copiados al emitir, no leidos de la ficha del cliente. */
    public function tipoDocumento(): BelongsTo
    {
        return $this->belongsTo(TipoDocumento::class, 'tipo_documento_id');
    }

    public function condicionIva(): BelongsTo
    {
        return $this->belongsTo(CondicionIva::class, 'condicion_iva_id');
    }

    public function lineas(): HasMany
    {
        return $this->hasMany(ComprobanteLinea::class, 'comprobante_id');
    }

    public function solicitudes(): HasMany
    {
        return $this->hasMany(AfipSolicitud::class, 'comprobante_id');
    }

    /** Una nota de credito es el origen del movimiento de devolucion. */
    public function movimientosStock(): MorphMany
    {
        return $this->morphMany(MovimientoStock::class, 'origen');
    }

    public function scopeAutorizados(Builder $query): Builder
    {
        return $query->where('estado', EstadoComprobante::Autorizado->value);
    }

    public function estaAutorizado(): bool
    {
        return $this->estado === EstadoComprobante::Autorizado && $this->cae !== null;
    }

    protected function mensajeNoEliminable(): string
    {
        return 'Un comprobante no se borra. Emitir una nota de crédito asociada.';
    }
}
