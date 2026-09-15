<?php

namespace App\Enums;

use App\Enums\Concerns\ConEtiqueta;

enum ProveedorAuth: string
{
    use ConEtiqueta;

    case Local  = 'local';
    case Google = 'google';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Local  => 'Usuario y contraseña',
            self::Google => 'Cuenta de Google',
        };
    }

    /** Con Google no hay contrasena local que guardar. */
    public function exigePassword(): bool
    {
        return $this === self::Local;
    }
}
