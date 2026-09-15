<?php

namespace App\Models;

use App\Enums\TipoDireccion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Polimorfica: empleados | clientes | proveedores.
 */
class Direccion extends Model
{
    protected $table = 'direcciones';

    protected $fillable = [
        'direccionable_type', 'direccionable_id', 'tipo', 'calle', 'numero',
        'piso_depto', 'codigo_postal', 'localidad', 'provincia', 'es_predeterminada',
    ];

    protected $casts = [
        'tipo'              => TipoDireccion::class,
        'es_predeterminada' => 'boolean',
    ];

    public function direccionable(): MorphTo
    {
        return $this->morphTo();
    }
}
