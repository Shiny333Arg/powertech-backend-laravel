<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\EstadoCarrito;
use App\Exceptions\PasarelaPagoException;
use App\Exceptions\TransicionNoPermitidaException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CheckoutMovilRequest;
use App\Http\Requests\Api\PagoMovilRequest;
use App\Http\Resources\CheckoutResource;
use App\Models\Carrito;
use App\Models\Pago;
use App\Models\User;
use App\Services\CarritoService;
use App\Services\CheckoutService;
use App\Services\Pagos\PasarelaPago;
use App\Services\Pagos\ReferenciaCarrito;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * La antesala del pago en la app: resumen, validacion final y preferencias
 * de entrega.
 *
 * Es el equivalente de `CheckoutController::index()` del circuito web, en
 * JSON y partido en dos verbos.
 *
 * <b>Aca no se crea ninguna venta, y no es una etapa que falte: es como esta
 * diseñado el sistema.</b> La venta online NACE PAGADA —ver el docblock de
 * CheckoutService— porque una venta confirmada sin pagar retendria stock
 * comprometido en cada checkout abandonado, que es el caso normal y no la
 * excepcion. Sin acreditacion no hay venta. El pedido lo crea
 * `CheckoutService::confirmar()`, que ya existe, ya funciona y lo dispara el
 * webhook de Mercado Pago; lo unico que falta para que la app llegue ahi es
 * la preferencia de pago, que es el paso 10.
 *
 * <b>Por que el modo de entrega se guarda en el carrito y no viaja al
 * pagar.</b> El webhook de Mercado Pago llega desde los servidores de MP, sin
 * sesion, sin cookies y sin usuario: todo lo que la venta necesita tiene que
 * poder leerse de la base. Por eso `carritos.modo_entrega` y
 * `carritos.observaciones` existen, y por eso se persisten antes.
 *
 * <b>El estado del carrito no se toca.</b> Revisar el resumen y elegir como
 * retirar no comprometen nada: el cliente puede volver atras sin haber hecho
 * nada. El paso a `en_checkout` ocurre al crear la preferencia de pago, que
 * es cuando se lo manda a pagar de verdad. Es el mismo criterio que la web.
 *
 * El middleware es el mismo del carrito: `auth:sanctum` + `api.activo` +
 * `api.ambito:tienda` + `api.verificado`. Ver routes/api.php.
 */
class CheckoutController extends Controller
{
    public function __construct(
        private readonly CarritoService $carritos,
        private readonly CheckoutService $checkout,
        private readonly PasarelaPago $pasarela,
    ) {
    }

    /**
     * GET /api/v1/checkout
     *
     * Resumen previo al pago.
     *
     * <b>Los precios se recalculan, no se arrastran.</b> Son los vigentes en
     * este momento, que son los que se van a congelar en la venta. Es el
     * mismo `CarritoService::resumen()` que usa el carrito, asi que las dos
     * pantallas no pueden mostrar totales distintos.
     *
     * <b>No da 404 ni redirige con un carrito vacio.</b> La version web manda
     * de vuelta al carrito con un aviso; aca el carrito vacio llega como un
     * bloqueo mas, con su codigo. La app ya sabe en que pantalla esta y
     * decide sola; devolverle un error la obligaria a tratar como excepcion
     * algo que es un estado perfectamente normal.
     */
    public function index(Request $request): CheckoutResource
    {
        return $this->resumen($this->carritos->abiertoDe($request->user()));
    }

    /**
     * PATCH /api/v1/checkout
     *
     * Guarda el modo de entrega y las observaciones.
     *
     * <b>Aplica solo las claves presentes.</b> La pantalla guarda a medida
     * que el cliente elige, asi que un PATCH con solo `observaciones` no
     * tiene por que borrar el modo de entrega ya elegido. `observaciones`
     * explicito en null si las vacia, que es distinto de no mandarlas.
     *
     * Devuelve el resumen completo, igual que el GET y por el mismo motivo
     * que en el carrito: un solo modelo que parsear y los totales siempre
     * frescos.
     *
     */
    public function update(CheckoutMovilRequest $request): CheckoutResource
    {
        // <b>No hace falta verificar que el carrito se pueda editar.</b>
        // `abiertoDe()` solo devuelve carritos `activo` o `en_checkout`: si
        // el ultimo quedo `convertido` o `abandonado`, crea uno nuevo. Un
        // carrito cerrado no es alcanzable por este camino, asi que un
        // chequeo aca seria codigo que no corre nunca. La defensa de verdad
        // esta en confirmar(), que recibe el carrito por id desde el webhook
        // y ahi si puede llegar uno convertido.
        $carrito = $this->carritos->abiertoDe($request->user());

        $cambios = [];

        if ($request->has('modo_entrega')) {
            $cambios['modo_entrega'] = $request->string('modo_entrega')->value();
        }

        if ($request->has('observaciones')) {
            $cambios['observaciones'] = $request->input('observaciones');
        }

        if ($cambios !== []) {
            $carrito->update($cambios);
        }

        return $this->resumen($carrito->refresh());
    }


    /**
     * POST /api/v1/checkout/pagar
     *
     * Crea la orden de pago en Mercado Pago y devuelve el link para abrirlo.
     *
     * <b>No crea la venta.</b> La venta online nace pagada: aparece cuando el
     * pago se acredita, por el webhook, dentro de
     * `CheckoutService::confirmar()`. Acá sólo se pide el link.
     *
     * <b>Y no reserva stock.</b> Iniciar el checkout no compromete nada; el
     * comprometido se mueve al crearse la venta. Un checkout abandonado —el
     * caso normal— no deja nada que vencer.
     *
     * <b>Todo se revalida en este momento, no en el resumen.</b> Entre que el
     * cliente miró la pantalla y tocó el botón pudo despublicarse un
     * producto o vaciarse el carrito. Se vuelve a preguntar por los mismos
     * bloqueos que devuelve `GET /checkout`, y el importe se recalcula desde
     * cero contra el precio vigente: nada de lo que mandó la app participa
     * del total.
     *
     * Respuesta 200:
     *
     *     { "data": { "preferencia_id": "...", "url_pago": "https://...",
     *                 "total": 250000.00 } }
     *
     * 409 con `bloqueos` si el carrito dejó de ser pagable. 502 si Mercado
     * Pago no respondió —`PasarelaPagoException` ya se renderiza así— para
     * que la app pueda ofrecer "probá de nuevo" en lugar de un error genérico.
     */
    public function pagar(PagoMovilRequest $request): JsonResponse
    {
        $carrito = $this->carritos->abiertoDe($request->user());

        // Lo que la app haya elegido en esta misma pantalla. Si no viene,
        // vale lo que el PATCH ya guardó.
        $cambios = [];

        if ($request->has('modo_entrega')) {
            $cambios['modo_entrega'] = $request->string('modo_entrega')->value();
        }

        if ($request->has('observaciones')) {
            $cambios['observaciones'] = $request->input('observaciones');
        }

        if ($cambios !== []) {
            $carrito->update($cambios);
            $carrito->refresh();
        }

        // El webhook arma la venta leyendo esta columna y llega sin sesión:
        // sin un modo elegido no hay forma de saber cómo se entrega.
        if ($carrito->modo_entrega === null) {
            throw TransicionNoPermitidaException::porque(
                'Elegí cómo querés recibir tu pedido antes de pagar.'
            );
        }

        $cliente = $carrito->user?->cliente;

        $cliente?->loadMissing(['tipoDocumento', 'condicionIva']);

        $bloqueos = $this->checkout->bloqueosDe($carrito, $cliente);

        if ($bloqueos !== []) {
            // 409 y no 422: no es que el cuerpo esté mal armado, es que el
            // estado del carrito cambió. La app distingue los dos para saber
            // si tiene que corregir un campo o refrescar la pantalla.
            return response()->json([
                'message'  => $bloqueos[0]['mensaje'],
                'bloqueos' => $bloqueos,
            ], 409);
        }

        $resumen = $this->carritos->resumen($carrito);

        $preferencia = $this->pasarela->crearPreferencia(
            $carrito,
            // Centavos enteros calculados por el backend. Ver el comentario
            // de `total_centavos` en CarritoService::resumen().
            $resumen['total_centavos'],
            // La ruta pública de rebote, no la del circuito web: el
            // navegador del sistema no tiene la sesión de Laravel.
            route('cliente.checkout.retorno-movil'),
        );

        // Lo que el webhook va a necesitar y no puede leer de una sesión.
        $carrito->update([
            'estado'           => EstadoCarrito::EnCheckout,
            'mp_preference_id' => $preferencia->id,
        ]);

        return response()->json([
            'data' => [
                'preferencia_id' => $preferencia->id,
                'url_pago'       => $preferencia->urlPago,
                'total'          => $resumen['total'],
            ],
        ]);
    }

    /**
     * GET /api/v1/checkout/estado
     *
     * Qué pasó con el último intento de pago de este cliente.
     *
     * <b>Es lo que la app consulta al volver del navegador, y no reemplaza al
     * webhook: lo lee.</b> El retorno del navegador no confirma nada —esa URL
     * es editable y el cliente puede no volver nunca— así que acá no se mira
     * ninguna query string. Lo único que se reporta es si el webhook, que es
     * el camino autoritativo y que Mercado Pago reintenta, ya produjo una
     * venta.
     *
     * Tres estados:
     *
     *     aprobado    hay venta. Viene su número, su estado y su total.
     *     rechazado   la pasarela registró el intento y no prosperó. El
     *                 carrito está intacto y se puede reintentar.
     *     pendiente   hay un pago en curso sin venta todavía: el webhook no
     *                 llegó aún, o el pago quedó en revisión. La app vuelve
     *                 a preguntar.
     *     sin_pago    no hay ningún intento registrado. El cliente cerró el
     *                 navegador sin pagar, o nunca empezó.
     *
     * <b>Por qué acá sí se le pregunta a la pasarela, y en qué se diferencia
     * del webhook.</b> Sin venta, tres situaciones distintas se ven idénticas
     * desde la base: pago rechazado, checkout abandonado, y pago bueno cuyo
     * webhook todavía no llegó. En la web no hacía falta distinguirlas porque
     * el retorno trae el `payment_id` en la query string; el navegador del
     * sistema vuelve a una página pública que no puede avisarle nada a la
     * app. Así que se consulta por la referencia del carrito.
     *
     * <b>Esa consulta no crea nada.</b> Un `approved` que salga de acá se
     * reporta como `pendiente`, no como aprobado: la venta la crea el
     * webhook, que viene firmado y que Mercado Pago reintenta. Dos caminos
     * creando ventas es exactamente el problema que `confirmar()` evita
     * siendo idempotente, y no hace falta agregar un tercero sin firma.
     *
     * Si la pasarela no responde, se informa `pendiente`: no saber no es lo
     * mismo que saber que no hay pago.
     */
    public function estado(Request $request): JsonResponse
    {
        /** @var User $usuario */
        $usuario = $request->user();

        // El último carrito, abierto o ya convertido. `abiertoDe()` no sirve
        // acá: después de una compra exitosa crearía uno nuevo y vacío, y se
        // perdería de vista justamente la venta que se quiere reportar.
        $carrito = Carrito::query()
            ->where('user_id', $usuario->id)
            ->latest('id')
            ->first();

        if (! $carrito || blank($carrito->mp_preference_id)) {
            return $this->respuestaEstado('sin_pago');
        }

        // El pago se encuentra por la preferencia, que es el único dato que
        // comparten el carrito y el pago: la venta todavía no existía cuando
        // se creó la preferencia, así que no hay una FK que seguir.
        $venta = Pago::query()
            ->where('mp_preference_id', $carrito->mp_preference_id)
            ->latest('id')
            ->first()
            ?->venta;

        if ($venta) {
            return $this->respuestaEstado('aprobado', [
                'venta' => [
                    'id'      => $venta->id,
                    'numero'  => $venta->numeroFormateado(),
                    'estado'  => $venta->estado->value,
                    'total'   => (float) $venta->total,
                    'mensaje' => $this->checkout->mensajeDeCierre($venta),
                ],
            ]);
        }

        // Carrito que volvió a `activo`: el cliente siguió comprando después
        // de un intento que no prosperó. No hay nada que esperar.
        if ($carrito->estado === EstadoCarrito::Activo) {
            return $this->respuestaEstado('sin_pago');
        }

        return $this->respuestaEstado($this->estadoSegunPasarela($carrito));
    }

    // ==================================================================
    // Auxiliares
    // ==================================================================

    /**
     * Traduce lo que dice la pasarela sobre esta referencia.
     *
     * `approved` cae en `pendiente` a propósito: ver el docblock de estado().
     */
    private function estadoSegunPasarela(Carrito $carrito): string
    {
        try {
            $estado = $this->pasarela->ultimoEstadoDeReferencia(
                ReferenciaCarrito::de($carrito)
            );
        } catch (PasarelaPagoException $e) {
            // No poder preguntar no es lo mismo que saber que no hay pago.
            return 'pendiente';
        }

        return match ($estado) {
            null => 'sin_pago',
            'rejected', 'cancelled' => 'rechazado',
            default => 'pendiente',
        };
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function respuestaEstado(string $estado, array $extra = []): JsonResponse
    {
        return response()->json([
            'data' => array_merge([
                'estado' => $estado,
                'venta'  => null,
            ], $extra),
        ]);
    }

    /**
     * El carrito valuado, mas la validacion final y los datos de facturacion.
     */
    private function resumen(Carrito $carrito): CheckoutResource
    {
        $cliente = $carrito->user?->cliente;

        $cliente?->loadMissing(['tipoDocumento', 'condicionIva']);

        return new CheckoutResource([
            'resumen'  => $this->carritos->resumen($carrito),
            'cliente'  => $cliente,
            'bloqueos' => $this->checkout->bloqueosDe($carrito, $cliente),
        ]);
    }
}
