<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Catalogo ARCA. Determina la letra del comprobante:
 * responsable inscripto recibe A, el resto B.
 */
class CondicionIva extends Model
{
    protected $table = 'condiciones_iva';

    protected $fillable = ['codigo_afip', 'nombre'];

    protected $casts = ['codigo_afip' => 'integer'];

    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class, 'condicion_iva_id');
    }

    public function proveedores(): HasMany
    {
        return $this->hasMany(Proveedor::class, 'condicion_iva_id');
    }

    public function comprobantes(): HasMany
    {
        return $this->hasMany(Comprobante::class, 'condicion_iva_id');
    }
}
