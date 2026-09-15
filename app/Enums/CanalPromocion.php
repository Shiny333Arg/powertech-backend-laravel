<?php

namespace App\Enums;

use App\Enums\Concerns\ConEtiqueta;

/**
 * No es el mismo enum que CanalVenta: la promocion agrega 'ambos', que no es
 * un canal sino la ausencia de restriccion.
 */
enum CanalPromocion: string
{
    use ConEtiqueta;

    case Ambos      = 'ambos';
    case Mostrador  = 'mostrador';
    case Online     = 'online';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Ambos     => 'Mostrador y tienda',
            self::Mostrador => 'Solo mostrador',
            self::Online    => 'Solo tienda online',
        };
    }

    public function alcanzaA(CanalVenta $canal): bool
    {
        return $this === self::Ambos || $this->value === $canal->value;
    }
}
