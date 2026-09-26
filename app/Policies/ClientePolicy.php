<?php

namespace App\Policies;

use App\Models\Cliente;
use App\Models\User;

/**
 * El cliente se da de baja, nunca se elimina: esta referenciado por ventas y
 * comprobantes emitidos. `delete()` devuelve false siempre y el guard del
 * modelo (ProtegeContraBorrado) es la segunda capa.
 */
class ClientePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tienePermiso('cliente.ver');
    }

    public function view(User $user, Cliente $cliente): bool
    {
        return $user->tienePermiso('cliente.ver');
    }

    public function create(User $user): bool
    {
        return $user->tienePermiso('cliente.crear');
    }

    public function update(User $user, Cliente $cliente): bool
    {
        return $user->tienePermiso('cliente.editar');
    }

    /** Baja logica: activo = false. Es reversible con la misma clave. */
    public function baja(User $user, Cliente $cliente): bool
    {
        return $user->tienePermiso('cliente.baja');
    }

    public function delete(User $user, Cliente $cliente): bool
    {
        return false;
    }

    public function forceDelete(User $user, Cliente $cliente): bool
    {
        return false;
    }
}
