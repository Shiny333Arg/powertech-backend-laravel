<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AlicuotaIva extends Model
{
    protected $table = 'alicuotas_iva';

    protected $fillable = ['codigo_afip', 'porcentaje', 'nombre'];

    protected $casts = [
        'codigo_afip' => 'integer',
        'porcentaje'  => 'decimal:2',
    ];

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'alicuota_iva_id');
    }
}
