<?php

namespace App\Services;

use App\Enums\CanalVenta;
use App\Enums\OrdenCatalogo;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Promocion;
use App\Support\VisitanteTienda;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as ColeccionEloquent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Consultas del catalogo de la tienda, sin saber nada de vistas ni de JSON.
 *
 * <b>DEUDA CONOCIDA: esto duplica los metodos privados de
 * Http\Controllers\CatalogoController y de HomeController.</b> Se duplico en
 * lugar de extraerlo porque esta etapa no toca el circuito web que ya
 * funciona. La consecuencia es concreta: si el puntaje de relevancia, los
 * slugs de la vitrina o la definicion de "mas vendido" cambian en un lado y
 * no en el otro, la app y la web muestran catalogos distintos.
 *
 * El camino de salida es mecanico y no cambia comportamiento: los dos
 * controladores web pasan a llamar a este servicio y se borran sus privados.
 * Hasta que eso pase, cualquier cambio va en los dos lugares.
 *
 * <b>Lo unico que NO es una copia</b> es el trato de los filtros de precio:
 * ver aplicarFiltrosDePrecio().
 */
class BuscadorCatalogo
{
    /** Grilla de 4 columnas x 6 filas en la web; se conserva para la app. */
    public const POR_PAGINA = 24;

    /** Estados que no representan mercaderia vendida. */
    private const ESTADOS_NO_VENDIDOS = ['presupuesto', 'cancelada', 'vencida'];

    /**
     * Secciones de la vitrina. Misma definicion que HomeController::index().
     *
     * @var array<string, array{titulo: string, slugs: list<string>}>
     */
    private const SECCIONES_VITRINA = [
        'perifericos' => [
            'titulo' => 'Periféricos',
            'slugs'  => ['teclado', 'mouse', 'parlante', 'auricular'],
        ],
        'componentes' => [
            'titulo' => 'Componentes',
            'slugs'  => ['procesador', 'placa-de-video'],
        ],
        'monitores' => [
            'titulo' => 'Monitores',
            'slugs'  => ['monitor'],
        ],
    ];

    public function __construct(
        private readonly MotorPromociones $motor,
    ) {
    }

    // ==================================================================
    // Precios
    // ==================================================================

    /**
     * Descuentos vigentes de un conjunto de productos, o vacio si quien
     * pregunta no puede ver precios.
     *
     * <b>Para un invitado ni siquiera se consulta el motor.</b> No es una
     * optimizacion: es que el resultado no existe en ninguna variable, asi
     * que no hay forma de que se filtre por un campo olvidado en un Resource.
     * El chequeo del Resource es la segunda capa, no la unica.
     *
     * @param  Collection<int, Producto>  $productos  indexados por id
     * @return array<int, array{descuento:int, aplicaciones: array<int,int>}>
     */
    public function preciosDe(Collection $productos, VisitanteTienda $visitante): array
    {
        if (! $visitante->puedeVerPrecios() || $productos->isEmpty()) {
            return [];
        }

        return $this->motor->paraProductos($productos, CanalVenta::Online);
    }

    /**
     * Deja el precio ya descontado sobre cada producto, para que el Resource
     * no tenga que recibirlo por separado.
     *
     * Son atributos transitorios: no existen como columna, no se persisten y
     * solo viven el tiempo de la respuesta. El motor trabaja en centavos y
     * esta es la unica division por 100 del camino.
     *
     * @param  iterable<Producto>  $productos
     * @param  array<int, array{descuento:int, aplicaciones: array<int,int>}>  $resultados
     */
    public function anotarPrecios(iterable $productos, array $resultados): void
    {
        foreach ($productos as $producto) {
            $descuento = $resultados[$producto->id]['descuento'] ?? null;

            if ($descuento === null) {
                continue;
            }

            $producto->setAttribute(
                'precio_final',
                round((float) $producto->precio - ($descuento / 100), 2),
            );
        }
    }

    // ==================================================================
    // Vitrina
    // ==================================================================

    /**
     * Las secciones de la home, cada una con sus mas vendidos.
     *
     * @return array<string, array{titulo: string, productos: ColeccionEloquent<int, Producto>}>
     */
    public function vitrina(int $porSeccion = 8): array
    {
        $secciones = [];

        foreach (self::SECCIONES_VITRINA as $clave => $definicion) {
            $secciones[$clave] = [
                'titulo'    => $definicion['titulo'],
                'productos' => $this->masVendidosDe($definicion['slugs'], $porSeccion),
            ];
        }

        return $secciones;
    }

    /**
     * Promociones con banner cargado, vigentes hoy y visibles en la tienda.
     *
     * @return ColeccionEloquent<int, Promocion>
     */
    public function promocionesVigentes(int $limite = 8): ColeccionEloquent
    {
        return Promocion::query()
            ->vigentes()
            ->delCanal(CanalVenta::Online)
            ->whereNotNull('banner')
            ->orderByDesc('prioridad')
            ->orderBy('nombre')
            ->limit($limite)
            ->get();
    }

    /**
     * Productos publicados de las categorias indicadas, ordenados por unidades
     * vendidas. Incluye los que nunca se vendieron, al final: una categoria
     * nueva no puede quedar con la seccion vacia.
     *
     * El conteo va como subconsulta y no como JOIN + GROUP BY: con
     * 'strict' => true, agrupar por la PK y seleccionar el resto de las
     * columnas depende de que el motor detecte la dependencia funcional, y
     * MariaDB no siempre lo hace.
     *
     * @param  list<string>  $slugs
     * @return ColeccionEloquent<int, Producto>
     */
    public function masVendidosDe(array $slugs, int $limite = 8): ColeccionEloquent
    {
        $unidades = DB::table('venta_lineas')
            ->selectRaw('COALESCE(SUM(venta_lineas.cantidad - venta_lineas.cantidad_devuelta), 0)')
            ->join('ventas', 'ventas.id', '=', 'venta_lineas.venta_id')
            ->whereColumn('venta_lineas.producto_id', 'productos.id')
            ->whereNotIn('ventas.estado', self::ESTADOS_NO_VENDIDOS);

        return Producto::query()
            ->select('productos.*')
            ->selectSub($unidades, 'unidades_vendidas')
            ->with('categoria:id,nombre')
            ->whereHas('categoria', fn (Builder $q) => $q->whereIn('slug', $slugs))
            ->publicados()
            ->orderByDesc('unidades_vendidas')
            ->orderBy('productos.nombre')
            ->limit($limite)
            ->get();
    }

    /**
     * Otros productos de la misma categoria.
     *
     * @return ColeccionEloquent<int, Producto>
     */
    public function relacionadosDe(Producto $producto, int $limite = 4): ColeccionEloquent
    {
        return Producto::query()
            ->publicados()
            ->with('categoria:id,nombre')
            ->where('categoria_id', $producto->categoria_id)
            ->whereKeyNot($producto->getKey())
            ->inRandomOrder()
            ->limit($limite)
            ->get();
    }

    /**
     * Promociones vigentes que efectivamente alcanzan a este producto.
     *
     * @param  array<int, int>  $aplicaciones  promocion_id => importe
     * @return ColeccionEloquent<int, Promocion>
     */
    public function promocionesAplicadas(array $aplicaciones): ColeccionEloquent
    {
        if ($aplicaciones === []) {
            return Promocion::query()->whereRaw('1 = 0')->get();
        }

        return Promocion::query()
            ->whereIn('id', array_keys($aplicaciones))
            ->orderByDesc('prioridad')
            ->get();
    }

    // ==================================================================
    // Busqueda
    // ==================================================================

    /**
     * @param  array{q:?string, categoria:?int, marca:?string, precio_min:?float,
     *               precio_max:?float, promocion:?int, orden:OrdenCatalogo}  $filtros
     * @return LengthAwarePaginator<Producto>
     */
    public function buscar(array $filtros, VisitanteTienda $visitante, int $porPagina = self::POR_PAGINA): LengthAwarePaginator
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
            ->when($filtros['categoria'], fn (Builder $q, int $id) => $q->where('productos.categoria_id', $id))
            ->when($filtros['marca'], fn (Builder $q, string $marca) => $q->where('productos.marca', $marca))
            ->when($filtros['promocion'], fn (Builder $q, int $id) => $this->aplicarPromocion($q, $id));

        $this->aplicarFiltrosDePrecio($query, $filtros, $visitante);

        $this->ordenEfectivo($filtros['orden'], $visitante)->aplicar($query, $hayTermino);

        return $query->paginate($porPagina)->withQueryString();
    }

    /**
     * <b>Los filtros de precio solo corren para quien puede ver precios.</b>
     *
     * Esta es la unica diferencia deliberada con el catalogo web, y el motivo
     * es que `precio_min` y `precio_max` son un oraculo: con una veintena de
     * consultas de rango, un invitado deduce el precio exacto de cualquier
     * producto sin que ningun campo se lo haya mostrado nunca. Ocultar el
     * importe en el Resource y dejar abierto el filtro seria cerrar la puerta
     * y dejar la ventana.
     *
     * Lo mismo vale para ordenar por precio: dos consultas con el orden
     * invertido ya ordenan el catalogo entero de mas barato a mas caro, que
     * es informacion de precio aunque no sea un numero (ver ordenPermitido).
     *
     * Para el invitado los filtros se ignoran en silencio y no se rechaza la
     * consulta: el criterio del catalogo es sanear y responder, nunca romper
     * una pantalla que tiene que poder mostrarse siempre.
     *
     * <b>Nota:</b> el catalogo web tiene hoy este mismo hueco, porque
     * CatalogoController aplica el rango sin mirar si hay sesion. No se tocó
     * por el alcance de esta etapa, pero conviene decidir si se cierra alla
     * tambien.
     *
     * @param  array{precio_min:?float, precio_max:?float, ...}  $filtros
     */
    private function aplicarFiltrosDePrecio(Builder $query, array $filtros, VisitanteTienda $visitante): void
    {
        if (! $visitante->puedeVerPrecios()) {
            return;
        }

        $query
            ->when($filtros['precio_min'], fn (Builder $q, float $min) => $q->where('productos.precio', '>=', $min))
            ->when($filtros['precio_max'], fn (Builder $q, float $max) => $q->where('productos.precio', '<=', $max));
    }

    /**
     * El orden que se va a aplicar de verdad.
     *
     * Ordenar por precio es informacion de precio: dos consultas con el orden
     * invertido ordenan el catalogo entero de mas barato a mas caro sin que
     * se haya mostrado un solo importe. Para el invitado cae en Relevancia.
     *
     * Es publico porque el controlador necesita devolverle a la app el orden
     * EFECTIVO y no el pedido: si la app sigue mostrando "menor precio"
     * seleccionado mientras el backend ordeno por relevancia, el usuario ve
     * una pantalla que miente.
     */
    public function ordenEfectivo(OrdenCatalogo $orden, VisitanteTienda $visitante): OrdenCatalogo
    {
        $esDePrecio = in_array($orden, [OrdenCatalogo::PrecioAsc, OrdenCatalogo::PrecioDesc], true);

        if ($esDePrecio && ! $visitante->puedeVerPrecios()) {
            return OrdenCatalogo::Relevancia;
        }

        return $orden;
    }

    /**
     * Busqueda por palabras con puntaje de relevancia.
     *
     * Copia literal de CatalogoController::aplicarTermino(). El criterio y el
     * razonamiento completo estan documentados alla: cada palabra tiene que
     * aparecer en algun campo (AND entre palabras, OR entre campos), la
     * categoria entra al filtro y al puntaje porque es lo que hace que una
     * busqueda generica como "mouse" devuelva los mouses y no los productos
     * que mencionan la palabra al pasar, y no se usa FULLTEXT porque se
     * comporta distinto en MySQL y SQLite.
     */
    private function aplicarTermino(Builder $query, string $termino): void
    {
        $query->join('categorias', 'categorias.id', '=', 'productos.categoria_id');

        $palabras = array_slice(
            array_filter(preg_split('/\s+/', $termino) ?: []),
            0,
            8 // Tope defensivo: una frase larga no puede generar 200 LIKE.
        );

        $query->where(function (Builder $q) use ($palabras) {
            foreach ($palabras as $palabra) {
                $like = '%'.$this->escaparLike($palabra).'%';

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
                $termino,                // codigo exacto
                $termino,                // nombre exacto
                $termino,                // categoria: nombre exacto
                $termino,                // categoria: slug exacto
                $escapado.'%',           // nombre empieza con
                $termino,                // marca exacta
                '%'.$escapado.'%',       // nombre contiene
                '%'.$escapado.'%',       // categoria contiene
                '%'.$escapado.'%',       // marca contiene
                '%'.$escapado.'%',       // descripcion contiene
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

    public function promocionDelFiltro(?int $id): ?Promocion
    {
        if ($id === null) {
            return null;
        }

        return Promocion::query()
            ->vigentes()
            ->delCanal(CanalVenta::Online)
            ->find($id);
    }

    // ==================================================================
    // Filtros disponibles
    // ==================================================================

    /**
     * Categorias activas con al menos un producto publicado.
     *
     * Listar una categoria vacia es ofrecerle al cliente un filtro que solo
     * puede devolverle "sin resultados".
     *
     * @return ColeccionEloquent<int, Categoria>
     */
    public function categoriasConProductos(): ColeccionEloquent
    {
        return Categoria::query()
            ->where('activo', true)
            ->whereHas('productos', fn (Builder $q) => $q->publicados())
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'slug']);
    }

    /**
     * Marcas cargadas en productos publicados. Es texto libre, no una tabla.
     *
     * @return Collection<int, string>
     */
    public function marcasDisponibles(): Collection
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