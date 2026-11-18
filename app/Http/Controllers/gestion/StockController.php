<?php

namespace App\Http\Controllers\gestion;

use App\Enums\TipoMovimientoStock;
use App\Exceptions\StockInsuficienteException;
use App\Http\Requests\AjusteStockRequest;
use App\Models\MovimientoStock;
use App\Models\Producto;
use App\Services\EntregaService;
use App\Services\StockService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Stock: consulta del historico y ajustes manuales.
 *
 * No hay pantalla que escriba `productos.stock_fisico` directamente. Todo
 * cambio deja un movimiento con tipo, cantidad, momento, responsable y motivo:
 * con facturacion electronica de por medio, las diferencias de inventario hay
 * que poder explicarlas.
 *
 * <b>Supuesto:</b> `AjusteStockRequest` valida `producto_id`, `cantidad` (con
 * signo) y `motivo`. Si usa otros nombres, cambiar solo `ajustar()`.
 *
 * <b>Contrato asumido de StockService:</b>
 *   ajustar(Producto $p, int $delta, string $motivo, ?User $usuario)
 *   inicial(Producto $p, int $cantidad, ?User $usuario)
 */
class StockController extends Controller
{
    public function __construct(
        private readonly StockService $stock,
        private readonly EntregaService $entregas,
    ) {
    }

    /** Kardex: el historico completo, filtrable por producto. */
    public function index(Request $request): View
    {
        $this->exigirPermiso($request, 'stock.ver');

        $filtros = $request->validate($this->reglasDeFiltros(incluyeProducto: true));

        $movimientos = $this->aplicarFiltros(
            MovimientoStock::query()->with(['producto:id,codigo,nombre', 'usuario:id,nombres,apellido']),
            $filtros,
        )
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('gestion.stock.index', [
            'movimientos' => $movimientos,
            'filtros'     => $filtros,
            'tipos'       => TipoMovimientoStock::cases(),
        ]);
    }

    /**
     * Situacion de stock de un producto y su historico.
     *
     * Acepta los mismos filtros que el kardex global, menos el de producto,
     * que ya lo impone la ruta. `withQueryString()` es lo que los conserva al
     * pasar de pagina, y `$filtros` lo que los deja marcados en el formulario.
     */
    public function show(Request $request, Producto $producto): View
    {
        $this->exigirPermiso($request, 'stock.ver');

        $filtros = $request->validate($this->reglasDeFiltros(incluyeProducto: false));

        $movimientos = $this->aplicarFiltros(
            $producto->movimientosStock()->with('usuario:id,nombres,apellido'),
            $filtros,
        )
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('gestion.stock.show', [
            'producto'    => $producto,
            'movimientos' => $movimientos,
            'filtros'     => $filtros,
        ]);
    }

    /**
     * Ajuste manual con motivo obligatorio: diferencias de conteo, roturas,
     * mermas.
     *
     * Un ajuste negativo que dejaria el fisico bajo cero se rechaza: una
     * faltante mayor al saldo indica que el error esta en un movimiento
     * anterior, no en el ajuste.
     *
     * Un ajuste positivo puede destrabar preventas, asi que al sumar se revisa
     * la cola de `pendiente_stock` de ese producto.
     */
    public function ajustar(AjusteStockRequest $request): RedirectResponse
    {
        $this->exigirPermiso($request, 'stock.ajustar');

        $datos = $request->validated();
        $producto = Producto::findOrFail($datos['producto_id']);
        $delta = (int) $datos['cantidad'];

        try {
            $liberadas = DB::transaction(function () use ($producto, $delta, $datos, $request) {
                $this->stock->ajustar($producto, $delta, $datos['motivo'], $request->user());

                return $delta > 0 ? $this->entregas->liberarCola($producto->fresh()) : 0;
            });
        } catch (StockInsuficienteException $e) {
            return back()->withInput()->withErrors(['cantidad' => $e->getMessage()]);
        }

        $mensaje = 'Ajuste registrado.';

        if ($liberadas > 0) {
            $mensaje .= " {$liberadas} venta(s) pendientes de stock pasaron a preparación.";
        }

        return back()->with('status', $mensaje);
    }

    /**
     * Carga inicial de inventario: el conteo fisico de puesta en marcha.
     *
     * Se hace por movimiento y nunca escribiendo `productos.stock_fisico`
     * directamente: el saldo tiene que quedar respaldado por el historico
     * desde el primer dia. Es la condicion de go-live.
     */
    public function cargaInicial(Request $request): RedirectResponse
    {
        $this->exigirPermiso($request, 'stock.ajustar');

        $datos = $request->validate([
            'conteos'               => ['required', 'array', 'min:1'],
            'conteos.*.producto_id' => ['required', 'integer', 'distinct', 'exists:productos,id'],
            'conteos.*.cantidad'    => ['required', 'integer', 'min:0', 'max:999999'],
        ]);

        DB::transaction(function () use ($datos, $request) {
            foreach ($datos['conteos'] as $conteo) {
                $producto = Producto::findOrFail($conteo['producto_id']);

                // Un producto que ya tiene historico no admite carga inicial:
                // eso seria un ajuste, y el ajuste pide motivo.
                if ($producto->movimientosStock()->exists()) {
                    continue;
                }

                if ((int) $conteo['cantidad'] > 0) {
                    $this->stock->inicial($producto, (int) $conteo['cantidad'], $request->user());
                }
            }
        });

        return back()->with('status', 'Conteo inicial cargado.');
    }

    /**
     * Reglas de los filtros del historico, compartidas por las dos pantallas.
     *
     * `producto_id` solo aplica al kardex global: en el de un producto el
     * filtro ya lo impone la ruta, y aceptarlo ahi permitiria pedir los
     * movimientos de otro producto por la query string.
     *
     * @return array<string, array<int, string>>
     */
    private function reglasDeFiltros(bool $incluyeProducto): array
    {
        $reglas = [
            'tipo'  => ['nullable', 'string'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
        ];

        if (! $incluyeProducto) {
            return $reglas;
        }

        return ['producto_id' => ['nullable', 'integer']] + $reglas;
    }

    /**
     * Aplica los filtros validados, sobre el modelo o sobre la relacion.
     *
     * @param  array{producto_id?: int|string|null, tipo?: string|null, desde?: string|null, hasta?: string|null}  $filtros
     */
    private function aplicarFiltros(Builder|HasMany $consulta, array $filtros): Builder|HasMany
    {
        return $consulta
            ->when($filtros['producto_id'] ?? null, fn ($q, $p) => $q->where('producto_id', $p))
            ->when($filtros['tipo'] ?? null, fn ($q, $t) => $q->where('tipo', $t))
            ->when($filtros['desde'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($filtros['hasta'] ?? null, fn ($q, $h) => $q->whereDate('created_at', '<=', $h));
    }

    private function exigirPermiso(Request $request, string $clave): void
    {
        abort_unless($request->user()?->tienePermiso($clave), 403);
    }
}
