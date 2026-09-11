<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * I.3 afip_solicitudes. Los web services del organismo fallan seguido:
 * caidas, timeouts, token vencido, errores de validacion. Sin el log de
 * request y response no hay forma de saber si un comprobante quedo
 * autorizado del otro lado cuando se corto la conexion, ni de reintentar sin
 * duplicar, ni de responder ante una inspeccion.
 *
 * Una fila por INTENTO, no un campo JSON en `comprobantes`: el campo unico
 * sirve para el caso feliz, no para el reintento.
 */
class AfipSolicitud extends Model
{
    protected $table = 'afip_solicitudes';

    public const UPDATED_AT = null;   // append-only

    protected $fillable = [
        'comprobante_id', 'operacion', 'request_payload', 'response_payload',
        'resultado', 'codigo_error', 'mensaje_error', 'intento',
    ];

    protected function casts(): array
    {
        return [
            'request_payload'  => 'array',
            'response_payload' => 'array',
            'intento'          => 'integer',
            'created_at'       => 'datetime',
        ];
    }

    public function comprobante(): BelongsTo
    {
        return $this->belongsTo(Comprobante::class, 'comprobante_id');
    }

    public function fueAprobada(): bool
    {
        return $this->resultado === 'A';
    }

    public static function proximoIntento(int $comprobanteId): int
    {
        return static::where('comprobante_id', $comprobanteId)->max('intento') + 1;
    }
}
