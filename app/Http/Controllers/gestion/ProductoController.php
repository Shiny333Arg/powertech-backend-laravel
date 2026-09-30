<?php

namespace App\Http\Controllers\gestion;

use App\Http\Requests\AjusteStockRequest;
use App\Http\Requests\ProductoRequest;
use App\Models\AlicuotaIva;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Services\StockService;
use App\Exceptions\StockInsuficienteException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * CRUD de productos, sin la D de delete: un producto NO se elimina nunca.
 *
 * Esta referenciado por lineas de venta, lineas de comprobante, movimientos de
 * stock y lineas de orden de compra, y todas esas filas son historia que no
 * puede quedar sin sujeto. La ruta destroy directamente no existe, y si alguien
 * llamara a delete() igual, el guard del modelo lanza la excepcion de dominio.
 * La unica baja es activo = false.
 */
class ProductoController extends Controller
{
    /** Carpeta del disco publico donde vive la foto del producto. */
    private const DIRECTORIO_IMAGENES = 'productos';

    public function __construct(private readonly StockService $stock)
    {
        // Descomentar cuando exista el login:
        // $this->authorizeResource(Producto::class, 'producto');
    }

    /**
     * Listado de gestion, con los filtros que pide la operacion diaria.
     *
     * Muestra activos e inactivos: es la pantalla desde donde se reactiva un
     * producto dado de baja. El filtro es explicito, no un global scope.
     */
    public function index(Request $request): View
    {
        $productos = Producto::query()
            ->with(['categoria:id,nombre', 'proveedor:id,razon_social'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $busqueda = $request->string('q')->trim()->value();

                // Codigo, nombre y marca son los tres campos por los que
                // alguien busca un producto en el mostrador.
                $query->where(function ($sub) use ($busqueda) {
                    $sub->where('codigo', 'like', "%{$busqueda}%")
                        ->orWhere('nombre', 'like', "%{$busqueda}%")
                        ->orWhere('marca', 'like', "%{$busqueda}%");
                });
            })
            ->when($request->filled('categoria_id'), fn ($query) => $query->where('categoria_id', $request->integer('categoria_id')))
            ->when($request->query('estado') === 'activos', fn ($query) => $query->activos())
            ->when($request->query('estado') === 'inactivos', fn ($query) => $query->inactivos())
            // Los dos cortes de stock que alimentan el dashboard del administrativo.
            ->when($request->query('stock') === 'reponer', fn ($query) => $query->bajoPuntoReposicion())
            ->when($request->query('stock') === 'preventa', fn ($query) => $query->where('stock_disponible', '<', 0))
            ->orderBy('nombre')
            ->paginate(10)
            ->withQueryString();

        return view('gestion.item.index', [
            'productos'  => $productos,
            'categorias' => Categoria::activos()->orderBy('nombre')->get(['id', 'nombre']),
            'filtros'    => $request->only('q', 'categoria_id', 'estado', 'stock'),
        ]);
    }

    public function create(): View
    {
        return view('gestion.item.create', [
            'producto' => new Producto([
                'activo'                => true,
                'publicado_app'         => false,
                'reposicion_automatica' => false,
                'punto_reposicion'      => 0,
                'cantidad_reposicion'   => 0,
            ]),
        ] + $this->opcionesDeFormulario());
    }

    /**
     * Alta.
     *
     * El stock inicial NO se escribe en la columna: se carga como movimiento de
     * tipo 'inicial', para que el saldo quede respaldado por el historico desde
     * el primer dia. Producto y movimiento van en la misma transaccion: un
     * producto creado con un movimiento a medias dejaria el saldo sin explicar.
     */
    public function store(ProductoRequest $request): RedirectResponse
    {
        $producto = DB::transaction(function () use ($request) {
            $datos = $request->datosDelProducto();

            $datos['activo'] = true;

            if ($request->hasFile('imagen')) {
                $datos['imagen'] = $this->guardarImagen($request->file('imagen'));
            }

            $producto = Producto::create($datos);

            if ($request->stockInicial() > 0) {
                $this->stock->cargaInicial($producto, $request->stockInicial());
            }

            return $producto;
        });

        return redirect()
            ->route('gestion.items.index')
            ->with('exito', "Se creó «{$producto->nombre}».");
    }

    public function edit(Producto $producto): View
    {
        return view('gestion.item.edit', ['producto' => $producto] + $this->opcionesDeFormulario());
    }

    /**
     * Edicion.
     *
     * No toca ninguna de las tres columnas de stock. Cambiar el saldo desde acá
     * dejaria un movimiento sin registrar y rompe la conciliacion: el stock se
     * corrige con un ajuste de inventario.
     */
    public function update(ProductoRequest $request, Producto $producto): RedirectResponse
    {
        $datos = $request->datosDelProducto();

        if ($request->hasFile('imagen')) {
            $imagenAnterior   = $producto->imagen;
            $datos['imagen']  = $this->guardarImagen($request->file('imagen'));

            $producto->update($datos);
            $this->borrarImagen($imagenAnterior); // recien despues de guardar la nueva

            return redirect()->route('gestion.items.index')
                ->with('exito', "Se guardaron los cambios en «{$producto->nombre}».");
        }

        $producto->update($datos);

        return redirect()
            ->route('gestion.items.index')
            ->with('exito', "Se guardaron los cambios en «{$producto->nombre}».");
    }

    /**
     * Baja logica, la unica baja posible.
     *
     * No hace falta tocar publicado_app: un producto dado de baja no se publica
     * nunca, cualquiera sea ese valor, porque la vitrina filtra por los dos.
     * Dejarlo intacto conserva la decision de publicacion por si se reactiva.
     */
    public function darDeBaja(Producto $producto): RedirectResponse
    {
        $producto->darDeBaja();

        return back()->with('exito', "«{$producto->nombre}» salió de operación. No se puede cargar en ventas nuevas.");
    }

    public function reactivar(Producto $producto): RedirectResponse
    {
        $producto->reactivar();

        return back()->with('exito', "«{$producto->nombre}» vuelve a estar en operación.");
    }

    /** Situacion de stock de un producto y su historico. */
    public function show(Request $request, Producto $producto): View
    {
    }

    /**
     * Publicacion en la tienda: pregunta distinta de la baja. Un producto activo
     * que se vende en mostrador y no se publica —reservado, de exhibicion— es un
     * caso normal del rubro.
     */
    public function publicar(Producto $producto): RedirectResponse
    {
        if (! $producto->activo) {
            return back()->with('error', 'Un producto dado de baja no se puede publicar. Reactivalo primero.');
        }

        $producto->update(['publicado_app' => true]);

        return back()->with('exito', "«{$producto->nombre}» ya se ve en la tienda.");
    }

    public function despublicar(Producto $producto): RedirectResponse
    {
        $producto->update(['publicado_app' => false]);

        return back()->with('exito', "«{$producto->nombre}» dejó de verse en la tienda. Se sigue vendiendo en mostrador.");
    }

    /**
     * Ajuste de inventario. Es el unico camino por el que una persona cambia el
     * stock fisico a mano, y siempre deja movimiento con motivo y responsable.
     *
     * Un ajuste negativo mayor al saldo lo rechaza el servicio: si falta mas de
     * lo que hay, el error esta en un movimiento anterior, no en el ajuste.
     */
    public function ajustarStock(AjusteStockRequest $request, Producto $producto): RedirectResponse
    {
        try {
            $movimiento = $this->stock->ajustar(
                $producto,
                (int) $request->validated('cantidad'),
                $request->validated('motivo'),
            );
        } catch (StockInsuficienteException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return back()->with('exito', sprintf(
            'Stock ajustado. «%s» queda con %d en depósito y %d disponibles.',
            $producto->nombre,
            $movimiento->stock_fisico_resultante,
            $producto->refresh()->stock_disponible,
        ));
    }

    /** Historial de movimientos del producto: explica cada cambio de saldo. */
    public function movimientos(Producto $producto): View
    {
        return view('gestion.item.movimientos', [
            'producto'    => $producto,
            'movimientos' => $producto->movimientosStock()
                ->with('usuario:id,nombres,apellido')
                ->latest('created_at')
                ->paginate(30),
        ]);
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

    /** Requiere `php artisan storage:link`. Guarda la ruta relativa en la columna. */
    private function guardarImagen(UploadedFile $archivo): string
    {
        return $archivo->store(self::DIRECTORIO_IMAGENES, 'public');
    }

    private function borrarImagen(?string $ruta): void
    {
        if ($ruta && Storage::disk('public')->exists($ruta)) {
            Storage::disk('public')->delete($ruta);
        }
    }
}
