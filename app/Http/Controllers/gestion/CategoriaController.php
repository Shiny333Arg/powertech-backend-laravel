<?php

namespace App\Http\Controllers\gestion;

use App\Models\Categoria;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Las pantallas de categorias del panel. <b>Ya no es un CRUD.</b>
 *
 * Entrega las tres paginas Blade —listado, alta y edicion— y nada mas. Los
 * datos los trae y los modifica el JavaScript de la pantalla contra
 * /api/v1/gestion/categorias, que es lo que pide el requisito 3 de la Etapa 3.
 *
 * store(), update(), darDeBaja(), reactivar() y destroy() se eliminaron junto
 * con sus rutas. Existian para servirlas, y mientras existieran eran una via
 * alternativa para ejecutar operaciones de negocio desde un controlador web.
 * Lo que hacia cada una esta mapeado a su endpoint en el comentario del bloque
 * CATEGORIAS de routes/web.php.
 *
 * Las reglas no se movieron de lugar: el alta y la edicion siguen pasando por
 * el mismo CategoriaRequest y la misma CategoriaPolicy, y el borrado lo sigue
 * frenando la foreign key —la categoria es la unica entidad del catalogo que
 * admite borrado fisico, y solo si ningun producto la referencia—; lo que
 * cambio es quien los invoca.
 */
class CategoriaController extends Controller
{
    public function __construct()
    {
        // Mapea cada accion del recurso a CategoriaPolicy. De las siete que
        // cubre quedan en pie tres —index, create y edit—, que son las que
        // tienen ruta: viewAny para el listado, create para el alta y update
        // para la edicion.
        $this->authorizeResource(Categoria::class, 'categoria');
    }

    /**
     * Listado de gestion.
     *
     * <b>La pagina ya no trae las categorias.</b> El listado, los filtros y el
     * paginado los resuelve resources/js/gestion/categorias/index.js contra
     * GET /api/v1/gestion/categorias, con los mismos dos filtros de siempre
     * —nombre y estado— viajando en la URL para que un enlace filtrado se
     * pueda seguir guardando y compartiendo.
     *
     * Muestra activas e inactivas por defecto, a proposito: esta pantalla es
     * desde donde se reactiva una categoria dada de baja. Ese criterio ahora
     * vive en el endpoint, que tiene el mismo default.
     *
     * Lo unico que baja del servidor son los permisos del rol: deciden que
     * botones se dibujan, no que se puede hacer. Eso lo sigue exigiendo la API
     * en cada llamada.
     */
    public function index(Request $request): View
    {
        return view('gestion.category.index', [
            'permisos' => $this->permisosDe($request->user()),
        ]);
    }

    /**
     * El formulario de alta, vacio.
     *
     * Ya no se precarga nada: el formulario no muestra el orden —no lo mostraba
     * tampoco antes— y el estado arranca en "en operacion", que es el default
     * de la columna. Sin consulta a la base, la pagina no entrega ningun dato
     * de negocio.
     */
    public function create(): View
    {
        return view('gestion.category.create');
    }

    /**
     * El formulario de edicion.
     *
     * Entrega el id y nada mas: los datos los pide la pantalla a
     * GET /api/v1/gestion/categorias/{categoria}. El binding de ruta sigue
     * sirviendo para dos cosas que no se pueden delegar al JavaScript: el 404
     * de una categoria que no existe y la autorizacion de authorizeResource().
     */
    public function edit(Categoria $categoria): View
    {
        return view('gestion.category.edit', [
            'categoria' => $categoria,

            // El casillero de estado se deshabilita sin este permiso: el PUT
            // no cambia 'activo' y la llamada al endpoint de estado daria 403.
            'puedeCambiarEstado' => Gate::allows('cambiarEstado', $categoria),
        ]);
    }

    /**
     * Que puede hacer este empleado con las categorias.
     *
     * Se le pregunta a la MISMA CategoriaPolicy que autoriza la API, con una
     * instancia vacia: la policy decide por el permiso del rol y no mira la
     * fila. De este modo las claves de permiso ('categoria.editar' y
     * companhia) siguen escritas en un solo lugar y no se repiten en la vista
     * ni en el JavaScript.
     *
     * @return array<string, bool>
     */
    private function permisosDe(User $empleado): array
    {
        $cualquiera = new Categoria;

        return [
            'crear' => $empleado->can('create', Categoria::class),
            'editar' => $empleado->can('update', $cualquiera),
            'cambiarEstado' => $empleado->can('cambiarEstado', $cualquiera),
            'eliminar' => $empleado->can('delete', $cualquiera),
        ];
    }
}
