<?php

namespace App\Enums;

enum EstadoComprobante: string
{
    case PENDIENTE = 'pendiente';
    case AUTORIZADO = 'autorizado';
    case RECHAZADO = 'rechazado';
}
