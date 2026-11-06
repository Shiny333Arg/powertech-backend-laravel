<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Falla al hablar con la pasarela de pago.
 *
 * Es un error de infraestructura, no de dominio: la pasarela no contesto,
 * contesto un error, o contesto algo que no se puede interpretar. Se separa
 * de TransicionNoPermitidaException —que significa "el circuito no admite
 * esto"— porque las dos se tratan distinto: una transicion no permitida es
 * culpa del pedido y se le explica al cliente; esto es culpa nuestra o de
 * Mercado Pago y hay que loguearlo.
 */
class PasarelaPagoException extends RuntimeException
{
    public static function porque(string $motivo): self
    {
        return new self($motivo);
    }
}
