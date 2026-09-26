<?php

namespace App\Policies;

use App\Enums\EstadoVenta;
use App\Enums\ModoEntrega;
use App\Models\User;
use App\Models\Venta;

/**
 * REEMPLAZA a la version anterior: suma las etapas posteriores del circuito
 * (cobro, entrega, devolucion).
 *
 * Los permisos son claves nombradas: lo que no se asigna explicitamente, no se
 * puede hacer. `venta.ver_todas` es lo que separa al vendedor —que ve su
 * propio historial— del administrativo y el administrador.
 */
class VentaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tienePermiso('venta.ver');
    }

    public function view(User $user, Venta $venta): bool
    {
        return $user->tienePermiso('venta.ver') && $this->alcanza($user, $venta);
    }

    public function create(User $user): bool
    {
        return $user->tienePermiso('venta.crear');
    }

    /**
     * Solo se edita el detalle de un presupuesto: una venta confirmada ya
     * comprometio stock y una pagada ya tiene comprobante con CAE.
     */
    public function update(User $user, Venta $venta): bool
    {
        return $user->tienePermiso('venta.editar')
            && $venta->esPresupuesto()
            && $this->alcanza($user, $venta);
    }

    /** Habilitar el cobro del presupuesto es un acto del vendedor. */
    public function validar(User $user, Venta $venta): bool
    {
        return $user->tienePermiso('venta.validar')
            && $venta->esPresupuesto()
            && $venta->puedeTransicionarA(EstadoVenta::Confirmada);
    }

    public function cancelar(User $user, Venta $venta): bool
    {
        return $user->tienePermiso('venta.anular')
            && $venta->puedeTransicionarA(EstadoVenta::Cancelada);
    }

    /**
     * El cajero cobra una venta confirmada y validada. Un presupuesto sin
     * validar no se cobra: es la regla del requerimiento 3.3.
     */
    public function cobrar(User $user, Venta $venta): bool
    {
        return $user->tienePermiso('venta.cobrar')
            && $venta->estado === EstadoVenta::Confirmada
            && $venta->estaValidada();
    }

    /** Salida de mercaderia del deposito. */
    public function entregar(User $user, Venta $venta): bool
    {
        if (! $user->tienePermiso('venta.entregar')) {
            return false;
        }

        $destino = $venta->modo_entrega === ModoEntrega::Envio
            ? EstadoVenta::Despachada
            : EstadoVenta::Entregada;

        return $venta->puedeTransicionarA($destino)
            || $venta->puedeTransicionarA(EstadoVenta::ListaRetiro);
    }

    /** Devolver emite nota de credito: es dinero, y por eso es un permiso aparte. */
    public function devolver(User $user, Venta $venta): bool
    {
        return $user->tienePermiso('venta.devolver')
            && ($venta->puedeTransicionarA(EstadoVenta::DevueltaParcial)
                || $venta->puedeTransicionarA(EstadoVenta::Devuelta));
    }

    /**
     * Una venta no se elimina nunca, por ningun rol. El modelo ademas lo
     * bloquea con el guard de `deleting` y las FK RESTRICT de venta_lineas,
     * pagos, comprobantes y envios. Esto es la tercera capa, la que hace que
     * el boton ni siquiera se dibuje.
     */
    public function delete(User $user, Venta $venta): bool
    {
        return false;
    }

    public function forceDelete(User $user, Venta $venta): bool
    {
        return false;
    }

    private function alcanza(User $user, Venta $venta): bool
    {
        return $user->tienePermiso('venta.ver_todas') || $venta->usuario_id === $user->id;
    }
}
