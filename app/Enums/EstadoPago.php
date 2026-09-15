<?php

namespace App\Enums;

use App\Enums\Concerns\ConEtiqueta;

/**
 * La fila nace pendiente junto con la preferencia de Mercado Pago, y el
 * webhook la pasa a acreditado. Acreditar emite el comprobante y NO mueve
 * stock: el fisico baja cuando la mercaderia sale del deposito.
 */
enum EstadoPago: string
{
    use ConEtiqueta;

    case Pendiente  = 'pendiente';
    case Acreditado = 'acreditado';
    case Rechazado  = 'rechazado';
    case Devuelto   = 'devuelto';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Pendiente  => 'Pendiente',
            self::Acreditado => 'Acreditado',
            self::Rechazado  => 'Rechazado',
            self::Devuelto   => 'Devuelto',
        };
    }

    public function sumaAlCobrado(): bool
    {
        return $this === self::Acreditado;
    }
}
