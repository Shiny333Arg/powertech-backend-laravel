<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CarritoLineaMovilRequest;
use App\Http\Resources\CarritoResource;
use App\Models\CarritoLinea;
use App\Models\Producto;
use App\Services\CarritoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Carrito del cliente en la app. Equivale a Http\Controllers\CarritoController,
 * con JSON en lugar de Blade y sobre el mismo CarritoService.
 *
 * <b>El carrito no se identifica en la URL.</b> No hay `{carrito}` ni un
 * index de carritos: siempre es el carrito abierto de quien trae el token,
 * resuelto por `CarritoService::abiertoDe()`. Eso hace estructuralmente
 * imposible operar sobre el carrito ajeno —no hay id que manipular— y es el
 * mismo diseño que la web.
 *
 * Lo que si lleva id es la LINEA, y ahi la pertenencia se verifica contra el
 * carrito abierto de quien pide. Es <b>404 y no 403</b>, igual que en la web:
 * una linea ajena no tiene por que existir para este usuario, y un 403
 * confirmaria que ese id existe.
 *
 * <b>El carrito no toca stock en ninguna de sus formas.</b> Ver CarritoService.
 *
 * <b>Las cinco acciones devuelven el carrito completo</b>, con los totales ya
 * recalculados. Ver CarritoResource.
 *
 * <b>Ningun importe se acepta del cliente.</b> El cuerpo de las dos
 * operaciones de escritura tiene `producto_id` y `cantidad`, nada mas; el
 * precio se calcula contra la base en cada lectura.
 *
 * El middleware de las rutas es `auth:sanctum` + `api.activo` +
 * `api.ambito:tienda` + `api.verificado`. Ver routes/api.php.
 */
class CarritoController extends Controller
{
    public function __construct(
        private readonly CarritoService $carritos,
    ) {}

    /**
     * GET /api/v1/carrito
     *
     * Devuelve el carrito con los precios VIGENTES, no los del momento en que
     * se agrego cada producto. Un cambio de precio o de promocion se ve en la
     * proxima consulta sin que haya que tocar nada.
     *
     * Nunca es 404: un cliente que no tiene carrito recibe uno vacio recien
     * creado. La app no necesita un caso especial para "todavia no compre
     * nunca".
     */
    public function index(Request $request): CarritoResource
    {
        return $this->respuesta($request);
    }

    /**
     * POST /api/v1/carrito  { producto_id, cantidad }
     *
     * Agrega unidades. Si el producto ya estaba en el carrito, SUMA sobre la
     * linea existente en lugar de crear otra: `carrito_lineas` tiene UK
     * (carrito_id, producto_id).
     *
     * El producto se valida en el Request —activo y publicado— asi que un id
     * de un producto despublicado corta en 422 antes de llegar aca. El
     * findOrFail es la red para la carrera entre la validacion y esta linea.
     *
     * <b>201 y no 200.</b> La operacion incorpora el producto al carrito,
     * que es un alta desde el punto de vista del cliente aunque adentro sea
     * un increment sobre una linea que ya existia. El cuerpo es el carrito
     * completo igual que en las otras cuatro acciones: lo que cambia es el
     * codigo, para que la app pueda distinguir "lo agregue" de "lo consulte"
     * sin mirar el contenido.
     */
    public function store(CarritoLineaMovilRequest $request): JsonResponse
    {
        $producto = Producto::findOrFail($request->integer('producto_id'));

        $this->carritos->agregar(
            $this->carritos->abiertoDe($request->user()),
            $producto,
            $request->integer('cantidad'),
        );

        return $this->respuesta($request)
            ->response()
            ->setStatusCode(201);
    }

    /**
     * PATCH /api/v1/carrito/{linea}  { cantidad }
     *
     * Fija la cantidad, no la incrementa: es el control de "-" y "+" de la
     * pantalla del carrito, que manda el valor resultante.
     *
     * La cantidad minima aceptada es 1. Quitar es DELETE, que es la operacion
     * que corresponde y la que la app ya tiene que implementar igual.
     */
    public function update(CarritoLineaMovilRequest $request, CarritoLinea $linea): CarritoResource
    {
        $this->exigirPropiedad($request, $linea);

        $this->carritos->cambiarCantidad($linea, $request->integer('cantidad'));

        return $this->respuesta($request);
    }

    /**
     * DELETE /api/v1/carrito/{linea}
     *
     * Quita el producto del carrito. La linea se borra fisicamente: no es un
     * documento y nada la referencia.
     */
    public function destroy(Request $request, CarritoLinea $linea): CarritoResource
    {
        $this->exigirPropiedad($request, $linea);

        $this->carritos->quitar($linea);

        return $this->respuesta($request);
    }

    /**
     * DELETE /api/v1/carrito
     *
     * Vacia el carrito. El carrito NO se borra: conserva su id y queda
     * utilizable. Ver CarritoService::vaciar().
     *
     * Es idempotente: vaciar un carrito ya vacio responde 200 con el mismo
     * cuerpo. No hay motivo para que la app tenga que preguntar antes.
     */
    public function vaciar(Request $request): CarritoResource
    {
        $this->carritos->vaciar(
            $this->carritos->abiertoDe($request->user())
        );

        return $this->respuesta($request);
    }

    // ==================================================================
    // Auxiliares
    // ==================================================================

    /**
     * El carrito abierto de quien pide, valuado.
     *
     * Se vuelve a resolver desde el usuario en cada accion en lugar de
     * arrastrar el objeto: despues de escribir hay que releer igual para que
     * los totales reflejen el cambio.
     */
    private function respuesta(Request $request): CarritoResource
    {
        return new CarritoResource(
            $this->carritos->resumen(
                $this->carritos->abiertoDe($request->user())
            )
        );
    }

    /**
     * La linea tiene que pertenecer al carrito abierto de QUIEN pide.
     *
     * Sin esto, el id de la URL alcanza para editar el carrito de otro
     * cliente. Copiado de CarritoController::verificarPropiedad(), incluido
     * el criterio de responder 404 y no 403.
     */
    private function exigirPropiedad(Request $request, CarritoLinea $linea): void
    {
        abort_unless(
            $linea->carrito_id === $this->carritos->abiertoDe($request->user())->id,
            404,
        );
    }
}
