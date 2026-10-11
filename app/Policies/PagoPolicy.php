<?php

namespace App\Policies;

use App\Models\Pago;
use App\Models\User;

/**
 * Un pago no se edita ni se borra: si se cobro de menos se agrega otro pago, y
 * si se cobro de mas se devuelve con nota de credito. Por eso solo hay lectura
 * y alta.
 */
class PagoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tienePermiso('venta.cobrar') || $user->tienePermiso('venta.ver_todas');
    }

    /** El cajero ve su propio arqueo; el administrativo ve el de todos. */
    public function view(User $user, Pago $pago): bool
    {
        return $user->tienePermiso('venta.ver_todas') || $pago->usuario_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->tienePermiso('venta.cobrar');
    }

    public function update(User $user, Pago $pago): bool
    {
        return false;
    }

    public function delete(User $user, Pago $pago): bool
    {
        return false;
    }
}
