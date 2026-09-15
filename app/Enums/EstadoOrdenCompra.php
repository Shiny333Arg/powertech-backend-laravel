<?php

namespace App\Enums;

use App\Enums\Concerns\ConEtiqueta;

enum EstadoOrdenCompra: string
{
    use ConEtiqueta;

    case Borrador        = 'borrador';
    case Aprobada        = 'aprobada';
    case Enviada         = 'enviada';
    case RecibidaParcial = 'recibida_parcial';
    case Recibida        = 'recibida';
    case Cancelada       = 'cancelada';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Borrador        => 'Borrador',
            self::Aprobada        => 'Aprobada',
            self::Enviada         => 'Enviada al proveedor',
            self::RecibidaParcial => 'Recibida parcialmente',
            self::Recibida        => 'Recibida',
            self::Cancelada       => 'Cancelada',
        };
    }

    /**
     * Una orden abierta todavia puede sumar stock. Antes de generar una orden
     * automatica hay que verificar que el producto no tenga ninguna: si no,
     * cada corrida pediria de nuevo el mismo faltante.
     */
    public function esAbierta(): bool
    {
        return $this->es(self::Borrador, self::Aprobada, self::Enviada, self::RecibidaParcial);
    }

    /** @return list<string> */
    public static function abiertas(): array
    {
        return array_values(array_map(
            fn (self $estado) => $estado->value,
            array_filter(self::cases(), fn (self $estado) => $estado->esAbierta()),
        ));
    }
}
