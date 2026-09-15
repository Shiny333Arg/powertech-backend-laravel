<?php

namespace App\Enums;

use App\Enums\Concerns\ConEtiqueta;

enum ModoEntrega: string
{
    use ConEtiqueta;

    case Retiro = 'retiro';
    case Envio  = 'envio';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Retiro => 'Retiro en local',
            self::Envio  => 'Envío a domicilio',
        };
    }

    /** modo_entrega = retiro significa que no hay fila en envios. */
    public function exigeEnvio(): bool
    {
        return $this === self::Envio;
    }

    /**
     * Donde sale la mercaderia del deposito, que es donde se descuenta el
     * fisico: en un envio es el despacho, en un retiro es cuando el cliente
     * se lleva el producto.
     */
    public function estadoPrevioAEntrega(): EstadoVenta
    {
        return match ($this) {
            self::Retiro => EstadoVenta::ListaRetiro,
            self::Envio  => EstadoVenta::Despachada,
        };
    }
}
