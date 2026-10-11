<?php

namespace App\Services\Facturacion;

use App\Models\Comprobante;

/**
 * Frontera con el organismo.
 *
 * Existe como interfaz por una razon concreta: el circuito de mostrador tiene
 * que poder operar de punta a punta antes de que esten el certificado, la
 * clave fiscal y el punto de venta habilitados. Se arranca con el autorizador
 * simulado y se cambia una linea de configuracion el dia que WSFE esta listo.
 * Ningun otro archivo del sistema se entera.
 */
interface AutorizadorComprobante
{
    /**
     * Pide el CAE para un comprobante ya persistido en estado `pendiente`.
     *
     * No lanza excepcion ante un rechazo del organismo: lo devuelve como
     * resultado, porque un rechazo es una respuesta valida y reintentable.
     */
    public function autorizar(Comprobante $comprobante): ResultadoAutorizacion;
}
