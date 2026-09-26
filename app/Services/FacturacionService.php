<?php

namespace App\Services;

use App\Enums\EstadoComprobante;
use App\Exceptions\FacturacionException;
use App\Models\CondicionIva;
use App\Models\Comprobante;
use App\Models\PuntoVenta;
use App\Models\TipoComprobante;
use App\Models\TipoDocumento;
use App\Models\Venta;
use App\Models\VentaLinea;
use App\Services\Facturacion\AutorizadorComprobante;
use App\Services\Facturacion\ResultadoAutorizacion;
use Illuminate\Support\Facades\DB;

/**
 * Emision de comprobantes fiscales.
 *
 * Reglas que este servicio hace cumplir:
 *  - La letra la decide la condicion frente al IVA del cliente: responsable
 *    inscripto recibe A, el resto B. Sin cliente identificado es B a
 *    consumidor final.
 *  - Los datos fiscales se COPIAN al emitir. Son los que se informaron al
 *    organismo y no pueden cambiar si despues el cliente edita su ficha.
 *  - La numeracion fiscal es correlativa por (tipo de comprobante, punto de
 *    venta) y no tiene nada que ver con `ventas.numero`.
 *  - Cada intento contra el organismo queda asentado en `afip_solicitudes`.
 *  - Un comprobante nunca se edita ni se borra: se anula con nota de credito.
 *
 * <b>Limitacion conocida:</b> el costo de envio todavia no se factura como
 * linea, porque `comprobante_lineas.producto_id` es obligatorio y el flete no
 * es un producto del catalogo. Para el circuito de mostrador con retiro, que
 * es lo que esta en produccion hoy, el costo de envio es cero. Cuando entre la
 * tienda online hay que decidir entre un producto de catalogo "Envio" o una
 * columna de concepto en la linea.
 */
class FacturacionService
{
    public function __construct(
        private readonly AutorizadorComprobante $autorizador,
    ) {
    }

    // ------------------------------------------------------------------
    // Factura
    // ------------------------------------------------------------------

    /**
     * Emite la factura de una venta. Se invoca al acreditarse el pago, nunca
     * antes, y no esta atada a la salida del stock: en una preventa la factura
     * se emite semanas antes de que llegue la mercaderia.
     */
    public function emitirPorVenta(Venta $venta, ?PuntoVenta $puntoVenta = null): Comprobante
    {
        $venta->loadMissing(['cliente.condicionIva', 'cliente.tipoDocumento', 'lineas']);

        $vigentes = $venta->comprobantes()
            ->where('estado', '!=', EstadoComprobante::Rechazado->value)
            ->whereHas('tipoComprobante', fn ($q) => $q->where('signo', 1))
            ->exists();

        if ($vigentes) {
            throw FacturacionException::porque('La venta ya tiene una factura emitida.');
        }

        if ((float) $venta->costo_envio > 0) {
            throw FacturacionException::porque(
                'Todavía no se puede facturar el costo de envío como línea del comprobante.'
            );
        }

        $puntoVenta ??= $this->puntoVentaPorDefecto();
        $fiscales = $this->datosFiscalesDe($venta);
        $tipo = $this->tipoComprobante($fiscales['letra'] === 'A' ? 'factura_a' : 'factura_b');

        return DB::transaction(function () use ($venta, $puntoVenta, $tipo, $fiscales) {
            $comprobante = Comprobante::create([
                'venta_id'            => $venta->id,
                'tipo_comprobante_id' => $tipo->id,
                'punto_venta_id'      => $puntoVenta->id,
                'numero'              => $this->proximoNumero($tipo, $puntoVenta),
                'cliente_id'          => $venta->cliente_id,
                'tipo_documento_id'   => $fiscales['tipo_documento_id'],
                'nro_doc'             => $fiscales['nro_doc'],
                'condicion_iva_id'    => $fiscales['condicion_iva_id'],
                'fecha_emision'       => now(),
                'neto'                => $venta->neto_gravado,
                'iva'                 => $venta->total_iva,
                'total'               => $venta->total,
                'estado'              => EstadoComprobante::Pendiente,
            ]);

            foreach ($venta->lineas as $linea) {
                $comprobante->lineas()->create([
                    'producto_id'     => $linea->producto_id,
                    'descripcion'     => $linea->descripcion,
                    'cantidad'        => $linea->cantidad,
                    'precio_unitario' => $linea->precio_unitario,
                    'alicuota_iva'    => $linea->alicuota_iva,
                    'neto'            => $linea->neto,
                    'iva'             => $linea->iva,
                    'total'           => $linea->total,
                ]);
            }

            return $this->autorizar($comprobante);
        });
    }

    // ------------------------------------------------------------------
    // Nota de credito
    // ------------------------------------------------------------------

    /**
     * Emite la nota de credito de una devolucion, total o parcial.
     *
     * Las lineas son propias y no coinciden necesariamente con las de la
     * venta: por eso `comprobante_lineas` existe y no se reutiliza
     * `venta_lineas`.
     *
     * @param  array<int, int>  $cantidades  venta_linea_id => unidades a devolver
     */
    public function emitirNotaCredito(Venta $venta, array $cantidades, string $motivo): Comprobante
    {
        $factura = $venta->comprobantes()
            ->whereHas('tipoComprobante', fn ($q) => $q->where('signo', 1))
            ->where('estado', EstadoComprobante::Autorizado->value)
            ->latest('id')
            ->first();

        if (! $factura) {
            throw FacturacionException::porque(
                'No se puede devolver una venta que no tiene factura autorizada.'
            );
        }

        $lineas = $venta->lineas()->whereIn('id', array_keys($cantidades))->get();

        if ($lineas->isEmpty()) {
            throw FacturacionException::porque('La devolución no tiene líneas.');
        }

        $letra = $factura->tipoComprobante->letra->value;
        $tipo = $this->tipoComprobante($letra === 'A' ? 'nota_credito_a' : 'nota_credito_b');
        $puntoVenta = $factura->puntoVenta;

        return DB::transaction(function () use ($venta, $factura, $tipo, $puntoVenta, $lineas, $cantidades, $motivo) {
            $detalle = [];
            $neto = 0;
            $iva = 0;
            $total = 0;

            /** @var VentaLinea $linea */
            foreach ($lineas as $linea) {
                $cantidad = (int) $cantidades[$linea->id];

                if ($cantidad <= 0) {
                    continue;
                }

                if ($cantidad > $linea->pendienteDeDevolucion()) {
                    throw FacturacionException::porque(
                        "La línea «{$linea->descripcion}» ya no tiene {$cantidad} unidades pendientes de devolución."
                    );
                }

                // Proporcional exacto sobre la linea original: se devuelve lo
                // que se cobro por esas unidades, descuento de promocion
                // incluido, no el precio de lista.
                $proporcion = $cantidad / $linea->cantidad;
                $netoLinea = $this->centavos($linea->neto, $proporcion);
                $ivaLinea = $this->centavos($linea->iva, $proporcion);
                $totalLinea = $netoLinea + $ivaLinea;

                $neto += $netoLinea;
                $iva += $ivaLinea;
                $total += $totalLinea;

                $detalle[] = [
                    'producto_id'     => $linea->producto_id,
                    'descripcion'     => $linea->descripcion,
                    'cantidad'        => $cantidad,
                    'precio_unitario' => $linea->precio_unitario,
                    'alicuota_iva'    => $linea->alicuota_iva,
                    'neto'            => $this->aDecimal($netoLinea),
                    'iva'             => $this->aDecimal($ivaLinea),
                    'total'           => $this->aDecimal($totalLinea),
                ];
            }

            $comprobante = Comprobante::create([
                'venta_id'                => $venta->id,
                'tipo_comprobante_id'     => $tipo->id,
                'punto_venta_id'          => $puntoVenta->id,
                'numero'                  => $this->proximoNumero($tipo, $puntoVenta),
                'comprobante_asociado_id' => $factura->id,
                'cliente_id'              => $factura->cliente_id,
                'tipo_documento_id'       => $factura->tipo_documento_id,
                'nro_doc'                 => $factura->nro_doc,
                'condicion_iva_id'        => $factura->condicion_iva_id,
                'fecha_emision'           => now(),
                'neto'                    => $this->aDecimal($neto),
                'iva'                     => $this->aDecimal($iva),
                'total'                   => $this->aDecimal($total),
                'estado'                  => EstadoComprobante::Pendiente,
                'motivo'                  => $motivo,
            ]);

            foreach ($detalle as $linea) {
                $comprobante->lineas()->create($linea);
            }

            return $this->autorizar($comprobante);
        });
    }

    // ------------------------------------------------------------------
    // Autorizacion
    // ------------------------------------------------------------------

    /**
     * Pide el CAE y asienta el intento. Es idempotente para un comprobante ya
     * autorizado: no se vuelve a pedir.
     */
    public function autorizar(Comprobante $comprobante): Comprobante
    {
        if ($comprobante->estaAutorizado()) {
            return $comprobante;
        }

        $intento = $comprobante->solicitudes()->count() + 1;
        $resultado = $this->autorizador->autorizar($comprobante);

        $comprobante->solicitudes()->create([
            'operacion'        => $resultado->operacion,
            'request_payload'  => $resultado->request,
            'response_payload' => $resultado->response,
            'resultado'        => $resultado->resultado(),
            'codigo_error'     => $resultado->codigoError,
            'mensaje_error'    => $resultado->mensajeError,
            'intento'          => $intento,
        ]);

        $comprobante->fill($this->camposDe($resultado))->save();

        return $comprobante;
    }

    /**
     * @return array<string, mixed>
     */
    private function camposDe(ResultadoAutorizacion $resultado): array
    {
        if (! $resultado->aprobado) {
            return [
                'estado' => EstadoComprobante::Rechazado,
                'motivo' => trim(($resultado->codigoError ?? '').' '.($resultado->mensajeError ?? '')) ?: null,
            ];
        }

        return [
            'estado'          => EstadoComprobante::Autorizado,
            'cae'             => $resultado->cae,
            'cae_vencimiento' => $resultado->caeVencimiento,
        ];
    }

    // ------------------------------------------------------------------
    // Numeracion y catalogos
    // ------------------------------------------------------------------

    /**
     * Correlativo fiscal por (tipo de comprobante, punto de venta).
     *
     * Se bloquea la fila del punto de venta para serializar la emision: dos
     * cajas facturando a la vez leerian el mismo maximo y la segunda chocaria
     * contra el indice unico (tipo, punto_venta, numero).
     *
     * Con WSFE real, este numero debe validarse ademas contra
     * FECompUltimoAutorizado antes de enviar: el organismo es la autoridad
     * sobre la serie, no nuestra tabla.
     */
    private function proximoNumero(TipoComprobante $tipo, PuntoVenta $puntoVenta): int
    {
        PuntoVenta::whereKey($puntoVenta->id)->lockForUpdate()->first();

        $ultimo = Comprobante::query()
            ->where('tipo_comprobante_id', $tipo->id)
            ->where('punto_venta_id', $puntoVenta->id)
            ->max('numero');

        return ((int) $ultimo) + 1;
    }

    /**
     * Determina letra y datos fiscales del receptor.
     *
     * @return array{letra:string, tipo_documento_id:int, nro_doc:string, condicion_iva_id:int}
     */
    private function datosFiscalesDe(Venta $venta): array
    {
        $codigos = config('facturacion.codigos');
        $cliente = $venta->cliente;

        if (! $cliente) {
            // Consumidor final anonimo de mostrador: siempre B.
            return [
                'letra'             => 'B',
                'tipo_documento_id' => $this->tipoDocumento($codigos['documento']['consumidor_final'])->id,
                'nro_doc'           => '0',
                'condicion_iva_id'  => $this->condicionIva($codigos['condicion_iva']['consumidor_final'])->id,
            ];
        }

        $condicion = $cliente->condicionIva;
        $esInscripto = $condicion->codigo_afip === $codigos['condicion_iva']['responsable_inscripto'];

        if ($esInscripto && $cliente->tipoDocumento->codigo_afip !== $codigos['documento']['cuit']) {
            throw FacturacionException::porque(
                'Un responsable inscripto necesita CUIT para recibir factura A.'
            );
        }

        return [
            'letra'             => $esInscripto ? 'A' : 'B',
            'tipo_documento_id' => $cliente->tipo_documento_id,
            'nro_doc'           => $cliente->nro_doc,
            'condicion_iva_id'  => $cliente->condicion_iva_id,
        ];
    }

    private function tipoComprobante(string $clave): TipoComprobante
    {
        $codigo = config("facturacion.codigos.comprobante.{$clave}");

        return TipoComprobante::where('codigo_afip', $codigo)->firstOr(
            fn () => throw FacturacionException::porque("Falta el tipo de comprobante {$clave} en el catálogo.")
        );
    }

    private function tipoDocumento(int $codigo): TipoDocumento
    {
        return TipoDocumento::where('codigo_afip', $codigo)->firstOr(
            fn () => throw FacturacionException::porque("Falta el tipo de documento {$codigo} en el catálogo.")
        );
    }

    private function condicionIva(int $codigo): CondicionIva
    {
        return CondicionIva::where('codigo_afip', $codigo)->firstOr(
            fn () => throw FacturacionException::porque("Falta la condición de IVA {$codigo} en el catálogo.")
        );
    }

    private function puntoVentaPorDefecto(): PuntoVenta
    {
        return PuntoVenta::where('numero', config('facturacion.punto_venta'))
            ->where('activo', true)
            ->firstOr(fn () => throw FacturacionException::porque(
                'No hay un punto de venta activo configurado para facturar.'
            ));
    }

    private function centavos(int|float|string $monto, float $proporcion = 1.0): int
    {
        return (int) round(((float) $monto) * 100 * $proporcion);
    }

    private function aDecimal(int $centavos): string
    {
        return number_format($centavos / 100, 2, '.', '');
    }
}
