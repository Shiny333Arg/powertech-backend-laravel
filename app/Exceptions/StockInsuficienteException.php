<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * El movimiento dejaria un saldo por debajo de cero.
 *
 * Solo la pueden provocar los dos tipos que el modelo admite rechazar:
 * entrega (exige fisico y comprometido suficientes) y ajuste negativo.
 * La reserva NUNCA la lanza: comprometer sin stock es la preventa.
 */
class StockInsuficienteException extends RuntimeException
{
}
