<?php

namespace App\Enums;

enum EstadoOrdenCompra: string
{
    case BORRADOR         = 'borrador';
    case APROBADA         = 'aprobada';
    case ENVIADA          = 'enviada';
    case RECIBIDA_PARCIAL = 'recibida_parcial';
    case RECIBIDA         = 'recibida';
    case CANCELADA        = 'cancelada';

    /**
     * Una orden abierta es la que todavia puede sumar mercaderia.
     * El job de reposicion no genera una orden nueva para un producto que
     * ya tiene una abierta: si no, cada corrida pediria de nuevo el mismo
     * faltante, porque la orden pendiente todavia no sumo nada al stock.
     */
    public function esAbierta(): bool
    {
        return in_array($this, [
            self::BORRADOR, self::APROBADA, self::ENVIADA, self::RECIBIDA_PARCIAL,
        ], true);
    }

    public static function abiertas(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $e) => $e->esAbierta()
        ));
    }
}
