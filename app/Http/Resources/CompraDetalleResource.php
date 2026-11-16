<?php

namespace App\Http\Resources;

use App\Models\Envio;
use App\Support\CompraDelCliente;
use App\Support\EstadoCompraCliente;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * El pedido completo: seguimiento, detalle, importes, entrega, pagos y
 * comprobantes. Equivale a resources/views/compras/show.blade.php.
 *
 * <b>Trae todo lo que la pantalla muestra y nada mas.</b> La app pinta el
 * detalle con una sola llamada: no hay un segundo GET para los pagos ni para
 * la factura. A cambio, cada bloque esta armado para mostrarse tal cual
 * llega: el seguimiento viene con la situacion de cada paso resuelta, las
 * instrucciones de entrega en texto, el numero del comprobante formateado.
 * Todo eso son reglas del negocio o de ARCA, y Flutter no tiene por que
 * conocerlas.
 *
 * <b>Seguimiento y desenlace son excluyentes.</b> Un pedido en curso tiene
 * pasos; uno cancelado o devuelto tiene un desenlace y `seguimiento` vacio.
 * `en_curso` dice cual de los dos mirar sin que la app tenga que preguntar si
 * una lista esta vacia. Ver CompraDelCliente.
 *
 * <b>Los importes viajan desagregados y sin recalcular.</b> Son las columnas
 * de `ventas`, congeladas al emitir. La app no suma lineas para llegar al
 * total: si una promocion se aplico distinto, el documento manda.
 *
 * <b>Lo que NO sale de aca:</b> `cliente_id`, `usuario_id` —el vendedor—,
 * `usuario_valido_id`, `fecha_validacion`, `validez_hasta` y `neto_gravado`.
 * Lo primero es informacion que la app ya tiene o que es de la empresa; los
 * campos de validacion pertenecen al circuito de presupuestos, que no llega a
 * esta seccion; el neto gravado es desglose fiscal que el cliente lee en la
 * factura.
 *
 * Exige cargados: `lineas.producto`, `pagos`, `envio`,
 * `comprobantes.tipoComprobante` y `comprobantes.puntoVenta`.
 *
 * @mixin \App\Models\Venta
 */
class CompraDetalleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $factura = CompraDelCliente::facturaVigente($this->resource);

        return [
            'id' => $this->id,

            'numero'            => $this->numero,
            'numero_formateado' => $this->numeroFormateado(),

            'fecha' => $this->fecha?->toIso8601String(),

            'canal' => [
                'valor'    => $this->canal->value,
                'etiqueta' => $this->canal->etiqueta(),
            ],

            'estado' => EstadoCompraCliente::paraJson($this->estado),

            // false para cancelada, vencida, devuelta y devuelta parcial. La
            // app muestra el desenlace en lugar del circuito.
            'en_curso' => ! $this->estado->esFinDeVida(),

            'seguimiento' => CompraDelCliente::seguimiento($this->resource),

            'desenlace' => CompraDelCliente::desenlace($this->resource),

            // Los cinco importes del documento, en el orden en que se leen.
            // `descuento_promociones` y `costo_envio` llegan en cero cuando no
            // aplican: la app decide si muestra la fila, igual que la vista.
            'importes' => [
                'subtotal'              => round((float) $this->subtotal, 2),
                'descuento_promociones' => round((float) $this->descuento_promociones, 2),
                'costo_envio'           => round((float) $this->costo_envio, 2),
                'total_iva'             => round((float) $this->total_iva, 2),
                'total'                 => round((float) $this->total, 2),
            ],

            'entrega' => [
                'modo' => [
                    'valor'    => $this->modo_entrega->value,
                    'etiqueta' => $this->modo_entrega->etiqueta(),
                ],

                // Que tiene que hacer el cliente, en texto. Depende del modo
                // de entrega y del numero de pedido.
                'instrucciones' => CompraDelCliente::instruccionesDeEntrega($this->resource),

                // Null en un retiro: modo_entrega = retiro significa que no
                // hay fila en `envios`, no que haya una vacia.
                'envio' => $this->envio ? $this->datosDelEnvio($this->envio) : null,
            ],

            // Lo que el cliente escribio al confirmar el pedido. Puede ser
            // multilinea.
            'observaciones' => $this->observaciones,

            'lineas' => CompraLineaResource::collection($this->lineas)->toArray($request),

            // El resumen de cobro mas el detalle de cada pago. El resumen no
            // se deriva del estado de la venta: suma los pagos acreditados.
            'pago' => CompraDelCliente::resumenDePago(
                (float) $this->total,
                CompraDelCliente::acreditadoDe($this->pagos),
            ) + [
                'pagos' => PagoResource::collection($this->pagos)->toArray($request),
            ],

            'facturacion' => [
                // La factura vigente, que es la que el cliente va a querer
                // guardar. Null mientras el pago no se acredito o si ARCA la
                // rechazo: en los dos casos el cliente no tiene nada que
                // hacer, asi que la app muestra el mismo mensaje de espera.
                'factura' => $factura ? new ComprobanteResource($factura) : null,

                // Todos los comprobantes del pedido, incluidas las notas de
                // credito de una devolucion.
                'comprobantes' => ComprobanteResource::collection($this->comprobantes)->toArray($request),
            ],
        ];
    }

    /**
     * Direccion y seguimiento del envio.
     *
     * La direccion se copia en `envios` al confirmar y no se referencia a
     * `direcciones`: un envio ya despachado no cambia de destino porque el
     * cliente edito su domicilio en el perfil. Por eso se muestra esta y no
     * la del perfil.
     *
     * `direccion_completa` viaja armada para que la app no tenga que decidir
     * como se concatena un domicilio argentino con piso opcional.
     *
     * @return array<string, mixed>
     */
    private function datosDelEnvio(Envio $envio): array
    {
        return [
            'direccion_completa' => $this->direccionEnUnaLinea($envio),

            'calle'         => $envio->calle,
            'numero'        => $envio->numero,
            'piso_depto'    => $envio->piso_depto,
            'codigo_postal' => $envio->codigo_postal,
            'localidad'     => $envio->localidad,
            'provincia'     => $envio->provincia,

            'costo' => round((float) $envio->costo, 2),

            // Estado del envio y seguimiento del transportista, cuando hay.
            // `tracking_url` es null hasta el despacho.
            'estado'         => $envio->estado,
            'tracking_url'   => $envio->tracking_url,
            'fecha_despacho' => $envio->fecha_despacho?->toIso8601String(),
        ];
    }

    private function direccionEnUnaLinea(Envio $envio): string
    {
        $calle = trim($envio->calle.' '.$envio->numero);

        if (filled($envio->piso_depto)) {
            $calle .= ', '.$envio->piso_depto;
        }

        $localidad = collect([$envio->localidad, $envio->provincia])
            ->filter()
            ->implode(', ');

        $postal = filled($envio->codigo_postal) ? '(CP '.$envio->codigo_postal.')' : null;

        return collect([$calle, $localidad, $postal])
            ->filter()
            ->implode(' — ');
    }
}
