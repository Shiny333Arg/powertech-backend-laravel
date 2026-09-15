<?php

namespace App\Enums;

use App\Enums\Concerns\ConEtiqueta;

enum TipoPromocion: string
{
    use ConEtiqueta;

    case Porcentaje      = 'porcentaje';
    case MontoFijo       = 'monto_fijo';
    case PrecioEspecial  = 'precio_especial';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Porcentaje     => 'Porcentaje de descuento',
            self::MontoFijo      => 'Monto fijo de descuento',
            self::PrecioEspecial => 'Precio especial',
        };
    }

    /** Como interpretar la columna valor. */
    public function unidad(): string
    {
        return $this === self::Porcentaje ? '%' : '$';
    }
}
