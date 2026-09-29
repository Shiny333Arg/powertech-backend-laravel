<?php

namespace App\Http\Controllers\gestion;

use App\Enums\TipoMovimientoStock;
use App\Exceptions\StockInsuficienteException;
use App\Http\Requests\AjusteStockRequest;
use App\Models\MovimientoStock;
use App\Models\Producto;
use App\Services\EntregaService;
use App\Services\StockService;
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

        $filtros = $request->validate([
            'producto_id' => ['nullable', 'integer'],
            'tipo'        => ['nullable', 'string'],
            'desde'       => ['nullable', 'date'],
            'hasta'       => ['nullable', 'date', 'after_or_equal:desde'],
        ]);

        $movimientos = MovimientoStock::query()
            ->with(['producto:id,codigo,nombre', 'usuario:id,nombres,apellido'])
            ->when($filtros['producto_id'] ?? null, fn ($q, $p) => $q->where('producto_id', $p))
            ->when($filtros['tipo'] ?? null, fn ($q, $t) => $q->where('tipo', $t))
            ->when($filtros['desde'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($filtros['hasta'] ?? null, fn ($q, $h) => $q->whereDate('created_at', '<=', $h))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('gestion.stock.index', [
            'movimientos' => $movimientos,
            'filtros'     => $filtros,
            'tipos'       => TipoMovimientoStock::cases(),
        ]);
    }

    /** Situacion de stock de un producto y su historico. */
    public function show(Request $request, Producto $producto): View
    {
        $this->exigirPermiso($request, 'stock.ver');

        return view('gestion.stock.show', [
            'producto'    => $producto,
            'movimientos' => $producto->movimientosStock()
                ->with('usuario:id,nombres,apellido')
                ->latest('id')
                ->paginate(20),
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

    private function exigirPermiso(Request $request, string $clave): void
    {
        abort_unless($request->user()?->tienePermiso($clave), 403);
    }
}
