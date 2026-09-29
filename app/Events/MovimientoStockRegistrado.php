<?php

namespace App\Events;

use App\Models\MovimientoStock;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Se emite una vez por cada fila de movimientos_stock, sin excepcion.
 *
 * Es deliberadamente tonto: no sabe nada de reposicion. Anuncia que el saldo
 * de un producto cambio y deja que cada oyente decida si le interesa. Si
 * manana hay que avisarle a la tienda que un producto volvio a tener stock,
 * se engancha otro listener acá y StockService no se entera.
 *
 * El movimiento viaja entero porque trae los dos deltas con signo, y con eso
 * alcanza para saber si el disponible subio o bajo sin volver a la base.
 */
class MovimientoStockRegistrado
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly MovimientoStock $movimiento,
    ) {}

    /**
     * Cuanto se movio el DISPONIBLE, que es fisico - comprometido.
     *
     * Vale la pena tenerlo acá y no en cada oyente porque es la unica cuenta
     * que explica por que una entrega no dispara nada: baja el fisico y el
     * comprometido en la misma cantidad, asi que el disponible no se mueve.
     *
     *   reserva     0 - (+q) = -q   baja
     *   ajuste -q  -q - 0    = -q   baja
     *   entrega    -q - (-q) =  0   sin cambio
     *   compra     +q - 0    = +q   sube
     *   liberacion  0 - (-q) = +q   sube
     */
    public function deltaDisponible(): int
    {
        return $this->movimiento->cantidad_fisico - $this->movimiento->cantidad_comprometido;
    }

    public function bajoElDisponible(): bool
    {
        return $this->deltaDisponible() < 0;
    }
}
