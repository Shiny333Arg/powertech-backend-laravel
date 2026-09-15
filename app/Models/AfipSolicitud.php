<?php

namespace App\Models;

use App\Models\Concerns\ProtegeContraBorrado;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Una fila por intento, append-only. Sin este log no hay forma de saber si un
 * comprobante quedo autorizado del otro lado cuando se corto la conexion, ni
 * de reintentar sin duplicar, ni de responder ante una inspeccion.
 */
class AfipSolicitud extends Model
{
    use ProtegeContraBorrado;

    public const UPDATED_AT = null;

    protected $table = 'afip_solicitudes';

    protected $fillable = [
        'comprobante_id', 'operacion', 'request_payload', 'response_payload',
        'resultado', 'codigo_error', 'mensaje_error', 'intento',
    ];

    protected $casts = [
        'request_payload'  => 'array',
        'response_payload' => 'array',
        'intento'          => 'integer',
        'created_at'       => 'datetime',
    ];

    public function comprobante(): BelongsTo
    {
        return $this->belongsTo(Comprobante::class, 'comprobante_id');
    }

    protected function mensajeNoEliminable(): string
    {
        return 'El log de solicitudes a ARCA no se borra.';
    }
}
