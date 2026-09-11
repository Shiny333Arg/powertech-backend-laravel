<?php

namespace App\Exceptions;

use App\Enums\EstadoVenta;
use RuntimeException;

class TransicionInvalidaException extends RuntimeException
{
    public static function entre(EstadoVenta $desde, EstadoVenta $hasta): self
    {
        return new self(
            "Transicion invalida: una venta en estado '{$desde->value}' "
            . "no puede pasar a '{$hasta->value}'."
        );
    }
}
