<?php

namespace App\Http\Controllers\Api\V1\Gestion\Acciones;

use App\Http\Controllers\Controller;
use App\Http\Requests\AjusteStockRequest;
use App\Http\Resources\Gestion\MovimientoStockResource;
use App\Http\Resources\Gestion\ProductoResource;
use App\Models\Producto;
use App\Services\EntregaService;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * POST /api/v1/gestion/productos/{producto}/stock/ajustes
 *
 * Ajuste manual de inventario: diferencias de conteo, roturas, mermas. Es el
 * unico endpoint de la API que escribe stock, y por eso esta en un archivo
 * propio y con su propio permiso (`stock.ajustar`, que el vendedor no tiene
 * aunque si pueda mirar el kardex).
 *
 * <b>Cuerpo:</b> `cantidad` (con signo) y `motivo`. El producto va en la ruta,
 * y `AjusteStockRequest::prepareForValidation()` lo copia a `producto_id`: un
 * `producto_id` que venga en el cuerpo se pisa, asi que no hay manera de
 * ajustar un producto distinto del que dice la URL.
 *
 * <b>Se envuelve el camino del panel, no se reescribe.</b> La misma
 * transaccion, el mismo `StockService::ajustar()` y la misma revision de la
 * cola de preventas. Las dos interfaces registran el ajuste exactamente igual;
 * lo unico propio de este archivo es la forma de la respuesta.
 *
 * <b>Respuestas:</b>
 *
 *   201  con el movimiento registrado, la situacion del producto y cuantas
 *        ventas se destrabaron
 *   409  si el ajuste negativo dejaria el fisico bajo cero — lo arma el render
 *        de bootstrap/app.php a partir de StockInsuficienteException
 *   422  validacion (sin motivo, cantidad cero, producto inexistente)
 *   403  sin el permiso
 *
 * El 409 no se atrapa a proposito. El panel si lo convierte en un error de
 * validacion sobre el campo, porque ahi el usuario tiene el formulario
 * delante y hay que devolverlo con el dato marcado; en la API un conflicto con
 * el estado actual es justamente lo que el codigo 409 significa, y el cliente
 * ya lo distingue del 422.
 */
class AjustarStockController extends Controller
{
    public function __construct(
        private readonly StockService $stock,
        private readonly EntregaService $entregas,
    ) {}

    public function __invoke(AjusteStockRequest $request, Producto $producto): JsonResponse
    {
        $delta  = (int) $request->validated('cantidad');
        $motivo = $request->validated('motivo');

        [$movimiento, $liberadas] = DB::transaction(function () use ($producto, $delta, $motivo, $request) {
            $movimiento = $this->stock->ajustar($producto, $delta, $motivo, $request->user());

            // Un ajuste positivo puede destrabar ventas que estaban esperando
            // mercaderia. Se revisa solo al sumar, igual que en el panel.
            $liberadas = $delta > 0 ? $this->entregas->liberarCola($producto->fresh()) : 0;

            return [$movimiento, $liberadas];
        });

        return (new MovimientoStockResource($movimiento->load('usuario:id,nombres,apellido')))
            ->additional([
                // El saldo que quedo, para que la pantalla no tenga que volver
                // a pedirlo. refresh() porque el UPDATE del servicio es
                // relativo y el modelo en memoria sigue con el valor viejo.
                'producto' => new ProductoResource(
                    $producto->refresh()->load(['categoria:id,nombre', 'proveedor:id,razon_social', 'alicuotaIva:id,nombre,porcentaje'])
                ),

                // Es lo que el panel muestra como «N venta(s) pendientes de
                // stock pasaron a preparación».
                'ventas_liberadas' => $liberadas,
            ])
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
