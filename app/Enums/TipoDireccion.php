<?php

namespace App\Enums;

enum TipoDireccion: string
{
    case FISCAL = 'fiscal';
    case ENVIO = 'envio';
    case PARTICULAR = 'particular';
}
