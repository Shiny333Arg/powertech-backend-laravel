<?php

namespace App\Mail;

use App\Models\OrdenCompra;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * El remito de pedido que se le manda al proveedor. Es el canal: no hay portal
 * ni carga manual, el pedido ES este correo.
 *
 * Va en HTML y no como PDF adjunto a proposito. Un PDF obliga a una dependencia
 * de render, se ve mal en el telefono y muchos proveedores chicos copian los
 * renglones a mano desde el cuerpo del mail. Si mas adelante se quiere el
 * adjunto, se agrega un attachments() acá sin tocar nada mas.
 *
 * El 'replyTo' apunta a la casilla de compras y no a la del sistema: la
 * respuesta del proveedor —confirmacion, faltantes, fecha de entrega— tiene que
 * llegarle a una persona.
 */
class RemitoDePedido extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly OrdenCompra $orden,
    ) {
        // Las relaciones se cargan acá y no en la vista: una vista que dispara
        // consultas es una vista que falla distinto segun quien la renderice.
        $this->orden->loadMissing(['proveedor', 'lineas.producto']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Pedido {$this->orden->numero} — ".config('app.name', 'PowerTech'),
            replyTo: array_filter([
                config('mail.compras.address')
                    ? new Address(config('mail.compras.address'), config('mail.compras.name', 'Compras'))
                    : null,
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.remito-pedido',
            with: [
                'orden'     => $this->orden,
                'proveedor' => $this->orden->proveedor,
                'lineas'    => $this->orden->lineas,
            ],
        );
    }
}
