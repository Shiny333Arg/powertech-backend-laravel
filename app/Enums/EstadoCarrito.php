<?php

namespace App\Enums;

use App\Enums\Concerns\ConEtiqueta;

enum EstadoCarrito: string
{
    use ConEtiqueta;

    case Activo     = 'activo';
    case EnCheckout = 'en_checkout';
    case Convertido = 'convertido';
    case Abandonado = 'abandonado';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Activo     => 'Activo',
            self::EnCheckout => 'En checkout',
            self::Convertido => 'Convertido en venta',
            self::Abandonado => 'Abandonado',
        };
    }
}
