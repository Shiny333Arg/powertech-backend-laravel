<?php

namespace App\Models;

use App\Enums\TipoDireccion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A.7 direcciones. Polimorfica: empleados | clientes | proveedores.
 * Una sola tabla resuelve los tres casos y soporta al cliente con varias
 * direcciones de envio.
 */
class Direccion extends Model
{
    protected $table = 'direcciones';

    protected $fillable = [
        'tipo', 'calle', 'numero', 'piso_depto', 'codigo_postal',
        'localidad', 'provincia', 'es_predeterminada',
    ];

    protected function casts(): array
    {
        return [
            'tipo'              => TipoDireccion::class,
            'es_predeterminada' => 'boolean',
        ];
    }

    public function direccionable(): MorphTo
    {
        return $this->morphTo();
    }

    public function unaLinea(): string
    {
        return trim(sprintf(
            '%s %s %s, %s (%s), %s',
            $this->calle,
            $this->numero ?? 's/n',
            $this->piso_depto ?? '',
            $this->localidad,
            $this->codigo_postal,
            $this->provincia
        ));
    }
}
