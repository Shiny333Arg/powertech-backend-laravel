<?php

namespace App\Enums;

use App\Enums\Concerns\ConEtiqueta;

/**
 * Mostrador y online son dos tipos de la MISMA entidad: comparten tabla,
 * catalogo, stock y numeracion interna, y se diferencian solo por esto.
 */
enum CanalVenta: string
{
    use ConEtiqueta;

    case Mostrador = 'mostrador';
    case Online    = 'online';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Mostrador => 'Mostrador',
            self::Online    => 'Tienda online',
        };
    }

    /** La venta online nace confirmada, esperando el pago. */
    public function estadoInicial(): EstadoVenta
    {
        return match ($this) {
            self::Mostrador => EstadoVenta::Presupuesto,
            self::Online    => EstadoVenta::Confirmada,
        };
    }
}
