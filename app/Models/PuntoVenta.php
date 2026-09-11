<?php

namespace App\Models;

use App\Enums\TipoEmision;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * B.5 puntos_venta. La numeracion fiscal es por punto de venta y tipo de
 * comprobante, y debe ser estrictamente correlativa: esa unicidad se hace
 * cumplir con el indice unico de `comprobantes`.
 */
class PuntoVenta extends Model
{
    protected $table = 'puntos_venta';

    public $timestamps = false;

    protected $fillable = ['numero', 'nombre', 'tipo_emision', 'activo'];

    protected function casts(): array
    {
        return [
            'numero'       => 'integer',
            'tipo_emision' => TipoEmision::class,
            'activo'       => 'boolean',
        ];
    }

    public function comprobantes(): HasMany
    {
        return $this->hasMany(Comprobante::class, 'punto_venta_id');
    }
}
