<?php

namespace App\Policies;

use App\Models\Categoria;
use App\Models\User;

/**
 * Traduce las claves del catalogo de permisos a las acciones de la pantalla.
 * Lo que no esta asignado al rol, no se puede hacer.
 *
 * TODAVIA NO ESTA CONECTADA: sin login no hay $user y todo daria 403. Cuando
 * exista la autenticacion, alcanza con descomentar la linea authorizeResource
 * del controlador y registrar la policy (automatico si respetás el nombre
 * App\Policies\CategoriaPolicy).
 */
class CategoriaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tienePermiso('categoria.ver');
    }

    public function view(User $user, Categoria $categoria): bool
    {
        return $user->tienePermiso('categoria.ver');
    }

    public function create(User $user): bool
    {
        return $user->tienePermiso('categoria.crear');
    }

    public function update(User $user, Categoria $categoria): bool
    {
        return $user->tienePermiso('categoria.editar');
    }

    /** Baja logica: desactivar y reactivar son la misma decision. */
    public function cambiarEstado(User $user, Categoria $categoria): bool
    {
        return $user->tienePermiso('categoria.baja');
    }

    /** Borrado fisico. Permiso distinto del de baja, a proposito. */
    public function delete(User $user, Categoria $categoria): bool
    {
        return $user->tienePermiso('categoria.eliminar');
    }
}
