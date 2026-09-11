<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * B.3 alicuotas_iva. PowerTech es responsable inscripto: el IVA va
 * discriminado y la alicuota se informa con el codigo que espera el WS,
 * no solo con el porcentaje.
 */
class AlicuotaIva extends Model
{
    protected $table = 'alicuotas_iva';

    public $timestamps = false;

    protected $fillable = ['codigo_afip', 'porcentaje', 'nombre'];

    protected function casts(): array
    {
        return ['porcentaje' => 'decimal:2'];
    }

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'alicuota_iva_id');
    }
}
