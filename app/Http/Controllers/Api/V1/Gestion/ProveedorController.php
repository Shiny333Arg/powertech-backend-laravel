<?php

namespace App\Http\Controllers\Api\V1\Gestion;

use App\Enums\CanalPedido;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProveedorRequest;
use App\Http\Resources\Gestion\ProveedorResource;
use App\Models\Proveedor;
use App\Support\DomicilioFiscal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Proveedores, por API.
 *
 * <b>Es la misma operacion que la del panel Blade, con JSON en lugar de
 * vistas.</b> Mismo modelo, misma policy, mismo FormRequest y el mismo
 * App\Support\DomicilioFiscal para el domicilio: lo unico propio de este
 * archivo es la forma de la respuesta. No hay ninguna regla de negocio "de la
 * API".
 *
 * <b>Sin destroy, igual que el controlador del panel: un proveedor NO se
 * elimina nunca.</b> Esta referenciado por productos y por ordenes de compra, y
 * esas ordenes son historia que no puede quedar sin sujeto —la orden dice a
 * quien se le compro—. La ruta DELETE no existe, ProveedorPolicy no tiene
 * metodo delete() y el guard del modelo (ProtegeContraBorrado) lanza la
 * excepcion de dominio si alguien llamara a delete() igual. Es la misma
 * decision que en la API de productos de la entrega B, y la diferencia con
 * categorias —que si admite borrado fisico cuando nadie la referencia— esta en
 * el comentario de routes/api_gestion.php.
 *
 * La unica baja es activo = false, por PATCH .../estado.
 *
 * <b>La autorizacion esta en las rutas, no en el constructor.</b> A diferencia
 * del controlador web, que usa authorizeResource(): ver el comentario de
 * routes/api_gestion.php.
 */
class ProveedorController extends Controller
{
    /** Tamaño de pagina por defecto y tope, para que nadie pida 10.000 filas. */
    private const POR_PAGINA = 15;

    private const POR_PAGINA_MAX = 100;

    /**
     * GET /api/v1/gestion/proveedores
     *
     * Los mismos tres filtros del listado del panel y con los mismos nombres:
     * busqueda libre, canal de pedido y estado. Muestra activos e inactivos por
     * defecto porque es la pantalla desde donde se reactiva un proveedor dado
     * de baja.
     *
     * Devuelve los dos conteos que el panel pone en la grilla —productos y
     * ordenes abiertas—, que son los que se miran antes de dar de baja.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filtros = $request->validate([
            'q'            => ['nullable', 'string', 'max:150'],
            'canal_pedido' => ['nullable', Rule::in(CanalPedido::valores())],
            'estado'       => ['nullable', 'in:todos,activos,inactivos'],
            'por_pagina'   => ['nullable', 'integer', 'min:1', 'max:'.self::POR_PAGINA_MAX],
        ]);

        $busqueda = $filtros['q'] ?? null;
        $canal = $filtros['canal_pedido'] ?? null;
        $estado = $filtros['estado'] ?? 'todos';

        $proveedores = Proveedor::query()
            // La unica relacion que serializa el listado, acotada a las
            // columnas que salen: sin esto, 15 proveedores son 15 consultas mas.
            ->with('condicionIva:id,nombre')
            ->withCount([
                // Cuantos productos dependen de este proveedor para reponerse.
                'productos',
                // Ordenes todavia capaces de sumar stock: es lo que hay que
                // mirar antes de dar de baja a alguien.
                'ordenesCompra as ordenes_abiertas_count' => fn ($query) => $query->abiertas(),
            ])
            ->when($busqueda, function ($query, $busqueda) {
                $soloDigitos = preg_replace('/\D/', '', $busqueda);

                $query->where(function ($sub) use ($busqueda, $soloDigitos) {
                    $sub->where('razon_social', 'like', "%{$busqueda}%")
                        ->orWhere('contacto', 'like', "%{$busqueda}%");

                    // Permite buscar el CUIT escrito con guiones aunque en la
                    // columna este guardado sin ellos. Misma regla que el panel.
                    if ($soloDigitos !== '') {
                        $sub->orWhere('cuit', 'like', "%{$soloDigitos}%");
                    }
                });
            })
            ->when($canal, fn ($query, $canal) => $query->where('canal_pedido', $canal))
            ->when($estado === 'activos', fn ($query) => $query->activos())
            ->when($estado === 'inactivos', fn ($query) => $query->inactivos())
            ->orderBy('razon_social')
            ->paginate($filtros['por_pagina'] ?? self::POR_PAGINA)
            ->withQueryString();

        // Los filtros REALMENTE aplicados, misma convencion que las entregas A
        // y B: la pantalla no tiene que adivinar si el backend acepto lo que
        // mando.
        return ProveedorResource::collection($proveedores)->additional([
            'filtros' => [
                'q'            => $busqueda,
                'canal_pedido' => $canal,
                'estado'       => $estado,
            ],
        ]);
    }

    /** GET /api/v1/gestion/proveedores/{proveedor} */
    public function show(Proveedor $proveedor): ProveedorResource
    {
        return new ProveedorResource($this->recargar($proveedor));
    }

    /**
     * POST /api/v1/gestion/proveedores
     *
     * 201 con el recurso creado. El domicilio fiscal es opcional y viaja en el
     * mismo cuerpo bajo la clave 'direccion', igual que en el formulario:
     * proveedor y domicilio van en una sola transaccion, porque un proveedor
     * guardado con el domicilio a medias obliga a completarlo despues sin que
     * nada avise.
     *
     * <b>Nace activo, y el 'activo' del cuerpo no se respeta.</b> El estado
     * tiene su endpoint y su permiso (proveedor.baja); dar de alta a alguien ya
     * dado de baja no es una operacion que tenga sentido, y como
     * ProveedorRequest castea 'activo' con boolean(), un alta que simplemente
     * no mandara el campo crearia un proveedor inactivo en silencio.
     */
    public function store(ProveedorRequest $request): JsonResponse
    {
        $proveedor = DB::transaction(function () use ($request) {
            $datos = $request->datosDelProveedor();

            $datos['activo'] = true;

            $proveedor = Proveedor::create($datos);

            DomicilioFiscal::guardar($proveedor, $request->datosDeLaDireccion());

            return $proveedor;
        });

        return (new ProveedorResource($this->recargar($proveedor)))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * PUT /api/v1/gestion/proveedores/{proveedor} — reemplazo completo.
     *
     * El cuerpo reemplaza el recurso, que es lo que ProveedorRequest ya valida
     * para el formulario web, incluida la unicidad del CUIT ignorando al propio
     * proveedor.
     *
     * <b>El estado NO se toca desde aca aunque venga en el cuerpo sin
     * querer</b>: tiene su endpoint y su permiso, y mezclarlos dejaria que
     * alguien con 'proveedor.editar' diera de baja. Es la misma regla que en
     * categorias y productos.
     *
     * El domicilio se reemplaza, no se acumula; y si el cuerpo no trae
     * domicilio, el que ya estaba cargado no se toca (ver DomicilioFiscal).
     */
    public function update(ProveedorRequest $request, Proveedor $proveedor): ProveedorResource
    {
        DB::transaction(function () use ($request, $proveedor) {
            $proveedor->update(
                collect($request->datosDelProveedor())->except('activo')->all()
            );

            DomicilioFiscal::guardar($proveedor, $request->datosDeLaDireccion());
        });

        return new ProveedorResource($this->recargar($proveedor));
    }

    /**
     * Relee el proveedor antes de serializarlo.
     *
     * En el alta hace falta porque las columnas que el cuerpo no trajo —y que
     * tienen default en la tabla— todavia no existen como atributo, y la
     * respuesta las emitiria con el valor equivocado. Las direcciones se
     * cargan aca y no en el listado: ver el comentario del Resource.
     */
    private function recargar(Proveedor $proveedor): Proveedor
    {
        return $proveedor->refresh()
            ->load(['condicionIva:id,nombre', 'direcciones'])
            ->loadCount([
                'productos',
                'ordenesCompra as ordenes_abiertas_count' => fn ($query) => $query->abiertas(),
            ]);
    }
}
