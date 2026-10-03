<?php

namespace App\Events;

use App\Models\OrdenCompra;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Una orden de compra llego al fin de su vida por el lado bueno: se recibio.
 *
 * <b>Por que existe este evento, si ya existe el de stock.</b> Toda recepcion
 * registra un movimiento de tipo 'compra', y ese movimiento emite
 * MovimientoStockRegistrado. Pero EvaluarReposicion filtra por
 * bajoElDisponible(), y una compra SUBE el disponible: el listener del restock
 * descarta el evento sin hacer nada. Es correcto que lo descarte —no tiene
 * sentido evaluar reposicion cada vez que entra mercaderia— pero deja un hueco.
 *
 * <b>El hueco.</b> Lo que habilita un pedido nuevo no es que entre mercaderia:
 * es que la orden deje de estar ABIERTA. Mientras figure como 'enviada' o
 * 'recibida_parcial', ReposicionService da el faltante por pedido y no genera
 * nada, lo cual es correcto porque el saldo viene en camino. En cuanto la orden
 * se cierra, el faltante que haya quedado descubierto vuelve a estar sin pedir
 * —y eso pasa en dos casos reales:
 *
 *   1. Se recibio todo, pero el faltante crecio despues de aprobar el pedido y
 *      lo recibido no alcanza. Es justo lo que avisa cubreElFaltante().
 *   2. Se cerro a mano con faltante, porque el proveedor avisa que el resto no
 *      viene. Ese camino ni siquiera mueve stock, asi que no hay ningun otro
 *      evento que se entere.
 *
 * Sin este aviso, los dos casos esperan al barrido de las 7 de la mañana, con
 * preventas ya pagadas trabadas toda la noche. Es el mismo razonamiento y el
 * mismo patron que OrdenCompraCancelada.
 */
class OrdenCompraCerrada
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly OrdenCompra $orden,
    ) {}

    /**
     * Los productos cuyo faltante hay que volver a evaluar.
     *
     * @return list<int>
     */
    public function productoIds(): array
    {
        return $this->orden->lineas()->pluck('producto_id')->unique()->values()->all();
    }
}
