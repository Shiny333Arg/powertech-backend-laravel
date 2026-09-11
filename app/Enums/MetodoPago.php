<?php

namespace App\Enums;

enum MetodoPago: string
{
    case EFECTIVO = 'efectivo';
    case TARJETA = 'tarjeta';
    case TRANSFERENCIA = 'transferencia';
}
