<?php

namespace App\Services\Pagos;

use App\Enums\MetodoPago;
use Illuminate\Support\Str;

/**
 * Un pago ya acreditado por la pasarela, listo para asentarse en `pagos`.
 *
 * Es el equivalente de ResultadoAutorizacion en el circuito de facturacion: el
 * borde entre un servicio externo y el dominio. CheckoutService lo recibe y
 * confia en el; no lo produce ni lo verifica.
 *
 * <b>Por que existe antes de que exista la integracion con Mercado Pago.</b>
 * Por decision de diseño la venta online nace `pagada`: no hay venta sin pago
 * acreditado, y por lo tanto no hay stock comprometido sin pago acreditado. La
 * consecuencia es que el pago ocurre ANTES de que exista la venta, asi que no
 * puede referenciarla: la referencia externa que viaja a la pasarela es el
 * carrito.
 *
 * Los campos son exactamente las columnas que `pagos` ya tiene reservadas para
 * Mercado Pago. Mientras la integracion no exista, el controlador construye
 * este objeto con `simulado()`. Cuando exista, lo construye la pasarela y
 * CheckoutService no cambia.
 *
 * <b>Importe en centavos.</b> Entero, igual que en MotorPromociones y
 * VentaService: comparar totales en centavos evita que un arrastre de punto
 * flotante haga fallar la verificacion contra el total de la venta.
 *
 * <b>Bruto y neto son distintos.</b> `montoCentavos` es lo que pago el cliente
 * y es lo que tiene que coincidir con el total de la venta. La comision de la
 * pasarela y el neto que la tienda efectivamente recibe son otra cosa, se
 * guardan aparte y no participan de esa verificacion.
 */
final readonly class ResultadoPago
{
    public function __construct(
        /**
         * El bucket local: efectivo, tarjeta o transferencia. Mercado Pago
         * devuelve tipos mas finos (credit_card, debit_card, ticket,
         * account_money) que viajan sin traducir en `mpPaymentType`. El mapeo
         * entre los dos es responsabilidad de la pasarela, no de este objeto.
         */
        public MetodoPago $metodo,
        /** Bruto pagado por el cliente, en centavos. */
        public int $montoCentavos,
        /** Solo con tarjeta. */
        public ?int $cuotas = null,
        /**
         * Id del pago en la pasarela. Es la clave de idempotencia del
         * callback: `pagos.mp_payment_id` tiene indice unico, asi que el mismo
         * pago asentado dos veces choca contra la base.
         */
        public ?string $mpPaymentId = null,
        public ?string $mpPreferenceId = null,
        public ?string $mpMerchantOrderId = null,
        public ?string $mpStatus = null,
        public ?string $mpStatusDetail = null,
        public ?string $mpPaymentType = null,
        /** Lo que cobra la pasarela, en centavos. */
        public ?int $comisionCentavos = null,
        /** Lo que la tienda recibe, en centavos: bruto menos comision. */
        public ?int $netoAcreditadoCentavos = null,
        /**
         * La referencia que mandamos a la pasarela: identifica el carrito.
         *
         * Es como el webhook sabe de que compra se trata. El webhook llega
         * desde los servidores de Mercado Pago, sin sesion y sin usuario
         * autenticado, asi que no hay otra forma de resolverlo.
         */
        public ?string $referenciaExterna = null,
    ) {
    }

    /**
     * SOLO PARA PRUEBAS. Da el total por acreditado sin pasar por la pasarela.
     *
     * Sirve para ejercitar CheckoutService —venta, comprobante, preparacion,
     * retiro— sin depender de que Mercado Pago responda ni de tener el tunel
     * publico levantado. Ningun controlador lo usa: el circuito real pasa
     * siempre por PasarelaMercadoPago.
     *
     * `mp_status` queda en 'simulado' y no en 'approved' a proposito: en la
     * base tiene que poder distinguirse un pago que nunca existio de uno
     * real. `mp_payment_id` se llena igual, con un valor aleatorio, para
     * ejercitar el indice unico que sostiene la idempotencia.
     */
    public static function simulado(int $montoCentavos, ?string $referenciaExterna = null): self
    {
        return new self(
            metodo: MetodoPago::Tarjeta,
            montoCentavos: $montoCentavos,
            cuotas: 1,
            mpPaymentId: 'SIM-'.Str::upper(Str::random(20)),
            mpStatus: 'simulado',
            referenciaExterna: $referenciaExterna,
        );
    }

    /** Los importes como los esperan las columnas decimal(12,2) de `pagos`. */
    public function montoDecimal(): string
    {
        return $this->aDecimal($this->montoCentavos);
    }

    public function comisionDecimal(): ?string
    {
        return $this->comisionCentavos === null
            ? null
            : $this->aDecimal($this->comisionCentavos);
    }

    public function netoAcreditadoDecimal(): ?string
    {
        return $this->netoAcreditadoCentavos === null
            ? null
            : $this->aDecimal($this->netoAcreditadoCentavos);
    }

    private function aDecimal(int $centavos): string
    {
        return number_format($centavos / 100, 2, '.', '');
    }
}
