<?php

namespace App\Services;

use App\Enums\EstadoCarrito;
use App\Enums\EstadoPago;
use App\Enums\EstadoVenta;
use App\Enums\ModoEntrega;
use App\Exceptions\TransicionNoPermitidaException;
use App\Models\Carrito;
use App\Models\Cliente;
use App\Models\Pago;
use App\Models\Venta;
use App\Services\Pagos\ResultadoPago;
use Illuminate\Support\Facades\DB;

/**
 * Cierre del carrito: lo convierte en una venta online ya pagada.
 *
 * Es el equivalente de CobroService para el canal online, y la diferencia
 * esta en el ORDEN de los eventos.
 *
 *   Mostrador:  la venta se confirma -> compromete stock -> el cajero cobra
 *   Online:     la pasarela acredita -> nace la venta pagada -> compromete
 *
 * <b>La venta online nace `pagada`.</b> No se crea una venta confirmada
 * esperando el pago, y es una decision, no un atajo: una venta confirmada sin
 * pagar retiene stock comprometido, y el checkout abandonado es el caso
 * normal, no la excepcion. Esas unidades bajarian del disponible, bloquearian
 * la vitrina y falsearian el dashboard de reposicion hasta que un job las
 * venciera. Naciendo pagada, no existe el estado intermedio que habria que
 * expirar: sin pago acreditado no hay venta, y sin venta no hay compromiso.
 *
 * La contrapartida es que el pago no puede referenciar a la venta, porque la
 * venta todavia no existe cuando se paga. La referencia externa que viaja a
 * la pasarela es el CARRITO.
 *
 * <b>Al confirmarse ocurre todo en una sola transaccion:</b>
 *   1. nace la venta en estado `pagada`, con el stock comprometido;
 *   2. se asienta el pago como acreditado;
 *   3. se emite el comprobante;
 *   4. se decide el estado siguiente segun haya o no stock fisico;
 *   5. el carrito pasa a `convertido`.
 *
 * Si cualquiera de los pasos falla, el ROLLBACK deshace la venta y devuelve
 * el numero al numerador.
 *
 * <b>Un rechazo de ARCA no revierte la compra.</b> FacturacionService deja el
 * comprobante en `rechazado` y no lanza: la venta queda pagada y el pedido se
 * prepara igual. Es el mismo criterio que en el mostrador —el rechazo se
 * resuelve desde el listado de comprobantes, no deshaciendo una venta
 * cobrada— y esta heredado a proposito.
 *
 * <b>El stock fisico no se toca.</b> Pagar no mueve mercaderia: el fisico
 * baja cuando el cliente retira. Lo que se mueve aca es el comprometido.
 */
class CheckoutService
{
    public function __construct(
        private readonly VentaService $ventas,
        private readonly FacturacionService $facturacion,
        private readonly EntregaService $entregas,
    ) {
    }

    /**
     * La venta que ya se creo para este pago, si existe.
     *
     * <b>Es la primera linea de defensa contra la doble acreditacion.</b> El
     * mismo pago llega por dos caminos —el retorno del navegador y el
     * webhook, que Mercado Pago reintenta— y los dos terminan aca. Sin esta
     * consulta, el segundo intentaria crear otra venta: otro numero interno
     * quemado, otro comprobante con CAE y el stock comprometido dos veces.
     *
     * El indice unico de `pagos.mp_payment_id` es la segunda linea, la que no
     * se puede sortear por una carrera entre los dos caminos.
     */
    public function ventaDePago(?string $mpPaymentId): ?Venta
    {
        if (blank($mpPaymentId)) {
            return null;
        }

        return Pago::query()
            ->where('mp_payment_id', $mpPaymentId)
            ->first()
            ?->venta;
    }

    /**
     * Convierte el carrito en una venta online pagada.
     *
     * <b>El modo de entrega y las observaciones salen del carrito, no de
     * parametros.</b> Los eligio el cliente antes de ir a pagar y quedaron
     * guardados ahi justamente porque el webhook llega sin sesion: es el
     * unico camino garantizado y no tiene de donde leer un formulario.
     *
     * @throws TransicionNoPermitidaException si el carrito no esta en condiciones
     *         de cerrarse, si los datos fiscales no permiten facturar, o si el
     *         importe pagado no coincide con el total.
     */
    public function confirmar(Carrito $carrito, ResultadoPago $pago): Venta
    {
        // Idempotencia antes de cualquier escritura: si este pago ya produjo
        // una venta, se devuelve esa y no se toca nada.
        if ($venta = $this->ventaDePago($pago->mpPaymentId)) {
            return $venta;
        }

        $this->exigirCarritoCerrable($carrito);

        $cliente = $carrito->user?->cliente;

        if (! $cliente) {
            throw TransicionNoPermitidaException::porque(
                'Para comprar necesitás completar tus datos de facturación en tu perfil.'
            );
        }

        // Antes de tocar el pago, no despues: ver el comentario del metodo.
        if ($problema = $this->problemaDeFacturacion($cliente)) {
            throw TransicionNoPermitidaException::porque($problema);
        }

        return DB::transaction(function () use ($carrito, $cliente, $pago) {

            // Se relee con la fila bloqueada: el retorno del navegador y el
            // webhook pueden llegar a la vez y tienen que serializarse aca, o
            // se crean dos ventas para el mismo carrito.
            $carrito = Carrito::query()
                ->lockForUpdate()
                ->findOrFail($carrito->getKey());

            // Dentro del lock se vuelve a preguntar: el otro camino pudo
            // haber terminado mientras este esperaba.
            if ($venta = $this->ventaDePago($pago->mpPaymentId)) {
                return $venta;
            }

            $this->exigirCarritoCerrable($carrito);

            $lineas = $carrito->lineas()
                ->with('producto')
                ->get();

            $this->exigirProductosVendibles($lineas);

            $venta = $this->ventas->crearOnline([
                'cliente_id'    => $cliente->id,
                // Hoy solo retiro. El fallback cubre un carrito que llego al
                // pago antes de que existiera la columna.
                'modo_entrega'  => ($carrito->modo_entrega ?? ModoEntrega::Retiro)->value,
                'observaciones' => $carrito->observaciones,
                'lineas'        => $lineas
                    ->map(fn ($linea) => [
                        'producto_id' => $linea->producto_id,
                        'cantidad'    => $linea->cantidad,
                    ])
                    ->all(),
            ]);

            // El total se verifica DESPUES de crear la venta porque es el
            // unico lugar donde existe calculado. Si no coincide, la
            // excepcion revierte la venta y devuelve el numero.
            $this->exigirImporteExacto($venta, $pago);

            $venta->pagos()->create([
                'metodo'   => $pago->metodo,
                'monto'    => $pago->montoDecimal(),
                'comision' => $pago->comisionDecimal(),
                // Lo que la tienda recibe. Distinto del monto: la pasarela se
                // queda con su comision.
                'neto_acreditado' => $pago->netoAcreditadoDecimal(),
                'cuotas'          => $pago->cuotas,
                // Ya viene acreditado: la pasarela aprobo antes de que
                // existiera la venta. El estado `pendiente` es del circuito
                // donde la fila nace junto con la preferencia de pago, que no
                // es este.
                'estado' => EstadoPago::Acreditado,
                // Sin cajero: en la venta online no hay empleado que cobre.
                'usuario_id'    => null,
                'mp_payment_id' => $pago->mpPaymentId,
                // La preferencia no viene en la respuesta del pago: la
                // guardamos nosotros en el carrito al crearla.
                'mp_preference_id'     => $pago->mpPreferenceId ?? $carrito->mp_preference_id,
                'mp_merchant_order_id' => $pago->mpMerchantOrderId,
                'mp_status'            => $pago->mpStatus,
                'mp_status_detail'     => $pago->mpStatusDetail,
                'mp_payment_type'      => $pago->mpPaymentType,
                'fecha'                => now(),
            ]);

            $this->facturacion->emitirPorVenta($venta);

            // Decide entre en_preparacion y pendiente_stock segun el fisico.
            $this->entregas->preparar($venta);

            $carrito->update(['estado' => EstadoCarrito::Convertido]);

            return $venta->fresh(['lineas.producto', 'pagos', 'comprobantes']);
        });
    }

    /**
     * Motivo por el que este cliente no puede recibir factura, o null si puede.
     *
     * <b>Por que se verifica aca y no se deja fallar a FacturacionService.</b>
     * Ese servicio rechaza al responsable inscripto sin CUIT lanzando una
     * FacturacionException, y lo hace en el paso 3 de la transaccion: para
     * entonces el cliente ya pago. El rollback deshace la venta, pero no
     * devuelve el dinero. Esta comprobacion corre antes de que el pago se
     * asiente, y en el checkout corre incluso antes de ofrecer pagar.
     *
     * Duplica una sola regla de FacturacionService::datosFiscalesDe(), que
     * sigue siendo la autoridad. Si esa regla cambia, hay que cambiar las dos.
     */
    public function problemaDeFacturacion(Cliente $cliente): ?string
    {
        $cliente->loadMissing(['condicionIva', 'tipoDocumento']);

        if (! $cliente->condicionIva || ! $cliente->tipoDocumento) {
            return 'Faltan tus datos de facturación. Completalos en tu perfil para poder comprar.';
        }

        $codigos = config('facturacion.codigos');

        $esInscripto = $cliente->condicionIva->codigo_afip
            === $codigos['condicion_iva']['responsable_inscripto'];

        if ($esInscripto && $cliente->tipoDocumento->codigo_afip !== $codigos['documento']['cuit']) {
            return 'Como responsable inscripto necesitás tener cargado tu CUIT para que podamos facturarte. '
                . 'Corregilo en tu perfil.';
        }

        return null;
    }

    /**
     * El carrito tiene que estar abierto y con algo adentro.
     *
     * `convertido` no es un error del cliente: es el webhook que llego dos
     * veces, o el boton apretado dos veces. Se corta con un mensaje que no
     * asusta.
     */
    private function exigirCarritoCerrable(Carrito $carrito): void
    {
        if ($carrito->estado === EstadoCarrito::Convertido) {
            throw TransicionNoPermitidaException::porque(
                'Este carrito ya se convirtió en un pedido.'
            );
        }

        if (! in_array($carrito->estado, [EstadoCarrito::Activo, EstadoCarrito::EnCheckout], true)) {
            throw TransicionNoPermitidaException::porque(
                'Este carrito ya no está disponible.'
            );
        }

        if ($carrito->lineas()->doesntExist()) {
            throw TransicionNoPermitidaException::porque(
                'Tu carrito está vacío.'
            );
        }
    }

    /**
     * Ningun producto puede haberse despublicado mientras estaba en el carrito.
     *
     * VentaService ya rechaza los dados de baja, pero no mira `publicado_app`:
     * para el mostrador un producto despublicado se sigue vendiendo, y esta
     * es una venta de la tienda. El mensaje nombra el producto porque el
     * cliente tiene que saber cual quitar.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\CarritoLinea>  $lineas
     */
    private function exigirProductosVendibles($lineas): void
    {
        foreach ($lineas as $linea) {
            $producto = $linea->producto;

            if (! $producto || ! $producto->activo || ! $producto->publicado_app) {
                $nombre = $producto->nombre ?? 'Un producto de tu carrito';

                throw TransicionNoPermitidaException::porque(
                    "{$nombre} ya no está disponible en la tienda. Quitalo del carrito para seguir."
                );
            }
        }
    }

    /**
     * Lo pagado tiene que ser exactamente el total de la venta.
     *
     * Se compara contra el BRUTO que pago el cliente, no contra el neto
     * acreditado: la comision de la pasarela es un costo de la tienda y no
     * reduce lo que el cliente debe.
     *
     * <b>Riesgo residual conocido.</b> El carrito no congela precios —los
     * congela la venta— asi que entre que se creo la preferencia y que el
     * pago se acredito, un precio o una promocion pudieron cambiar. Si eso
     * pasa, esto aborta y el cliente queda pagado sin pedido, con el aviso de
     * que se comunique. La ventana se acoto por dos lados: la preferencia
     * vence en minutos y los medios de pago diferidos estan excluidos, asi
     * que en la practica son segundos. Cerrarlo del todo exige congelar el
     * precio en carrito_lineas al crear la preferencia, que contradice la
     * decision de que el carrito muestre siempre el precio vigente.
     */
    private function exigirImporteExacto(Venta $venta, ResultadoPago $pago): void
    {
        $total = (int) round(((float) $venta->total) * 100);

        if ($total !== $pago->montoCentavos) {
            throw TransicionNoPermitidaException::porque(
                'El importe abonado no coincide con el total del pedido. '
                . 'No se registró la compra: comunicate con PowerTech así lo resolvemos.'
            );
        }
    }

    /** Lo que el cliente ve despues de pagar, segun donde quedo la venta. */
    public function mensajeDeCierre(Venta $venta): string
    {
        $numero = $venta->numeroFormateado();

        return match ($venta->estado) {
            EstadoVenta::PendienteStock => "Registramos tu pedido {$numero}. "
                . 'Estamos esperando la reposición de algunos productos: te avisamos en cuanto puedas retirarlo.',

            default => "¡Listo! Tu pedido {$numero} está pago. "
                . 'Te avisamos en cuanto esté preparado para que pases a retirarlo por el local.',
        };
    }
}
