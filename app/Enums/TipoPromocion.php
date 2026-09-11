<?php

namespace App\Enums;

enum TipoPromocion: string
{
    case PORCENTAJE = 'porcentaje';
    case MONTO_FIJO = 'monto_fijo';
    case PRECIO_ESPECIAL = 'precio_especial';
}
