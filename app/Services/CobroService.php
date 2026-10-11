<?php

namespace App\Services;

use App\Enums\EstadoPago;
use App\Enums\EstadoVenta;
use App\Enums\MetodoPago;
use App\Enums\ModoEntrega;
use App\Exceptions\TransicionNoPermitidaException;
use App\Models\Pago;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;

/**
 * El cobro de mostrador.
 *
 * Admite pago mixto y pagos parciales: la venta pasa a `pagada` recien cuando
 * lo acreditado cubre el total. Un booleano de "cobrada" no permitiria ninguna
 * de las dos cosas, ni el arqueo del cajero.
 *
 * <b>Al completarse el cobro ocurren tres cosas en una misma transaccion:</b>
 *   1. la venta pasa a `pagada`;
 *   2. se emite el comprobante;
 *   3. se decide el estado siguiente segun haya o no stock fisico.
 *
 * <b>El stock no se toca.</b> El fisico baja cuando la mercaderia sale del
 * deposito, no cuando se paga. En una venta de mostrador que el cliente se
 * lleva en el acto, cobro y entrega ocurren seguidos y parecen un solo paso,
 * pero son dos eventos distintos: en una preventa pueden separarse por semanas.
 */
class CobroService
{
    public function __construct(
        private readonly FacturacionService $facturacion,
        private readonly EntregaService $entregas,
    ) {
    }

    /**
     * Registra uno o varios pagos presenciales sobre una venta confirmada.
     *
     * @param  list<array{metodo:string, monto:float|string, cuotas?:int|null}>  $pagos
     * @param  bool  $entregarInmediato  el cliente se lleva la mercaderia en el acto
     */
    public function cobrar(Venta $venta, array $pagos, User $cajero, bool $entregarInmediato = false): Venta
    {
        if ($venta->estado !== EstadoVenta::Confirmada) {
            throw TransicionNoPermitidaException::porque(
                'Solo se cobra una venta confirmada. Un presupuesto tiene que validarse primero.'
            );
        }

        if (! $venta->estaValidada()) {
            throw TransicionNoPermitidaException::porque(
                'El presupuesto no fue validado por un vendedor: no se puede cobrar.'
            );
        }

        return DB::transaction(function () use ($venta, $pagos, $cajero, $entregarInmediato) {
            $this->exigirQueNoSePaseDelTotal($venta, $pagos);

            foreach ($pagos as $pago) {
                $venta->pagos()->create([
                    'metodo'   => $pago['metodo'],
                    'monto'    => number_format((float) $pago['monto'], 2, '.', ''),
                    'cuotas'   => $this->cuotasDe($pago),
                    // Un cobro presencial se acredita en el acto: el efectivo
                    // esta en la caja y el posnet ya aprobo. El estado
                    // `pendiente` es del circuito online.
                    'estado'     => EstadoPago::Acreditado,
                    'usuario_id' => $cajero->id,
                    'fecha'      => now(),
                ]);
            }

            if ($this->saldoPendiente($venta->fresh('pagos')) > 0) {
                // Pago parcial: la venta sigue confirmada y no se factura.
                return $venta->fresh('pagos');
            }

            $venta->fill(['estado' => EstadoVenta::Pagada])->save();

            $this->facturacion->emitirPorVenta($venta);

            $this->entregas->preparar($venta);

            if ($entregarInmediato
                && $venta->estado === EstadoVenta::EnPreparacion
                && $venta->modo_entrega === ModoEntrega::Retiro) {
                $this->entregas->entregar($venta, $cajero);
            }

            return $venta->fresh(['pagos', 'comprobantes']);
        });
    }

    /**
     * Saldo que falta cobrar, en centavos de peso pero devuelto como entero de
     * centavos para comparar sin arrastre de punto flotante.
     */
    public function saldoPendiente(Venta $venta): int
    {
        $acreditado = $venta->pagos()
            ->where('estado', EstadoPago::Acreditado->value)
            ->sum('monto');

        return $this->centavos($venta->total) - $this->centavos($acreditado);
    }

    /**
     * @param  list<array{metodo:string, monto:float|string, cuotas?:int|null}>  $pagos
     */
    private function exigirQueNoSePaseDelTotal(Venta $venta, array $pagos): void
    {
        $nuevo = array_sum(array_map(fn ($p) => $this->centavos($p['monto']), $pagos));

        if ($nuevo <= 0) {
            throw TransicionNoPermitidaException::porque('El cobro tiene que ser mayor a cero.');
        }

        if ($nuevo > $this->saldoPendiente($venta)) {
            throw TransicionNoPermitidaException::porque(
                'El cobro supera el saldo pendiente de la venta. El vuelto no se registra como pago.'
            );
        }
    }

    /** Las cuotas solo tienen sentido con tarjeta. */
    private function cuotasDe(array $pago): ?int
    {
        return $pago['metodo'] === MetodoPago::Tarjeta->value
            ? (int) ($pago['cuotas'] ?? 1)
            : null;
    }

    private function centavos(int|float|string|null $monto): int
    {
        return (int) round(((float) $monto) * 100);
    }
}
