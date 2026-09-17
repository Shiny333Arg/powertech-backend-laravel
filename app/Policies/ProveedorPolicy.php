<?php

namespace App\Policies;

use App\Models\Proveedor;
use App\Models\User;

/**
 * Sin metodo delete: el proveedor no se elimina, esta referenciado por
 * productos y por ordenes de compra. El guard del modelo rechaza el delete().
 */
class ProveedorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tienePermiso('proveedor.ver');
    }

    public function view(User $user, Proveedor $proveedor): bool
    {
        return $user->tienePermiso('proveedor.ver');
    }

    public function create(User $user): bool
    {
        return $user->tienePermiso('proveedor.crear');
    }

    public function update(User $user, Proveedor $proveedor): bool
    {
        return $user->tienePermiso('proveedor.editar');
    }

    public function cambiarEstado(User $user, Proveedor $proveedor): bool
    {
        return $user->tienePermiso('proveedor.baja');
    }
}
