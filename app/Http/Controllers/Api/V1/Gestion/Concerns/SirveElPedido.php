<?php

namespace App\Http\Controllers\Api\V1\Gestion\Concerns;

use App\Exceptions\TransicionNoPermitidaException;
use App\Http\Resources\Gestion\OrdenCompraDetalleResource;
use App\Models\OrdenCompra;
use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;

/**
 * Lo que comparten el controlador de consulta de pedidos y los de sus
 * transiciones: cómo se relee un pedido para serializarlo, y cómo se serializa
 * una transición contra las demás.
 *
 * <b>Por qué un concern y no un método repetido.</b> La lista de relaciones que
 * exige OrdenCompraDetalleResource estaba una vez, en el show de E.1. Con las
 * transiciones pasan a ser cuatro endpoints devolviendo el mismo detalle, y
 * cuatro copias de esa lista es la forma segura de que dentro de dos entregas
 * una de ellas se olvide de 'hijas.proveedor' y el reparto aparezca vacío en un
 * solo endpoint.
 */
trait SirveElPedido
{
    /**
     * Relee el pedido con todo lo que el detalle serializa.
     *
     * Son las mismas relaciones que carga el show del panel. El proveedor va
     * completo y no con select acotado porque el detalle muestra su contacto y
     * su casilla; las hijas y el padre van siempre, aunque estén vacíos, para
     * que la respuesta tenga la misma forma sea cual sea la forma del documento.
     *
     * El refresh() no es opcional después de una transición: el servicio escribe
     * con fill()->save() y con increment(), y las hijas del reparto nacen dentro
     * de la transacción, así que el modelo que trajo el route model binding está
     * viejo en cuanto la operación termina.
     */
    protected function recargar(OrdenCompra $orden): OrdenCompra
    {
        return $orden->refresh()->load([
            'proveedor',
            'lineas.producto',
            'lineas.proveedor',
            'usuarioCreo',
            'usuarioAprobo',
            'hijas.proveedor',
            'padre:id,numero',

            // El historial de entregas de mercadería, desde E.2.b. No hay tabla
            // de recepciones: cada entrada ES un movimiento de stock con origen
            // en esta orden y el remito del proveedor en 'motivo'.
            //
            // Se carga SIEMPRE, aunque el pedido esté en borrador y no tenga
            // ninguna: la respuesta del detalle tiene la misma forma sea cual
            // sea el estado del documento, igual que 'hijas' y 'padre'. En un
            // pedido sin entregas cuesta una consulta que devuelve cero filas.
            //
            // 'nombres,apellido' y no 'usuario': MovimientoStockResource arma el
            // nombre con el accessor nombre_completo, que sale de esas dos
            // columnas. Es el mismo select que usa AjustarStockController.
            'recepciones.producto:id,codigo,nombre',
            'recepciones.usuario:id,nombres,apellido',
        ]);
    }

    /** El detalle completo del pedido, listo para devolver. */
    protected function detalle(OrdenCompra $orden): OrdenCompraDetalleResource
    {
        return new OrdenCompraDetalleResource($this->recargar($orden));
    }

    /**
     * Corre una transición con el pedido tomado, y con el estado releído.
     *
     * <b>Las dos cosas, y ninguna alcanza sola.</b>
     *
     * El lock resuelve la carrera: ni aprobar() ni enviar() bloquean la fila de
     * la orden —a diferencia de RecepcionService::registrar(), que sí lo hace—,
     * así que dos peticiones simultáneas pasan las dos por la policy, las dos
     * leen 'borrador' y las dos reparten. Hoy eso lo frena el índice único de
     * 'numero' cuando la segunda intenta insertar OC-0000042/1, pero sale como
     * un error del motor —un 500— en vez de como lo que es. Y en el envío no
     * hay índice que lo frene: el proveedor recibe dos veces el mismo remito.
     *
     * El refresh() resuelve lo que el lock solo pospone: la segunda petición
     * entra cuando la primera soltó, pero con el modelo que le dio el route
     * model binding, que sigue diciendo 'borrador'. Sin releer, la serialización
     * no evita nada: aprobaría de nuevo un pedido ya aprobado. Releído, el
     * servicio encuentra el estado real y rechaza la transición, que es
     * exactamente lo que tiene que pasar.
     *
     * Es el mismo patrón —y el mismo almacén— que ya usan
     * OrdenCompraService::pedir() y ReposicionService para no abrir dos
     * borradores del mismo proveedor a la vez.
     *
     * <b>Por qué el timeout sale como 422 y no como 503.</b> El pedido es
     * entendible y el sistema está sano; lo que pasa es que su estado no admite
     * atenderlo ahora mismo. Es la definición del 422 que fijó bootstrap/app.php
     * para TransicionNoPermitidaException, y le ahorra al cliente un código más
     * que tratar.
     *
     * @template T
     *
     * @param  Closure(OrdenCompra): T  $operacion  Recibe el pedido ya releído.
     * @param  int  $segundos  Vida del candado. 30 alcanza para mover el
     *                         documento; la recepción pide 60 — ver abajo.
     * @return T
     *
     * @throws TransicionNoPermitidaException
     */
    protected function enTransicion(OrdenCompra $orden, Closure $operacion, int $segundos = 30): mixed
    {
        // 30 segundos de vida para el candado y 10 de espera: el envío manda un
        // correo sincrónico, que es lo más lento de mover el documento, y una
        // madre con varios proveedores manda uno por cada uno.
        //
        // <b>La recepción pide más.</b> Retiene el candado durante la
        // liberación de preventas, que corre después del commit y recorre la
        // cola de ventas de cada producto que entró: con muchos renglones y
        // mucha preventa, 30 segundos pueden quedar cortos y el candado vencería
        // con el trabajo todavía en curso. Por eso el TTL es un parámetro y no
        // una constante.
        $candado = Cache::lock("orden-compra:transicion:{$orden->getKey()}", $segundos);

        try {
            return $candado->block(10, function () use ($orden, $operacion) {
                $orden->refresh();

                return $operacion($orden);
            });
        } catch (LockTimeoutException) {
            throw TransicionNoPermitidaException::porque(
                "Hay otra operación en curso sobre el pedido {$orden->numero}. Esperá unos segundos y reintentá."
            );
        }
    }
}
