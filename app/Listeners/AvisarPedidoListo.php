<?php

namespace App\Listeners;

use App\Events\VentaListaParaRetirar;
use App\Notifications\PedidoListoParaRetirar;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Manda el aviso de retiro.
 *
 * <b>Va a la cola y despues del commit.</b> Las dos cosas importan:
 *
 *   ShouldQueue     el cambio de estado no espera al servidor SMTP. Sin esto,
 *                   un correo lento deja al empleado mirando un spinner con
 *                   locks de fila tomados.
 *
 *   $afterCommit    un listener encolado dentro de una transaccion puede
 *                   ejecutarse ANTES de que la transaccion cierre. Si despues
 *                   hay un rollback, el cliente ya recibio el aviso de un
 *                   pedido que no quedo listo.
 *
 * <b>No se registra a mano con Event::listen().</b> Laravel descubre los
 * listeners de app/Listeners por el tipo del parametro de handle(). Agregar
 * el registro manual engancha el mismo listener dos veces y el cliente recibe
 * dos correos. Verificable con: php artisan event:list
 */
class AvisarPedidoListo implements ShouldQueue
{
    public bool $afterCommit = true;

    /** Un correo que no sale no puede tumbar la entrega: se reintenta y se loguea. */
    public int $tries = 3;

    public function handle(VentaListaParaRetirar $evento): void
    {
        $venta = $evento->venta;

        // La venta de mostrador puede no tener cliente —consumidor final
        // anonimo— y el cliente puede no tener cuenta: se carga desde gestion
        // sin invitarlo nunca. En los dos casos no hay a donde escribir, y no
        // es un error: el pedido se avisa en el mostrador.
        $usuario = $venta->cliente?->user;

        if (! $usuario?->email) {
            return;
        }

        try {
            $usuario->notify(new PedidoListoParaRetirar($venta));
        } catch (\Throwable $e) {
            // El pedido YA esta listo y apartado. Que el correo falle no
            // cambia ese hecho, asi que no se propaga: se deja rastro para
            // poder avisar por otra via.
            Log::error('No se pudo avisar que el pedido está listo', [
                'venta'  => $venta->getKey(),
                'email'  => $usuario->email,
                'motivo' => $e->getMessage(),
            ]);
        }
    }
}