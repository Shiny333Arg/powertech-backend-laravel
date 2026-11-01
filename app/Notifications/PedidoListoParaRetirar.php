<?php

namespace App\Notifications;

use App\Models\Venta;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Avisa al cliente que su pedido lo espera en el local.
 *
 * Lo manda el listener AvisarPedidoListo, no EntregaService: ver el comentario
 * del evento VentaListaParaRetirar.
 *
 * <b>El mail no lleva precios ni el detalle completo.</b> Lleva lo que el
 * cliente necesita para venir a buscarlo —el numero de pedido y que traer— y
 * un enlace al pedido, que es donde esta todo lo demas y siempre esta al dia.
 * Un mail con importes queda desactualizado si despues hay una devolucion
 * parcial, y entonces hay dos versiones del mismo pedido circulando.
 *
 * <b>Acá va a colgar la copia de la factura</b> cuando exista el PDF del
 * comprobante: es el mail natural para adjuntarla, porque es el unico que el
 * cliente recibe con la compra ya cerrada.
 */
class PedidoListoParaRetirar extends Notification
{
    public function __construct(private readonly Venta $venta)
    {
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $numero = $this->venta->numeroFormateado();

        return (new MailMessage())
            ->subject("Tu pedido {$numero} está listo para retirar")
            ->markdown('emails.pedido-listo', [
                'venta'      => $this->venta,
                'numero'     => $numero,
                'nombre'     => $notifiable->nombres ?? null,
                'urlPedido'  => route('cliente.compras.show', $this->venta),
            ]);
    }
}