<?php

namespace App\Http\Controllers;

use App\Enums\CanalVenta;
use App\Enums\OrdenCatalogo;
use App\Http\Requests\BusquedaProductoRequest;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Promocion;
use App\Services\MotorPromociones;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

/**
 * Resultados de busqueda del catalogo de la tienda.
 *
 * Una sola pantalla para tres entradas distintas: la lupa del header, el
 * panel de filtros y —cuando se conecte— el click en un banner de promocion,
 * que llega con ?promocion={id}. Las tres son la misma consulta con distintos
 * parametros, asi que no hay motivo para tener tres rutas.
 *
 * <b>Que se filtra y ordena en SQL:</b> todo, incluido el precio. Filtrar o
 * reordenar despues de traer la pagina romperia la paginacion (la pagina 2 se
 * calcula en el motor, no en PHP). Ver la nota sobre precios al pie.
 */
class CatalogoController extends Controller
{
    /** Grilla de 4 columnas x 6 filas. */
    private const POR_PAGINA = 24;

    public function index(BusquedaProductoRequest $request, MotorPromociones $motor): View
    {
        $filtros = $request->filtros();

        $productos = $this->buscar($filtros);

        return view('item.resultados', [
            'productos'  => $productos,
            'filtros'    => $filtros,
            'categorias' => $this->categoriasConProductos(),
            'marcas'     => $this->marcasDisponibles(),
            'ordenes'    => OrdenCatalogo::opciones(),
            'promocion'  => $this->promocionDelFiltro($filtros['promocion']),
            'precios'    => $this->preciosFinales($productos, $motor),
        ]);
    }

    /**
     * @param  array{q:?string, categoria:?int, marca:?string, precio_min:?float,
     *               precio_max:?float, promocion:?int, orden:OrdenCatalogo}  $filtros
     * @return LengthAwarePaginator<Producto>
     */
    private function buscar(array $filtros): LengthAwarePaginator
    {
        $query = Producto::query()
            ->select('productos.*')
            ->publicados()
            ->with('categoria:id,nombre');

        $hayTermino = $filtros['q'] !== null;

        if ($hayTermino) {
            $this->aplicarTermino($query, $filtros['q']);
        }

        $query
            ->when($filtros['categoria'], fn(Builder $q, int $id) => $q->where('productos.categoria_id', $id))
            ->when($filtros['marca'], fn(Builder $q, string $marca) => $q->where('productos.marca', $marca))
            ->when($filtros['precio_min'], fn(Builder $q, float $min) => $q->where('productos.precio', '>=', $min))
            ->when($filtros['precio_max'], fn(Builder $q, float $max) => $q->where('productos.precio', '<=', $max))
            ->when($filtros['promocion'], fn(Builder $q, int $id) => $this->aplicarPromocion($q, $id));

        $filtros['orden']->aplicar($query, $hayTermino);

        // withQueryString: sin esto, el enlace a la pagina 2 pierde los
        // filtros y devuelve el catalogo entero.
        return $query->paginate(self::POR_PAGINA)->withQueryString();
    }

    /**
     * Busqueda por palabras con puntaje de relevancia.
     *
     * <b>Filtro:</b> el termino se parte en palabras y cada una tiene que
     * aparecer en algun campo (AND entre palabras, OR entre campos). Asi
     * "teclado logitech" encuentra el teclado de Logitech, que con un LIKE
     * sobre la frase completa no aparece nunca: en la fila dice "Teclado
     * mecanico K835" y la marca esta en otra columna.
     *
     * <b>La CATEGORIA es uno de esos campos, y es el que hace que una busqueda
     * generica funcione.</b> Buscar "mouse" tiene que devolver los mouses, no
     * los productos que casualmente escribieron la palabra en la descripcion:
     * sin este join, el G203 aparecia porque su descripcion dice "mouse de 6
     * botones" y el G502 no, porque en la suya dice el codigo del fabricante.
     * Lo que los hermana no es el texto que cargo quien dio de alta el
     * producto, es la categoria.
     *
     * Se buscan nombre Y slug de la categoria: el slug es estable y sin
     * acentos, asi que "perifericos" encuentra a "Periféricos" aunque el
     * usuario no ponga la tilde.
     *
     * <b>Puntaje:</b> suma de CASE, calculado sobre la frase entera. El codigo
     * exacto pesa mas que todo porque quien lo escribe sabe lo que busca; la
     * categoria exacta pesa fuerte pero por debajo del nombre exacto, para que
     * buscar el nombre de un producto no lo entierre bajo sus 40 hermanos de
     * categoria; la descripcion pesa poco porque ahi cae cualquier mencion de
     * paso.
     *
     * No se usa FULLTEXT a proposito: obliga a un indice que la migracion de
     * productos no tiene y se comporta distinto en MySQL y SQLite, con lo cual
     * los tests dejarian de representar a produccion.
     */
    private function aplicarTermino(Builder $query, string $termino): void
    {
        // JOIN y no whereHas: las columnas de la categoria se necesitan dos
        // veces, en el filtro y en el puntaje. Con whereHas habria que repetir
        // una subconsulta correlacionada por cada CASE.
        //
        // Es un INNER JOIN sin riesgo de perder filas: productos.categoria_id
        // es NOT NULL con FK, todo producto tiene categoria.
        $query->join('categorias', 'categorias.id', '=', 'productos.categoria_id');

        $palabras = array_slice(
            array_filter(preg_split('/\s+/', $termino) ?: []),
            0,
            8 // Tope defensivo: una frase larga no puede generar 200 LIKE.
        );

        $query->where(function (Builder $q) use ($palabras) {
            foreach ($palabras as $palabra) {
                $like = '%' . $this->escaparLike($palabra) . '%';

                $q->where(function (Builder $sub) use ($like) {
                    $sub->where('productos.nombre', 'like', $like)
                        ->orWhere('productos.marca', 'like', $like)
                        ->orWhere('productos.codigo', 'like', $like)
                        ->orWhere('categorias.nombre', 'like', $like)
                        ->orWhere('categorias.slug', 'like', $like)
                        ->orWhere('productos.descripcion', 'like', $like);
                });
            }
        });

        $escapado = $this->escaparLike($termino);

        $query->selectRaw(
            '(CASE WHEN productos.codigo = ? THEN 200 ELSE 0 END
            + CASE WHEN productos.nombre = ? THEN 120 ELSE 0 END
            + CASE WHEN categorias.nombre = ? OR categorias.slug = ? THEN 80 ELSE 0 END
            + CASE WHEN productos.nombre LIKE ? THEN 60 ELSE 0 END
            + CASE WHEN productos.marca = ? THEN 40 ELSE 0 END
            + CASE WHEN productos.nombre LIKE ? THEN 30 ELSE 0 END
            + CASE WHEN categorias.nombre LIKE ? THEN 25 ELSE 0 END
            + CASE WHEN productos.marca LIKE ? THEN 20 ELSE 0 END
            + CASE WHEN productos.descripcion LIKE ? THEN 5 ELSE 0 END
            + CASE WHEN productos.destacado = 1 THEN 3 ELSE 0 END) as relevancia',
            [
                $termino,            // codigo exacto
                $termino,            // nombre exacto
                $termino,            // categoria: nombre exacto
                $termino,            // categoria: slug exacto
                $escapado . '%',       // nombre empieza con
                $termino,            // marca exacta
                '%' . $escapado . '%',   // nombre contiene
                '%' . $escapado . '%',   // categoria contiene
                '%' . $escapado . '%',   // marca contiene
                '%' . $escapado . '%',   // descripcion contiene
            ]
        );
    }

    /**
     * Solo alcanza productos de una promocion que HOY este vigente y sea del
     * canal online. Un banner viejo guardado en favoritos no puede seguir
     * mostrando una lista con un descuento que ya no existe.
     */
    private function aplicarPromocion(Builder $query, int $promocionId): Builder
    {
        return $query->whereHas('promociones', function (Builder $q) use ($promocionId) {
            $q->whereKey($promocionId)
                ->vigentes()
                ->delCanal(CanalVenta::Online);
        });
    }

    private function promocionDelFiltro(?int $id): ?Promocion
    {
        if ($id === null) {
            return null;
        }

        return Promocion::query()
            ->vigentes()
            ->delCanal(CanalVenta::Online)
            ->find($id);
    }

    /**
     * Precio final con promociones, solo para los 24 de la pagina.
     *
     * El descuento no se puede resolver en SQL: la regla de prioridad +
     * acumulable del MotorPromociones es iterativa (cada promocion se aplica
     * sobre el precio ya descontado) y no se expresa en una subconsulta. Por
     * eso se calcula despues de paginar, sobre el puñado que se va a mostrar.
     *
     * @param  LengthAwarePaginator<Producto>  $productos
     * @return array<int, float>  producto_id => precio final
     */
    private function preciosFinales(LengthAwarePaginator $productos, MotorPromociones $motor): array
    {
        $coleccion = $productos->getCollection()->keyBy('id');

        if ($coleccion->isEmpty()) {
            return [];
        }

        $resultados = $motor->paraProductos($coleccion, CanalVenta::Online);

        $precios = [];

        foreach ($coleccion as $id => $producto) {
            $descuento = $resultados[$id]['descuento'] ?? 0;

            // El motor trabaja en centavos; la vista muestra pesos.
            $precios[$id] = (float) $producto->precio - ($descuento / 100);
        }

        return $precios;
    }

    /**
     * Categorias activas que tienen al menos un producto publicado.
     *
     * Listar una categoria vacia es ofrecerle al cliente un filtro que solo
     * puede devolverle "sin resultados".
     */
    private function categoriasConProductos()
    {
        return Categoria::query()
            ->where('activo', true)
            ->whereHas('productos', fn(Builder $q) => $q->publicados())
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get(['id', 'nombre']);
    }

    /** Marcas cargadas en productos publicados. Es texto libre, no una tabla. */
    private function marcasDisponibles()
    {
        return Producto::query()
            ->publicados()
            ->whereNotNull('marca')
            ->where('marca', '<>', '')
            ->distinct()
            ->orderBy('marca')
            ->pluck('marca');
    }

    /** Escapa los comodines de LIKE para que '100%' no busque cualquier cosa. */
    private function escaparLike(string $valor): string
    {
        return addcslashes($valor, '%_\\');
    }
}

/*
 * NOTA SOBRE PRECIO Y PROMOCIONES
 *
 * El filtro de rango y el orden por precio trabajan sobre productos.precio, es
 * decir el precio DE LISTA, no el final con promocion. Consecuencia concreta:
 * un producto de $100.000 con 50% off no entra en "hasta $60.000", aunque en
 * la tarjeta se vea $50.000.
 *
 * Se eligio asi porque la alternativa —traer todo, calcular el descuento en
 * PHP y recien ahi filtrar y ordenar— obliga a cargar el catalogo entero en
 * memoria en cada busqueda y rompe la paginacion del motor.
 *
 * Si la diferencia molesta cuando haya promociones grandes, la salida limpia
 * es materializar el descuento vigente en una columna o tabla auxiliar que el
 * MotorPromociones actualice, y pasar a filtrar y ordenar por ella. Eso es un
 * cambio de modelo de datos, no un ajuste de esta consulta.
 */