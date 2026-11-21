<?php

namespace App\Http\Controllers\Api\V1\Gestion;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductoRequest;
use App\Http\Resources\Gestion\ProductoResource;
use App\Models\Producto;
use App\Services\StockService;
use App\Support\ImagenProducto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * Productos del catalogo, por API.
 *
 * <b>Sin destroy, igual que el controlador del panel: un producto NO se
 * elimina nunca.</b> Esta referenciado por lineas de venta, de comprobante, de
 * orden de compra y por los movimientos de stock, y todas esas filas son
 * historia que no puede quedar sin sujeto. La ruta no existe, y si alguien
 * llamara a delete() igual, el guard del modelo lanza la excepcion de dominio.
 * La unica baja es activo = false, por PATCH .../estado.
 *
 * Las reglas de negocio no se reimplementan: validacion en ProductoRequest
 * —el mismo del formulario Blade, incluidas las dos reglas cruzadas de
 * reposicion automatica—, permisos en ProductoPolicy, y el stock inicial en
 * StockService. Lo propio de este archivo es la forma de la respuesta.
 *
 * <b>Las tres columnas de stock no se escriben desde aca.</b> Ni en el alta ni
 * en la edicion: el saldo se mueve con movimientos, que es lo que lo deja
 * explicado. En el alta se acepta `stock_inicial`, que no es la columna sino
 * la cantidad del movimiento de tipo 'inicial'.
 */
class ProductoController extends Controller
{
    private const POR_PAGINA = 15;

    private const POR_PAGINA_MAX = 100;

    public function __construct(private readonly StockService $stock) {}

    /**
     * GET /api/v1/gestion/productos
     *
     * Los mismos cuatro filtros del listado del panel, con los mismos
     * nombres: busqueda libre, categoria, estado y los dos cortes de stock.
     * Muestra activos e inactivos por defecto porque es la pantalla desde
     * donde se reactiva un producto dado de baja.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filtros = $request->validate([
            'q'            => ['nullable', 'string', 'max:150'],
            'categoria_id' => ['nullable', 'integer'],
            'estado'       => ['nullable', 'in:todos,activos,inactivos'],
            'stock'        => ['nullable', 'in:todos,reponer,preventa'],
            'por_pagina'   => ['nullable', 'integer', 'min:1', 'max:'.self::POR_PAGINA_MAX],
        ]);

        $busqueda = $filtros['q'] ?? null;
        $estado   = $filtros['estado'] ?? 'todos';
        $stock    = $filtros['stock'] ?? 'todos';
        $categoria = $filtros['categoria_id'] ?? null;

        $productos = Producto::query()
            // Las tres relaciones que serializa el Resource, acotadas a las
            // columnas que salen: sin esto, un listado de 15 productos son 45
            // consultas mas.
            ->with([
                'categoria:id,nombre',
                'proveedor:id,razon_social,activo',
                'alicuotaIva:id,nombre,porcentaje',
            ])
            ->when($busqueda, function ($query, $busqueda) {
                // Codigo, nombre y marca: los tres campos por los que alguien
                // busca un producto en el mostrador.
                $query->where(function ($sub) use ($busqueda) {
                    $sub->where('codigo', 'like', "%{$busqueda}%")
                        ->orWhere('nombre', 'like', "%{$busqueda}%")
                        ->orWhere('marca', 'like', "%{$busqueda}%");
                });
            })
            ->when($categoria, fn ($query, $categoria) => $query->where('categoria_id', $categoria))
            ->when($estado === 'activos', fn ($query) => $query->activos())
            ->when($estado === 'inactivos', fn ($query) => $query->inactivos())
            // Los dos cortes que alimentan el tablero de reposicion.
            ->when($stock === 'reponer', fn ($query) => $query->bajoPuntoReposicion())
            ->when($stock === 'preventa', fn ($query) => $query->where('stock_disponible', '<', 0))
            ->orderBy('nombre')
            ->paginate($filtros['por_pagina'] ?? self::POR_PAGINA)
            ->withQueryString();

        return ProductoResource::collection($productos)->additional([
            'filtros' => [
                'q'            => $busqueda,
                'categoria_id' => $categoria,
                'estado'       => $estado,
                'stock'        => $stock,
            ],
        ]);
    }

    /** GET /api/v1/gestion/productos/{producto} */
    public function show(Producto $producto): ProductoResource
    {
        return new ProductoResource(
            $producto->load(['categoria:id,nombre', 'proveedor:id,razon_social,activo', 'alicuotaIva:id,nombre,porcentaje'])
        );
    }

    /**
     * POST /api/v1/gestion/productos — multipart/form-data si trae imagen.
     *
     * Nace activo y con la decision de publicacion que venga en
     * `publicado_app` (false por omision). El stock inicial NO se escribe en
     * la columna: se carga como movimiento de tipo 'inicial', para que el
     * saldo quede respaldado por el historico desde el primer dia.
     *
     * Producto y movimiento van en la misma transaccion: un producto creado
     * con el movimiento a medias dejaria el saldo sin explicar. Es la misma
     * secuencia del controlador del panel.
     */
    public function store(ProductoRequest $request): JsonResponse
    {
        $producto = DB::transaction(function () use ($request) {
            $datos = $request->datosDelProducto();

            $datos['activo'] = true;

            if ($request->hasFile('imagen')) {
                $datos['imagen'] = ImagenProducto::guardar($request->file('imagen'));
            }

            $producto = Producto::create($datos);

            if ($request->stockInicial() > 0) {
                $this->stock->cargaInicial($producto, $request->stockInicial(), $request->user());
            }

            return $producto;
        });

        return (new ProductoResource($this->recargar($producto)))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * PUT /api/v1/gestion/productos/{producto} — reemplazo completo.
     *
     * Para mandar una imagen hay que usar `POST` con `_method=PUT`: PHP no
     * parsea el cuerpo multipart de un PUT, asi que el formulario con archivo
     * viaja como POST y Laravel lo enruta aca. Es la misma convencion que usa
     * el formulario Blade con @method('PUT').
     *
     * <b>No toca ninguna de las tres columnas de stock</b>, y `stock_inicial`
     * esta 'prohibited' fuera del alta: corregir el saldo desde el producto
     * dejaria un movimiento sin registrar y rompe la conciliacion. Eso se hace
     * con un ajuste de inventario.
     *
     * <b>Tampoco cambia el estado ni la publicacion: cada uno tiene su
     * endpoint y su permiso.</b> Con la publicacion eso hay que hacerlo
     * explicito, y por dos motivos distintos:
     *
     *   - ProductoRequest::prepareForValidation() hace boolean('publicado_app')
     *     SIEMPRE, asi que el campo esta en validated() aunque el cuerpo no lo
     *     traiga, y con valor false. Sin el except, un PUT que no lo manda
     *     despublicaria el producto sin que nadie lo pida.
     *   - Si el PUT lo escribiera, publicar quedaria al alcance de cualquiera
     *     con 'producto.editar', salteando 'producto.publicar', que es
     *     justamente el permiso que separa las dos decisiones.
     *
     * El unico camino para publicar y despublicar es
     * PATCH /{producto}/publicacion. 'activo' no hace falta excluirlo: el
     * FormRequest lo rechaza con 'prohibited'.
     */
    public function update(ProductoRequest $request, Producto $producto): ProductoResource
    {
        $datos = collect($request->datosDelProducto())
            ->except('publicado_app')
            ->all();

        if (! $request->hasFile('imagen')) {
            $producto->update($datos);

            return new ProductoResource($this->recargar($producto));
        }

        $imagenAnterior  = $producto->imagen;
        $datos['imagen'] = ImagenProducto::guardar($request->file('imagen'));

        $producto->update($datos);

        // Recien despues de guardar la nueva: si el borrado fuera primero y el
        // guardado fallara, el producto se quedaria sin ninguna.
        ImagenProducto::borrar($imagenAnterior);

        return new ProductoResource($this->recargar($producto));
    }

    /**
     * Relee el producto antes de serializarlo.
     *
     * Hace falta por dos motivos distintos: en el alta, las columnas que el
     * cuerpo no trajo —y que tienen default en la tabla— todavia no existen
     * como atributo; y despues de un movimiento de stock, los saldos que
     * cambio el UPDATE relativo siguen en memoria con el valor viejo.
     */
    private function recargar(Producto $producto): Producto
    {
        return $producto->refresh()->load([
            'categoria:id,nombre',
            'proveedor:id,razon_social,activo',
            'alicuotaIva:id,nombre,porcentaje',
        ]);
    }
}
