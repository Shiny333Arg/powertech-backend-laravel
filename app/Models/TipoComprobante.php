<?php

namespace App\Models;

use App\Enums\LetraComprobante;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** signo (+1 / -1) permite sumar saldos sin condicionales dispersos. */
class TipoComprobante extends Model
{
    protected $table = 'tipos_comprobante';

    protected $fillable = ['codigo_afip', 'nombre', 'letra', 'signo'];

    protected $casts = [
        'codigo_afip' => 'integer',
        'letra'       => LetraComprobante::class,
        'signo'       => 'integer',
    ];

    public function comprobantes(): HasMany
    {
        return $this->hasMany(Comprobante::class, 'tipo_comprobante_id');
    }

    public function esNotaCredito(): bool
    {
        return $this->signo === -1;
    }
}
