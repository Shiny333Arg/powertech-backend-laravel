<?php

namespace App\Enums;

use App\Enums\Concerns\ConEtiqueta;

enum TipoEmision: string
{
    use ConEtiqueta;

    case Wsfe   = 'wsfe';
    case Manual = 'manual';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Wsfe   => 'Web service (WSFE)',
            self::Manual => 'Manual',
        };
    }
}
