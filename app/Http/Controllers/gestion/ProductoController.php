<?php

namespace App\Http\Controllers\gestion;

use App\Models\AlicuotaIva;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Las pantallas de productos del panel. <b>Ya no es un CRUD.</b>
 *
 * Entrega las tres paginas Blade —listado, alta y edicion— y nada mas. Los
 * datos los trae y los modifica el JavaScript de cada pantalla contra
 * /api/v1/gestion/productos, igual que en categorias.
 *
 * store(), update(), darDeBaja(), reactivar(), publicar() y despublicar() se
 * eliminaron junto con sus rutas: existian para servirlas, y mientras
 * existieran eran una via alternativa para ejecutar operaciones de negocio
 * desde controladores web, que es lo que el requisito 3 de la Etapa 3 pide
 * cerrar. Lo que hacia cada una esta mapeado a su endpoint en el comentario
 * del bloque PRODUCTOS de routes/web.php.
 *
 * El alta y la baja siguen siendo las mismas operaciones de siempre, con el
 * mismo ProductoRequest, la misma ProductoPolicy y el mismo StockService: lo
 * que cambio es quien las invoca. Un producto NO se elimina nunca —esta
 * referenciado por lineas de venta, de comprobante, de orden de compra y por
 * los movimientos de stock—, asi que la unica baja sigue siendo activo =
 * false, ahora por PATCH .../estado.
 *
 * <b>Lo unico que la pagina sigue trayendo de la base son los desplegables</b>
 * —categorias, proveedores y alicuotas de IVA—: son el catalogo de opciones
 * del formulario, no los datos del producto. Categorias y proveedores tienen
 * endpoint propio, pero las alicuotas de IVA no, y crear uno pedia inventar
 * tambien el permiso que lo protege. Queda anotado como pendiente para cuando
 * se migren esos dos modulos.
 */
class ProductoController extends Controller
{
    public function __construct()
    {
        // Descomentar cuando exista el login:
        $this->authorizeResource(Producto::class, 'producto');
    }

    /**
     * Listado de gestion.
     *
     * <b>La pagina ya no trae los productos.</b> El listado, los cuatro
     * filtros y el paginado los resuelve resources/js/gestion/productos/index.js
     * contra GET /api/v1/gestion/productos, con los filtros viajando en la URL
     * para que un listado filtrado se pueda seguir guardando y compartiendo.
     *
     * Muestra activos e inactivos por defecto, a proposito: es la pantalla
     * desde donde se reactiva un producto dado de baja. Ese criterio ahora
     * vive en el endpoint, que tiene el mismo default.
     *
     * Del servidor bajan dos cosas: el desplegable de categorias del filtro y
     * los permisos del rol, que deciden que botones se dibujan y no que se
     * puede hacer. Eso lo sigue exigiendo la API en cada llamada.
     */
    public function index(Request $request): View
    {
        return view('gestion.item.index', [
            'categorias' => Categoria::activos()->orderBy('nombre')->get(['id', 'nombre']),
            'permisos' => $this->permisosDe($request->user()),
        ]);
    }

    /**
     * El formulario de alta, vacio.
     *
     * Ya no se precarga un Producto en blanco: los valores por omision del
     * formulario —en operacion, sin publicar, sin reposicion automatica— son
     * los mismos de antes y estan escritos en la vista. Lo unico que baja de
     * la base son los desplegables.
     */
    public function create(): View
    {
        return view('gestion.item.create', $this->opcionesDeFormulario());
    }

    /**
     * El formulario de edicion.
     *
     * Entrega el id, los desplegables y los permisos: los datos del producto
     * los pide la pantalla a GET /api/v1/gestion/productos/{producto}. El
     * binding de ruta sigue sirviendo para dos cosas que no se pueden delegar
     * al JavaScript: el 404 de un producto que no existe y la autorizacion de
     * authorizeResource().
     */
    public function edit(Producto $producto): View
    {
        return view('gestion.item.edit', [
            'producto' => $producto,

            // El interruptor de publicacion se deshabilita sin este permiso:
            // publicar tiene endpoint propio ('producto.publicar') y no se
            // decide junto con el resto de los datos.
            'puedePublicar' => Gate::allows('publicar', $producto),
        ] + $this->opcionesDeFormulario());
    }

    /**
     * El ajuste de inventario y el kardex NO viven aca: son StockController,
     * en el bloque STOCK de routes/web.php. Habia dos metodos comentados
     * —ajustarStock() y movimientos()— con rutas declaradas que los
     * apuntaban, asi que esas dos rutas respondian 500 al invocarse. Se
     * quitaron las rutas y los metodos; la vista de edicion ahora enlaza
     * gestion.stock.show para el historial.
     */

    /**
     * Que puede hacer este empleado con los productos.
     *
     * Se le pregunta a la MISMA ProductoPolicy que autoriza la API, con una
     * instancia vacia: la policy decide por el permiso del rol y no mira la
     * fila. Asi las claves de permiso siguen escritas en un solo lugar y no se
     * repiten en la vista ni en el JavaScript.
     *
     * 'verStock' no es del catalogo sino del modulo stock ('stock.ver'): es el
     * permiso que ya exige la pantalla del kardex a la que lleva el boton de
     * historial.
     *
     * @return array<string, bool>
     */
    private function permisosDe(User $empleado): array
    {
        $cualquiera = new Producto;

        return [
            'crear' => $empleado->can('create', Producto::class),
            'editar' => $empleado->can('update', $cualquiera),
            'cambiarEstado' => $empleado->can('cambiarEstado', $cualquiera),
            'publicar' => $empleado->can('publicar', $cualquiera),
            'verStock' => $empleado->can('verStock', $cualquiera),
        ];
    }

    /** @return array<string, mixed> */
    private function opcionesDeFormulario(): array
    {
        return [
            // Solo activas/activos en los desplegables de alta: dar de baja
            // significa, justamente, dejar de ofrecerse acá.
            'categorias'  => Categoria::activos()->orderBy('orden')->orderBy('nombre')->get(['id', 'nombre']),
            'proveedores' => Proveedor::activos()->orderBy('razon_social')->get(['id', 'razon_social']),
            'alicuotas'   => AlicuotaIva::orderBy('porcentaje')->get(['id', 'nombre', 'porcentaje']),
        ];
    }

    /*
     * Guardar y borrar la imagen viven en App\Support\ImagenProducto: ahora
     * tambien las sube la API de gestion y el directorio de destino tiene que
     * ser uno solo. El comportamiento es el mismo de antes.
     */
}
