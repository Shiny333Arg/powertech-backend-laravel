<?php

namespace App\Events;

use App\Models\Venta;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * El pedido quedó apartado y esperando en el mostrador.
 *
 * <b>Por que un evento y no un mail dentro de EntregaService.</b> Es el mismo
 * criterio que el resto del sistema: los servicios emiten hechos y los
 * listeners reaccionan. EntregaService mueve stock y cambia estados; que
 * ademas sepa redactar un correo lo ata a una decision de comunicacion que
 * manana puede ser un WhatsApp, un push de la app, o las tres cosas.
 *
 * El otro motivo es tecnico: el cambio de estado corre dentro de una
 * transaccion, y mandar un mail ahi adentro significa sostener locks de fila
 * mientras se espera al servidor SMTP. El listener va a la cola y se despacha
 * despues del commit.
 *
 * Se emite SOLO en la transicion a `lista_retiro`. Las ventas con envio
 * tienen su propio aviso cuando exista el circuito de despacho: el texto es
 * distinto porque lo que el cliente tiene que hacer es distinto.
 */
class VentaListaParaRetirar
{
    use Dispatchable;

    public function __construct(public readonly Venta $venta)
    {
    }
}