<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Catalogo ARCA. Se carga por seeder; no se administra desde la app. */
class TipoDocumento extends Model
{
    protected $table = 'tipos_documento';

    protected $fillable = ['codigo_afip', 'nombre'];

    protected $casts = ['codigo_afip' => 'integer'];

    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class, 'tipo_documento_id');
    }

    public function empleados(): HasMany
    {
        return $this->hasMany(Empleado::class, 'tipo_documento_id');
    }

    public function comprobantes(): HasMany
    {
        return $this->hasMany(Comprobante::class, 'tipo_documento_id');
    }
}
