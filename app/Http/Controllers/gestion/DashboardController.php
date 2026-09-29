<?php

namespace App\Http\Controllers\gestion;

use App\Enums\EstadoPago;
use App\Enums\EstadoVenta;
use App\Models\OrdenCompra;
use App\Models\Pago;
use App\Models\Producto;
use App\Models\Promocion;
use App\Models\Venta;
use App\Models\VentaLinea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Un controlador, tres dashboards. Cada rol ve lo suyo, y lo que decide que
 * bloque se arma es el permiso, no el nombre del rol: agregar un rol nuevo no
 * obliga a tocar este archivo.
 */
class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $usuario = $request->user();
        $datos = [];

        if ($usuario->tienePermiso('venta.ver_todas')) {
            $datos['gestion'] = $this->gestion();
        }

        if ($usuario->tienePermiso('venta.crear')) {
            $datos['vendedor'] = $this->vendedor($usuario->id);
        }

        if ($usuario->tienePermiso('venta.cobrar')) {
            $datos['cajero'] = $this->cajero($usuario->id);
        }

        return view('gestion.dashboard', $datos);
    }

    /**
     * Dashboard del administrativo (requerimiento 3.2).
     *
     * @return array<string, mixed>
     */
    private function gestion(): array
    {
        $cerradas = [
            EstadoVenta::Presupuesto->value,
            EstadoVenta::Cancelada->value,
            EstadoVenta::Vencida->value,
        ];

        return [
            'ventasHoy' => Venta::whereDate('fecha', today())
                ->whereNotIn('estado', $cerradas)->count(),

            'ventasMes' => Venta::whereBetween('fecha', [now()->startOfMonth(), now()->endOfMonth()])
                ->whereNotIn('estado', $cerradas)->count(),

            'facturadoMes' => Venta::whereBetween('fecha', [now()->startOfMonth(), now()->endOfMonth()])
                ->whereNotIn('estado', $cerradas)->sum('total'),

            // Mas vendidos del mes, por unidades.
            'masVendidos' => VentaLinea::query()
                ->select('producto_id', DB::raw('SUM(cantidad) as unidades'))
                ->whereHas('venta', fn ($q) => $q
                    ->whereBetween('fecha', [now()->startOfMonth(), now()->endOfMonth()])
                    ->whereNotIn('estado', $cerradas))
                ->with('producto:id,codigo,nombre')
                ->groupBy('producto_id')
                ->orderByDesc('unidades')
                ->limit(10)
                ->get(),

            // El umbral se compara contra el DISPONIBLE, no contra el fisico:
            // contra el fisico, un producto con todo vendido y pendiente de
            // entrega pareceria abastecido.
            //
            // Se traen proveedor_id y reposicion_automatica porque la vista los
            // necesita para distinguir los tres casos que se ven igual en la
            // tabla: ya pedido, sin pedir, y sin poder pedir.
            'bajoPuntoReposicion' => Producto::query()
                ->activos()
                ->bajoPuntoReposicion()
                ->orderBy('stock_disponible')
                ->limit(20)
                ->get([
                    'id', 'codigo', 'nombre', 'proveedor_id', 'reposicion_automatica',
                    'stock_fisico', 'stock_comprometido', 'stock_disponible', 'punto_reposicion',
                ]),

            // Reposicion automatica activada y sin proveedor: no hay a quien
            // pedirle. No falla y no avisa; por eso se lista aparte en vez de
            // quedar mezclado con el resto.
            'sinProveedor' => Producto::query()
                ->activos()
                ->conReposicionAutomatica()
                ->sinProveedor()
                ->orderBy('nombre')
                ->limit(20)
                ->get(['id', 'codigo', 'nombre', 'stock_disponible', 'punto_reposicion']),

            // Preventas facturadas esperando mercaderia, por antiguedad.
            'pendientesDeStock' => Venta::query()
                ->pendientesDeStock()
                ->with('cliente:id,razon_social')
                ->limit(20)
                ->get(),

            // Pasa por el modelo y su scope en vez de un DB::table con el
            // estado escrito a mano: la definicion de "pendiente de aprobacion"
            // vive en un solo lugar y el enum la nombra.
            'ordenesPendientes' => OrdenCompra::pendientesDeAprobacion()->count(),

            'comprobantesConProblema' => DB::table('comprobantes')
                ->whereIn('estado', ['pendiente', 'rechazado'])->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function vendedor(int $usuarioId): array
    {
        return [
            'misVentasHoy' => Venta::where('usuario_id', $usuarioId)
                ->whereDate('fecha', today())->count(),

            'misVentasMes' => Venta::where('usuario_id', $usuarioId)
                ->whereBetween('fecha', [now()->startOfMonth(), now()->endOfMonth()])->count(),

            'presupuestosAbiertos' => Venta::where('usuario_id', $usuarioId)
                ->where('estado', EstadoVenta::Presupuesto->value)
                ->orderBy('validez_hasta')
                ->limit(10)->get(),

            'ultimasVentas' => Venta::where('usuario_id', $usuarioId)
                ->with('cliente:id,razon_social')
                ->latest('fecha')->limit(10)->get(),

            'promocionesVigentes' => Promocion::vigentes()
                ->withCount('productos')
                ->orderByDesc('prioridad')
                ->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function cajero(int $usuarioId): array
    {
        $hoy = Pago::where('usuario_id', $usuarioId)
            ->where('estado', EstadoPago::Acreditado->value)
            ->whereDate('fecha', today());

        return [
            'cobradoHoy'    => (clone $hoy)->sum('monto'),
            'arqueoHoy'     => (clone $hoy)->selectRaw('metodo, SUM(monto) as total, COUNT(*) as cantidad')
                ->groupBy('metodo')->get(),
            'ultimosCobros' => (clone $hoy)->with('venta:id,numero')->latest('fecha')->limit(10)->get(),
            'porCobrar'     => Venta::where('estado', EstadoVenta::Confirmada->value)
                ->whereNotNull('fecha_validacion')->count(),
        ];
    }
}
