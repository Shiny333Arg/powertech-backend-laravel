<?php

namespace App\Listeners;

use App\Events\OrdenCompraCancelada;
use App\Services\ReposicionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * El tercer camino de la reposicion: cancelar un pedido vuelve a descubrir el
 * faltante que ese pedido cubria, y hay que pedirlo de nuevo.
 *
 * <b>Por que hace falta.</b> Los otros dos caminos no ven este caso. El
 * reactivo escucha movimientos de stock, y cancelar no mueve ninguno: la
 * mercaderia nunca llego, el disponible no cambia. El barrido lo ve, pero
 * recien a las 7 de la manana siguiente. En el medio queda un producto en
 * preventa, con clientes que ya pagaron, esperando sin que nada lo pida —
 * porque la unica orden que lo iba a cubrir dejo de existir.
 *
 * Reponer acá no es una optimizacion: es el momento exacto en que el faltante
 * aparece.
 *
 * <b>Si el producto se discontinuo</b>, esto lo va a volver a pedir en el acto.
 * El freno correcto es apagarle 'reposicion_automatica' al producto ANTES de
 * cancelar la orden, no esperar que el sistema adivine por que se cancelo. El
 * dialogo de cancelacion lo advierte.
 */
class ReponerPorCancelacion implements ShouldQueue
{
    /**
     * Misma conexion que el resto del circuito: corre en este proceso PHP,
     * despues de la respuesta, sin worker. Ver EvaluarReposicion para el
     * razonamiento completo y lo que se resigna.
     */
    public string $connection = 'deferred';

    /**
     * Misma cola propia que el resto de la reposicion. Sin efecto bajo
     * 'deferred'; se conserva para poder volver a 'database' borrando una linea.
     */
    public string $queue = 'reposicion';

    /** Por si alguna vez la cancelacion queda envuelta en una transaccion mayor. */
    public bool $afterCommit = true;

    /** Sin efecto bajo 'deferred'. Se conservan junto con $queue. */
    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 60];

    public function __construct(
        private readonly ReposicionService $reposicion,
    ) {}

    public function handle(OrdenCompraCancelada $evento): void
    {
        // Varios productos del mismo proveedor terminan en UN solo borrador
        // nuevo, no en uno por producto: pedir() busca el borrador abierto del
        // proveedor antes de abrir otro. Un pedido cancelado con ocho renglones
        // se reemplaza por un pedido con ocho renglones.
        foreach ($evento->productos() as $producto) {
            // evaluarProducto() es el que decide. Si el producto ya no esta
            // bajo su umbral, si no tiene reposicion automatica, si no tiene
            // proveedor, o si otra orden abierta lo cubre, no hace nada. Acá
            // solo se le da la oportunidad de mirar.
            $this->reposicion->evaluarProducto($producto);
        }
    }

    /**
     * Que falle no puede dejar la cancelacion a medias: la orden ya esta
     * cancelada y eso es lo que el usuario pidio. Queda el log, y el barrido
     * diario pasa igual por estos productos.
     */
    public function failed(OrdenCompraCancelada $evento, Throwable $error): void
    {
        Log::error('No se pudo reponer tras cancelar una orden de compra.', [
            'orden_compra_id' => $evento->orden->id,
            'numero'          => $evento->orden->numero,
            'error'           => $error->getMessage(),
        ]);
    }
}
