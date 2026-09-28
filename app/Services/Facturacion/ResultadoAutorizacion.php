<?php

namespace App\Services\Facturacion;

use Carbon\CarbonInterface;

/**
 * Lo que devuelve un autorizador. Incluye el request y el response crudos
 * porque cada intento se asienta en `afip_solicitudes`: sin ese log no hay
 * forma de saber si un comprobante quedo autorizado del otro lado cuando se
 * corto la conexion, ni de responder ante una inspeccion.
 */
readonly class ResultadoAutorizacion
{
    /**
     * @param  array<string, mixed>  $request
     * @param  array<string, mixed>  $response
     */
    public function __construct(
        public bool $aprobado,
        public string $operacion,
        public array $request = [],
        public array $response = [],
        public ?string $cae = null,
        public ?CarbonInterface $caeVencimiento = null,
        public ?string $codigoError = null,
        public ?string $mensajeError = null,
    ) {
    }

    /** 'A' aprobado / 'R' rechazado, tal como lo informa el organismo. */
    public function resultado(): string
    {
        return $this->aprobado ? 'A' : 'R';
    }
}
