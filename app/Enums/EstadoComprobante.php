<?php

namespace App\Enums;

use App\Enums\Concerns\ConEtiqueta;

enum EstadoComprobante: string
{
    use ConEtiqueta;

    case Pendiente  = 'pendiente';
    case Autorizado = 'autorizado';
    case Rechazado  = 'rechazado';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Pendiente  => 'Pendiente de autorización',
            self::Autorizado => 'Autorizado',
            self::Rechazado  => 'Rechazado',
        };
    }

    /** Autorizado y con CAE, el comprobante es inmutable. */
    public function esInmutable(): bool
    {
        return $this === self::Autorizado;
    }

    public function admiteReintento(): bool
    {
        return $this->es(self::Pendiente, self::Rechazado);
    }
}
