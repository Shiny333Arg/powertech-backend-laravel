<?php

namespace App\Exceptions;

use App\Enums\EstadoVenta;
use DomainException;

/**
 * El circuito de estados de la venta es una regla de negocio, no una
 * restriccion de integridad: el motor no puede rechazarla. Esta excepcion es
 * la que convierte un intento invalido en un error del dominio con mensaje
 * propio, en lugar de un update silencioso que deja la venta en un estado
 * imposible.
 */
class TransicionNoPermitidaException extends DomainException
{
    public static function entre(EstadoVenta $actual, EstadoVenta $destino): self
    {
        return new self(
            "Una venta en estado «{$actual->value}» no puede pasar a «{$destino->value}»."
        );
    }

    public static function porque(string $mensaje): self
    {
        return new self($mensaje);
    }
}
