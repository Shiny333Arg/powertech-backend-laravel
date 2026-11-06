<?php

namespace App\Services\Pagos;

/**
 * Una preferencia de pago creada en la pasarela: a donde mandar al cliente y
 * con que id se la vuelve a encontrar.
 *
 * No tiene nada del dominio adentro. Es el resultado de "pedile a la pasarela
 * un link de pago por tanto dinero", y lo unico que el checkout hace con esto
 * es guardar el id y redirigir.
 */
final readonly class PreferenciaPago
{
    public function __construct(
        /** Id de la preferencia en la pasarela. Se guarda en carritos. */
        public string $id,
        /** URL a la que se redirige al cliente para pagar. */
        public string $urlPago,
    ) {
    }
}
