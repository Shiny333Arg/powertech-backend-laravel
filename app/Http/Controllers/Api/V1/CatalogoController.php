<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OrdenCatalogo;
use App\Http\Controllers\Controller;
use App\Http\Requests\BusquedaProductoRequest;
use App\Http\Resources\CategoriaResource;
use App\Http\Resources\ProductoDetalleResource;
use App\Http\Resources\ProductoResource;
use App\Http\Resources\PromocionResource;
use App\Models\Producto;
use App\Services\BuscadorCatalogo;
use App\Support\VisitanteTienda;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Catalogo de la tienda para la app. Solo lectura.
 *
 * <b>Publico con autenticacion opcional.</b> Ninguna de estas rutas lleva
 * 'auth:sanctum': el invitado tiene que poder recorrer la vitrina sin cuenta,
 * y un token vencido o invalido no puede transformar una consulta publica en
 * un 401. El usuario se resuelve con VisitanteTienda, que devuelve "invitado"
 * en los tres casos (sin token, token vencido, token basura).
 *
 * <b>Lo unico que cambia entre invitado y cliente es el precio.</b> Mismos
 * productos, mismo orden, misma paginacion, misma disponibilidad. Es la misma
 * regla que ya aplican tarjeta-producto.blade.php e item/show.blade.php con
 * @auth, trasladada al JSON.
 *
 * Las consultas viven en BuscadorCatalogo; aca solo se arma la respuesta.
 */
class CatalogoController extends Controller
{
    public function __construct(
        private readonly BuscadorCatalogo $buscador,
    ) {
    }

    // ==================================================================
    // Vitrina
    // ==================================================================

    /**
     * GET /api/v1/home
     *
     * Promociones con banner y las tres secciones de mas vendidos.
     *
     * Los precios de las tres secciones se resuelven en UNA sola pasada del
     * MotorPromociones: se juntan todos los productos, se consulta una vez y
     * despues se anota cada seccion con ese mismo resultado. Una llamada por
     * seccion serian tres consultas para el mismo trabajo.
     */
    public function home(Request $request): JsonResponse
    {
        $visitante = VisitanteTienda::de($request);

        $secciones = $this->buscador->vitrina();

        $todos = collect($secciones)
            ->flatMap(fn (array $seccion) => $seccion['productos']->all())
            ->keyBy('id');

        $precios = $this->buscador->preciosDe($todos, $visitante);

        foreach ($secciones as $seccion) {
            $this->buscador->anotarPrecios($seccion['productos'], $precios);
        }

        return response()->json([
            'promociones' => PromocionResource::collection(
                $this->buscador->promocionesVigentes()
            ),

            'secciones' => collect($secciones)
                ->map(fn (array $seccion, string $clave) => [
                    'clave'     => $clave,
                    'titulo'    => $seccion['titulo'],
                    'productos' => ProductoResource::collection($seccion['productos']),
                ])
                ->values(),
        ]);
    }

    // ==================================================================
    // Busqueda
    // ==================================================================

    /**
     * GET /api/v1/productos
     *
     * Catalogo paginado. Filtros por querystring, igual que la web:
     *   ?q=&categoria=&marca=&precio_min=&precio_max=&orden=&promocion=
     *
     * BusquedaProductoRequest se reutiliza sin tocarlo: su criterio es sanear
     * y no rechazar —los parametros los arma el usuario, un enlace compartido
     * o un banner viejo— que es exactamente lo que conviene en la API.
     *
     * <b>Para un invitado, precio_min, precio_max y el orden por precio se
     * ignoran.</b> Ver BuscadorCatalogo::aplicarFiltrosDePrecio(). La
     * respuesta devuelve los filtros REALMENTE aplicados, asi que la app
     * puede mostrar el estado correcto en lugar de sostener en pantalla un
     * filtro que el backend descarto.
     */
    public function index(BusquedaProductoRequest $request): AnonymousResourceCollection
    {
        $visitante = VisitanteTienda::de($request);

        $filtros = $request->filtros();

        // Si la promocion del filtro no existe o ya vencio, se descarta el
        // filtro en lugar de devolver una lista vacia.
        $promocion = $this->buscador->promocionDelFiltro($filtros['promocion']);

        if ($promocion === null) {
            $filtros['promocion'] = null;
        }

        $productos = $this->buscador->buscar($filtros, $visitante);

        $precios = $this->buscador->preciosDe(
            $productos->getCollection()->keyBy('id'),
            $visitante,
        );

        $this->buscador->anotarPrecios($productos->getCollection(), $precios);

        $puedeVerPrecios = $visitante->puedeVerPrecios();

        return ProductoResource::collection($productos)->additional([
            'filtros' => [
                'q'          => $filtros['q'],
                'categoria'  => $filtros['categoria'],
                'marca'      => $filtros['marca'],
                'precio_min' => $puedeVerPrecios ? $filtros['precio_min'] : null,
                'precio_max' => $puedeVerPrecios ? $filtros['precio_max'] : null,
                'promocion'  => $promocion?->id,
                'orden'      => $this->buscador->ordenEfectivo($filtros['orden'], $visitante)->value,
            ],

            // La promocion del filtro, para que la app pueda titular la lista
            // con el nombre del banner que el cliente toco.
            'promocion' => $promocion ? new PromocionResource($promocion) : null,

            'ordenes' => $this->ordenesDisponibles($puedeVerPrecios),
        ]);
    }

    /**
     * GET /api/v1/productos/{producto}
     *
     * 404 si no esta publicado: un producto despublicado no tiene que ser
     * alcanzable por URL directa, igual que en HomeController::producto().
     *
     * El producto y sus relacionados se resuelven en una sola pasada del
     * motor: son los mismos objetos en memoria, asi que anotar la coleccion
     * completa alcanza para los dos.
     */
    public function show(Request $request, Producto $producto): ProductoDetalleResource
    {
        abort_unless($producto->activo && $producto->publicado_app, 404);

        $visitante = VisitanteTienda::de($request);

        $producto->load('categoria:id,nombre');

        $relacionados = $this->buscador->relacionadosDe($producto);

        $todos = $relacionados->keyBy('id')->put($producto->id, $producto);

        $precios = $this->buscador->preciosDe($todos, $visitante);

        $this->buscador->anotarPrecios($todos, $precios);

        return new ProductoDetalleResource(
            $producto,
            $this->buscador->promocionesAplicadas($precios[$producto->id]['aplicaciones'] ?? []),
            $relacionados,
        );
    }

    // ==================================================================
    // Filtros disponibles
    // ==================================================================

    /** GET /api/v1/categorias */
    public function categorias(): AnonymousResourceCollection
    {
        return CategoriaResource::collection(
            $this->buscador->categoriasConProductos()
        );
    }

    /**
     * GET /api/v1/marcas
     *
     * Texto libre, no una tabla: va como lista de cadenas y no como recurso.
     */
    public function marcas(): JsonResponse
    {
        return response()->json([
            'data' => $this->buscador->marcasDisponibles()->values(),
        ]);
    }

    /** GET /api/v1/promociones */
    public function promociones(): AnonymousResourceCollection
    {
        return PromocionResource::collection(
            $this->buscador->promocionesVigentes()
        );
    }

    // ==================================================================
    // Auxiliares
    // ==================================================================

    /**
     * Opciones de orden que la app puede ofrecer.
     *
     * Al invitado no se le ofrecen las de precio: pedirlas no rompe nada
     * —caen en Relevancia— pero mostrarlas en pantalla seria ofrecerle un
     * control que no hace lo que dice.
     *
     * @return list<array{valor: string, etiqueta: string}>
     */
    private function ordenesDisponibles(bool $puedeVerPrecios): array
    {
        $opciones = [];

        foreach (OrdenCatalogo::cases() as $orden) {

            $esDePrecio = in_array($orden, [OrdenCatalogo::PrecioAsc, OrdenCatalogo::PrecioDesc], true);

            if ($esDePrecio && ! $puedeVerPrecios) {
                continue;
            }

            $opciones[] = [
                'valor'    => $orden->value,
                'etiqueta' => $orden->etiqueta(),
            ];
        }

        return $opciones;
    }
}