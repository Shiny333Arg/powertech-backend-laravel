<?php

namespace App\Enums;

use App\Enums\Concerns\ConEtiqueta;

enum TipoDireccion: string
{
    use ConEtiqueta;

    case Fiscal     = 'fiscal';
    case Envio      = 'envio';
    case Particular = 'particular';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Fiscal     => 'Fiscal',
            self::Envio      => 'Envío',
            self::Particular => 'Particular',
        };
    }
}
