<?php

namespace App\Http\Controllers;

use App\Enums\CanalVenta;
use App\Exceptions\FacturacionException;
use App\Exceptions\TransicionNoPermitidaException;
use App\Http\Requests\CheckoutRequest;
use App\Models\Carrito;
use App\Services\CarritoService;
use App\Services\CheckoutService;
use App\Services\MotorPromociones;
use App\Services\Pagos\ResultadoPago;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Cierre de la compra online. Solo retiro en el local.
 *
 * <b>Donde va a entrar la pasarela de pago.</b> Hoy `store()` hace dos cosas
 * que mañana se separan: da el pago por hecho y cierra el carrito. Al
 * integrar Mercado Pago, este controlador se parte en dos acciones —una que
 * crea la preferencia y redirige, y otra que recibe el callback y verifica—
 * y las dos terminan llamando al mismo CheckoutService::confirmar() con un
 * ResultadoPago que, esa vez, viene de la pasarela y no de un simulador.
 *
 * CheckoutService no cambia: es el punto del diseño.
 */
class CheckoutController extends Controller
{
    public function __construct(
        private readonly CarritoService $carritos,
        private readonly CheckoutService $checkout,
    ) {
    }

    /**
     * Resumen previo al pago: que se lleva, cuanto sale y con que datos se
     * factura.
     *
     * Los precios se recalculan aca y no se arrastran desde el carrito: son
     * los vigentes en este momento, y son los que se van a congelar en la
     * venta.
     *
     * <b>Esta pantalla NO cambia el estado del carrito.</b> Es solo una
     * revision: el cliente puede volver atras sin haber hecho nada. El paso a
     * `en_checkout` corresponde al momento en que se lo manda efectivamente a
     * pagar —o sea, cuando exista la pasarela y se cree la preferencia— que es
     * lo que el reporte de abandono en el checkout necesita medir. Marcarlo
     * aca, con solo abrir el resumen, mide otra cosa.
     */
    public function index(MotorPromociones $motor): View
    {
        $carrito = $this->carritos->abiertoDe(request()->user());

        if ($carrito->lineas()->doesntExist()) {
            abort(redirect()
                ->route('cliente.carrito.index')
                ->with('info', 'Tu carrito está vacío.'));
        }

        $carrito->load(['lineas.producto']);

        $productos = $carrito->lineas->pluck('producto')->filter()->keyBy('id');
        $promociones = $motor->paraProductos($productos, CanalVenta::Online);

        $precios = $productos->mapWithKeys(fn ($producto) => [
            $producto->id => (float) $producto->precio
                - (($promociones[$producto->id]['descuento'] ?? 0) / 100),
        ]);

        $cliente = request()->user()->cliente;

        return view('checkout.index', [
            'carrito' => $carrito,
            'cliente' => $cliente,
            'precios' => $precios,
            'total'   => $carrito->lineas->sum(
                fn ($linea) => ($precios[$linea->producto_id] ?? 0) * $linea->cantidad
            ),
            // Si los datos fiscales no permiten facturar, se avisa ACA, antes
            // de ofrecer pagar. Descubrirlo al emitir el comprobante significa
            // revertir una venta ya cobrada.
            'problemaFacturacion' => $cliente
                ? $this->checkout->problemaDeFacturacion($cliente)
                : 'Para comprar necesitás completar tus datos de facturación en tu perfil.',
        ]);
    }

    /**
     * Confirma la compra: nace la venta pagada y el carrito se cierra.
     *
     * El pago se simula. Es lo unico que falta para que el circuito sea real.
     */
    public function store(CheckoutRequest $request): RedirectResponse
    {
        $carrito = $this->carritos->abiertoDe($request->user());

        try {
            // ----------------------------------------------------------
            // PASO 2: acá va la pasarela.
            //
            // Hoy se da el total por cobrado sin cobrarlo. El importe se
            // calcula sobre el carrito y el precio vigente —nunca sobre el
            // body— asi que el simulador no puede inventar un monto.
            //
            // Cuando exista Mercado Pago, este bloque se reemplaza por la
            // verificacion del pago contra la pasarela y el resto no cambia.
            // ----------------------------------------------------------
            $pago = ResultadoPago::simulado(
                $this->totalEnCentavos($carrito)
            );

            $venta = $this->checkout->confirmar(
                carrito: $carrito,
                modoEntrega: $request->modoEntrega(),
                pago: $pago,
                observaciones: $request->input('observaciones'),
            );
        } catch (TransicionNoPermitidaException|FacturacionException $e) {
            // FacturacionException tambien se atrapa: el comprobante se emite
            // dentro de confirmar(), y un catalogo fiscal incompleto o un
            // punto de venta sin configurar no son un error 500 para el
            // cliente. La venta ya se revirtio con la transaccion.
            return redirect()
                ->route('cliente.carrito.index')
                ->with('error', $e->getMessage());
        }

        // Al detalle del pedido: es donde el cliente ve el seguimiento y las
        // instrucciones de retiro.
        return redirect()
            ->route('cliente.compras.show', $venta)
            ->with('exito', $this->checkout->mensajeDeCierre($venta));
    }

    /**
     * Total del carrito a precio vigente, en centavos.
     *
     * Duplica el calculo que hace VentaService porque el importe hay que
     * tenerlo ANTES de que exista la venta: es lo que se le cobra al cliente.
     * CheckoutService compara los dos y aborta si difieren, asi que la
     * duplicacion esta verificada en cada compra y no puede desincronizarse
     * en silencio.
     */
    private function totalEnCentavos(Carrito $carrito): int
    {
        $carrito->load('lineas.producto');

        $productos = $carrito->lineas->pluck('producto')->filter()->keyBy('id');
        $promociones = app(MotorPromociones::class)->paraProductos($productos, CanalVenta::Online);

        $total = 0;

        foreach ($carrito->lineas as $linea) {
            $producto = $productos->get($linea->producto_id);

            if (! $producto) {
                continue;
            }

            $unitario = (int) round(((float) $producto->precio) * 100)
                - ($promociones[$producto->id]['descuento'] ?? 0);

            $total += $unitario * $linea->cantidad;
        }

        return $total;
    }
}
