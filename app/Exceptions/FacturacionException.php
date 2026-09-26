<?php

namespace App\Exceptions;

use DomainException;

/**
 * Error del circuito de facturacion ANTES de llegar al organismo: datos
 * fiscales incompletos, comprobante ya emitido, letra imposible.
 *
 * El rechazo del propio web service NO se lanza como excepcion: queda
 * asentado en `afip_solicitudes` y deja el comprobante en estado `rechazado`,
 * porque es un resultado esperado y reintentable, no una falla del sistema.
 */
class FacturacionException extends DomainException
{
    public static function porque(string $mensaje): self
    {
        return new self($mensaje);
    }
}
