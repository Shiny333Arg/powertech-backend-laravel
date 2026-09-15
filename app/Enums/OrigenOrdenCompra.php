<?php

namespace App\Enums;

use App\Enums\Concerns\ConEtiqueta;

/** Distinguirlas es la unica forma de medir si los umbrales estan bien calibrados. */
enum OrigenOrdenCompra: string
{
    use ConEtiqueta;

    case Manual     = 'manual';
    case Automatica = 'automatica';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Manual     => 'Manual',
            self::Automatica => 'Automática por reposición',
        };
    }
}
