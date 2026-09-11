<?php

namespace App\Models;

use App\Enums\LetraComprobante;
use Illuminate\Database\Eloquent\Model;

/**
 * B.2 condiciones_iva. Determina la letra del comprobante: responsable
 * inscripto recibe factura A, el resto B.
 */
class CondicionIva extends Model
{
    protected $table = 'condiciones_iva';

    public $timestamps = false;

    protected $fillable = ['codigo_afip', 'nombre'];

    /** Codigo ARCA de responsable inscripto. */
    public const RESPONSABLE_INSCRIPTO = 1;

    public function letraQueCorresponde(): LetraComprobante
    {
        return $this->codigo_afip === self::RESPONSABLE_INSCRIPTO
            ? LetraComprobante::A
            : LetraComprobante::B;
    }
}
