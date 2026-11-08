<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CheckoutMovilRequest;
use App\Http\Resources\CheckoutResource;
use App\Models\Carrito;
use App\Services\CarritoService;
use App\Services\CheckoutService;
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

    // ==================================================================
    // Auxiliares
    // ==================================================================

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
