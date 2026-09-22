<?php

namespace App\Policies;

use App\Models\Empleado;
use App\Models\User;

/**
 * Sin metodo delete: un usuario NO se elimina nunca. De esta tabla cuelga toda
 * la trazabilidad —ventas registradas, cobros tomados, movimientos de stock
 * firmados, ordenes aprobadas—. El guard del modelo User rechaza el delete().
 *
 * Tampoco hay nada sobre contraseñas: el administrativo ya no las asigna ni
 * las resetea. Cada empleado elige la suya desde la invitacion.
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

    /**
     * Reenviar la invitacion es terminar un alta que quedo a medias, asi que
     * lo puede hacer quien puede dar de alta. No necesita permiso propio.
     */
    public function reenviarInvitacion(User $user, Empleado $empleado): bool
    {
        return $user->tienePermiso('empleado.crear');
    }
}
