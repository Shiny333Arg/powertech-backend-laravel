<?php

namespace App\Policies;

use App\Models\Empleado;
use App\Models\User;

/**
 * as
 */
class EmpleadoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tienePermiso('empleado.ver');
    }

    public function view(User $user, Empleado $empleado): bool
    {
        return $user->tienePermiso('empleado.ver');
    }

    public function create(User $user): bool
    {
        return $user->tienePermiso('empleado.crear');
    }

    public function update(User $user, Empleado $empleado): bool
    {
        return $user->tienePermiso('empleado.editar');
    }

    public function cambiarEstado(User $user, Empleado $empleado): bool
    {
        return $user->tienePermiso('empleado.baja');
    }

    public function reenviarInvitacion(User $user, Empleado $empleado): bool
    {
        return $user->tienePermiso('empleado.crear');
    }
}
