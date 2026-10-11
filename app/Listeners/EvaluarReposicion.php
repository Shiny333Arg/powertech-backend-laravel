<?php

namespace App\Listeners;

use App\Events\MovimientoStockRegistrado;
use App\Services\ReposicionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * El camino reactivo del restock: un saldo bajo, se evalua ese producto.
 *
 * <b>$afterCommit = true no es un detalle de performance.</b> Una venta
 * confirmada registra sus reservas dentro de una transaccion que todavia puede
 * fallar. Si el job se encolara al emitirse el evento y esa transaccion hiciera
 * rollback, quedaria una orden de compra pedida por un faltante que nunca
 * existio, con un numero de la serie ya consumido. Encolar despues del commit
 * es lo que garantiza que solo se reacciona a hechos firmes.
 *
 * <b>Por que se filtra acá y no en el servicio.</b> La mayoria de los
 * movimientos no puede bajar el disponible —una entrega lo deja igual, una
 * recepcion lo sube— y despachar un job por cada uno significaria una consulta
 * de ordenes abiertas por cada movimiento del sistema. El filtro es una resta.
 */
class EvaluarReposicion implements ShouldQueue
{
    /** Se encola recien cuando la transaccion que lo origino hizo commit. */
    public bool $afterCommit = true;

    /** Cola propia: la reposicion nunca debe demorar mails ni facturacion. */
    public string $queue = 'reposicion';

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 60];

    public function __construct(
        private readonly ReposicionService $reposicion,
    ) {}

    public function handle(MovimientoStockRegistrado $evento): void
    {
        // Solo bajar el disponible puede cruzar el umbral hacia abajo.
        // Ver MovimientoStockRegistrado::deltaDisponible() para la cuenta.
        if (! $evento->bajoElDisponible()) {
            return;
        }

        $producto = $evento->movimiento->producto;

        if ($producto === null) {
            return;
        }

        $this->reposicion->evaluarProducto($producto);
    }

    /**
     * Que la reposicion falle no puede tumbar nada: el movimiento de stock ya
     * esta registrado y es la informacion que importa. Queda el log, y el
     * barrido diario vuelve a pasar por este producto de todos modos. Esa
     * redundancia es justamente para lo que esta.
     */
    public function failed(MovimientoStockRegistrado $evento, Throwable $error): void
    {
        Log::error('No se pudo evaluar la reposición automática.', [
            'movimiento_id' => $evento->movimiento->id,
            'producto_id'   => $evento->movimiento->producto_id,
            'error'         => $error->getMessage(),
        ]);
    }
}
