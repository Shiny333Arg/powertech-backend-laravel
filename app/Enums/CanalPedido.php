<?php

namespace App\Enums;

use App\Enums\Concerns\ConEtiqueta;

/**
 * Desacopla el proceso interno del medio de entrega al proveedor: el sistema
 * siempre genera la orden en borrador, lo unico que cambia es como le llega.
 */
enum CanalPedido: string
{
    use ConEtiqueta;

    case Email         = 'email';
    case PortalExterno = 'portal_externo';
    case Manual        = 'manual';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Email         => 'Correo electrónico',
            self::PortalExterno => 'Portal del proveedor',
            self::Manual        => 'Manual',
        };
    }

    public function exigePortalUrl(): bool
    {
        return $this === self::PortalExterno;
    }
}
