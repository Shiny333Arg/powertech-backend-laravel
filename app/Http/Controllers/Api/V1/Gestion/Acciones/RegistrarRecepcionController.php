<?php

namespace App\Http\Controllers\Api\V1\Gestion\Acciones;

use App\Http\Controllers\Api\V1\Gestion\Concerns\SirveElPedido;
use App\Http\Controllers\Controller;
use App\Http\Requests\RecepcionRequest;
use App\Models\OrdenCompra;
use App\Services\RecepcionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * POST /api/v1/gestion/compras/{orden}/recepciones
 *
 * La entrada de mercadería contra un pedido a proveedor. <b>Es el único
 * endpoint de la API de compras que toca el inventario</b>, y el único punto
 * del circuito por el que una compra suma stock.
 *
 * <b>Sub-recurso en plural porque es una colección de verdad:</b> cada POST
 * agrega una entrega al pedido, y un pedido puede recibir tres remitos
 * distintos. De ahí el 201. El historial de lo que ya entró viaja en el detalle,
 * en `data.recepciones`.
 *
 * <b>Este controlador no mueve stock ni toca estados.</b> Todo eso vive en
 * RecepcionService, que es el mismo que usa el formulario del panel: acumula en
 * `cantidad_recibida` con un UPDATE relativo, registra el movimiento de compra
 * por StockService con el remito en el motivo, decide el estado del pedido
 * mirando el acumulado de TODOS los renglones, sincroniza el pedido madre y
 * recorre la cola de preventas después del commit. Lo propio de este archivo
 * son tres cosas, y ninguna es una regla de negocio nueva: la guarda del remito
 * repetido, la revalidación del excedente con el candado tomado, y la forma de
 * la respuesta.
 *
 * <b>Cuerpo.</b> `cantidades` es un mapa `id_de_renglón → unidades que entran
 * ahora`, la misma forma que manda el formulario del panel. Un renglón que no
 * vino se omite o va en cero: un cero no es un error. `remito` es el número del
 * proveedor y `confirmar_excedente` habilita registrar de más.
 *
 * <b>Respuestas:</b>
 *
 *   201  el detalle del pedido —con `recepciones[]` ya actualizado— más
 *        `recepcion`, el resumen que devuelve el servicio
 *   403  sin 'compra.recibir', o el pedido no admite recepciones: borrador,
 *        aprobada sin enviar, ya recibida, cancelada, o un pedido repartido
 *        —la mercadería se recibe en la orden de cada proveedor— (policy)
 *   409  'REMITO_DUPLICADO': ese remito ya fue cargado contra este pedido
 *   422  cantidades vacías, negativas o de un renglón ajeno; excedente sin
 *        confirmar; u otra operación en curso sobre el mismo pedido
 *   404  el pedido no existe
 */
class RegistrarRecepcionController extends Controller
{
    use SirveElPedido;

    /**
     * El candado vive 60 segundos y no 30 como el resto de las transiciones:
     * la recepción lo retiene durante la liberación de preventas, que corre
     * después del commit y recorre la cola de ventas de cada producto que
     * entró. Ver SirveElPedido::enTransicion().
     */
    private const SEGUNDOS_DE_CANDADO = 60;

    public function __construct(
        private readonly RecepcionService $recepciones,
    ) {}

    public function __invoke(RecepcionRequest $request, OrdenCompra $orden): JsonResponse
    {
        return $this->enTransicion($orden, function (OrdenCompra $pedido) use ($request): JsonResponse {

            // ----------------------------------------------------------
            // Las dos comprobaciones que tienen que pasar DENTRO del candado
            // ----------------------------------------------------------
            //
            // Las dos leen de la base cuánto se recibió hasta ahora, y las dos
            // darían un resultado viejo si corrieran donde corre la validación
            // del FormRequest: el pipeline resuelve RecepcionRequest ANTES de
            // entrar acá, es decir antes de que el candado esté tomado. Con el
            // candado puesto y el pedido ya releído por enTransicion(), lo que
            // se lee es lo que la petición anterior dejó commiteado.

            if ($this->remitoYaCargado($pedido, $request->remito())) {
                return response()->json([
                    'message' => "El remito «{$request->remito()}» ya fue cargado contra el pedido {$pedido->numero}. "
                        .'Si llegó una segunda entrega, registrala con su propio número de remito.',
                    'codigo'  => 'REMITO_DUPLICADO',
                ], Response::HTTP_CONFLICT);
            }

            // Misma pregunta que hizo el FormRequest y con el mismo texto: el
            // método es suyo. Acá se la vuelve a hacer con el dato firme.
            $aviso = $request->avisoDeExcedente();

            if ($aviso !== null) {
                throw ValidationException::withMessages(['confirmar_excedente' => $aviso]);
            }

            // ----------------------------------------------------------
            // La entrega
            // ----------------------------------------------------------

            try {
                $resultado = $this->recepciones->registrar(
                    $pedido,
                    $request->cantidades(),
                    $request->user(),
                    $request->remito(),
                );
            } catch (InvalidArgumentException $error) {
                // Cantidad negativa o cuerpo sin nada que recibir. El
                // RecepcionRequest ya los rechaza, así que esto es defensa en
                // profundidad; se atrapa igual porque InvalidArgumentException
                // no está en el render de bootstrap/app.php y saldría como 500.
                //
                // <b>No se agrega al render global a propósito:</b> es una
                // excepción genérica de PHP, y mapearla a 422 en toda la API
                // taparía errores de programación en cualquier otro endpoint.
                throw ValidationException::withMessages(['cantidades' => $error->getMessage()]);
            }

            return $this->detalle($resultado['orden'])
                ->additional([
                    'recepcion' => [
                        // El remito es lo que agrupa esta entrega dentro de
                        // 'data.recepciones': los movimientos que acaban de
                        // entrar son los que lo llevan en su 'motivo'.
                        'remito'    => $request->remito(),
                        'unidades'  => $resultado['unidades'],
                        'renglones' => $resultado['lineas'],

                        // El pedido quedó servido por completo con esta entrega.
                        'cerrada' => $resultado['cerrada'],

                        // Lo que el panel muestra como «se destrabaron N ventas
                        // que esperaban stock». Es información que el depósito
                        // no tiene de otra forma: la mercadería que acaba de
                        // entrar puede estar ya vendida y esperada por alguien
                        // que pagó hace tres semanas.
                        'ventas_liberadas' => $resultado['ventas_liberadas'],
                    ],
                ])
                ->response()
                ->setStatusCode(Response::HTTP_CREATED);

        }, self::SEGUNDOS_DE_CANDADO);
    }

    /**
     * ¿Este remito ya entró contra este pedido?
     *
     * <b>Es la guarda de idempotencia.</b> Nada más en la cadena distingue «dos
     * entregas legítimas» de «la misma entrega mandada dos veces»:
     * `recibida_parcial → recibida_parcial` es una transición válida y los
     * incrementos son relativos, así que un cliente que reintenta por timeout
     * de red sumaría las unidades dos veces al físico, con dos movimientos de
     * stock, y podría destrabar preventas contra mercadería que no existe. El
     * único arreglo posterior sería un ajuste negativo a mano.
     *
     * <b>El alcance es por pedido, no global.</b> `recepciones()` solo mira los
     * movimientos cuyo origen es esta orden. El mismo número de remito contra
     * otro pedido se acepta, y es lo correcto: lo que se impide es cargar dos
     * veces la misma entrega contra el mismo documento, no que dos proveedores
     * usen numeraciones que se pisan.
     *
     * <b>Sin remito no hay idempotencia</b>, y no es un descuido: sin número no
     * hay nada que comparar, y dos entregas del mismo proveedor en el mismo día
     * sin documento pueden ser las dos legítimas. La forma de tenerla es cargar
     * el remito.
     *
     * Consulta barata: el índice movimientos_stock_origen_index sobre
     * (origen_type, origen_id) acota a los movimientos de esta orden, que son
     * unos pocos, y el filtro por motivo se resuelve sobre ese puñado.
     */
    private function remitoYaCargado(OrdenCompra $orden, ?string $remito): bool
    {
        if ($remito === null) {
            return false;
        }

        return $orden->recepciones()->where('motivo', $remito)->exists();
    }
}
