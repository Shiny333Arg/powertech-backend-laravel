<?php

namespace App\Enums;

enum CanalPedido: string
{
    case EMAIL = 'email';
    case PORTAL_EXTERNO = 'portal_externo';
    case MANUAL = 'manual';
}
