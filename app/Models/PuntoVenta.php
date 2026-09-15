<?php

namespace App\Models;

use App\Enums\TipoEmision;
use App\Models\Concerns\TieneBajaLogica;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PuntoVenta extends Model
{
    use TieneBajaLogica;

    protected $table = 'puntos_venta';

    protected $fillable = ['numero', 'nombre', 'tipo_emision', 'activo'];

    protected $casts = [
        'numero'       => 'integer',
        'tipo_emision' => TipoEmision::class,
        'activo'       => 'boolean',
    ];

    public function comprobantes(): HasMany
    {
        return $this->hasMany(Comprobante::class, 'punto_venta_id');
    }
}
