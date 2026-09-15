<?php

namespace App\Enums;

use App\Enums\Concerns\ConEtiqueta;

/** Decide a que aplicacion entra el usuario al autenticarse. */
enum AmbitoRol: string
{
    use ConEtiqueta;

    case Gestion = 'gestion';
    case Tienda  = 'tienda';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Gestion => 'Gestión',
            self::Tienda  => 'Tienda',
        };
    }
}
