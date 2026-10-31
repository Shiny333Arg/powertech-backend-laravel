<?php

namespace App\Services;

use App\Enums\CanalVenta;
use App\Enums\EstadoVenta;
use App\Exceptions\TransicionNoPermitidaException;
use App\Models\Numerador;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Toda la escritura de la venta pasa por aca. El controlador no arma lineas,
 * no calcula totales y no toca stock.
 *
 * Reglas que este servicio hace cumplir:
 *  - El numero interno se toma de `numeradores` con la fila bloqueada, DENTRO
 *    de la transaccion que inserta la venta. Un ROLLBACK devuelve el numero.
 *  - Se asigna en el alta, incluido el presupuesto, y no cambia nunca.
 *  - El presupuesto NO compromete stock. El compromiso nace al confirmarse.
 *  - El precio, la descripcion, la alicuota y el costo quedan CONGELADOS en
 *    la linea: el documento no puede depender de un join contra el catalogo.
 *  - Una venta no se elimina: cancelar y vencer son transiciones de estado.
 *
 * <b>Contrato asumido de StockService</b> (ajustar los nombres si el servicio
 * ya existente usa otra firma; son los dos unicos puntos de contacto):
 *
 *   comprometer(Producto $producto, int $cantidad, Model $origen, ?User $usuario): MovimientoStock
 *   liberar(Producto $producto, int $cantidad, Model $origen, ?User $usuario): MovimientoStock
 *
 * `entregar()` no se usa aca: la salida de mercaderia es un evento posterior
 * e independiente, y vive en el controlador de preparacion/despacho.
 */
class VentaService
{
    /** El flete se factura gravado al 21 %. */
    private const ALICUOTA_ENVIO = 21.00;

    public function __construct(
        private readonly StockService $stock,
        private readonly MotorPromociones $promociones,
    ) {}

    // ------------------------------------------------------------------
    // Alta
    // ------------------------------------------------------------------

    /**
     * Crea un presupuesto o una venta confirmada.
     *
     * `confirmar = true` significa que el vendedor carga y confirma en el
     * mismo acto: la venta nace validada por el, y compromete stock.
     *
     * @param  array{cliente_id?:int|null, modo_entrega:string, confirmar?:bool,
     *               validez_hasta?:string|null, observaciones?:string|null,
     *               costo_envio?:float|string|null,
     *               lineas: list<array{producto_id:int, cantidad:int}>}  $datos
     */
    public function crear(array $datos, User $usuario, CanalVenta $canal = CanalVenta::Mostrador): Venta
    {
        $confirmar = (bool) ($datos['confirmar'] ?? false);

        return DB::transaction(function () use ($datos, $usuario, $canal, $confirmar) {
            $productos = $this->productosDe($datos['lineas']);
            $calculo = $this->calcular($datos['lineas'], $productos, $canal, $datos['costo_envio'] ?? 0);

            $venta = new Venta([
                'numero'        => Numerador::proximoNumero('venta'),
                'canal'         => $canal,
                'cliente_id'    => $datos['cliente_id'] ?? null,
                'usuario_id'    => $usuario->id,
                'estado'        => $confirmar ? EstadoVenta::Confirmada : EstadoVenta::Presupuesto,
                'modo_entrega'  => $datos['modo_entrega'],
                'validez_hasta' => $confirmar ? null : ($datos['validez_hasta'] ?? null),
                'observaciones' => $datos['observaciones'] ?? null,
                'fecha'         => now(),
                ...$calculo['totales'],
            ]);

            if ($confirmar) {
                $venta->usuario_valido_id = $usuario->id;
                $venta->fecha_validacion = now();
            }

            $venta->save();

            $this->guardarLineas($venta, $calculo['lineas']);

            if ($confirmar) {
                $this->comprometerStock($venta, $usuario);
            }

            return $venta->load('lineas.producto');
        });
    }


    /**
     * Venta de la tienda, que nace PAGADA.
     *
     * No pasa por presupuesto ni por confirmada: el pago ya se acredito antes de
     * que esta venta existiera. Es la unica forma de que el stock no quede
     * comprometido por un checkout abandonado, que es el caso normal.
     *
     * Sin vendedor y sin validador: no hay empleado en el circuito online.
     *
     * El comprobante y la preparacion NO se hacen aca: los orquesta
     * CheckoutService, que es quien sabe que el pago entro.
     *
     * @param  array{cliente_id:int, modo_entrega:string, observaciones?:string|null,
     *               lineas: list<array{producto_id:int, cantidad:int}>}  $datos
     */
    public function crearOnline(array $datos): Venta
    {
        return DB::transaction(function () use ($datos) {
            $productos = $this->productosDe($datos['lineas']);
            $calculo = $this->calcular($datos['lineas'], $productos, CanalVenta::Online, 0);

            $venta = new Venta([
                'numero'            => Numerador::proximoNumero('venta'),
                'canal'             => CanalVenta::Online,
                'cliente_id'        => $datos['cliente_id'],
                'usuario_id'        => null,
                'estado'            => EstadoVenta::Pagada,
                'modo_entrega'      => $datos['modo_entrega'],
                'validez_hasta'     => null,
                'usuario_valido_id' => null,
                'fecha_validacion'  => null,
                'observaciones'     => $datos['observaciones'] ?? null,
                'fecha'             => now(),
                ...$calculo['totales'],
            ]);

            $venta->save();

            $this->guardarLineas($venta, $calculo['lineas']);

            // Compromete al nacer porque ya esta pagada. No es el compromiso de
            // una venta confirmada esperando cobro: ese estado no existe online.
            $this->comprometerStock($venta, null);

            return $venta->load('lineas.producto');
        });
    }


    // ------------------------------------------------------------------
    // Edicion
    // ------------------------------------------------------------------

    /**
     * Reemplaza el detalle de un presupuesto.
     *
     * Solo en estado presupuesto: una venta confirmada ya comprometio stock y
     * una pagada ya tiene comprobante con CAE. Editarlas no es edicion, es
     * cancelar y rehacer, o emitir una nota de credito.
     *
     * El numero, el canal y la fecha de alta no se tocan.
     *
     * @param  array<string, mixed>  $datos
     */
    public function actualizar(Venta $venta, array $datos): Venta
    {
        if (! $venta->esPresupuesto()) {
            throw TransicionNoPermitidaException::porque(
                'Solo se puede editar el detalle de una venta en estado presupuesto.'
            );
        }

        return DB::transaction(function () use ($venta, $datos) {
            $productos = $this->productosDe($datos['lineas']);
            $calculo = $this->calcular($datos['lineas'], $productos, $venta->canal, $datos['costo_envio'] ?? 0);

            // Orden obligatorio: promociones_aplicadas referencia venta_lineas.
            $venta->promocionesAplicadas()->delete();
            $venta->lineas()->delete();

            $venta->fill([
                'cliente_id'    => $datos['cliente_id'] ?? null,
                'modo_entrega'  => $datos['modo_entrega'],
                'validez_hasta' => $datos['validez_hasta'] ?? null,
                'observaciones' => $datos['observaciones'] ?? null,
                ...$calculo['totales'],
            ])->save();

            $this->guardarLineas($venta, $calculo['lineas']);

            return $venta->load('lineas.producto');
        });
    }

    // ------------------------------------------------------------------
    // Transiciones
    // ------------------------------------------------------------------

    /**
     * Validacion del presupuesto: lo habilita para el cobro y lo confirma.
     *
     * Es el momento en que nace el compromiso de stock. La reserva nunca se
     * rechaza: comprometer sin stock fisico ES la preventa.
     */
    public function validar(Venta $venta, User $usuario): Venta
    {
        $this->exigirTransicion($venta, EstadoVenta::Confirmada);

        return DB::transaction(function () use ($venta, $usuario) {
            $venta->fill([
                'estado'            => EstadoVenta::Confirmada,
                'usuario_valido_id' => $usuario->id,
                'fecha_validacion'  => now(),
            ])->save();

            $this->comprometerStock($venta, $usuario);

            return $venta;
        });
    }

    /**
     * Cancela la venta. No la borra: la venta no se elimina nunca.
     *
     * Si el estado actual tenia unidades comprometidas, se liberan y vuelven
     * al disponible. Un presupuesto no compromete nada, asi que cancelarlo no
     * libera nada.
     *
     * Una venta ya facturada no se cancela: se devuelve con nota de credito.
     * Quien decide eso es EstadoVenta::puedeTransicionarA().
     */
    public function cancelar(Venta $venta, User $usuario, string $motivo): Venta
    {
        $this->exigirTransicion($venta, EstadoVenta::Cancelada);

        return DB::transaction(function () use ($venta, $usuario, $motivo) {
            if ($venta->comprometeStock()) {
                $this->liberarStock($venta, $usuario);
            }

            $venta->fill([
                'estado'        => EstadoVenta::Cancelada,
                'observaciones' => $this->anotar($venta->observaciones, "Cancelada: {$motivo}"),
            ])->save();

            return $venta;
        });
    }

    /**
     * Vence un presupuesto pasado su `validez_hasta`. Lo invoca el job diario.
     *
     * No libera stock porque el presupuesto nunca lo comprometio: solo caduca
     * el precio ofertado.
     */
    public function vencer(Venta $venta): Venta
    {
        $this->exigirTransicion($venta, EstadoVenta::Vencida);

        $venta->fill(['estado' => EstadoVenta::Vencida])->save();

        return $venta;
    }

    // ------------------------------------------------------------------
    // Stock
    // ------------------------------------------------------------------

    private function comprometerStock(Venta $venta, ?User $usuario): void
    {
        foreach ($venta->load('lineas.producto')->lineas as $linea) {
            $this->stock->comprometer($linea->producto, $linea->cantidad, $venta, $usuario);
        }
    }

    private function liberarStock(Venta $venta, User $usuario): void
    {
        foreach ($venta->load('lineas.producto')->lineas as $linea) {
            // Lo entregado ya salio del comprometido; solo se libera el saldo.
            $pendiente = $linea->cantidad - $linea->cantidad_devuelta;

            if ($pendiente > 0) {
                $this->stock->liberar($linea->producto, $pendiente, $venta, $usuario);
            }
        }
    }

    // ------------------------------------------------------------------
    // Calculo
    // ------------------------------------------------------------------

    /**
     * @param  list<array{producto_id:int, cantidad:int}>  $lineas
     * @return Collection<int, Producto>
     */
    private function productosDe(array $lineas): Collection
    {
        // $ids = collect($lineas)->pluck('producto_id')->map(intval(...))->unique()->all();
        $ids = collect($lineas)
            ->pluck('producto_id')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $productos = Producto::query()
            ->with('alicuotaIva')
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');


        foreach ($ids as $id) {
            $producto = $productos->get($id);

            if (! $producto || ! $producto->activo) {
                throw new RuntimeException("El producto #{$id} no existe o esta dado de baja.");
            }
        }

        return $productos;
    }

    /**
     * Arma el detalle y los totales de la venta.
     *
     * <b>Criterio de precios:</b> `productos.precio` es el precio final al
     * publico, con IVA incluido. El neto y el IVA se desagregan por linea para
     * el comprobante. Si la empresa cargara precios sin IVA, este es el unico
     * metodo que cambia.
     *
     * Todo se calcula en centavos enteros, asi que
     * `neto_gravado + total_iva = total` es exacto por construccion: no hay
     * redondeo que reconciliar despues contra el importe autorizado por ARCA.
     *
     * @param  list<array{producto_id:int, cantidad:int}>  $lineas
     * @param  Collection<int, Producto>  $productos
     * @return array{lineas: list<array<string, mixed>>, totales: array<string, string>}
     */
    private function calcular(array $lineas, Collection $productos, CanalVenta $canal, int|float|string|null $costoEnvio): array
    {
        $descuentos = $this->promociones->paraProductos($productos, $canal);

        $subtotal = 0;
        $descuentoTotal = 0;
        $neto = 0;
        $iva = 0;
        $detalle = [];

        foreach ($lineas as $entrada) {
            $producto = $productos->get((int) $entrada['producto_id']);
            $cantidad = (int) $entrada['cantidad'];

            $precioUnitario = $this->aCentavos($producto->precio);
            $bruto = $precioUnitario * $cantidad;

            $promo = $descuentos[$producto->id] ?? ['descuento' => 0, 'aplicaciones' => []];
            $descuentoLinea = $promo['descuento'] * $cantidad;

            $totalLinea = $bruto - $descuentoLinea;
            $alicuota = (float) $producto->alicuotaIva->porcentaje;
            $netoLinea = (int) round($totalLinea * 100 / (100 + $alicuota));
            $ivaLinea = $totalLinea - $netoLinea;

            $subtotal += $bruto;
            $descuentoTotal += $descuentoLinea;
            $neto += $netoLinea;
            $iva += $ivaLinea;

            $detalle[] = [
                'linea' => [
                    'producto_id'         => $producto->id,
                    'descripcion'         => $producto->nombre,
                    'cantidad'            => $cantidad,
                    'cantidad_devuelta'   => 0,
                    'precio_unitario'     => $this->aDecimal($precioUnitario),
                    'descuento_promocion' => $this->aDecimal($descuentoLinea),
                    'alicuota_iva'        => number_format($alicuota, 2, '.', ''),
                    'costo_unitario'      => $producto->costo !== null ? $this->aDecimal($this->aCentavos($producto->costo)) : null,
                    'neto'                => $this->aDecimal($netoLinea),
                    'iva'                 => $this->aDecimal($ivaLinea),
                    'total'               => $this->aDecimal($totalLinea),
                ],
                // promocion_id => importe de descuento de esta linea
                'aplicaciones' => array_map(
                    fn(int $unitario) => $unitario * $cantidad,
                    $promo['aplicaciones']
                ),
            ];
        }

        $envio = $this->aCentavos($costoEnvio ?? 0);

        if ($envio > 0) {
            $netoEnvio = (int) round($envio * 100 / (100 + self::ALICUOTA_ENVIO));
            $neto += $netoEnvio;
            $iva += $envio - $netoEnvio;
        }

        return [
            'lineas'  => $detalle,
            'totales' => [
                'subtotal'              => $this->aDecimal($subtotal),
                'descuento_promociones' => $this->aDecimal($descuentoTotal),
                'costo_envio'           => $this->aDecimal($envio),
                'neto_gravado'          => $this->aDecimal($neto),
                'total_iva'             => $this->aDecimal($iva),
                'total'                 => $this->aDecimal($subtotal - $descuentoTotal + $envio),
            ],
        ];
    }

    /**
     * @param  list<array{linea: array<string, mixed>, aplicaciones: array<int,int>}>  $detalle
     */
    private function guardarLineas(Venta $venta, array $detalle): void
    {
        foreach ($detalle as $item) {
            $linea = $venta->lineas()->create($item['linea']);

            foreach ($item['aplicaciones'] as $promocionId => $importe) {
                // Deja registrado POR QUE esta linea salio mas barata.
                $venta->promocionesAplicadas()->create([
                    'venta_linea_id'    => $linea->id,
                    'promocion_id'      => $promocionId,
                    'importe_descuento' => $this->aDecimal($importe),
                ]);
            }
        }
    }

    // ------------------------------------------------------------------
    // Auxiliares
    // ------------------------------------------------------------------

    private function exigirTransicion(Venta $venta, EstadoVenta $destino): void
    {
        if (! $venta->puedeTransicionarA($destino)) {
            throw TransicionNoPermitidaException::entre($venta->estado, $destino);
        }
    }

    private function anotar(?string $observaciones, string $nota): string
    {
        $sello = now()->format('d/m/Y H:i');

        return trim(($observaciones ?? '') . "\n[{$sello}] {$nota}");
    }

    private function aCentavos(int|float|string|null $monto): int
    {
        return (int) round(((float) $monto) * 100);
    }

    private function aDecimal(int $centavos): string
    {
        return number_format($centavos / 100, 2, '.', '');
    }
}
