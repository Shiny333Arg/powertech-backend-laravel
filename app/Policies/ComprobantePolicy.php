<?php

namespace App\Policies;

use App\Enums\EstadoComprobante;
use App\Models\Comprobante;
use App\Models\User;

/**
 * El comprobante es inmutable una vez que tiene CAE. No hay update ni delete
 * en ningun rol: toda correccion es una nota de credito.
 *
 * Lo unico accionable es reintentar la autorizacion de uno rechazado o que
 * quedo pendiente porque se corto la conexion con el organismo.
 */
class ComprobantePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tienePermiso('comprobante.ver');
    }

    public function view(User $user, Comprobante $comprobante): bool
    {
        return $user->tienePermiso('comprobante.ver');
    }

    public function reintentar(User $user, Comprobante $comprobante): bool
    {
        return $user->tienePermiso('comprobante.reintentar')
            && $comprobante->estado !== EstadoComprobante::Autorizado;
    }

    public function create(User $user): bool
    {
        // Se emite solo como consecuencia de acreditar un pago o de una
        // devolucion. No hay alta manual de comprobantes.
        return false;
    }

    public function update(User $user, Comprobante $comprobante): bool
    {
        return false;
    }

    public function delete(User $user, Comprobante $comprobante): bool
    {
        return false;
    }
}
