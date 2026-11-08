<?php

namespace Tests\Apoyo;

use App\Enums\MetodoPago;
use App\Exceptions\PasarelaPagoException;
use App\Models\Carrito;
use App\Services\Pagos\PasarelaPago;
use App\Services\Pagos\PreferenciaPago;
use App\Services\Pagos\ReferenciaCarrito;
use App\Services\Pagos\ResultadoPago;
use Illuminate\Http\Request;

/**
 * Pasarela de pago en memoria, para las pruebas.
 *
 * <b>No habla con Mercado Pago y eso es el punto.</b> Las pruebas de esta
 * suite verifican el circuito de PowerTech —que se valide antes de cobrar,
 * que no se creen ventas duplicadas, que el stock no se mueva antes de
 * tiempo— y para eso la pasarela tiene que ser predecible. Una prueba que
 * dependa de la API de Mercado Pago no prueba nuestro código: prueba la red.
 *
 * <b>Lo que NO se prueba con esto, y hay que decirlo:</b> que el cuerpo de la
 * preferencia sea el que Mercado Pago acepta, que la firma del webhook se
 * verifique bien contra una firma real, y que el mapeo de estados sea el
 * correcto. Eso sólo se comprueba contra el entorno de prueba de Mercado Pago,
 * a mano, con credenciales TEST-.
 *
 * Se registra con `$this->swap(PasarelaPago::class, new PasarelaFalsa())`.
 */
class PasarelaFalsa implements PasarelaPago
{
    /** Las preferencias creadas, en orden. Para poder revisarlas. */
    public array $preferenciasCreadas = [];

    /** Qué devuelve buscarPago(). Null simula un pago sin acreditar. */
    public ?ResultadoPago $resultado = null;

    /** Si crearPreferencia() tiene que fallar, como cuando MP no responde. */
    public bool $fallaAlCrear = false;

    /** Si esAvisoConfiable() acepta la firma. */
    public bool $firmaValida = true;

    /** Si buscarPago() tiene que explotar, como ante un timeout. */
    public bool $fallaAlBuscar = false;

    /** Lo que devuelve ultimoEstadoDeReferencia(): approved, rejected, etc. */
    public ?string $estadoDeReferencia = null;

    /** Si ultimoEstadoDeReferencia() tiene que explotar. */
    public bool $fallaAlConsultarReferencia = false;

    public function crearPreferencia(
        Carrito $carrito,
        int $totalCentavos,
        ?string $urlRetorno = null,
    ): PreferenciaPago {
        if ($this->fallaAlCrear) {
            throw PasarelaPagoException::porque(
                'No pudimos generar el link de pago. Intentá de nuevo en unos minutos.'
            );
        }

        if ($totalCentavos <= 0) {
            throw PasarelaPagoException::porque('No se puede cobrar un importe de cero.');
        }

        $preferencia = new PreferenciaPago(
            id: 'PREF-'.$carrito->getKey(),
            urlPago: 'https://sandbox.mercadopago.com.ar/checkout/PREF-'.$carrito->getKey(),
        );

        $this->preferenciasCreadas[] = [
            'carrito'        => $carrito->getKey(),
            'total_centavos' => $totalCentavos,
            'url_retorno'    => $urlRetorno,
            'preferencia'    => $preferencia,
        ];

        return $preferencia;
    }

    public function buscarPago(string $idPagoExterno): ?ResultadoPago
    {
        if ($this->fallaAlBuscar) {
            throw PasarelaPagoException::porque(
                "No pudimos comunicarnos con Mercado Pago para consultar el pago {$idPagoExterno}."
            );
        }

        return $this->resultado;
    }

    public function ultimoEstadoDeReferencia(string $referencia): ?string
    {
        if ($this->fallaAlConsultarReferencia) {
            throw PasarelaPagoException::porque(
                'No pudimos consultar el estado del pago en Mercado Pago.'
            );
        }

        return $this->estadoDeReferencia;
    }

    public function esAvisoConfiable(Request $request): bool
    {
        return $this->firmaValida;
    }

    public function idPagoDelAviso(Request $request): ?string
    {
        $id = $request->input('data.id') ?? $request->query('id');

        return $id !== null ? (string) $id : null;
    }

    /**
     * Un pago acreditado por el total exacto de este carrito.
     *
     * El importe se toma del resumen del carrito y no se pasa a mano: una
     * prueba que invente el monto no comprobaría nada sobre
     * `exigirImporteExacto()`.
     */
    public function acreditar(
        Carrito $carrito,
        int $totalCentavos,
        string $idPago = 'MP-1',
    ): ResultadoPago {
        return $this->resultado = new ResultadoPago(
            metodo: MetodoPago::Tarjeta,
            montoCentavos: $totalCentavos,
            cuotas: 1,
            mpPaymentId: $idPago,
            mpPreferenceId: $carrito->mp_preference_id,
            mpMerchantOrderId: 'ORD-1',
            mpStatus: 'approved',
            mpStatusDetail: 'accredited',
            mpPaymentType: 'credit_card',
            comisionCentavos: 0,
            netoAcreditadoCentavos: $totalCentavos,
            referenciaExterna: ReferenciaCarrito::de($carrito),
        );
    }
}
