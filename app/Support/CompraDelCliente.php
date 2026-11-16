<?php

namespace App\Support;

use App\Enums\EstadoPago;
use App\Enums\EstadoVenta;
use App\Enums\ModoEntrega;
use App\Models\Cliente;
use App\Models\Comprobante;
use App\Models\Venta;

/**
 * "Mis compras" visto por el cliente: que ventas son suyas, cuales cuentan
 * como compra y en que paso del circuito esta cada pedido.
 *
 * <b>Existe para que la web y la app contesten lo mismo.</b> Todas estas
 * reglas nacieron en CompraController (Blade) como metodos privados, y
 * Api\V1\CompraController necesita exactamente las mismas: que un presupuesto
 * no es una compra, que un pedido ajeno responde 404 y no 403, y como se
 * deriva el seguimiento del estado actual. Copiadas en los dos controladores,
 * el primer cambio del circuito deja el historial de la app diciendo una cosa
 * y el de la web otra.
 *
 * <b>Solo lectura.</b> El cliente no cancela ni modifica desde aca: cancelar
 * una venta pagada libera stock comprometido y, si ya tiene factura con CAE,
 * exige nota de credito. Eso es una decision de la empresa, no una accion de
 * autogestion.
 */
final class CompraDelCliente
{
    /**
     * Estados que no son una compra del cliente.
     *
     * Un presupuesto no es una compra, es un precio ofertado. Si mas adelante
     * se quiere que el cliente vea sus presupuestos para venir a pagarlos, es
     * una seccion aparte con otra semantica, no una fila mas de esta lista.
     *
     * @var list<EstadoVenta>
     */
    public const NO_SON_COMPRAS = [
        EstadoVenta::Presupuesto,
        EstadoVenta::Vencida,
    ];

    /**
     * Los mismos estados como valores de columna, para un whereNotIn.
     *
     * @return list<string>
     */
    public static function valoresQueNoSonCompras(): array
    {
        return array_map(fn (EstadoVenta $estado) => $estado->value, self::NO_SON_COMPRAS);
    }

    public static function esCompra(EstadoVenta $estado): bool
    {
        return ! in_array($estado, self::NO_SON_COMPRAS, true);
    }

    /**
     * La venta es una compra de este cliente y se le puede mostrar.
     *
     * Un cliente sin ficha en `clientes` no tiene ventas posibles: la
     * comparacion con null da false y no hay que tratarlo como caso aparte.
     */
    public static function esVisiblePara(Venta $venta, ?Cliente $cliente): bool
    {
        return $cliente !== null
            && $venta->cliente_id === $cliente->id
            && self::esCompra($venta->estado);
    }

    // ==================================================================
    // Seguimiento
    // ==================================================================

    /**
     * Pasos del seguimiento, en orden, con su situacion.
     *
     * <b>Se deriva del estado actual, no de un historial.</b> `ventas` no
     * guarda cuando ocurrio cada transicion, asi que lo que se puede afirmar
     * es "este paso ya pasó" por la posicion del estado en el circuito. Si mas
     * adelante hace falta la fecha de cada paso, hay que registrar las
     * transiciones; hoy no existen.
     *
     * `pendiente_stock` es una rama, no un paso fijo: solo se muestra cuando
     * el pedido esta efectivamente esperando reposicion. Un pedido que paso de
     * pagado a preparacion sin faltantes nunca estuvo ahi, y mostrarlo como
     * paso cumplido seria mentir.
     *
     * Vacio cuando el pedido no tiene seguimiento sino un desenlace
     * (cancelado o devuelto). Ver desenlace().
     *
     * @return list<array{etiqueta:string, detalle:string, situacion:string}>
     */
    public static function seguimiento(Venta $venta): array
    {
        if ($venta->estado->esFinDeVida()) {
            return [];
        }

        $esRetiro = $venta->modo_entrega === ModoEntrega::Retiro;

        $circuito = [
            [
                'estado'   => EstadoVenta::Pagada,
                'etiqueta' => 'Pago acreditado',
                'detalle'  => 'Recibimos tu pago y emitimos la factura.',
            ],
        ];

        if ($venta->estado === EstadoVenta::PendienteStock) {
            $circuito[] = [
                'estado'   => EstadoVenta::PendienteStock,
                'etiqueta' => 'Esperando reposición',
                'detalle'  => 'Algún producto no está en depósito. Ya se lo pedimos al proveedor.',
            ];
        }

        $circuito[] = [
            'estado'   => EstadoVenta::EnPreparacion,
            'etiqueta' => 'En preparación',
            'detalle'  => 'Estamos armando tu pedido.',
        ];

        $circuito[] = $esRetiro
            ? [
                'estado'   => EstadoVenta::ListaRetiro,
                'etiqueta' => 'Listo para retirar',
                'detalle'  => 'Te esperamos en el local con tu DNI y el número de pedido.',
            ]
            : [
                'estado'   => EstadoVenta::Despachada,
                'etiqueta' => 'Despachado',
                'detalle'  => 'Tu pedido salió del depósito.',
            ];

        $circuito[] = [
            'estado'   => EstadoVenta::Entregada,
            'etiqueta' => $esRetiro ? 'Retirado' : 'Entregado',
            'detalle'  => 'Pedido entregado. ¡Gracias por tu compra!',
        ];

        $actual = self::posicionDe($venta->estado, $circuito);

        return array_map(
            fn (array $paso, int $i) => [
                'etiqueta'  => $paso['etiqueta'],
                'detalle'   => $paso['detalle'],
                'situacion' => match (true) {
                    $i < $actual   => 'hecho',
                    $i === $actual => 'actual',
                    default        => 'pendiente',
                },
            ],
            $circuito,
            array_keys($circuito),
        );
    }

    /**
     * Indice del estado actual dentro del circuito.
     *
     * `confirmada` devuelve -1 a proposito: es una venta de mostrador que
     * todavia no se pago, asi que ni el primer paso esta cumplido.
     *
     * @param  list<array{estado:EstadoVenta, etiqueta:string, detalle:string}>  $circuito
     */
    private static function posicionDe(EstadoVenta $estado, array $circuito): int
    {
        foreach ($circuito as $i => $paso) {
            if ($paso['estado'] === $estado) {
                return $i;
            }
        }

        return -1;
    }

    /**
     * Que paso con un pedido que ya no esta en curso, o null si sigue vivo.
     *
     * Un pedido cancelado o devuelto no tiene seguimiento: tiene un desenlace,
     * y es lo unico que el cliente necesita leer. El texto lo arma el backend
     * y no la app, para que las dos interfaces expliquen lo mismo.
     *
     * @return array{titulo:string, detalle:string}|null
     */
    public static function desenlace(Venta $venta): ?array
    {
        if (! $venta->estado->esFinDeVida()) {
            return null;
        }

        return [
            'titulo'  => $venta->estado->etiqueta(),
            'detalle' => match ($venta->estado) {
                EstadoVenta::Cancelada => 'Este pedido fue cancelado. Si pagaste, comunicate con PowerTech por la devolución.',
                EstadoVenta::Devuelta => 'Devolvimos este pedido completo y emitimos la nota de crédito.',
                EstadoVenta::DevueltaParcial => 'Devolvimos parte de este pedido y emitimos la nota de crédito correspondiente.',
                default => 'Consultá con PowerTech por el estado de este pedido.',
            },
        ];
    }

    /**
     * Que tiene que hacer el cliente para recibir el pedido.
     *
     * Lo arma el backend porque depende del modo de entrega y del numero de
     * pedido, y porque es la misma indicacion que da la web. Si manana se
     * habilitan los envios con tracking, cambia aca y no en Flutter.
     */
    public static function instruccionesDeEntrega(Venta $venta): string
    {
        if ($venta->modo_entrega === ModoEntrega::Retiro) {
            return 'Cuando el pedido esté listo, pasá a buscarlo por el local. '
                .'Llevá tu DNI y el número de pedido '.$venta->numeroFormateado().'.';
        }

        return 'Te vamos a avisar cuando el pedido salga del depósito.';
    }

    // ==================================================================
    // Pago y facturacion
    // ==================================================================

    /**
     * Cuanto de este pedido esta efectivamente cobrado.
     *
     * <b>No se deriva del estado de la venta.</b> Una venta `pagada` lo esta
     * porque hay pagos acreditados, y es esa suma la que se informa: inferirlo
     * del estado mostraria "pagado" en un pedido de mostrador que se cobro a
     * medias. Solo suman los acreditados, que es lo que ya decide
     * EstadoPago::sumaAlCobrado().
     *
     * `situacion` resume la comparacion para que la app no tenga que hacer
     * aritmetica de importes:
     *   pendiente  nada acreditado
     *   parcial    algo acreditado, menos que el total
     *   acreditado alcanza o supera el total
     *
     * @return array{situacion:string, etiqueta:string, total_acreditado:float, saldo:float}
     */
    public static function resumenDePago(float $total, float $acreditado): array
    {
        $saldo = round(max(0, $total - $acreditado), 2);

        $situacion = match (true) {
            $acreditado <= 0      => 'pendiente',
            $saldo > 0            => 'parcial',
            default               => 'acreditado',
        };

        return [
            'situacion' => $situacion,
            'etiqueta'  => match ($situacion) {
                'pendiente' => 'Pendiente de pago',
                'parcial'   => 'Pago parcial',
                default     => 'Pagado',
            },
            'total_acreditado' => round($acreditado, 2),
            'saldo'            => $saldo,
        ];
    }

    /**
     * Lo acreditado de una coleccion de pagos ya cargada.
     *
     * Para el detalle, donde los pagos vienen con el modelo. En el listado la
     * suma la hace la base con withSum(), que es una sola consulta para toda
     * la pagina.
     *
     * @param  iterable<\App\Models\Pago>  $pagos
     */
    public static function acreditadoDe(iterable $pagos): float
    {
        $total = 0.0;

        foreach ($pagos as $pago) {
            if ($pago->estado === EstadoPago::Acreditado) {
                $total += (float) $pago->monto;
            }
        }

        return round($total, 2);
    }

    /**
     * La factura vigente del pedido, o null.
     *
     * La de signo positivo que no fue rechazada. Un rechazo de ARCA no
     * invalida la compra, pero tampoco es un comprobante que el cliente pueda
     * usar.
     *
     * El casteo de `signo` es deliberado: es una columna de tipos_comprobante
     * y no se puede dar por hecho que el modelo la castee a entero. Sin el,
     * con PDO devolviendo "1" como string, la comparacion estricta falla y el
     * cliente nunca ve su factura.
     *
     * Exige `comprobantes.tipoComprobante` cargado.
     */
    public static function facturaVigente(Venta $venta): ?Comprobante
    {
        return $venta->comprobantes
            ->first(fn (Comprobante $c) => (int) $c->tipoComprobante?->signo === 1 && $c->estaAutorizado());
    }
}
