<?php

namespace App\Services;

use App\Enums\EstadoVenta;
use App\Exceptions\TransicionNoPermitidaException;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;

/**
 * Devolucion total o parcial de una venta ya entregada.
 *
 * Tres efectos, en una sola transaccion:
 *   1. nota de credito asociada a la factura de origen;
 *   2. movimiento de stock de tipo `devolucion`: reingresa fisico;
 *   3. `cantidad_devuelta` de cada linea y el estado de la venta.
 *
 * La venta no se elimina ni se cancela: `devuelta_parcial` y `devuelta` son
 * estados, y dicen algo que un flag de baja no diria.
 *
 * <b>Contrato asumido de StockService:</b>
 *   devolver(Producto $p, int $cantidad, Model $origen, ?User $usuario)
 *     suma al fisico y no toca el comprometido. El `origen` es el COMPROBANTE
 *     (la nota de credito), no la venta: es la nota la que justifica el
 *     reingreso.
 */
class DevolucionService
{
    public function __construct(
        private readonly FacturacionService $facturacion,
        private readonly StockService $stock,
    ) {
    }

    /**
     * @param  array<int, int>  $cantidades  venta_linea_id => unidades a devolver
     */
    public function devolver(Venta $venta, array $cantidades, User $usuario, string $motivo): Venta
    {
        $cantidades = array_filter($cantidades, fn ($c) => (int) $c > 0);

        if ($cantidades === []) {
            throw TransicionNoPermitidaException::porque('No se indicaron unidades a devolver.');
        }

        if (! $this->yaSeEntrego($venta)) {
            throw TransicionNoPermitidaException::porque(
                'La mercadería todavía no salió del depósito: esta venta se cancela, no se devuelve.'
            );
        }

        return DB::transaction(function () use ($venta, $cantidades, $usuario, $motivo) {
            $nota = $this->facturacion->emitirNotaCredito($venta, $cantidades, $motivo);

            foreach ($venta->load('lineas.producto')->lineas as $linea) {
                $cantidad = (int) ($cantidades[$linea->id] ?? 0);

                if ($cantidad <= 0) {
                    continue;
                }

                // El origen del movimiento es la nota de credito.
                $this->stock->devolver($linea->producto, $cantidad, $nota, $usuario);

                $linea->increment('cantidad_devuelta', $cantidad);
            }

            $destino = $this->todoDevuelto($venta->fresh('lineas'))
                ? EstadoVenta::Devuelta
                : EstadoVenta::DevueltaParcial;

            if (! $venta->puedeTransicionarA($destino)) {
                throw TransicionNoPermitidaException::entre($venta->estado, $destino);
            }

            $venta->fill(['estado' => $destino])->save();

            return $venta->fresh(['lineas', 'comprobantes']);
        });
    }

    private function yaSeEntrego(Venta $venta): bool
    {
        return in_array($venta->estado, [
            EstadoVenta::Despachada,
            EstadoVenta::Entregada,
            EstadoVenta::DevueltaParcial,
        ], true);
    }

    private function todoDevuelto(Venta $venta): bool
    {
        return $venta->lineas->every(fn ($linea) => $linea->pendienteDeDevolucion() === 0);
    }
}
