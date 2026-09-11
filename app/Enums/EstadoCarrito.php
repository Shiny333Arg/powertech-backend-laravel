<?php

namespace App\Enums;

enum EstadoCarrito: string
{
    case ACTIVO = 'activo';
    case EN_CHECKOUT = 'en_checkout';
    case CONVERTIDO = 'convertido';
    case ABANDONADO = 'abandonado';
}
