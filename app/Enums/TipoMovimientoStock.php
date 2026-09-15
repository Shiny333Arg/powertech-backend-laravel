<?php

namespace App\Enums;

use App\Enums\Concerns\ConEtiqueta;

/**
 * Los deltas de cada tipo, tal como los fija el modelo de datos (D.1):
 *
 *   inicial     +q /  0      compra      +q /  0
 *   reserva      0 / +q      entrega     -q / -q
 *   liberacion   0 / -q      devolucion  +q /  0
 *   ajuste      +-q /  0
 *
 * NO existe un movimiento de tipo pago: acreditar solo emite el comprobante.
 */
enum TipoMovimientoStock: string
{
    use ConEtiqueta;

    case Inicial    = 'inicial';
    case Compra     = 'compra';
    case Reserva    = 'reserva';
    case Entrega    = 'entrega';
    case Liberacion = 'liberacion';
    case Devolucion = 'devolucion';
    case Ajuste     = 'ajuste';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Inicial    => 'Carga inicial',
            self::Compra     => 'Recepción de compra',
            self::Reserva    => 'Reserva por venta confirmada',
            self::Entrega    => 'Entrega de mercadería',
            self::Liberacion => 'Liberación de reserva',
            self::Devolucion => 'Devolución',
            self::Ajuste     => 'Ajuste de inventario',
        };
    }

    /**
     * Signo del delta sobre el stock fisico.
     * En ajuste devuelve 1: el signo lo trae la cantidad, que puede ser negativa.
     */
    public function signoFisico(): int
    {
        return match ($this) {
            self::Inicial, self::Compra, self::Devolucion, self::Ajuste => 1,
            self::Entrega    => -1,
            self::Reserva, self::Liberacion => 0,
        };
    }

    /** Signo del delta sobre el stock comprometido. */
    public function signoComprometido(): int
    {
        return match ($this) {
            self::Reserva    => 1,
            self::Entrega, self::Liberacion => -1,
            default          => 0,
        };
    }

    /**
     * Los unicos dos que el motor puede rechazar: entrega exige fisico y
     * comprometido suficientes, y un ajuste negativo no puede dejar el fisico
     * bajo cero. El resto siempre es valido: reserva puede comprometer sin
     * stock, y eso es exactamente la preventa.
     */
    public function puedeSerRechazado(): bool
    {
        return $this->es(self::Entrega, self::Ajuste);
    }

    /** motivo es obligatorio solo en ajuste; en el resto lo explica el documento de origen. */
    public function exigeMotivo(): bool
    {
        return $this === self::Ajuste;
    }
}
