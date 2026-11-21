<?php

namespace App\Http\Controllers\gestion;

use App\Enums\CanalPedido;
use App\Models\CondicionIva;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Las pantallas de proveedores del panel. <b>Ya no es un CRUD.</b>
 *
 * Entrega las tres paginas Blade —listado, alta y edicion— y nada mas. Los
 * datos los trae y los modifica el JavaScript de cada pantalla contra
 * /api/v1/gestion/proveedores, igual que en categorias y productos.
 *
 * store(), update(), darDeBaja() y reactivar() se eliminaron junto con sus
 * rutas: existian para servirlas, y mientras existieran eran una via
 * alternativa para ejecutar operaciones de negocio desde controladores web,
 * que es lo que el requisito 3 de la Etapa 3 pide cerrar. Lo que hacia cada
 * una esta mapeado a su endpoint en el comentario del bloque PROVEEDORES de
 * routes/web.php.
 *
 * Las reglas no se movieron de lugar: el alta y la edicion siguen pasando por
 * el mismo ProveedorRequest, la misma ProveedorPolicy y el mismo
 * App\Support\DomicilioFiscal —un solo domicilio por proveedor, reemplazado
 * en lugar de acumulado—; lo que cambio es quien los invoca.
 *
 * El proveedor esta referenciado por productos y por ordenes de compra, asi
 * que no se elimina nunca: la unica baja es activo = false, ahora por
 * PATCH .../estado.
 *
 * Los proveedores NO son usuarios: no tienen login ni interactuan con el
 * sistema. Por eso aca no hay nada de credenciales.
 *
 * <b>Lo unico que la pagina sigue trayendo de la base son los desplegables</b>
 * —condiciones de IVA y canales de pedido—: son el catalogo de opciones del
 * formulario y del filtro, no los datos del proveedor. Los canales salen del
 * enum CanalPedido y las condiciones de IVA no tienen endpoint propio; crear
 * uno pedia inventar tambien el permiso que lo protege. Queda anotado como
 * pendiente.
 */
class ProveedorController extends Controller
{
    public function __construct()
    {
        // Descomentar cuando exista el login:
        $this->authorizeResource(Proveedor::class, 'proveedor');
    }

    /**
     * Listado de gestion.
     *
     * <b>La pagina ya no trae los proveedores.</b> El listado, los tres
     * filtros y el paginado los resuelve
     * resources/js/gestion/proveedores/index.js contra
     * GET /api/v1/gestion/proveedores, con los filtros viajando en la URL para
     * que un listado filtrado se pueda seguir guardando y compartiendo.
     *
     * Muestra activos e inactivos por defecto, a proposito: es la pantalla
     * desde donde se reactiva un proveedor dado de baja. Ese criterio ahora
     * vive en el endpoint, que tiene el mismo default.
     *
     * Del servidor bajan dos cosas: los canales de pedido del filtro —que son
     * el enum, no datos— y los permisos del rol, que deciden que botones se
     * dibujan y no que se puede hacer. Eso lo sigue exigiendo la API.
     */
    public function index(Request $request): View
    {
        return view('gestion.proveedors.index', [
            'canales'  => CanalPedido::opciones(),
            'permisos' => $this->permisosDe($request->user()),
        ]);
    }

    /**
     * El formulario de alta, vacio.
     *
     * Ya no se precarga un Proveedor en blanco: el unico valor por omision que
     * tenia —el canal 'email'— es el primero del desplegable, y el otro
     * —'activo' en true— dejo de existir como campo del formulario: el alta
     * por API deja al proveedor activo y descarta el 'activo' del cuerpo.
     */
    public function create(): View
    {
        return view('gestion.proveedors.create', $this->opcionesDeFormulario());
    }

    /**
     * El formulario de edicion.
     *
     * Entrega el id y los desplegables: los datos del proveedor y su domicilio
     * fiscal los pide la pantalla a GET /api/v1/gestion/proveedores/{proveedor}.
     * El binding de ruta sigue sirviendo para dos cosas que no se pueden
     * delegar al JavaScript: el 404 de un proveedor que no existe y la
     * autorizacion de authorizeResource().
     */
    public function edit(Proveedor $proveedor): View
    {
        return view('gestion.proveedors.edit', [
            'proveedor' => $proveedor,
        ] + $this->opcionesDeFormulario());
    }

    /**
     * Que puede hacer este empleado con los proveedores.
     *
     * Se le pregunta a la MISMA ProveedorPolicy que autoriza la API, con una
     * instancia vacia: la policy decide por el permiso del rol y no mira la
     * fila. Asi las claves de permiso siguen escritas en un solo lugar y no se
     * repiten en la vista ni en el JavaScript.
     *
     * @return array<string, bool>
     */
    private function permisosDe(User $empleado): array
    {
        $cualquiera = new Proveedor;

        return [
            'crear' => $empleado->can('create', Proveedor::class),
            'editar' => $empleado->can('update', $cualquiera),
            'cambiarEstado' => $empleado->can('cambiarEstado', $cualquiera),
        ];
    }

    /** @return array<string, mixed> */
    private function opcionesDeFormulario(): array
    {
        return [
            'condiciones' => CondicionIva::orderBy('nombre')->get(['id', 'nombre']),
            'canales'     => CanalPedido::opciones(),
        ];
    }
}
