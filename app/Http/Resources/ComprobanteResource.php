<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un comprobante fiscal del pedido: la factura, o una nota de credito.
 *
 * Equivale a la tarjeta "Facturación" de compras/show.blade.php, con la
 * diferencia de que la vista muestra solo la factura vigente y la API
 * devuelve ademas las notas: un pedido devuelto parcialmente tiene factura y
 * nota de credito, y el cliente que mira ese pedido en la app tiene que poder
 * ver las dos.
 *
 * <b>`numero_formateado` lo arma el backend.</b> El numero fiscal se lee
 * "00001-00000123": punto de venta en 5 digitos, correlativo en 8. Es el
 * mismo padding que hace la vista, y repetirlo en Dart es repetir una regla
 * de ARCA en un lugar donde no se puede verificar.
 *
 * <b>`tipo` es el nombre comercial, no el codigo de AFIP.</b> El cliente lee
 * "Factura B", no "codigo_afip 6". El signo tampoco viaja: ya esta resuelto
 * en `es_nota_credito`, que es la pregunta que la app se hace.
 *
 * <b>Lo que NO sale de aca:</b> `cliente_id`, `tipo_documento_id`,
 * `condicion_iva_id`, `nro_doc` y los netos. Son los datos fiscales copiados
 * al emitir, y el cliente ya los ve —vigentes y editables— en su perfil.
 * `motivo` tampoco: es la glosa interna del rechazo de ARCA, que se resuelve
 * desde gestion reintentando, y no algo sobre lo que el cliente pueda actuar.
 *
 * Exige `tipoComprobante` y `puntoVenta` cargados.
 *
 * @mixin \App\Models\Comprobante
 */
class ComprobanteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $tipo  = $this->tipoComprobante;
        $letra = $tipo?->letra?->value;

        $esNotaCredito = (int) $tipo?->signo === -1;

        return [
            'id' => $this->id,

            'es_nota_credito' => $esNotaCredito,

            // "Factura B" / "Nota de crédito B". La letra puede faltar en un
            // tipo mal cargado: en ese caso se muestra el tipo sin letra en
            // lugar de un nombre con un espacio colgando.
            'tipo' => trim(($esNotaCredito ? 'Nota de crédito' : 'Factura').' '.($letra ?? '')),

            'letra' => $letra,

            'numero_formateado' => str_pad((string) ($this->puntoVenta?->numero ?? 0), 5, '0', STR_PAD_LEFT)
                .'-'.str_pad((string) $this->numero, 8, '0', STR_PAD_LEFT),

            'fecha_emision' => $this->fecha_emision?->toIso8601String(),

            'total' => round((float) $this->total, 2),

            'estado' => [
                'valor'    => $this->estado->value,
                'etiqueta' => $this->estado->etiqueta(),
            ],

            // Autorizado y con CAE. Es la pregunta que decide si el
            // comprobante sirve o si todavia no esta disponible.
            'autorizado' => $this->estaAutorizado(),

            // Null mientras no este autorizado. Van juntos: un CAE sin fecha
            // de vencimiento no se puede presentar.
            'cae'             => $this->cae,
            'cae_vencimiento' => $this->cae_vencimiento?->toIso8601String(),
        ];
    }
}
