<?php

namespace App\Http\Controllers;

use App\Enums\EstadoVenta;
use App\Enums\ModoEntrega;
use App\Models\Venta;
use Illuminate\View\View;

/**
 * "Mis compras": el historial del cliente y el seguimiento de cada pedido.
 *
 * Solo lectura. El cliente no cancela ni modifica desde aca: cancelar una
 * venta pagada libera stock comprometido y, si ya tiene factura con CAE,
 * exige nota de credito. Eso es una decision de la empresa, no una accion de
 * autogestion.
 *
 * <b>Muestra los dos canales.</b> Una compra en el mostrador tambien es una
 * compra del cliente, y aparece con su etiqueta. Lo que NO aparece son los
 * presupuestos ni los presupuestos vencidos: un presupuesto no es una compra,
 * es un precio ofertado. Si mas adelante se quiere que el cliente vea sus
 * presupuestos para venir a pagarlos, es una seccion aparte con otra
 * semantica, no una fila mas de esta lista.
 */
class CompraController extends Controller
{
    /** Estados que no son una compra del cliente. */
    private const NO_SON_COMPRAS = [
        EstadoVenta::Presupuesto,
        EstadoVenta::Vencida,
    ];

    /**
     * Historial, lo mas reciente primero.
     *
     * Un cliente sin ficha (`clientes`) no tiene ventas posibles: se le
     * muestra la lista vacia en lugar de un error.
     */
    public function index(): View
    {
        $cliente = request()->user()->cliente;

        $compras = Venta::query()
            ->where('cliente_id', $cliente?->id)
            ->whereNotIn('estado', array_map(fn ($e) => $e->value, self::NO_SON_COMPRAS))
            ->withCount('lineas')
            ->latest('fecha')
            ->latest('id')
            ->paginate(10);

        return view('compras.index', [
            'compras' => $compras,
        ]);
    }

    /**
     * Detalle y seguimiento de un pedido.
     *
     * El detalle sale de `venta_lineas` y no del catalogo: descripcion, precio
     * y alicuota estan congelados ahi, y es lo que se le cobro. Si el producto
     * se renombro o cambio de precio despues, el pedido tiene que seguir
     * mostrando lo que el cliente compro.
     */
    public function show(Venta $venta): View
    {
        $this->verificarPropiedad($venta);

        $venta->load([
            'lineas.producto:id,imagen,nombre,activo,publicado_app',
            'pagos',
            'comprobantes.tipoComprobante',
            'comprobantes.puntoVenta',
        ]);

        return view('compras.show', [
            'venta'       => $venta,
            'seguimiento' => $this->seguimientoDe($venta),
            // Factura vigente: la de signo positivo que no fue rechazada.
            // Un rechazo de ARCA no invalida la compra, pero tampoco es un
            // comprobante que el cliente pueda usar.
            //
            // El casteo de `signo` es deliberado: es una columna de
            // tipos_comprobante y no se puede dar por hecho que el modelo la
            // castee a entero. Sin el, con PDO devolviendo "1" como string,
            // la comparacion estricta falla y el cliente nunca ve su factura.
            'factura' => $venta->comprobantes
                ->first(fn ($c) => (int) $c->tipoComprobante?->signo === 1 && $c->estaAutorizado()),
        ]);
    }

    /**
     * Pasos del seguimiento, en orden, con su situacion.
     *
     * <b>Se deriva del estado actual, no de un historial.</b> `ventas` no
     * guarda cuando ocurrio cada transicion, asi que lo que se puede afirmar
     * es "este paso ya pasó" por la posicion del estado en el circuito. Si
     * mas adelante hace falta la fecha de cada paso, hay que registrar las
     * transiciones; hoy no existen.
     *
     * `pendiente_stock` es una rama, no un paso fijo: solo se muestra cuando
     * el pedido esta efectivamente esperando reposicion. Un pedido que paso de
     * pagado a preparacion sin faltantes nunca estuvo ahi, y mostrarlo como
     * paso cumplido seria mentir.
     *
     * @return list<array{etiqueta:string, detalle:string, situacion:string}>
     */
    private function seguimientoDe(Venta $venta): array
    {
        // Un pedido cancelado o devuelto no tiene seguimiento: tiene un
        // desenlace. Se informa aparte, en la vista.
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

        $actual = $this->posicionDe($venta->estado, $circuito);

        return array_map(
            fn (array $paso, int $i) => [
                'etiqueta' => $paso['etiqueta'],
                'detalle'  => $paso['detalle'],
                'situacion' => match (true) {
                    $i < $actual  => 'hecho',
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
    private function posicionDe(EstadoVenta $estado, array $circuito): int
    {
        foreach ($circuito as $i => $paso) {
            if ($paso['estado'] === $estado) {
                return $i;
            }
        }

        return -1;
    }

    /**
     * El pedido tiene que ser del cliente que pregunta.
     *
     * 404 y no 403: el pedido de otro cliente no tiene por que existir para
     * este usuario. Un 403 confirmaria que ese numero de pedido existe.
     */
    private function verificarPropiedad(Venta $venta): void
    {
        $cliente = request()->user()->cliente;

        abort_unless(
            $cliente && $venta->cliente_id === $cliente->id,
            404,
        );

        // Un presupuesto no es una compra: no se accede por esta puerta ni
        // escribiendo la URL a mano.
        abort_if(
            in_array($venta->estado, self::NO_SON_COMPRAS, true),
            404,
        );
    }
}
