<?php

namespace App\Policies;

use App\Models\Empleado;
use App\Models\User;

/**
 * Sin metodo delete: un usuario NO se elimina nunca. De esta tabla cuelga toda
 * la trazabilidad —ventas registradas, cobros tomados, movimientos de stock
 * firmados, ordenes aprobadas—. El guard del modelo User rechaza el delete().
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

    /** Clave provisoria + cambio forzado. Es el circuito de clave olvidada. */
    public function resetearPassword(User $user, Empleado $empleado): bool
    {
        return $user->tienePermiso('empleado.forzar_password');
    }
}
