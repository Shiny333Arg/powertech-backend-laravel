<?php

namespace App\Http\Controllers;

use App\Enums\CanalVenta;
use App\Enums\EstadoCarrito;
use App\Exceptions\FacturacionException;
use App\Exceptions\PasarelaPagoException;
use App\Exceptions\TransicionNoPermitidaException;
use App\Http\Requests\CheckoutRequest;
use App\Models\Carrito;
use App\Services\CarritoService;
use App\Services\CheckoutService;
use App\Services\MotorPromociones;
use App\Services\Pagos\PasarelaPago;
use App\Services\Pagos\ReferenciaCarrito;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\RedirectResponse as RespuestaExterna;

/**
 * Cierre de la compra online con Mercado Pago Checkout Pro. Solo retiro.
 *
 * <b>Cuatro acciones, dos caminos de vuelta.</b>
 *
 *   index    GET  /checkout           resumen antes de pagar
 *   pagar    POST /checkout/pagar     crea la preferencia y manda a MP
 *   retorno  GET  /checkout/retorno   el cliente vuelve del sitio de MP
 *   webhook  POST /checkout/webhook   MP avisa desde sus servidores
 *
 * El cobro ocurre fuera de este sitio, asi que el pago vuelve por dos
 * caminos independientes y los dos pueden llegar primero:
 *
 *   El RETORNO tiene sesion y usuario, pero puede no llegar nunca: el cliente
 *   cierra la pestaña despues de pagar y listo. No se le cree nada de lo que
 *   trae en la query string, que es editable a mano.
 *
 *   El WEBHOOK es el camino autoritativo: llega aunque el cliente desaparezca,
 *   y Mercado Pago lo reintenta. No tiene sesion, ni cookies, ni usuario, asi
 *   que todo lo que la venta necesita sale de la base. Por eso modo_entrega y
 *   observaciones se guardan en el carrito en `pagar()`.
 *
 * Los dos terminan en el mismo lugar: consultar el pago contra la API y, si
 * esta acreditado, llamar a CheckoutService::confirmar(), que es idempotente.
 */
class CheckoutController extends Controller
{
    public function __construct(
        private readonly CarritoService $carritos,
        private readonly CheckoutService $checkout,
        private readonly PasarelaPago $pasarela,
    ) {
    }

    // ------------------------------------------------------------------
    // 1. Resumen
    // ------------------------------------------------------------------

    /**
     * Resumen previo al pago: que se lleva, cuanto sale y con que datos se
     * factura.
     *
     * Los precios se recalculan aca y no se arrastran desde el carrito: son
     * los vigentes en este momento, y son los que se van a congelar en la
     * venta.
     *
     * Esta pantalla no cambia el estado del carrito: es solo una revision y
     * el cliente puede volver atras sin haber hecho nada. El paso a
     * `en_checkout` ocurre en `pagar()`, que es cuando se lo manda a pagar de
     * verdad.
     */
    public function index(MotorPromociones $motor): View
    {
        $carrito = $this->carritos->abiertoDe(request()->user());

        if ($carrito->lineas()->doesntExist()) {
            abort(redirect()
                ->route('cliente.carrito.index')
                ->with('info', 'Tu carrito está vacío.'));
        }

        $carrito->load(['lineas.producto']);

        $productos = $carrito->lineas->pluck('producto')->filter()->keyBy('id');
        $promociones = $motor->paraProductos($productos, CanalVenta::Online);

        $precios = $productos->mapWithKeys(fn ($producto) => [
            $producto->id => (float) $producto->precio
                - (($promociones[$producto->id]['descuento'] ?? 0) / 100),
        ]);

        $cliente = request()->user()->cliente;

        return view('checkout.index', [
            'carrito' => $carrito,
            'cliente' => $cliente,
            'precios' => $precios,
            'total'   => $carrito->lineas->sum(
                fn ($linea) => ($precios[$linea->producto_id] ?? 0) * $linea->cantidad
            ),
            // Si los datos fiscales no permiten facturar, se avisa ACA, antes
            // de ofrecer pagar. Descubrirlo al emitir el comprobante significa
            // revertir una venta ya cobrada.
            'problemaFacturacion' => $cliente
                ? $this->checkout->problemaDeFacturacion($cliente)
                : 'Para comprar necesitás completar tus datos de facturación en tu perfil.',
        ]);
    }

    // ------------------------------------------------------------------
    // 2. Salida hacia Mercado Pago
    // ------------------------------------------------------------------

    /**
     * Crea la preferencia de pago y redirige al sitio de Mercado Pago.
     *
     * Guarda en el carrito lo que el cliente eligio —modo de entrega,
     * observaciones— y el id de la preferencia. No crea la venta: eso pasa
     * recien cuando el pago se acredita.
     *
     * La redireccion es externa, asi que sale con `away()` y no con `route()`.
     */
    public function pagar(CheckoutRequest $request): RedirectResponse|RespuestaExterna
    {
        $carrito = $this->carritos->abiertoDe($request->user());

        if ($carrito->lineas()->doesntExist()) {
            return redirect()
                ->route('cliente.carrito.index')
                ->with('info', 'Tu carrito está vacío.');
        }

        // Se valida antes de cobrar, no despues: si el cliente no puede
        // recibir factura, la venta fallaria con el dinero ya tomado.
        $cliente = $request->user()->cliente;

        if (! $cliente || $problema = $this->checkout->problemaDeFacturacion($cliente)) {
            return redirect()
                ->route('cliente.checkout.index')
                ->with('error', $problema ?? 'Completá tus datos de facturación para poder comprar.');
        }

        try {
            $preferencia = $this->pasarela->crearPreferencia(
                $carrito,
                $this->totalEnCentavos($carrito),
            );
        } catch (PasarelaPagoException $e) {
            return redirect()
                ->route('cliente.checkout.index')
                ->with('error', $e->getMessage());
        }

        // Lo que el webhook va a necesitar y no puede leer de una sesion.
        $carrito->update([
            'estado'           => EstadoCarrito::EnCheckout,
            'mp_preference_id' => $preferencia->id,
            'modo_entrega'     => $request->modoEntrega(),
            'observaciones'    => $request->input('observaciones'),
        ]);

        return redirect()->away($preferencia->urlPago);
    }

    // ------------------------------------------------------------------
    // 3. Vuelta del cliente
    // ------------------------------------------------------------------

    /**
     * El cliente volvio del sitio de Mercado Pago.
     *
     * <b>Nada de lo que trae la query string se toma como verdad.</b> Esa URL
     * es visible y editable: un ?status=approved escrito a mano no puede
     * crear una venta. Lo unico que se usa de ahi es el id del pago, y con
     * ese id se le pregunta a la API.
     *
     * Puede que el webhook ya haya creado la venta. No es un problema:
     * confirmar() es idempotente y devuelve la misma venta.
     */
    public function retorno(Request $request): RedirectResponse
    {
        $idPago = $request->query('payment_id') ?? $request->query('collection_id');

        if (blank($idPago)) {
            // Llego sin pago: el cliente cancelo o cerro el checkout de
            // Mercado Pago sin pagar. El carrito sigue intacto.
            return redirect()
                ->route('cliente.carrito.index')
                ->with('info', 'No se registró ningún pago. Tu carrito quedó como estaba.');
        }

        // Si este pago ya produjo una venta, se va derecho al pedido. Cubre
        // el caso normal de que el webhook haya llegado primero.
        if ($venta = $this->checkout->ventaDePago((string) $idPago)) {
            return redirect()->route('cliente.compras.show', $venta);
        }

        try {
            $pago = $this->pasarela->buscarPago((string) $idPago);
        } catch (PasarelaPagoException $e) {
            Log::error('No se pudo consultar el pago al volver del checkout', [
                'pago'  => $idPago,
                'error' => $e->getMessage(),
            ]);

            // El webhook va a reintentar: no se pierde la compra.
            return redirect()
                ->route('cliente.compras.index')
                ->with('info', 'Estamos confirmando tu pago. En unos minutos vas a ver el pedido acá.');
        }

        if (! $pago) {
            // Existe pero no esta acreditado: rechazado, pendiente o en
            // revision. Sin acreditacion no hay venta, y el carrito queda
            // intacto para que pueda reintentar.
            return redirect()
                ->route('cliente.carrito.index')
                ->with('error', 'El pago no se aprobó. Podés intentar de nuevo con otro medio de pago.');
        }

        try {
            $venta = $this->checkout->confirmar($carrito = $this->carritoDe($pago->referenciaExterna, $request), $pago);
        } catch (TransicionNoPermitidaException|FacturacionException $e) {
            // El dinero ya se tomo y la venta no se pudo crear. La
            // transaccion revirtio todo, asi que no quedo nada a medias,
            // pero hay un pago sin pedido que alguien tiene que resolver.
            Log::critical('Pago acreditado sin pedido', [
                'mp_payment_id' => $pago->mpPaymentId,
                'carrito'       => $carrito?->getKey(),
                'monto'         => $pago->montoDecimal(),
                'motivo'        => $e->getMessage(),
            ]);

            return redirect()
                ->route('cliente.carrito.index')
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('cliente.compras.show', $venta)
            ->with('exito', $this->checkout->mensajeDeCierre($venta));
    }

    // ------------------------------------------------------------------
    // 4. Aviso de Mercado Pago
    // ------------------------------------------------------------------

    /**
     * Webhook de Mercado Pago. El camino autoritativo.
     *
     * <b>Devuelve 200 en casi todos los casos, a proposito.</b> Mercado Pago
     * reintenta ante cualquier respuesta que no sea 2xx, asi que un 200 es la
     * forma de decir "recibido, no insistas". Solo se devuelve 503 cuando el
     * problema es nuestro y un reintento puede funcionar: no pudimos
     * consultar la API. Un pago no acreditado, un aviso de otro tipo o un
     * carrito ya convertido son 200: reintentar no cambiaria nada.
     *
     * No tiene sesion ni usuario: todo sale de la base. Y no tiene CSRF, asi
     * que la URL hay que excluirla de la verificacion (ver web.php).
     */
    public function webhook(Request $request): JsonResponse
    {
        if (! $this->pasarela->esAvisoConfiable($request)) {
            Log::warning('Webhook de Mercado Pago con firma inválida', [
                'ip'    => $request->ip(),
                'query' => $request->query(),
            ]);

            // 401 y no 200: no es un aviso de Mercado Pago.
            return response()->json(['error' => 'firma inválida'], 401);
        }

        $idPago = $this->pasarela->idPagoDelAviso($request);

        if (blank($idPago)) {
            // Aviso de otro tipo de evento. Recibido y descartado.
            return response()->json(['ok' => true]);
        }

        if ($this->checkout->ventaDePago($idPago)) {
            // Ya procesado. Es el caso normal de un reintento.
            return response()->json(['ok' => true]);
        }

        try {
            $pago = $this->pasarela->buscarPago($idPago);
        } catch (PasarelaPagoException $e) {
            Log::error('Webhook: no se pudo consultar el pago', [
                'pago'  => $idPago,
                'error' => $e->getMessage(),
            ]);

            // Problema nuestro o de la API: que reintente.
            return response()->json(['error' => 'no se pudo consultar'], 503);
        }

        if (! $pago) {
            // Pendiente, rechazado o en revision. Si despues cambia, Mercado
            // Pago manda otro aviso.
            return response()->json(['ok' => true]);
        }

        $carrito = $this->carritoDe($pago->referenciaExterna);

        if (! $carrito) {
            Log::critical('Webhook: pago acreditado sin carrito identificable', [
                'mp_payment_id' => $pago->mpPaymentId,
                'referencia'    => $pago->referenciaExterna,
            ]);

            // Reintentar no va a hacer aparecer el carrito.
            return response()->json(['ok' => true]);
        }

        try {
            $this->checkout->confirmar($carrito, $pago);
        } catch (TransicionNoPermitidaException|FacturacionException $e) {
            Log::critical('Webhook: pago acreditado sin pedido', [
                'mp_payment_id' => $pago->mpPaymentId,
                'carrito'       => $carrito->getKey(),
                'monto'         => $pago->montoDecimal(),
                'motivo'        => $e->getMessage(),
            ]);

            // El motivo no se arregla reintentando: hace falta una persona.
            return response()->json(['ok' => true]);
        }

        return response()->json(['ok' => true]);
    }

    // ------------------------------------------------------------------
    // Auxiliares
    // ------------------------------------------------------------------

    /**
     * Resuelve el carrito del pago.
     *
     * La referencia externa es la fuente principal porque es la unica que
     * funciona sin sesion, que es la situacion del webhook. En el retorno se
     * acepta ademas el carrito abierto del usuario como respaldo, para el
     * caso de que Mercado Pago no devuelva la referencia.
     */
    private function carritoDe(?string $referencia, ?Request $request = null): ?Carrito
    {
        if ($id = ReferenciaCarrito::carritoId($referencia)) {
            return Carrito::find($id);
        }

        return $request?->user()
            ? $this->carritos->abiertoDe($request->user())
            : null;
    }

    /**
     * Total del carrito a precio vigente, en centavos.
     *
     * Duplica el calculo que hace VentaService porque el importe hay que
     * tenerlo ANTES de que exista la venta: es lo que se le cobra al cliente.
     * CheckoutService compara los dos y aborta si difieren, asi que la
     * duplicacion esta verificada en cada compra y no puede desincronizarse
     * en silencio.
     */
    private function totalEnCentavos(Carrito $carrito): int
    {
        $carrito->load('lineas.producto');

        $productos = $carrito->lineas->pluck('producto')->filter()->keyBy('id');
        $promociones = app(MotorPromociones::class)->paraProductos($productos, CanalVenta::Online);

        $total = 0;

        foreach ($carrito->lineas as $linea) {
            $producto = $productos->get($linea->producto_id);

            if (! $producto) {
                continue;
            }

            $unitario = (int) round(((float) $producto->precio) * 100)
                - ($promociones[$producto->id]['descuento'] ?? 0);

            $total += $unitario * $linea->cantidad;
        }

        return $total;
    }
}
