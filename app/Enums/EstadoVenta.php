<?php

namespace App\Enums;

/**
 * Circuito de estados de la venta (H.1 del modelo de datos).
 *
 *   presupuesto -> confirmada -> pagada --+-> en_preparacion -> despachada   -> entregada
 *        |             ^                  |                  \ lista_retiro  -> entregada
 *     vencida    (validacion              +-> pendiente_stock ----+
 *                 del vendedor)               (preventa: espera reposicion)
 *
 * La venta online nace directamente en `confirmada`, esperando el pago.
 * En mostrador con retiro inmediato, `pagada -> entregada` es valida y directa.
 *
 * ESTE ENUM NO TIENE UN ESTADO "ELIMINADA". Una venta no se elimina de
 * ninguna forma: su fin de vida es `cancelada`, `vencida`, `devuelta_parcial`
 * o `devuelta`. Ver Venta::booted().
 */
enum EstadoVenta: string
{
    case PRESUPUESTO      = 'presupuesto';
    case CONFIRMADA       = 'confirmada';
    case PAGADA           = 'pagada';
    case PENDIENTE_STOCK  = 'pendiente_stock';
    case EN_PREPARACION   = 'en_preparacion';
    case DESPACHADA       = 'despachada';
    case LISTA_RETIRO     = 'lista_retiro';
    case ENTREGADA        = 'entregada';
    case DEVUELTA_PARCIAL = 'devuelta_parcial';
    case DEVUELTA         = 'devuelta';
    case VENCIDA          = 'vencida';
    case CANCELADA        = 'cancelada';

    /**
     * Transiciones permitidas desde este estado.
     *
     * `cancelada` solo se admite antes de la facturacion. Una vez emitido el
     * comprobante la reversion es `devuelta` / `devuelta_parcial`, porque
     * requiere nota de credito: cancelar una venta facturada dejaria un CAE
     * autorizado sin contrapartida.
     */
    public function transicionesValidas(): array
    {
        return match ($this) {
            self::PRESUPUESTO     => [self::CONFIRMADA, self::VENCIDA, self::CANCELADA],
            self::CONFIRMADA      => [self::PAGADA, self::CANCELADA],
            self::PAGADA          => [self::EN_PREPARACION, self::PENDIENTE_STOCK, self::ENTREGADA],
            self::PENDIENTE_STOCK => [self::EN_PREPARACION],
            self::EN_PREPARACION  => [self::DESPACHADA, self::LISTA_RETIRO],
            self::DESPACHADA      => [self::ENTREGADA],
            self::LISTA_RETIRO    => [self::ENTREGADA],
            self::ENTREGADA       => [self::DEVUELTA_PARCIAL, self::DEVUELTA],
            self::DEVUELTA_PARCIAL => [self::DEVUELTA],
            self::DEVUELTA, self::VENCIDA, self::CANCELADA => [],
        };
    }

    public function puedeTransicionarA(self $destino): bool
    {
        return in_array($destino, $this->transicionesValidas(), true);
    }

    /** Estados de los que ya no se sale. */
    public function esFinal(): bool
    {
        return $this->transicionesValidas() === [];
    }

    /** El presupuesto no compromete stock ni emite comprobante. */
    public function esPresupuesto(): bool
    {
        return $this === self::PRESUPUESTO;
    }

    /**
     * Estados en los que las unidades estan comprometidas y todavia no
     * salieron del deposito. Es lo que hay que liberar al cancelar.
     */
    public function tieneUnidadesComprometidas(): bool
    {
        return in_array($this, [
            self::CONFIRMADA, self::PAGADA, self::PENDIENTE_STOCK,
            self::EN_PREPARACION, self::LISTA_RETIRO,
        ], true);
    }

    /**
     * Movimiento de stock que genera la transicion $desde -> $hasta, o null
     * si no mueve stock.
     *
     * No alcanza con mirar el estado de destino, por dos razones:
     *
     *  - La ENTREGA se dispara cuando la mercaderia sale del deposito, y eso
     *    depende del modo: en un envio es el despacho, en un retiro es el
     *    momento en que el cliente se lleva el producto. Un `despachada ->
     *    entregada` es la confirmacion de que llego, y no vuelve a descontar.
     *  - La LIBERACION solo corresponde si habia unidades comprometidas.
     *    Cancelar un presupuesto no libera nada, porque nunca comprometio.
     *
     * `pagada` devuelve null a proposito: el cobro emite la factura y no toca
     * ningun saldo.
     */
    public static function movimientoDeTransicion(
        self $desde,
        self $hasta,
        ModoEntrega $modo
    ): ?TipoMovimientoStock {
        return match (true) {
            $hasta === self::CONFIRMADA => TipoMovimientoStock::RESERVA,

            // Salida real del deposito, una sola vez por venta.
            $hasta === self::DESPACHADA && $modo === ModoEntrega::ENVIO
                => TipoMovimientoStock::ENTREGA,
            $hasta === self::ENTREGADA && $modo === ModoEntrega::RETIRO
                => TipoMovimientoStock::ENTREGA,

            // Reversion antes de la entrega.
            in_array($hasta, [self::CANCELADA, self::VENCIDA], true)
                => $desde->tieneUnidadesComprometidas() ? TipoMovimientoStock::LIBERACION : null,

            // Reingreso por nota de credito.
            in_array($hasta, [self::DEVUELTA, self::DEVUELTA_PARCIAL], true)
                => TipoMovimientoStock::DEVOLUCION,

            default => null,
        };
    }

    /** Cola que alimenta el dashboard del administrativo. */
    public static function pendientesDeEntrega(): array
    {
        return [self::PENDIENTE_STOCK, self::EN_PREPARACION, self::LISTA_RETIRO, self::DESPACHADA];
    }
}
