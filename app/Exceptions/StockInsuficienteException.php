<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Solo la entrega y el ajuste negativo pueden fallar por stock.
 * La reserva nunca: comprometer sin stock es la preventa.
 */
class StockInsuficienteException extends RuntimeException
{
    public static function paraEntrega(int $productoId, int $cantidad): self
    {
        return new self(
            "No hay stock fisico suficiente para entregar {$cantidad} unidades "
            . "del producto {$productoId}."
        );
    }
}
