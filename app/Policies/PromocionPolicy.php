<?php

namespace App\Policies;

use App\Models\Promocion;
use App\Models\User;

/**
 * Sin metodo delete: la promocion esta referenciada por promociones_aplicadas,
 * que es lo que explica por que una linea de venta salio mas barata. Solo baja
 * logica; el guard del modelo rechaza el delete().
 */
class PromocionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tienePermiso('promocion.ver');
    }

    public function view(User $user, Promocion $promocion): bool
    {
        return $user->tienePermiso('promocion.ver');
    }

    public function create(User $user): bool
    {
        return $user->tienePermiso('promocion.crear');
    }

    public function update(User $user, Promocion $promocion): bool
    {
        return $user->tienePermiso('promocion.editar');
    }

    public function cambiarEstado(User $user, Promocion $promocion): bool
    {
        return $user->tienePermiso('promocion.baja');
    }
}
