<?php

namespace App\Enums;

enum ProveedorAuth: string
{
    case LOCAL = 'local';
    case GOOGLE = 'google';
}
