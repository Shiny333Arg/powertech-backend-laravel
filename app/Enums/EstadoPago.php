<?php

namespace App\Enums;

enum EstadoPago: string
{
    case PENDIENTE = 'pendiente';
    case ACREDITADO = 'acreditado';
    case RECHAZADO = 'rechazado';
    case DEVUELTO = 'devuelto';
}
