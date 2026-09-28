<?php

namespace App\Services;

use App\Services\Facturacion\AutorizadorComprobante;
use App\Services\Facturacion\ResultadoAutorizacion;
use App\Models\Comprobante;

/**
 * Autorizador de desarrollo y de puesta en marcha.
 *
 * Asigna un CAE ficticio de 14 digitos con vencimiento a 10 dias, que es el
 * formato real, y arma el mismo payload que se le mandaria al organismo. Sirve
 * para que el local pueda operar el circuito completo —cobrar, emitir, entregar
 * y ver el comprobante— mientras se tramita la habilitacion.
 *
 * NO USAR EN PRODUCCION: los comprobantes que emite no tienen validez fiscal.
 * El servicio lo deja marcado en `afip_solicitudes.operacion`, asi que siempre
 * se puede distinguir que comprobantes se emitieron contra el simulador.
 */
class AutorizadorSimulado implements AutorizadorComprobante
{
    public function autorizar(Comprobante $comprobante): ResultadoAutorizacion
    {
        $comprobante->loadMissing(['tipoComprobante', 'puntoVenta', 'lineas']);

        $request = [
            'CbteTipo'  => $comprobante->tipoComprobante->codigo_afip,
            'PtoVta'    => $comprobante->puntoVenta->numero,
            'CbteDesde' => $comprobante->numero,
            'CbteHasta' => $comprobante->numero,
            'DocTipo'   => $comprobante->tipoDocumento?->codigo_afip,
            'DocNro'    => $comprobante->nro_doc,
            'CbteFch'   => $comprobante->fecha_emision?->format('Ymd'),
            'ImpNeto'   => (float) $comprobante->neto,
            'ImpIVA'    => (float) $comprobante->iva,
            'ImpTotal'  => (float) $comprobante->total,
        ];

        $cae = $this->caeFicticio();
        $vencimiento = now()->addDays(10);

        return new ResultadoAutorizacion(
            aprobado: true,
            operacion: 'FECAESolicitar (SIMULADO)',
            request: $request,
            response: [
                'Resultado'   => 'A',
                'CAE'         => $cae,
                'CAEFchVto'   => $vencimiento->format('Ymd'),
                'Observaciones' => 'Comprobante emitido por el autorizador simulado. Sin validez fiscal.',
            ],
            cae: $cae,
            caeVencimiento: $vencimiento,
        );
    }

    private function caeFicticio(): string
    {
        return (string) random_int(10_000_000_000_000, 99_999_999_999_999);
    }
}
