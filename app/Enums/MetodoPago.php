<?php

namespace App\Enums;

use App\Enums\Concerns\ConEtiqueta;

enum MetodoPago: string
{
    use ConEtiqueta;

    case Efectivo      = 'efectivo';
    case Tarjeta       = 'tarjeta';
    case Transferencia = 'transferencia';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Efectivo      => 'Efectivo',
            self::Tarjeta       => 'Tarjeta',
            self::Transferencia => 'Transferencia',
        };
    }
}
