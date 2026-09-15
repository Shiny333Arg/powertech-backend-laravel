<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoriaRequest;
use App\Models\Categoria;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoriaController extends Controller
{
    /**
     * Codigos de error del motor para "hay filas hijas que referencian esta".
     * 1451 es el de MySQL/MariaDB al borrar el padre con ON DELETE RESTRICT.
     */
    private const ERRORES_CLAVE_FORANEA = [1451, 1217];

    public function __construct()
    {
        // Descomentar cuando exista el login. Mapea cada accion del recurso a
        // CategoriaPolicy; para las rutas propias (baja y reactivacion) la
        // autorizacion se pide a mano en cada metodo.
        // $this->authorizeResource(Categoria::class, 'categoria');
    }

    /**
     * Listado de gestion.
     *
     * Muestra activas e inactivas por defecto, a proposito: esta pantalla es
     * desde donde se reactiva una categoria dada de baja, asi que filtrarlas
     * de entrada la dejaria sin su funcion principal. El modelo no tiene
     * global scope justamente para que esto sea posible.
     */
    public function index(Request $request): View
    {
        $estado = $request->query('estado', 'todas');
        $nombre = $request->query('nombre');

        $categorias = Categoria::query()
            ->withCount('productos')
            ->when($nombre, function ($query, $nombre) {
                $query->where('nombre', 'like', '%' . $nombre . '%');
            })
            ->when($estado === 'activas', fn($query) => $query->activos())
            ->when($estado === 'inactivas', fn($query) => $query->inactivos())
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();

        return view('category.index', compact('categorias', 'estado', 'nombre'));
    }

    public function create(): View
    {
        // Sugiere la posicion siguiente para no obligar a pensar un numero.
        $categoria = new Categoria(['orden' => (int) Categoria::max('orden') + 1, 'activo' => true]);

        return view('category.create', compact('categoria'));
    }

    public function store(CategoriaRequest $request): RedirectResponse
    {
        $categoria = Categoria::create($request->validated());

        return redirect()
            ->route('categorias.index')
            ->with('exito', "Se creó la categoría «{$categoria->nombre}».");
    }

    public function edit(Categoria $categoria): View
    {
        return view('category.edit', compact('categoria'));
    }

    public function update(CategoriaRequest $request, Categoria $categoria): RedirectResponse
    {
        $categoria->update($request->validated());

        return redirect()
            ->route('categorias.index')
            ->with('exito', "Se guardaron los cambios en «{$categoria->nombre}».");
    }

    /**
     * Baja logica: la categoria sale de operacion y deja de ofrecerse al cargar
     * un producto, pero la fila sigue existiendo y los productos que la nombran
     * la siguen mostrando. Es reversible.
     */
    public function darDeBaja(Categoria $categoria): RedirectResponse
    {
        $categoria->darDeBaja();

        return back()->with('exito', "«{$categoria->nombre}» ya no está en operación.");
    }

    public function reactivar(Categoria $categoria): RedirectResponse
    {
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
            if (! $this->esViolacionDeClaveForanea($e)) {
                throw $e; // cualquier otro error de base no se disimula
            }

            return back()->with('error', "«{$nombre}» tiene productos asociados, así que no se puede eliminar. Dala de baja para sacarla de operación sin perder el historial.");
        }

        return redirect()
            ->route('categorias.index')
            ->with('exito', "Se eliminó la categoría «{$nombre}».");
    }

    private function esViolacionDeClaveForanea(QueryException $e): bool
    {
        return in_array((int) ($e->errorInfo[1] ?? 0), self::ERRORES_CLAVE_FORANEA, true);
    }
}
