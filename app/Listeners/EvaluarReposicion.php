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
    /**
     * Corre en este mismo proceso PHP, apenas la respuesta HTTP salio hacia el
     * navegador. <b>No hace falta worker ni una consola abierta.</b>
     *
     * Es el reemplazo natural de la conexion 'database' para este circuito: lo
     * que la reposicion necesitaba era no demorar la confirmacion de la venta,
     * no durabilidad. Nunca hizo falta que estos jobs sobrevivieran a un
     * reinicio ni que corrieran en otra maquina.
     *
     * <b>Lo que se resigna, y que lo cubre.</b> Un job que se pierde porque el
     * proceso murio entre la respuesta y su ejecucion no deja rastro en la
     * tabla jobs y no se reintenta. Lo recupera el barrido diario
     * (compras:revisar-reposicion), que para eso esta. Ese barrido necesita que
     * el scheduler corra: una tarea programada que ejecute
     * `php artisan schedule:run` cada minuto. Sin ella, esta conexion se queda
     * sin red.
     *
     * Para volver a un worker de verdad alcanza con borrar esta linea: $queue
     * sigue abajo, intacta.
     */
    public string $connection = 'deferred';

    /**
     * Cola propia: la reposicion nunca debe demorar mails ni facturacion.
     *
     * Sin efecto mientras $connection sea 'deferred' —esa conexion no tiene
     * colas— pero se conserva para que volver a 'database' sea borrar una
     * linea y no reconstruir esto.
     */
    public string $queue = 'reposicion';

    /**
     * Sin efecto bajo 'deferred': el job corre una sola vez, en el proceso que
     * lo despacho, y no vuelve a una cola. Se conservan por el mismo motivo que
     * $queue.
     */
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
