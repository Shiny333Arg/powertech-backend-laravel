<?php

namespace App\Services\Pagos;

use App\Models\Carrito;

/**
 * La referencia que viaja a la pasarela para saber a que volver.
 *
 * <b>Es el carrito y no la venta, y eso no es un detalle.</b> La venta online
 * nace `pagada`: cuando el cliente paga, la venta todavia no existe, asi que
 * no hay nada mas a que apuntar. Cuando el pago vuelve —por el retorno del
 * navegador o por el webhook, que no tiene sesion— esta referencia es lo
 * unico que dice de que carrito se trata.
 *
 * El formato se define en un solo lugar a proposito: lo escribe la pasarela
 * al crear la preferencia y lo lee el controlador al recibir el aviso. Si
 * cada uno lo armara por su cuenta, un cambio de formato rompe el webhook en
 * silencio y los pagos quedan sin pedido.
 */
final class ReferenciaCarrito
{
    private const PREFIJO = 'carrito';

    public static function de(Carrito $carrito): string
    {
        return self::PREFIJO.':'.$carrito->getKey();
    }

    /** Id del carrito, o null si la referencia no es nuestra o esta mal armada. */
    public static function carritoId(?string $referencia): ?int
    {
        if (! $referencia) {
            return null;
        }

        [$prefijo, $id] = array_pad(explode(':', $referencia, 2), 2, null);

        if ($prefijo !== self::PREFIJO || ! ctype_digit((string) $id)) {
            return null;
        }

        return (int) $id;
    }
}
