<?php

namespace App\Exceptions;

use App\Enums\EstadoOrdenCompra;
use App\Enums\EstadoVenta;
use DomainException;

/**
 * El circuito de estados de la venta es una regla de negocio, no una
 * restriccion de integridad: el motor no puede rechazarla. Esta excepcion es
 * la que convierte un intento invalido en un error del dominio con mensaje
 * propio, en lugar de un update silencioso que deja la venta en un estado
 * imposible.
 *
 * <b>Sirve a los dos circuitos de estados del sistema</b>, el de la venta y el
 * del pedido a proveedor. OrdenCompraService::exigirTransicion() y
 * RecepcionService::transicionar() ya llamaban a entre() con un
 * EstadoOrdenCompra desde que existen, pero la firma solo aceptaba EstadoVenta:
 * un enum no se convierte en otro, asi que ese camino terminaba en TypeError
 * —un 500— en lugar de en el 422 del dominio. No se notaba porque las policies
 * rechazan antes casi todas las transiciones invalidas; aparece en cuanto dos
 * peticiones compiten por el mismo pedido y la segunda llega con el estado ya
 * cambiado. Se corrige ampliando la firma, sin tocar a ningun llamador.
 */
class TransicionNoPermitidaException extends DomainException
{
    public static function entre(
        EstadoVenta|EstadoOrdenCompra $actual,
        EstadoVenta|EstadoOrdenCompra $destino,
    ): self {
        // El documento se nombra por el enum que llega: decirle «venta» a un
        // pedido a proveedor manda al administrativo a buscar el problema a la
        // pantalla equivocada. El texto de las ventas no cambia.
        $documento = $actual instanceof EstadoOrdenCompra ? 'Un pedido' : 'Una venta';

        return new self(
            "{$documento} en estado «{$actual->value}» no puede pasar a «{$destino->value}»."
        );
    }

    public static function porque(string $mensaje): self
    {
        return new self($mensaje);
    }
}
