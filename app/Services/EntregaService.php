<?php

namespace App\Services;

use App\Enums\EstadoVenta;
use App\Enums\ModoEntrega;
use App\Enums\TipoMovimientoStock;
use App\Exceptions\TransicionNoPermitidaException;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaLinea;
use Illuminate\Support\Collection;
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
     * Si hay stock fisico libre para todas sus lineas va a preparacion; si no,
     * queda en `pendiente_stock`: facturada, con las unidades comprometidas,
     * hasta que llegue la reposicion. Alcanza con que falte un solo producto
     * para que toda la venta espere.
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
     * El orden lo garantiza el scope pendientesDeStock(), que ya ordena por
     * fecha. Tenerlo en el scope y no en cada consulta es lo que hace que esta
     * cola y la pantalla del deposito muestren lo mismo en el mismo orden sin
     * que nadie se tenga que acordar.
     *
     * <b>Lo que el orden por antiguedad NO resuelve.</b> Decide a quien se le
     * promete primero, no cuanto hay para prometer. Si llegan 10 unidades y hay
     * tres preventas de 10 esperando, comparar cada una contra el fisico crudo
     * las habilita a las tres: las tres le dicen al cliente que su pedido se
     * esta preparando, la primera que se despache se lleva todo y las otras dos
     * fallan recien en el mostrador, con el pedido ya armado. Por eso lo que se
     * compara es el fisico LIBRE, y por eso se descuenta sobre la marcha lo que
     * se va asignando en este mismo recorrido.
     *
     * @return int cuantas ventas se liberaron
     */
    public function liberarCola(Producto $producto): int
    {
        $ventas = Venta::query()
            ->pendientesDeStock()
            ->whereHas('lineas', fn ($q) => $q->where('producto_id', $producto->id))
            ->with('lineas')
            ->get();

        if ($ventas->isEmpty()) {
            return 0;
        }

        $libre = $this->fisicoLibreDe($this->productosDe($ventas));

        $liberadas = 0;

        foreach ($ventas as $venta) {
            if (! $this->alcanzaCon($venta, $libre)) {
                continue;
            }

            $this->transicionar($venta, EstadoVenta::EnPreparacion);

            // Estas unidades ya tienen dueño. Descontarlas acá es lo que
            // impide prometerle las mismas diez unidades a las tres ventas
            // siguientes de la cola.
            foreach ($venta->lineas as $linea) {
                $libre[$linea->producto_id] -= $linea->cantidad;
            }

            $liberadas++;
        }

        return $liberadas;
    }

    // ------------------------------------------------------------------
    // Disponibilidad fisica
    // ------------------------------------------------------------------

    /**
     * Hay fisico libre para todas las lineas de la venta.
     *
     * Libre, no total: ver fisicoLibreDe().
     */
    public function hayStockFisico(Venta $venta): bool
    {
        $venta->loadMissing('lineas');

        $libre = $this->fisicoLibreDe($venta->lineas->pluck('producto_id'));

        return $this->alcanzaCon($venta, $libre);
    }

    /**
     * Unidades de cada producto que estan en el deposito y todavia no estan
     * prometidas a nadie.
     *
     * <b>El fisico solo no sirve para esta pregunta.</b> Una venta en
     * `en_preparacion` o `lista_retiro` tiene su mercaderia en el estante —no
     * salio todavia, el movimiento de entrega corre al despachar o al
     * entregar— pero esas unidades ya tienen dueño. Contarlas como disponibles
     * es prometer dos veces lo mismo.
     *
     * `despachada` queda afuera: ahi el movimiento de entrega ya corrio y esas
     * unidades ya no estan en el fisico, asi que restarlas seria restarlas dos
     * veces. `pagada` tambien queda afuera, por una razon distinta: es un
     * estado de transito de milisegundos —preparar() corre inmediatamente
     * despues de acreditar el pago— y esperar a que se resuelva no cambia la
     * cuenta.
     *
     * No se usa stock_disponible (fisico − comprometido) porque el comprometido
     * incluye TODAS las ventas sin entregar, empezando por las preventas que
     * son justamente las que esta cola quiere liberar: con esa cuenta, un
     * producto en preventa nunca alcanzaria para nadie.
     *
     * @param  iterable<int>  $productoIds
     * @return array<int, int>  producto_id => unidades libres
     */
    private function fisicoLibreDe(iterable $productoIds): array
    {
        $ids = collect($productoIds)->map(fn ($id) => (int) $id)->unique()->values();

        if ($ids->isEmpty()) {
            return [];
        }

        $fisico = Producto::query()
            ->whereIn('id', $ids)
            ->pluck('stock_fisico', 'id');

        $prometido = VentaLinea::query()
            ->whereIn('producto_id', $ids)
            ->whereHas('venta', fn ($q) => $q->whereIn('estado', [
                EstadoVenta::EnPreparacion->value,
                EstadoVenta::ListaRetiro->value,
            ]))
            ->groupBy('producto_id')
            ->selectRaw('producto_id, SUM(cantidad) as reservado')
            ->pluck('reservado', 'producto_id');

        return $ids
            ->mapWithKeys(fn (int $id) => [
                $id => (int) ($fisico[$id] ?? 0) - (int) ($prometido[$id] ?? 0),
            ])
            ->all();
    }

    /**
     * @param  array<int, int>  $libre
     */
    private function alcanzaCon(Venta $venta, array $libre): bool
    {
        foreach ($venta->lineas as $linea) {
            if (($libre[$linea->producto_id] ?? 0) < $linea->cantidad) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  Collection<int, Venta>  $ventas
     * @return Collection<int, int>
     */
    private function productosDe(Collection $ventas): Collection
    {
        return $ventas
            ->flatMap(fn (Venta $venta) => $venta->lineas->pluck('producto_id'))
            ->unique()
            ->values();
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
