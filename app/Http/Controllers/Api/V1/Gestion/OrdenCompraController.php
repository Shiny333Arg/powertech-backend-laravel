<?php

namespace App\Http\Controllers\Api\V1\Gestion;

use App\Enums\EstadoOrdenCompra;
use App\Enums\OrigenOrdenCompra;
use App\Http\Controllers\Api\V1\Gestion\Concerns\SirveElPedido;
use App\Http\Controllers\Controller;
use App\Http\Requests\OrdenCompraRequest;
use App\Http\Resources\Gestion\OrdenCompraDetalleResource;
use App\Http\Resources\Gestion\OrdenCompraResource;
use App\Models\OrdenCompra;
use App\Models\OrdenCompraLinea;
use App\Models\Producto;
use App\Services\OrdenCompraService;
use App\Services\ReposicionService;
use Illuminate\Database\Eloquent\Builder;
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
 * <b>Alcance.</b> Este controlador es el de consulta, alta y edicion del
 * borrador, mas las sugerencias de reposicion que alimentan el alta. Las
 * transiciones del documento —aprobar, enviar y cancelar— son controladores de
 * accion unica aparte, en Acciones\, desde E.2.a; recibir y cerrar con
 * faltante tambien, desde E.2.b.
 *
 * <b>El PUT y las sugerencias entraron con la migracion del panel</b>, que es
 * lo que los hizo necesarios: mientras el formulario Blade guardaba por POST
 * tradicional, la edicion del detalle y la lista de lo que falta pedir vivian
 * en el controlador web. Al pasar la pantalla a la API, las rutas web
 * 'gestion.compras.update' y 'gestion.compras.sugerencias' se retiraron y su
 * logica se MUDO aca: no hay dos implementaciones, hay una sola y cambio de
 * lugar.
 *
 * <b>La autorizacion esta en las rutas, no en el constructor.</b> A diferencia
 * del controlador web, que usa authorizeResource(): ver el comentario de
 * routes/api_gestion.php.
 */
class OrdenCompraController extends Controller
{
    // recargar() y detalle() viven en el concern: desde E.2.a los comparte con
    // los controladores de las transiciones, que devuelven el mismo detalle.
    // La lista de relaciones que exige OrdenCompraDetalleResource tiene que
    // estar en un solo lugar.
    use SirveElPedido;

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
        return $this->detalle($orden);
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

        return $this->detalle($orden)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
    /**
     * PUT /api/v1/gestion/compras/{orden} — edicion del detalle del borrador.
     *
     * Reemplaza los renglones y las observaciones. Delega entero en
     * OrdenCompraService::actualizar(), que es el mismo metodo que usaba el
     * formulario del panel: borra las lineas, las vuelve a crear con el costo
     * del catalogo del momento, recalcula el total estimado y redefine el
     * destinatario —un pedido que se queda con renglones de un solo proveedor
     * vuelve a estar dirigido a el; uno al que le agregan productos de otro
     * pasa a repartirse al aprobarse—.
     *
     * <b>Solo un borrador.</b> La policy ya lo exige en la ruta
     * —update() consulta esEditable(), asi que una orden aprobada o enviada
     * responde 403 y no 422: el estado manda sobre el permiso—. El servicio lo
     * vuelve a exigir por su cuenta, y esa segunda guarda no es redundante: lo
     * protege tambien del restock, que lo llama sin pasar por ninguna ruta.
     *
     * <b>Lo que el cuerpo NO decide es lo mismo que en el alta</b> (ver store()
     * y OrdenCompraRequest): ni numero, ni estado, ni origen, ni proveedor, ni
     * costos, ni total. Por eso los dos verbos comparten el FormRequest.
     *
     * <b>Por que va dentro del candado.</b> Editar no es una transicion, pero
     * escribe el detalle, y el detalle es exactamente lo que lee aprobar() para
     * repartir por proveedor: sin serializar, una edicion que entra entre la
     * policy y el reparto de una aprobacion simultanea deja emitidas ordenes
     * que no se corresponden con los renglones guardados. Es el mismo candado y
     * el mismo almacen que ya usan las tres transiciones —ver
     * SirveElPedido::enTransicion()—, con el TTL por omision: la edicion no
     * manda correos ni recorre colas, asi que 30 segundos sobran.
     *
     * Devuelve 200 con el detalle completo, igual que el GET: la pantalla
     * repinta con la respuesta y no necesita un segundo pedido.
     */
    public function update(OrdenCompraRequest $request, OrdenCompra $orden): OrdenCompraDetalleResource
    {
        return $this->enTransicion($orden, function (OrdenCompra $pedido) use ($request) {
            $this->ordenes->actualizar(
                $pedido,
                $request->lineas(),
                $request->validated('observaciones'),
            );

            return $this->detalle($pedido);
        });
    }

    // ------------------------------------------------------------------
    // Sugerencias para el alta
    // ------------------------------------------------------------------

    /**
     * GET /api/v1/gestion/compras/sugerencias
     *
     * Lo que hay que pedir y nadie pidio todavia, listo para que el formulario
     * lo cargue de una. <b>No crea nada</b>: es una consulta, y por eso es un
     * GET y no un POST que abra borradores.
     *
     * <b>«Todavia no pedido» significa que ningun proveedor lo sabe.</b> Un
     * producto que solo figura en un BORRADOR entra igual en la lista: un
     * borrador no es un compromiso, no salio ningun remito, y el administrativo
     * que arma este pedido lo esta reemplazando. Lo que deja un producto afuera
     * es una orden aprobada, enviada o recibida a medias, porque esas ya estan
     * en manos del proveedor y pedir de nuevo seria comprar dos veces.
     *
     * <b>El borrador que queda cubierto no se toca desde aca.</b> Se cancela
     * recien cuando este pedido se apruebe, en
     * OrdenCompraService::anularBorradoresSuperados(). Mientras tanto los dos
     * conviven, que es lo correcto: si el administrativo abandona el formulario
     * sin guardar, el borrador automatico sigue en pie y nadie perdio nada.
     *
     * <b>Los que no se pueden pedir se informan, no se agregan.</b> Un renglon
     * sin proveedor —o con el proveedor dado de baja— deja el pedido sin poder
     * guardarse, porque OrdenCompraRequest lo rechaza, y si llegara a aprobarse
     * reventaria en repartirPorProveedor(). Meterlos en 'data' convertiria una
     * ayuda en un bloqueo, asi que viajan aparte en 'omitidos' y la pantalla
     * dice cuales son y por que.
     *
     * <b>Se protege con el permiso de crear pedidos y no con 'compra.ver'</b>,
     * igual que hacia el controlador web: esta lista existe para armar un
     * pedido, y quien solo consulta compras no tiene nada que hacer con ella.
     * Lo exige el 'can:create' de la ruta.
     *
     * La cantidad sale de ReposicionService::cantidadSugerida(), que es la
     * misma cuenta que usa el restock automatico: lo que falta para volver al
     * umbral mas el lote de compra. Que las dos vias pidan lo mismo es lo que
     * hace comparables un pedido armado a mano y uno generado solo.
     */
    public function sugerencias(ReposicionService $reposicion): JsonResponse
    {
        $productos = Producto::query()
            ->activos()
            ->bajoPuntoReposicion()
            // El filtro no es por 'reposicion_automatica': un producto con
            // reposicion manual y el stock en el piso es justamente lo que
            // nadie va a pedir solo, y es el motivo por el que alguien abre
            // esta pantalla.
            ->whereNotIn('id', $this->productosYaPedidos())
            ->with('proveedor:id,razon_social,activo')
            ->orderBy('nombre')
            ->get();

        $renglones = [];
        $omitidos  = [];

        foreach ($productos as $producto) {
            $proveedor = $producto->proveedor;

            if ($proveedor === null) {
                $omitidos[] = $this->omitido($producto, 'no tiene proveedor asignado');

                continue;
            }

            if (! $proveedor->activo) {
                $omitidos[] = $this->omitido($producto, "«{$proveedor->razon_social}» está dado de baja");

                continue;
            }

            $renglones[] = [
                'producto_id'       => $producto->id,
                'codigo'            => $producto->codigo,
                'nombre'            => $producto->nombre,
                'marca'             => $producto->marca,
                'costo'             => $producto->costo === null ? null : (float) $producto->costo,
                'cantidad_sugerida' => $reposicion->cantidadSugerida($producto),
                'proveedor'         => [
                    'id'           => $proveedor->id,
                    'razon_social' => $proveedor->razon_social,
                    'activo'       => (bool) $proveedor->activo,
                ],
            ];
        }

        return response()->json([
            'data'     => $renglones,
            'omitidos' => $omitidos,
        ]);
    }

    /**
     * Los ids de producto que ya estan en manos de algun proveedor.
     *
     * Una sola consulta en vez de preguntarle a cada producto por su orden
     * abierta: sobre un catalogo entero en falta, eso es un N+1.
     *
     * <b>whereNotNull('proveedor_id')</b> replica la regla de
     * OrdenCompra::scopeConProducto(): una madre ya repartida no cubre nada,
     * porque quien puede cumplir el pedido es la hija. Aca la condicion alcanza
     * sola porque los tres estados que miramos excluyen el borrador, que es el
     * unico caso en que una madre si cuenta.
     *
     * <b>No es ReposicionService::ordenAbiertaDe()</b>, y la diferencia es
     * deliberada: ese metodo cuenta el borrador como orden abierta —para que el
     * barrido automatico amplie el que ya existe en lugar de abrir otro— y esta
     * pantalla no, porque el pedido que se esta armando reemplaza al borrador.
     *
     * @return list<int>
     */
    private function productosYaPedidos(): array
    {
        return OrdenCompraLinea::query()
            ->whereHas('ordenCompra', fn (Builder $orden) => $orden
                ->whereIn('estado', [
                    EstadoOrdenCompra::Aprobada->value,
                    EstadoOrdenCompra::Enviada->value,
                    EstadoOrdenCompra::RecibidaParcial->value,
                ])
                ->whereNotNull('proveedor_id'))
            ->distinct()
            ->pluck('producto_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /** @return array<string, mixed> */
    private function omitido(Producto $producto, string $motivo): array
    {
        return [
            'producto_id' => $producto->id,
            'codigo'      => $producto->codigo,
            'nombre'      => $producto->nombre,
            'motivo'      => $motivo,
        ];
    }
}
