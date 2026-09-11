<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * B.1 tipos_documento. CUIT, CUIL, DNI, consumidor final.
 * Tabla y no ENUM: los valores los define ARCA y el web service recibe
 * codigos numericos, no etiquetas de texto. Se carga por seeder.
 */
class TipoDocumento extends Model
{
    protected $table = 'tipos_documento';

    public $timestamps = false;

    protected $fillable = ['codigo_afip', 'nombre'];

    protected function casts(): array
    {
        return ['codigo_afip' => 'integer'];
    }
}
