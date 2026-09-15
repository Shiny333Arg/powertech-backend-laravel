<?php

namespace App\Enums;

use App\Enums\Concerns\ConEtiqueta;

/**
 * Circuito de la venta (Modelo de datos H.1):
 *
 *   presupuesto -> confirmada -> pagada -+-> en_preparacion -> despachada   -> entregada
 *        |             ^                 |                  \ lista_retiro -> entregada
 *     vencida     (validacion)           +-> pendiente_stock --+
 *
 * La venta NO se da de baja ni se elimina: su fin de vida es un estado.
 */
enum EstadoVenta: string
{
    use ConEtiqueta;

    case Presupuesto     = 'presupuesto';
    case Confirmada      = 'confirmada';
    case Pagada          = 'pagada';
    case PendienteStock  = 'pendiente_stock';
    case EnPreparacion   = 'en_preparacion';
    case Despachada      = 'despachada';
    case ListaRetiro     = 'lista_retiro';
    case Entregada       = 'entregada';
    case DevueltaParcial = 'devuelta_parcial';
    case Devuelta        = 'devuelta';
    case Vencida         = 'vencida';
    case Cancelada       = 'cancelada';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Presupuesto     => 'Presupuesto',
            self::Confirmada      => 'Confirmada',
            self::Pagada          => 'Pagada',
            self::PendienteStock  => 'Pendiente de stock',
            self::EnPreparacion   => 'En preparación',
            self::Despachada      => 'Despachada',
            self::ListaRetiro     => 'Lista para retirar',
            self::Entregada       => 'Entregada',
            self::DevueltaParcial => 'Devuelta parcialmente',
            self::Devuelta        => 'Devuelta',
            self::Vencida         => 'Vencida',
            self::Cancelada       => 'Cancelada',
        };
    }

    /**
     * Transiciones admitidas. pagada -> entregada existe a proposito: en el
     * mostrador con retiro inmediato el cliente se lleva el producto en el
     * acto, y los estados intermedios son del circuito logistico, no un
     * tramite obligatorio.
     *
     * @return list<self>
     */
    public function transicionesPosibles(): array
    {
        return match ($this) {
            self::Presupuesto     => [self::Confirmada, self::Vencida, self::Cancelada],
            self::Confirmada      => [self::Pagada, self::Cancelada],
            self::Pagada          => [self::EnPreparacion, self::PendienteStock, self::Entregada, self::Cancelada],
            self::PendienteStock  => [self::EnPreparacion, self::Cancelada],
            self::EnPreparacion   => [self::Despachada, self::ListaRetiro, self::Cancelada],
            self::Despachada      => [self::Entregada],
            self::ListaRetiro     => [self::Entregada, self::Cancelada],
            self::Entregada       => [self::DevueltaParcial, self::Devuelta],
            self::DevueltaParcial => [self::DevueltaParcial, self::Devuelta],
            self::Devuelta, self::Vencida, self::Cancelada => [],
        };
    }

    public function puedeTransicionarA(self $destino): bool
    {
        return in_array($destino, $this->transicionesPosibles(), true);
    }

    /**
     * Hay unidades comprometidas que todavia no salieron del deposito.
     * Despachada ya no: el movimiento de entrega corrio al despachar.
     */
    public function comprometeStock(): bool
    {
        return $this->es(
            self::Confirmada,
            self::Pagada,
            self::PendienteStock,
            self::EnPreparacion,
            self::ListaRetiro,
        );
    }

    /** Sin transiciones salientes. */
    public function esTerminal(): bool
    {
        return $this->transicionesPosibles() === [];
    }

    /**
     * Fin de vida de la venta. No es una baja: cada uno de estos estados tiene
     * un efecto distinto sobre el stock, cosa que un flag activo no expresaria.
     */
    public function esFinDeVida(): bool
    {
        return $this->es(self::Cancelada, self::Vencida, self::DevueltaParcial, self::Devuelta);
    }

    public function permiteCancelar(): bool
    {
        return $this->puedeTransicionarA(self::Cancelada);
    }

    /** Estado siguiente al acreditarse el pago, segun haya o no stock fisico. */
    public static function trasElPago(bool $hayStockFisico): self
    {
        return $hayStockFisico ? self::EnPreparacion : self::PendienteStock;
    }
}
