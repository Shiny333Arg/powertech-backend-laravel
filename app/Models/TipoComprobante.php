<?php

namespace App\Models;

use App\Enums\LetraComprobante;
use Illuminate\Database\Eloquent\Model;

/**
 * B.4 tipos_comprobante. Factura A y B, notas de credito y debito.
 * `signo` (+1 / -1) permite calcular saldos sumando todos los comprobantes,
 * sin condicionales dispersos por el codigo.
 */
class TipoComprobante extends Model
{
    protected $table = 'tipos_comprobante';

    public $timestamps = false;

    protected $fillable = ['codigo_afip', 'nombre', 'letra', 'signo'];

    protected function casts(): array
    {
        return [
            'letra' => LetraComprobante::class,
            'signo' => 'integer',
        ];
    }

    public function esNotaDeCredito(): bool
    {
        return $this->signo < 0;
    }
}
