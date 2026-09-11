<?php

namespace App\Enums;

/**
 * Deltas de cada tipo de movimiento sobre los dos saldos registrados.
 * Es la tabla D.1 del modelo de datos, en codigo, para que no haya dos
 * versiones de la misma regla.
 *
 * NO existe un tipo `pago`: acreditar el cobro emite el comprobante y no
 * toca ningun saldo. Si el descuento del fisico colgara del cobro, una
 * preventa cobrada intentaria bajar el fisico por debajo de cero.
 */
enum TipoMovimientoStock: string
{
    case INICIAL     = 'inicial';
    case COMPRA      = 'compra';
    case RESERVA     = 'reserva';
    case ENTREGA     = 'entrega';
    case LIBERACION  = 'liberacion';
    case DEVOLUCION  = 'devolucion';
    case AJUSTE      = 'ajuste';

    /** Delta sobre stock_fisico para una cantidad positiva $q. */
    public function deltaFisico(int $q): int
    {
        return match ($this) {
            self::INICIAL, self::COMPRA, self::DEVOLUCION => $q,
            self::ENTREGA                                 => -$q,
            self::RESERVA, self::LIBERACION               => 0,
            self::AJUSTE => throw new \LogicException(
                'El ajuste lleva el signo explicito: usar deltaAjuste().'
            ),
        };
    }

    /** Delta sobre stock_comprometido para una cantidad positiva $q. */
    public function deltaComprometido(int $q): int
    {
        return match ($this) {
            self::RESERVA               => $q,
            self::ENTREGA, self::LIBERACION => -$q,
            default                     => 0,
        };
    }

    /**
     * Los dos unicos movimientos que el motor puede rechazar.
     * `reserva` nunca se rechaza: comprometer sin stock ES la preventa.
     */
    public function puedeSerRechazado(): bool
    {
        return in_array($this, [self::ENTREGA, self::AJUSTE], true);
    }

    public function requiereMotivo(): bool
    {
        return $this === self::AJUSTE;
    }
}
