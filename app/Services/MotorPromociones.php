<?php

namespace App\Services;

use App\Enums\CanalVenta;
use App\Enums\TipoPromocion;
use App\Models\Producto;
use App\Models\Promocion;
use Illuminate\Support\Collection;

/**
 * Resuelve que promocion (o combinacion) alcanza a cada producto y cuanto
 * descuenta por unidad.
 *
 * Las promociones son la UNICA fuente de descuento del sistema: el descuento
 * manual sobre la venta se elimino por requerimiento. Por eso el calculo vive
 * en un servicio propio y no dentro del formulario de venta.
 *
 * <b>Regla de resolucion</b> (implementa prioridad + acumulable):
 *   1. Se ordenan por `prioridad` descendente. La mas alta siempre se aplica.
 *   2. Si esa primera NO es acumulable, se aplica sola y se corta.
 *   3. Si es acumulable, se siguen aplicando las que tambien lo sean, en
 *      orden, cada una sobre el precio ya descontado.
 *   4. La primera no acumulable que aparezca despues corta la cadena.
 *
 * Sin estas dos columnas el resultado dependeria del orden en que el motor
 * devuelve las filas: impredecible y no reproducible ante un reclamo.
 *
 * <b>Todo el calculo es en centavos (int).</b> Trabajar en unidades enteras
 * evita que el redondeo de cada descuento parcial se arrastre a la siguiente
 * promocion de la cadena y termine desajustando el total de la linea.
 */
class MotorPromociones
{
    /**
     * Descuento unitario vigente para cada producto, en una sola consulta.
     *
     * @param  Collection<int, Producto>  $productos  indexados por id
     * @return array<int, array{descuento:int, aplicaciones: array<int,int>}>
     *         producto_id => [descuento total por unidad en centavos,
     *                         promocion_id => importe por unidad en centavos]
     */
    public function paraProductos(Collection $productos, CanalVenta $canal): array
    {
        if ($productos->isEmpty()) {
            return [];
        }

        $ids = $productos->keys()->all();

        $promociones = Promocion::query()
            ->vigentes()
            ->delCanal($canal)
            ->whereHas('productos', fn ($q) => $q->whereIn('productos.id', $ids))
            ->with(['productos' => fn ($q) => $q->select('productos.id')->whereIn('productos.id', $ids)])
            ->orderByDesc('prioridad')
            ->orderBy('id')
            ->get();

        /** @var array<int, list<Promocion>> $porProducto */
        $porProducto = [];

        foreach ($promociones as $promocion) {
            foreach ($promocion->productos as $producto) {
                $porProducto[$producto->id][] = $promocion;
            }
        }

        $resultado = [];

        foreach ($productos as $producto) {
            $resultado[$producto->id] = $this->resolver(
                $producto,
                $porProducto[$producto->id] ?? []
            );
        }

        return $resultado;
    }

    /**
     * @param  list<Promocion>  $promociones  ya ordenadas por prioridad
     * @return array{descuento:int, aplicaciones: array<int,int>}
     */
    private function resolver(Producto $producto, array $promociones): array
    {
        $precio = $this->aCentavos($producto->precio);
        $restante = $precio;
        $aplicaciones = [];

        foreach ($promociones as $i => $promocion) {
            // Despues de la primera, solo siguen las acumulables.
            if ($i > 0 && ! $promocion->acumulable) {
                break;
            }

            $descuento = min($restante, $this->descuentoDe($promocion, $restante));

            if ($descuento > 0) {
                $restante -= $descuento;
                $aplicaciones[$promocion->id] = ($aplicaciones[$promocion->id] ?? 0) + $descuento;
            }

            // Una promocion no acumulable se aplica sola.
            if (! $promocion->acumulable) {
                break;
            }
        }

        return [
            'descuento'    => $precio - $restante,
            'aplicaciones' => $aplicaciones,
        ];
    }

    /** Descuento en centavos que esta promocion produce sobre $base (centavos). */
    private function descuentoDe(Promocion $promocion, int $base): int
    {
        $valor = (float) $promocion->valor;

        return match ($promocion->tipo) {
            TipoPromocion::Porcentaje     => (int) round($base * $valor / 100),
            TipoPromocion::MontoFijo      => (int) round($valor * 100),
            TipoPromocion::PrecioEspecial => max(0, $base - (int) round($valor * 100)),
        };
    }

    private function aCentavos(int|float|string $monto): int
    {
        return (int) round(((float) $monto) * 100);
    }
}
