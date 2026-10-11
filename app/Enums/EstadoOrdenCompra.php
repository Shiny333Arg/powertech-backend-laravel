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

    /** Fin de vida: no vuelve a mover stock ni se puede reabrir. */
    public function esCerrada(): bool
    {
        return ! $this->esAbierta();
    }

    /** @return list<string> */
    public static function cerradas(): array
    {
        return array_values(array_map(
            fn (self $estado) => $estado->value,
            array_filter(self::cases(), fn (self $estado) => $estado->esCerrada()),
        ));
    }

    /**
     * Solo el borrador se edita.
     *
     * Una aprobada ya paso por la decision de un administrativo y una enviada
     * ya esta en manos del proveedor: cambiarles el detalle es reescribir lo
     * que otro leyo. Lo que se hace en ese caso es cancelarla y emitir otra.
     */
    public function permiteEdicion(): bool
    {
        return $this === self::Borrador;
    }

    /**
     * ¿Se le puede cargar una recepcion de mercaderia?
     *
     * Solo 'enviada' y 'recibida_parcial'. Un borrador todavia no lo aprobo
     * nadie y una aprobada sin enviar no llego al proveedor, asi que no hay
     * nada que pueda estar entrando contra ella; si la mercaderia aparece
     * igual, lo que falta es enviar el pedido, no recibirlo.
     */
    public function permiteRecepcion(): bool
    {
        return $this->es(self::Enviada, self::RecibidaParcial);
    }

    /**
     * La maquina de estados completa, en un solo lugar.
     *
     * Vale la pena leerla como diagrama:
     *
     *   borrador ──aprobar──> aprobada ──enviar──> enviada ──recibir──> recibida_parcial
     *       │                     │                   │                      │ ↺
     *       └─────────────────────┴───cancelar────────┴──────────────────────┘
     *                                                 └──recibir todo──> recibida
     *
     * Cuatro cosas que la tabla dice y conviene no perder de vista:
     *  - No se puede enviar sin aprobar. Es el requerimiento explicito de que
     *    toda orden pase por un administrativo.
     *  - Una recibida_parcial puede seguir recibiendo, o cerrarse completa.
     *  - <b>recibida_parcial admite transicionar a si misma</b>: la segunda y
     *    la tercera entrega parcial contra la misma orden no cambian el estado,
     *    pero igual pasan por el mismo chequeo que las demas. Sin esta linea,
     *    la segunda recepcion parcial de un pedido explota.
     *  - Recibida y cancelada son terminales: de ahi no se sale.
     */
    public function puedeTransicionarA(self $destino): bool
    {
        return in_array($destino, $this->transicionesPosibles(), true);
    }

    /** @return list<self> */
    public function transicionesPosibles(): array
    {
        return match ($this) {
            self::Borrador        => [self::Aprobada, self::Cancelada],
            self::Aprobada        => [self::Enviada, self::Cancelada],
            self::Enviada         => [self::RecibidaParcial, self::Recibida, self::Cancelada],
            self::RecibidaParcial => [self::RecibidaParcial, self::Recibida, self::Cancelada],
            self::Recibida        => [],
            self::Cancelada       => [],
        };
    }

    /** Clase de Bootstrap para el badge de estado. Lo usan index y show. */
    public function color(): string
    {
        return match ($this) {
            self::Borrador        => 'secondary',
            self::Aprobada        => 'info',
            self::Enviada         => 'primary',
            self::RecibidaParcial => 'warning',
            self::Recibida        => 'success',
            self::Cancelada       => 'danger',
        };
    }
}
