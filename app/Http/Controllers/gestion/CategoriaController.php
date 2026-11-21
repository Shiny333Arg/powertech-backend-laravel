<?php

namespace App\Http\Controllers\gestion;

use App\Exceptions\EntidadNoEliminableException;
use App\Http\Requests\CategoriaRequest;
use App\Models\Categoria;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Las pantallas de categorias del panel.
 *
 * <b>Desde la migracion a la API, este controlador ya no ejecuta operaciones
 * de negocio desde la interfaz.</b> index(), create() y edit() entregan la
 * pagina Blade —la cascara, sin datos— y el JavaScript de la pantalla trae y
 * modifica los datos contra /api/v1/gestion/categorias, que es lo que pide el
 * requisito 3 de la Etapa 3.
 *
 * store(), update(), darDeBaja(), reactivar() y destroy() siguen existiendo
 * porque sus rutas todavia estan publicadas: ya ninguna pantalla las usa, y se
 * retiran junto con las rutas web de negocio de los demas modulos, en el paso
 * siguiente del plan. Hasta entonces comparten FormRequest, policy y modelo
 * con la API, asi que no hay dos reglas distintas conviviendo.
 */
class CategoriaController extends Controller
{
    public function __construct()
    {
        // Descomentar cuando exista el login. Mapea cada accion del recurso a
        // CategoriaPolicy; para las rutas propias (baja y reactivacion) la
        // autorizacion se pide a mano en cada metodo.
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

    public function store(CategoriaRequest $request): RedirectResponse
    {
        $categoria = Categoria::create($request->validated());

        return redirect()
            ->route('gestion.categorias.index')
            ->with('exito', "Se creó la categoría «{$categoria->nombre}».");
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

    public function update(CategoriaRequest $request, Categoria $categoria): RedirectResponse
    {
        $categoria->update($request->validated());

        return redirect()
            ->route('gestion.categorias.index')
            ->with('exito', "Se guardaron los cambios en «{$categoria->nombre}».");
    }

    /**
     * Baja logica: la categoria sale de operacion y deja de ofrecerse al cargar
     * un producto, pero la fila sigue existiendo y los productos que la nombran
     * la siguen mostrando. Es reversible.
     */
    public function darDeBaja(Categoria $categoria): RedirectResponse
    {
        // authorizeResource() solo cubre los siete metodos de recurso: las
        // acciones de estado tienen que autorizar a mano, o quedan abiertas a
        // cualquier cuenta del ambito gestion.
        Gate::authorize('cambiarEstado', $categoria);

        $categoria->darDeBaja();

        return back()->with('exito', "«{$categoria->nombre}» ya no está en operación.");
    }

    public function reactivar(Categoria $categoria): RedirectResponse
    {
        Gate::authorize('cambiarEstado', $categoria);

        $categoria->reactivar();

        return back()->with('exito', "«{$categoria->nombre}» vuelve a estar en operación.");
    }

    /**
     * Borrado fisico. La categoria es la unica entidad del catalogo que lo
     * admite, y solo si ningun producto la referencia.
     *
     * No se antepone un count() de productos: reimplementar en PHP una
     * comprobacion que el motor ya hace abre una ventana contra un alta
     * concurrente —alguien crea un producto entre el count y el DELETE—.
     * Se intenta el DELETE y se traduce el rechazo de la FK a un mensaje
     * entendible. La restriccion es la regla; esto es solo su traduccion.
     *
     * Ojo con el alcance: como los productos nunca se eliminan, el RESTRICT
     * tambien ve a los dados de baja. Una categoria que alguna vez tuvo un
     * producto ya no se puede eliminar, y eso es correcto.
     */
    public function destroy(Categoria $categoria): RedirectResponse
    {
        $nombre = $categoria->nombre;

        try {
            $categoria->delete();
        } catch (QueryException $e) {
            // La lista de codigos vive en la excepcion de dominio, porque la
            // API hace la misma pregunta para responder 409. Misma regla, un
            // solo lugar; lo que cambia es la traduccion: alla una excepcion,
            // aca el mensaje en pantalla de siempre.
            if (! EntidadNoEliminableException::esRechazoDeClaveForanea($e)) {
                throw $e; // cualquier otro error de base no se disimula
            }

            return back()->with('error', "«{$nombre}» tiene productos asociados, así que no se puede eliminar. Dala de baja para sacarla de operación sin perder el historial.");
        }

        return redirect()
            ->route('gestion.categorias.index')
            ->with('exito', "Se eliminó la categoría «{$nombre}».");
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
