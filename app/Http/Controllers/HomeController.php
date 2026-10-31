<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Promocion;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use App\Enums\CanalVenta;
use App\Services\MotorPromociones;

/**
 * Vitrina de la tienda.
 *
 * Los "mas vendidos" se calculan sobre venta_lineas, descontando lo devuelto y
 * sin contar presupuestos, ventas canceladas ni vencidas: un presupuesto no es
 * una venta y una cancelada no vendio nada.
 *
 * El conteo va como subconsulta y no como JOIN + GROUP BY a proposito: con
 * 'strict' => true en la conexion, agrupar por la PK y seleccionar el resto de
 * las columnas depende de que el motor detecte la dependencia funcional, y
 * MariaDB no siempre lo hace.
 */
class HomeController extends Controller
{
    /** Estados que no representan mercaderia vendida. */
    private const ESTADOS_NO_VENDIDOS = ['presupuesto', 'cancelada', 'vencida'];

    public function index(): View
    {
        return view('home.index', [
            'promociones' => $this->promocionesVigentes(),
            'perifericos' => $this->masVendidosDe(['teclado', 'mouse', 'parlante', 'auricular']),
            'componentes' => $this->masVendidosDe(['procesador', 'placa-de-video']),
            'monitores'   => $this->masVendidosDe(['monitor']),
        ]);
    }

    /**
     * Promociones con banner cargado, vigentes hoy y visibles en la tienda.
     */
    private function promocionesVigentes()
    {
        return Promocion::query()
            ->where('activo', true)
            ->whereNotNull('banner')
            ->whereIn('canal', ['ambos', 'online'])
            ->where('vigencia_desde', '<=', now())
            ->where('vigencia_hasta', '>=', now())
            ->orderByDesc('prioridad')
            ->orderBy('nombre')
            ->limit(8)
            ->get();
    }


    /**
     * Ficha de producto de la tienda.
     *
     * 404 si no esta publicado: scopePublicados exige activo Y publicado_app, y
     * un producto despublicado no tiene que ser alcanzable por URL directa.
     *
     * El precio con promocion sale del MotorPromociones y no de un calculo local:
     * es la unica fuente de descuento del sistema y trabaja en centavos.
     */
    public function producto(Producto $producto, MotorPromociones $motor): View
    {
        abort_unless($producto->activo && $producto->publicado_app, 404);

        $producto->load('categoria:id,nombre');

        $resultado = $motor->paraProductos(
            collect([$producto->id => $producto]),
            CanalVenta::Online
        )[$producto->id] ?? ['descuento' => 0, 'aplicaciones' => []];

        return view('item.show', [
            'producto'     => $producto,
            'precioFinal'  => (float) $producto->precio - ($resultado['descuento'] / 100),
            'promociones'  => Promocion::whereIn('id', array_keys($resultado['aplicaciones']))->get(),
            'relacionados' => Producto::query()
                ->publicados()
                ->where('categoria_id', $producto->categoria_id)
                ->whereKeyNot($producto->getKey())
                ->inRandomOrder()
                ->limit(4)
                ->get(),
        ]);
    }


    /**
     * Productos publicados de las categorias indicadas, ordenados por unidades
     * vendidas. Incluye los que nunca se vendieron, al final: una categoria
     * nueva no puede quedar con la seccion vacia.
     *
     * @param  list<string>  $slugs
     */
    private function masVendidosDe(array $slugs, int $limite = 8)
    {
        $unidades = DB::table('venta_lineas')
            ->selectRaw('COALESCE(SUM(venta_lineas.cantidad - venta_lineas.cantidad_devuelta), 0)')
            ->join('ventas', 'ventas.id', '=', 'venta_lineas.venta_id')
            ->whereColumn('venta_lineas.producto_id', 'productos.id')
            ->whereNotIn('ventas.estado', self::ESTADOS_NO_VENDIDOS);

        return Producto::query()
            ->select('productos.*')
            ->selectSub($unidades, 'unidades_vendidas')
            ->whereHas('categoria', fn($q) => $q->whereIn('slug', $slugs))
            ->where('productos.activo', true)
            ->where('productos.publicado_app', true)
            ->orderByDesc('unidades_vendidas')
            ->orderBy('productos.nombre')
            ->limit($limite)
            ->get();
    }
}
