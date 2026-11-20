<?php

namespace App\Http\Controllers\Api\V1\Gestion;

use App\Enums\EstadoOrdenCompra;
use App\Enums\OrigenOrdenCompra;
use App\Http\Controllers\Controller;
use App\Http\Requests\OrdenCompraRequest;
use App\Http\Resources\Gestion\OrdenCompraDetalleResource;
use App\Http\Resources\Gestion\OrdenCompraResource;
use App\Models\OrdenCompra;
use App\Services\OrdenCompraService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

/**
 * Pedidos a proveedores, por API. Entrega E.1: consultar y crear borradores.
 *
 * <b>Este controlador no arma ordenes</b>, igual que el del panel: no crea
 * lineas, no calcula totales, no asigna numeros y no toca estados. Todo eso
 * vive en OrdenCompraService, que es el mismo que usa el restock automatico.
 * Que los tres caminos —la pantalla, la app y el automatico— pasen por el mismo
 * servicio es lo que garantiza que una orden cargada desde cualquiera de ellos
 * sea el mismo documento.
 *
 * <b>Crear un borrador NO toca el inventario.</b> Pedir no es recibir: el stock
 * se mueve en un solo lugar del circuito, que es la recepcion de mercaderia
 * (RecepcionService con StockService), y ni este controlador ni
 * OrdenCompraService::crear() emiten movimientos. Un borrador es una intencion
 * de compra; lo unico que cambia en la base son la orden, sus renglones y el
 * total estimado.
 *
 * <b>Alcance de E.1.</b> Aprobar, enviar, recibir y cancelar NO tienen endpoint
 * todavia: son E.2. La ausencia es deliberada y no un olvido —la policy ya
 * tiene los metodos y los permisos ya existen—, asi que esas rutas responden
 * 405 y no 403. Lo mismo vale para la edicion del detalle: en E.1 no hay PUT.
 *
 * <b>La autorizacion esta en las rutas, no en el constructor.</b> A diferencia
 * del controlador web, que usa authorizeResource(): ver el comentario de
 * routes/api_gestion.php.
 */
class OrdenCompraController extends Controller
{
    /** Mismo tamaño de pagina que el panel; el tope es para que nadie pida 10.000. */
    private const POR_PAGINA = 15;

    private const POR_PAGINA_MAX = 100;

    public function __construct(
        private readonly OrdenCompraService $ordenes,
    ) {}

    /**
     * GET /api/v1/gestion/compras
     *
     * <b>Muestra pedidos, no repartos.</b> Solo ordenes raiz, igual que el
     * panel: una hija ya esta representada por su madre, y listar las dos
     * convertiria un pedido de tres proveedores en cuatro filas sin que se
     * sepa cual es el pedido. Cada hija sigue siendo consultable por su id en
     * el detalle, y aparece dentro del de su madre.
     *
     * Los mismos tres filtros del panel y con los mismos nombres. La diferencia
     * es que acá 'estado' y 'origen' se validan contra el enum: la web los pasa
     * crudos al where y un valor inventado devuelve una lista vacia, que es
     * indistinguible de «no hay pedidos en ese estado».
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filtros = $request->validate([
            'q'          => ['nullable', 'string', 'max:150'],
            'estado'     => ['nullable', Rule::in(EstadoOrdenCompra::valores())],
            'origen'     => ['nullable', Rule::in(OrigenOrdenCompra::valores())],
            'por_pagina' => ['nullable', 'integer', 'min:1', 'max:'.self::POR_PAGINA_MAX],
        ]);

        $busqueda = $filtros['q'] ?? null;
        $estado   = $filtros['estado'] ?? null;
        $origen   = $filtros['origen'] ?? null;

        $ordenes = OrdenCompra::query()
            ->raiz()
            // Las dos relaciones de proveedor que mira proveedoresInvolucrados():
            // las hijas si el pedido ya se repartio, las lineas si sigue en
            // borrador. Cargarlas acá convierte un N+1 por fila en dos
            // consultas. Las lineas van con select acotado porque el listado no
            // publica renglones —de eso se ocupa el detalle—; es el motivo por
            // el que el listado tiene su propio Resource.
            ->with([
                'proveedor:id,razon_social',
                'hijas:id,orden_padre_id,proveedor_id,estado',
                'hijas.proveedor:id,razon_social',
                'lineas:id,orden_compra_id,proveedor_id',
                'lineas.proveedor:id,razon_social',
            ])
            ->withCount('lineas')
            ->when($busqueda, function ($query, $busqueda) {
                $query->where(function ($sub) use ($busqueda) {
                    $sub->where('numero', 'like', "%{$busqueda}%")
                        ->orWhereHas('proveedor', fn ($p) => $p->where('razon_social', 'like', "%{$busqueda}%"))
                        // Un pedido repartido no tiene proveedor propio: se lo
                        // busca por el de cualquiera de sus ordenes.
                        ->orWhereHas('hijas.proveedor', fn ($p) => $p->where('razon_social', 'like', "%{$busqueda}%"));
                });
            })
            ->when($estado, fn ($query, $estado) => $query->where('estado', $estado))
            ->when($origen, fn ($query, $origen) => $query->where('origen', $origen))
            // Pendientes de aprobacion primero: es lo que el administrativo
            // entra a hacer. Ordenar solo por fecha dejaria lo accionable
            // mezclado con lo que ya siguio su curso.
            //
            // <b>CASE WHEN y no el FIELD() del panel.</b> FIELD() es de
            // MySQL/MariaDB; esta forma la entienden los dos motores y produce
            // el mismo orden, asi que el listado se puede cubrir con pruebas
            // —que corren sobre SQLite en memoria—. El controlador web queda
            // como esta.
            ->orderByRaw('CASE WHEN estado = ? THEN 0 ELSE 1 END', [EstadoOrdenCompra::Borrador->value])
            ->latest('id')
            ->paginate($filtros['por_pagina'] ?? self::POR_PAGINA)
            ->withQueryString();

        // Los filtros REALMENTE aplicados, misma convencion que las entregas
        // anteriores: la pantalla no tiene que adivinar si el backend acepto lo
        // que mando.
        return OrdenCompraResource::collection($ordenes)->additional([
            'filtros' => [
                'q'      => $busqueda,
                'estado' => $estado,
                'origen' => $origen,
            ],
        ]);
    }

    /**
     * GET /api/v1/gestion/compras/{orden}
     *
     * Sirve cualquier orden, incluida una hija: es una orden real —tiene su
     * numero, su remito y su recepcion— y el detalle es donde se la mira. No se
     * restringe a las raiz como el listado, porque acá no hay ambiguedad: se
     * pidio un documento puntual.
     */
    public function show(OrdenCompra $orden): OrdenCompraDetalleResource
    {
        return new OrdenCompraDetalleResource($this->recargar($orden));
    }

    /**
     * POST /api/v1/gestion/compras — alta en borrador.
     *
     * Delega entero en OrdenCompraService::crear(), que es el mismo metodo que
     * usa el formulario del panel. Lo que el cuerpo NO decide, y la ausencia es
     * la regla (ver OrdenCompraRequest):
     *
     *   numero         lo asigna el numerador dentro de la transaccion.
     *   estado         nace borrador. No se mueve con un campo, se mueve con
     *                  transiciones, y esas son E.2.
     *   origen         lo fija el servicio en 'manual'. Dejar que el cuerpo lo
     *                  mande permitiria disfrazar una manual de automatica y
     *                  arruinaria la unica medicion que dice si los umbrales
     *                  del restock estan calibrados.
     *   proveedor_id   sale del detalle: con un solo proveedor la orden queda
     *                  dirigida a el, con varios queda en NULL y se reparte al
     *                  aprobarse. Aceptarlo del cuerpo permitiria emitirle a
     *                  una empresa un pedido de productos que no vende.
     *   costo_unitario sale del catalogo: el dato tiene un solo dueño, que es
     *                  la ficha del producto.
     *   total_estimado se recalcula desde los renglones.
     *
     * Devuelve 201 con el detalle completo, para que el cliente no tenga que
     * hacer un GET detras del POST solo para saber que numero le toco.
     */
    public function store(OrdenCompraRequest $request): JsonResponse
    {
        $orden = $this->ordenes->crear(
            $request->lineas(),
            $request->user(),
            $request->validated('observaciones'),
        );

        return (new OrdenCompraDetalleResource($this->recargar($orden)))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Relee la orden con todo lo que el detalle serializa.
     *
     * Son las mismas relaciones que carga el show del panel. El proveedor va
     * completo y no con select acotado porque el detalle muestra su contacto y
     * su casilla; las hijas y el padre van siempre, aunque esten vacios, para
     * que la respuesta tenga la misma forma sea cual sea la forma del documento.
     */
    private function recargar(OrdenCompra $orden): OrdenCompra
    {
        return $orden->refresh()->load([
            'proveedor',
            'lineas.producto',
            'lineas.proveedor',
            'usuarioCreo',
            'usuarioAprobo',
            'hijas.proveedor',
            'padre:id,numero',
        ]);
    }
}
