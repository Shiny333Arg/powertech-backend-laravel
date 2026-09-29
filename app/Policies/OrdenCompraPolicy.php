<?php

namespace App\Policies;

use App\Models\OrdenCompra;
use App\Models\User;

/**
 * Sin metodo delete: la orden de compra no se elimina. Su fin de vida es un
 * estado —'recibida' o 'cancelada'—, igual que el de una venta.
 *
 * Las dos reglas que esta policy hace cumplir y no son obvias:
 *
 *  - <b>Quien crea no puede aprobar lo que creo.</b> Es la unica separacion de
 *    funciones del circuito de compras: sin ella, un mismo usuario arma el
 *    pedido, lo aprueba y lo manda, y la aprobacion deja de significar algo.
 *    El administrador queda exento porque en una empresa chica suele ser la
 *    misma persona y trabarlo lo dejaria sin poder operar.
 *  - <b>El estado manda sobre el permiso.</b> Tener 'compra.editar' no alcanza
 *    para editar una orden enviada: el documento ya esta en manos del
 *    proveedor. Por eso update() consulta el estado, no solo el permiso.
 */
class OrdenCompraPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tienePermiso('compra.ver');
    }

    public function view(User $user, OrdenCompra $orden): bool
    {
        return $user->tienePermiso('compra.ver');
    }

    public function create(User $user): bool
    {
        return $user->tienePermiso('compra.crear');
    }

    public function update(User $user, OrdenCompra $orden): bool
    {
        return $user->tienePermiso('compra.editar') && $orden->esEditable();
    }

    /**
     * Requerimiento explicito: toda orden pasa por un administrativo antes de
     * salir.
     */
    public function aprobar(User $user, OrdenCompra $orden): bool
    {
        if (! $user->tienePermiso('compra.aprobar')) {
            return false;
        }

        if (! $orden->estado->puedeTransicionarA(\App\Enums\EstadoOrdenCompra::Aprobada)) {
            return false;
        }

        // Separacion de funciones. El administrador es la excepcion deliberada.
        if ($orden->usuario_creo_id === $user->id && ! $user->tienePermiso('compra.aprobar_propias')) {
            return false;
        }

        return true;
    }

    public function enviar(User $user, OrdenCompra $orden): bool
    {
        return $user->tienePermiso('compra.enviar')
            && $orden->estado->puedeTransicionarA(\App\Enums\EstadoOrdenCompra::Enviada);
    }

    public function cancelar(User $user, OrdenCompra $orden): bool
    {
        return $user->tienePermiso('compra.cancelar')
            && $orden->estado->puedeTransicionarA(\App\Enums\EstadoOrdenCompra::Cancelada);
    }
}
