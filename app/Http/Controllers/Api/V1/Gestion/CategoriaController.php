<?php

namespace App\Http\Controllers\Api\V1\Gestion;

use App\Exceptions\EntidadNoEliminableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\CategoriaRequest;
use App\Http\Resources\Gestion\CategoriaResource;
use App\Models\Categoria;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * Categorias del catalogo, por API.
 *
 * <b>Es la misma operacion que la del panel Blade, con JSON en lugar de
 * vistas.</b> Mismo modelo, misma policy, mismo FormRequest: lo unico propio
 * de este archivo es la forma de la respuesta. Las reglas de negocio no se
 * reimplementan aca —no hay ninguna que sea "de la API"—, que es el objetivo 3
 * de la consigna.
 *
 * <b>La autorizacion esta en las rutas, no en el constructor.</b> Ver el
 * comentario de routes/api_gestion.php: 'can:' por ruta en lugar de
 * authorizeResource(), para que ninguna operacion pueda quedar sin su linea.
 *
 * <b>El borrado fisico no se antepone con un count().</b> Se intenta el DELETE
 * y se traduce el rechazo de la FK, igual que en el panel: reimplementar en PHP
 * una comprobacion que el motor ya hace abre una ventana contra un alta
 * concurrente —alguien crea un producto entre el count y el DELETE—.
 */
class CategoriaController extends Controller
{
    /** Tamaño de pagina por defecto y tope, para que nadie pida 10.000 filas. */
    private const POR_PAGINA = 15;

    private const POR_PAGINA_MAX = 100;

    /**
     * GET /api/v1/gestion/categorias
     *
     * Los mismos dos filtros que el listado del panel —nombre y estado—, y por
     * el mismo motivo muestra activas e inactivas por defecto: es la pantalla
     * desde donde se reactiva una categoria dada de baja.
     *
     * Devuelve 'productos_count' porque es el dato con el que la pantalla sabe
     * si el boton de eliminar va a terminar en 409.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filtros = $request->validate([
            'nombre'     => ['nullable', 'string', 'max:100'],
            'estado'     => ['nullable', 'in:todas,activas,inactivas'],
            'por_pagina' => ['nullable', 'integer', 'min:1', 'max:'.self::POR_PAGINA_MAX],
        ]);

        $estado = $filtros['estado'] ?? 'todas';
        $nombre = $filtros['nombre'] ?? null;

        $categorias = Categoria::query()
            ->withCount('productos')
            ->when($nombre, fn ($query, $nombre) => $query->where('nombre', 'like', '%'.$nombre.'%'))
            ->when($estado === 'activas', fn ($query) => $query->activos())
            ->when($estado === 'inactivas', fn ($query) => $query->inactivos())
            ->orderBy('orden')
            ->orderBy('nombre')
            ->paginate($filtros['por_pagina'] ?? self::POR_PAGINA)
            ->withQueryString();

        // Los filtros REALMENTE aplicados, misma convencion que
        // CatalogoController@index: la pantalla no tiene que adivinar si el
        // backend acepto lo que mando.
        return CategoriaResource::collection($categorias)->additional([
            'filtros' => [
                'nombre' => $nombre,
                'estado' => $estado,
            ],
        ]);
    }

    /**
     * POST /api/v1/gestion/categorias
     *
     * 201 con el recurso creado. 'activo' es opcional: sin el, la columna toma
     * su default (true), que es lo que se espera de una categoria recien
     * creada.
     */
    public function store(CategoriaRequest $request): JsonResponse
    {
        $categoria = Categoria::create($request->validated());

        // refresh() y no el objeto que devuelve create(): las columnas que el
        // cuerpo no trajo —'activo' y 'orden' tienen default en la tabla— no
        // existen como atributo hasta releerlas, y la respuesta las emitiria
        // como false y 0 en lugar de los valores que quedaron en la base.
        return (new CategoriaResource($categoria->refresh()->loadCount('productos')))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /** GET /api/v1/gestion/categorias/{categoria} */
    public function show(Categoria $categoria): CategoriaResource
    {
        return new CategoriaResource($categoria->loadCount('productos'));
    }

    /**
     * PUT /api/v1/gestion/categorias/{categoria}
     *
     * Reemplazo completo. El estado NO se toca desde aca aunque venga en el
     * cuerpo sin querer: tiene su endpoint y su permiso (categoria.baja), y
     * mezclarlos dejaria que alguien con 'categoria.editar' diera de baja.
     */
    public function update(CategoriaRequest $request, Categoria $categoria): CategoriaResource
    {
        $categoria->update(collect($request->validated())->except('activo')->all());

        return new CategoriaResource($categoria->loadCount('productos'));
    }

    /**
     * DELETE /api/v1/gestion/categorias/{categoria}
     *
     * Borrado fisico, la unica entidad del catalogo que lo admite. 204 si se
     * elimino; 409 si hay productos que la referencian —lo arma el render de
     * bootstrap/app.php a partir de la excepcion de dominio—.
     *
     * Ojo con el alcance, igual que en el panel: como los productos nunca se
     * eliminan, el RESTRICT tambien ve a los dados de baja. Una categoria que
     * alguna vez tuvo un producto ya no se puede eliminar, y eso es correcto.
     */
    public function destroy(Categoria $categoria): Response
    {
        $nombre = $categoria->nombre;

        try {
            $categoria->delete();
        } catch (QueryException $e) {
            if (! EntidadNoEliminableException::esRechazoDeClaveForanea($e)) {
                throw $e; // cualquier otro error de base no se disimula
            }

            throw new EntidadNoEliminableException(
                "«{$nombre}» tiene productos asociados, así que no se puede eliminar. Dala de baja para sacarla de operación sin perder el historial."
            );
        }

        return response()->noContent();
    }
}
