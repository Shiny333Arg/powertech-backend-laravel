<?php

namespace App\Enums;

use App\Enums\Concerns\ConEtiqueta;

/** La letra la determina la condicion frente al IVA del cliente. */
enum LetraComprobante: string
{
    use ConEtiqueta;

    case A = 'A';
    case B = 'B';

    public function etiqueta(): string
    {
        return "Letra {$this->value}";
    }

    /** Solo la A discrimina el IVA en el cuerpo del comprobante. */
    public function discriminaIva(): bool
    {
        return $this === self::A;
    }
}
