<?php

namespace App\Listeners;

use App\Events\OrdenCompraCerrada;
use App\Models\Producto;
use App\Services\ReposicionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Cerrada la orden, el faltante que haya quedado vuelve a estar descubierto.
 *
 * Es el gemelo de ReponerPorCancelacion, y existe por el mismo motivo: hay
 * transiciones de la orden de compra que cambian lo que el restock ve y que el
 * listener reactivo no puede detectar. Cancelar no mueve stock; cerrar una
 * orden mueve stock para arriba, que es justo lo que EvaluarReposicion
 * descarta. En los dos casos, lo que cambia la situacion es que la orden deja
 * de contar como pedido abierto.
 *
 * <b>Por evento y no llamando al servicio desde RecepcionService.</b> Podria
 * llamarse directo —no hay dependencia circular por ese lado— pero
 * OrdenCompraService ya resolvio este mismo problema con un evento, y tener dos
 * formas distintas de disparar la misma reposicion en el mismo modulo es peor
 * que la linea de codigo que se ahorra.
 *
 * <b>$afterCommit.</b> La recepcion escribe cantidad_recibida, el movimiento de
 * stock y el estado de la orden en una sola transaccion. Si el job se encolara
 * al emitirse el evento y esa transaccion fallara, se evaluaria la reposicion
 * de una orden que quedo abierta: el restock la veria cerrada y pediria de
 * nuevo algo que ya estaba pedido.
 */
class ReponerPorCierre implements ShouldQueue
{
    /**
     * Misma conexion que el resto del circuito: corre en este proceso PHP,
     * despues de la respuesta, sin worker. Ver EvaluarReposicion para el
     * razonamiento completo y lo que se resigna.
     */
    public string $connection = 'deferred';

    /**
     * La misma cola propia que el resto del circuito de reposicion. Sin efecto
     * bajo 'deferred'; se conserva para poder volver a 'database' borrando una
     * linea.
     */
    public string $queue = 'reposicion';

    /** Se encola recien cuando la transaccion que lo origino hizo commit. */
    public bool $afterCommit = true;

    /** Sin efecto bajo 'deferred'. Se conservan junto con $queue. */
    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 60];

    public function __construct(
        private readonly ReposicionService $reposicion,
    ) {}

    public function handle(OrdenCompraCerrada $evento): void
    {
        // Releidos de la base y no tomados de la relacion cargada: entre el
        // cierre y este job pudo entrar cualquier otra cosa, y lo que importa
        // es el saldo de ahora. evaluarProducto() vuelve a refrescar bajo su
        // propio lock, pero igual conviene no arrastrar una coleccion vieja.
        $productos = Producto::query()
            ->whereIn('id', $evento->productoIds())
            ->get();

        foreach ($productos as $producto) {
            // Idempotente y barato: la enorme mayoria de las veces encuentra
            // que no hay nada que pedir y devuelve null.
            $this->reposicion->evaluarProducto($producto);
        }
    }

    /**
     * Que falle la reposicion no puede tumbar nada: la mercaderia ya entro y
     * la orden ya se cerro, que es la informacion que importa. Queda el log, y
     * el barrido diario vuelve a pasar por estos productos de todos modos.
     */
    public function failed(OrdenCompraCerrada $evento, Throwable $error): void
    {
        Log::error('No se pudo evaluar la reposición al cerrar el pedido.', [
            'orden_compra_id' => $evento->orden->id,
            'numero'          => $evento->orden->numero,
            'error'           => $error->getMessage(),
        ]);
    }
}
