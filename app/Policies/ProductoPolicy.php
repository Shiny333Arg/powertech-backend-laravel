<?php

namespace App\Policies;

use App\Models\Producto;
use App\Models\User;

/**
 * No tiene metodo delete: el producto NO se elimina nunca. El guard del modelo
 * rechaza cualquier delete() antes de que la autorizacion llegue a opinar.
 */
class ProductoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tienePermiso('producto.ver');
    }

    public function view(User $user, Producto $producto): bool
    {
        return $user->tienePermiso('producto.ver');
    }

    public function create(User $user): bool
    {
        return $user->tienePermiso('producto.crear');
    }

    public function update(User $user, Producto $producto): bool
    {
        return $user->tienePermiso('producto.editar');
    }

    public function cambiarEstado(User $user, Producto $producto): bool
    {
        return $user->tienePermiso('producto.baja');
    }

    /** Publicar en la tienda es una decision aparte de estar en operacion. */
    public function publicar(User $user, Producto $producto): bool
    {
        return $user->tienePermiso('producto.publicar');
    }

    /** Permiso del modulo stock, no del catalogo: lo tiene el administrativo. */
    public function ajustarStock(User $user, Producto $producto): bool
    {
        return $user->tienePermiso('stock.ajustar');
    }

    public function verStock(User $user, Producto $producto): bool
    {
        return $user->tienePermiso('stock.ver');
    }
}
