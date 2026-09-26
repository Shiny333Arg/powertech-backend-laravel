<?php

namespace App\Services;

use App\Enums\EstadoVenta;
use App\Enums\ModoEntrega;
use App\Enums\TipoMovimientoStock;
use App\Exceptions\TransicionNoPermitidaException;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;

/**
 * Etapa posterior al cobro: la mercaderia sale del deposito.
 *
 * Es el unico lugar del sistema donde baja el stock fisico. No baja al cobrar:
 * una preventa se cobra y se factura sin que haya mercaderia, y descontar el
 * fisico en ese momento violaria el invariante `stock_fisico >= 0`.
 *
 * <b>Cuando sale la mercaderia depende del modo de entrega:</b>
 *   retiro : al entregarsela al cliente en el mostrador  -> `entregada`
 *   envio  : al despacharse                              -> `despachada`
 *
 * Por eso el movimiento de `entrega` se registra una sola vez, y las
 * transiciones posteriores (despachada -> entregada) ya no tocan stock. El
 * guard es la existencia del movimiento, no el estado.
 *
 * <b>Contrato asumido de StockService:</b>
 *   entregar(Producto $p, int $cantidad, Model $origen, ?User $usuario)
 *     descuenta fisico y libera comprometido en un solo movimiento.
 *     Lanza StockInsuficienteException si no hay unidades.
 */
class EntregaService
{
    public function __construct(private readonly StockService $stock)
    {
    }

    // ------------------------------------------------------------------
    // Preparacion
    // ------------------------------------------------------------------

    /**
     * Decide que pasa con una venta recien pagada.
     *
     * Si hay stock fisico para todas sus lineas va a preparacion; si no, queda
     * en `pendiente_stock`: facturada, con las unidades comprometidas, hasta
     * que llegue la reposicion. Alcanza con que falte un solo producto para
     * que toda la venta espere.
     */
    public function preparar(Venta $venta): Venta
    {
        $destino = $this->hayStockFisico($venta)
            ? EstadoVenta::EnPreparacion
            : EstadoVenta::PendienteStock;

        return $this->transicionar($venta, $destino);
    }

    /** El pedido ya esta armado y espera al cliente en el local. */
    public function listaParaRetiro(Venta $venta): Venta
    {
        if ($venta->modo_entrega !== ModoEntrega::Retiro) {
            throw TransicionNoPermitidaException::porque(
                'Solo una venta con retiro en local puede quedar lista para retirar.'
            );
        }

        return $this->transicionar($venta, EstadoVenta::ListaRetiro);
    }

    // ------------------------------------------------------------------
    // Salida del deposito
    // ------------------------------------------------------------------

    /**
     * Despacho de un envio: es el momento en que la mercaderia sale.
     */
    public function despachar(Venta $venta, User $usuario): Venta
    {
        if ($venta->modo_entrega !== ModoEntrega::Envio) {
            throw TransicionNoPermitidaException::porque(
                'Solo se despacha una venta con envío a domicilio.'
            );
        }

        return DB::transaction(function () use ($venta, $usuario) {
            $this->sacarDelDeposito($venta, $usuario);

            $venta->envio?->forceFill(['fecha_despacho' => now()])->save();

            return $this->transicionar($venta, EstadoVenta::Despachada);
        });
    }

    /**
     * Entrega al cliente.
     *
     * En una venta de mostrador con retiro inmediato es acá donde sale la
     * mercadería, y `pagada -> entregada` es una transición válida y directa:
     * los estados intermedios son del circuito logístico, no un trámite
     * obligatorio.
     *
     * En un envío ya despachado la mercadería salió antes, así que esto solo
     * cierra el documento.
     */
    public function entregar(Venta $venta, User $usuario): Venta
    {
        return DB::transaction(function () use ($venta, $usuario) {
            $this->sacarDelDeposito($venta, $usuario);

            return $this->transicionar($venta, EstadoVenta::Entregada);
        });
    }

    /**
     * Registra el movimiento de `entrega` de cada linea: descuenta fisico y
     * libera comprometido en el mismo instante.
     *
     * Si ya se registro (caso del envio que se despacho y despues se entrega),
     * no hace nada: el stock sale una sola vez.
     */
    private function sacarDelDeposito(Venta $venta, User $usuario): void
    {
        if ($this->yaSalio($venta)) {
            return;
        }

        foreach ($venta->load('lineas.producto')->lineas as $linea) {
            $this->stock->entregar($linea->producto, $linea->cantidad, $venta, $usuario);
        }
    }

    private function yaSalio(Venta $venta): bool
    {
        return $venta->movimientosStock()
            ->where('tipo', TipoMovimientoStock::Entrega->value)
            ->exists();
    }

    // ------------------------------------------------------------------
    // Cola de preventas
    // ------------------------------------------------------------------

    /**
     * Al entrar mercaderia de un producto, habilita para preparacion las
     * ventas en `pendiente_stock` que ya puedan entregarse, EN ORDEN DE
     * ANTIGUEDAD: quien pago primero se entrega primero.
     *
     * El orden no es un detalle estetico. Sin el, dos preventas del mismo
     * producto compiten por las unidades recien llegadas segun el orden que
     * devuelva el motor, y el cliente que pago primero puede quedar esperando
     * otro ciclo de reposicion.
     *
     * @return int cuantas ventas se liberaron
     */
    public function liberarCola(Producto $producto): int
    {
        $ventas = Venta::query()
            ->pendientesDeStock()
            ->whereHas('lineas', fn ($q) => $q->where('producto_id', $producto->id))
            ->with('lineas.producto')
            ->get();

        $liberadas = 0;

        foreach ($ventas as $venta) {
            if (! $this->hayStockFisico($venta)) {
                continue;
            }

            $this->transicionar($venta, EstadoVenta::EnPreparacion);
            $liberadas++;
        }

        return $liberadas;
    }

    // ------------------------------------------------------------------
    // Auxiliares
    // ------------------------------------------------------------------

    /** Hay fisico para todas las lineas de la venta. */
    public function hayStockFisico(Venta $venta): bool
    {
        // fresh() a proposito: el saldo pudo moverlo otra operacion mientras
        // esta venta estaba en pantalla.
        foreach ($venta->load('lineas')->lineas as $linea) {
            $fisico = Producto::whereKey($linea->producto_id)->value('stock_fisico');

            if ((int) $fisico < $linea->cantidad) {
                return false;
            }
        }

        return true;
    }

    private function transicionar(Venta $venta, EstadoVenta $destino): Venta
    {
        if (! $venta->puedeTransicionarA($destino)) {
            throw TransicionNoPermitidaException::entre($venta->estado, $destino);
        }

        $venta->fill(['estado' => $destino])->save();

        return $venta;
    }
}
