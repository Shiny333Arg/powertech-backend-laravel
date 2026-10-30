@extends('layouts.gestion')

@section('title', 'PowerTech')

@php
$usuario = auth()->user();
@endphp

@section('content')

<div class="container-fluid py-4">

    {{-- Bienvenida --}}
    <div class="w-100 mb-4">
        <h5 class="mb-0 fw-bold">
            Bienvenido/a {{ $usuario->apellido }}, {{ $usuario->nombres }}
        </h5>
    </div>

    <p>tareas pendientes:</p>
    <ul>
        <li>los presupuestos deben tener fecha de vencimiento: 1 semana. al actualizar un precio, un presupuesto y una venta no deben cambiar</li>
        <li>varios proveedores por cada producto</li>
        <li>gestion/compra/crear: autoreposición automática con un botón</li>
        <li>gestion/venta/crear: corregir la tabla, "precio unitario" "importe"</li>
        <li>gestion/venta/crear: mostrar impuestos</li>
        <li>hardcodear los correos para los proveedores</li>
        <li>remito para el manejo de stock, obligatorio</li>
        <li>no puedo tener stock negativo</li>
        <li>cuando hago una venta, la promoción no debe ser autompática, debe ser seleccionable</li>
        <li>for update en el cobro de ventas, para asegurarse de que hay stock</li>
        <li>iva, ingresos brutos, investigar para las ventas</li>
        <li>facturación con arca</li>
        <li>mercado pago</li>
        <li>carrito de compras</li>
        <li>tracking</li>
        <li>ver productos, lado cliente</li>
        <li>buscador de productos, lado cliente</li>
        <li>ver detalles de una promoción, lado cliente</li>
    </ul>


    {{-- Avisos --}}
    @if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}

        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar">
        </button>
    </div>
    @endif

    @if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}

        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar">
        </button>
    </div>
    @endif

    @if (session('warning'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        {{ session('warning') }}

        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar">
        </button>
    </div>
    @endif

    @if (session('info'))
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        {{ session('info') }}

        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar">
        </button>
    </div>
    @endif


    {{-- Cajero --}}
    @isset($cajero)

    <section class="mb-5">

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h2 class="h5 fw-semibold mb-0">
                Caja
            </h2>
        </div>

        <div class="row g-3">

            {{-- Cobrado hoy --}}
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <p class="small text-muted mb-1">
                            Cobrado hoy
                        </p>

                        <p class="h3 fw-semibold mb-0">
                            ${{ number_format($cajero['cobradoHoy'], 2, ',', '.') }}
                        </p>
                    </div>
                </div>
            </div>


            {{-- Arqueo --}}
            @foreach ($cajero['arqueoHoy'] as $fila)
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">

                        <p class="small text-muted mb-1">
                            {{ ucfirst(is_object($fila->metodo) ? $fila->metodo->value : $fila->metodo) }}
                        </p>

                        <p class="h3 fw-semibold mb-1">
                            ${{ number_format($fila->total, 2, ',', '.') }}
                        </p>

                        <p class="small text-muted mb-0">
                            {{ $fila->cantidad }}
                            {{ $fila->cantidad === 1 ? 'cobro' : 'cobros' }}
                        </p>

                    </div>
                </div>
            </div>
            @endforeach

        </div>


        {{-- Últimos cobros --}}
        @if ($cajero['ultimosCobros']->isNotEmpty())
        <div class="card shadow-sm mt-3">

            <div class="card-header bg-white">
                <h3 class="h6 mb-0">
                    Últimos cobros
                </h3>
            </div>

            <ul class="list-group list-group-flush">

                @foreach ($cajero['ultimosCobros'] as $pago)
                <li class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-2">

                    <div>
                        <a href="{{ route('gestion.ventas.show', $pago->venta_id) }}" class="text-decoration-underline">
                            V-{{ str_pad((string) $pago->venta?->numero, 7, '0', STR_PAD_LEFT) }}
                        </a>

                        <span class="ms-2 text-muted">
                            {{ ucfirst($pago->metodo->value) }}
                        </span>
                    </div>

                    <span class="fw-semibold">
                        ${{ number_format($pago->monto, 2, ',', '.') }}
                    </span>

                </li>
                @endforeach

            </ul>

        </div>
        @endif

    </section>
    @endisset


    {{-- Vendedor --}}
    @isset($vendedor)
    <section class="mb-5">

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h2 class="h5 fw-semibold mb-0">
                Mis ventas
            </h2>
        </div>


        {{-- Estadísticas --}}
        <div class="row g-3 mb-3">

            <div class="col-12 col-sm-6">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">

                        <p class="small text-muted mb-1">
                            Hoy
                        </p>

                        <p class="h3 fw-semibold mb-0">
                            {{ $vendedor['misVentasHoy'] }}
                        </p>

                    </div>
                </div>
            </div>


            <div class="col-12 col-sm-6">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">

                        <p class="small text-muted mb-1">
                            Este mes
                        </p>

                        <p class="h3 fw-semibold mb-0">
                            {{ $vendedor['misVentasMes'] }}
                        </p>

                    </div>
                </div>
            </div>

        </div>


        <div class="row g-3">

            {{-- Presupuestos abiertos --}}
            <div class="col-12 col-lg-6">
                <div class="card h-100 shadow-sm">

                    <div class="card-header bg-white">
                        <h3 class="h6 mb-0">
                            Presupuestos abiertos
                        </h3>
                    </div>

                    @if ($vendedor['presupuestosAbiertos']->isEmpty())

                    <div class="card-body">

                        <p class="fw-semibold mb-1">
                            Ningún presupuesto pendiente
                        </p>

                        <p class="text-muted small mb-0">
                            Los que emitas van a listarse acá hasta que se validen o venzan.
                        </p>

                    </div>

                    @else

                    <ul class="list-group list-group-flush">

                        @foreach ($vendedor['presupuestosAbiertos'] as $venta)
                        <li class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-2">

                            <a href="{{ route('gestion.ventas.show', $venta) }}" class="text-decoration-underline">
                                {{ $venta->numeroFormateado() }}
                            </a>

                            <span class="text-muted small">
                                vence {{ $venta->validez_hasta?->format('d/m') ?? '—' }}
                            </span>

                            <span class="fw-semibold">
                                ${{ number_format($venta->total, 2, ',', '.') }}
                            </span>

                        </li>
                        @endforeach

                    </ul>

                    @endif

                </div>
            </div>


            {{-- Promociones vigentes --}}
            <div class="col-12 col-lg-6">
                <div class="card h-100 shadow-sm">

                    <div class="card-header bg-white">
                        <h3 class="h6 mb-0">
                            Promociones vigentes
                        </h3>
                    </div>

                    @if ($vendedor['promocionesVigentes']->isEmpty())

                    <div class="card-body">

                        <p class="fw-semibold mb-1">
                            No hay promociones activas
                        </p>

                        <p class="text-muted small mb-0">
                            Las carga un administrativo desde el módulo de promociones.
                        </p>

                    </div>

                    @else

                    <ul class="list-group list-group-flush">

                        @foreach ($vendedor['promocionesVigentes'] as $promocion)
                        <li class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-2">

                            <span>
                                {{ $promocion->nombre }}
                            </span>

                            <span class="text-muted small">
                                {{ $promocion->productos_count }} productos
                                · hasta {{ $promocion->vigencia_hasta?->format('d/m') }}
                            </span>

                        </li>
                        @endforeach

                    </ul>

                    @endif

                </div>
            </div>

        </div>

    </section>
    @endisset


    {{-- Administrativo --}}
    @isset($gestion)
    @can('create', App\Models\Producto::class)
    <section class="mb-5">

        <h2 class="h5 fw-semibold mb-3">
            El negocio
        </h2>


        {{-- Estadísticas generales --}}
        <div class="row g-3 mb-3">

            {{-- Ventas hoy --}}
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">

                        <p class="small text-muted mb-1">
                            Ventas hoy
                        </p>

                        <p class="h3 fw-semibold mb-0">
                            {{ $gestion['ventasHoy'] }}
                        </p>

                    </div>
                </div>
            </div>


            {{-- Ventas mes --}}
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">

                        <p class="small text-muted mb-1">
                            Ventas del mes
                        </p>

                        <p class="h3 fw-semibold mb-0">
                            {{ $gestion['ventasMes'] }}
                        </p>

                    </div>
                </div>
            </div>


            {{-- Facturado --}}
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">

                        <p class="small text-muted mb-1">
                            Facturado en el mes
                        </p>

                        <p class="h3 fw-semibold mb-0">
                            ${{ number_format($gestion['facturadoMes'], 2, ',', '.') }}
                        </p>

                    </div>
                </div>
            </div>


            {{-- Comprobantes --}}
            <!-- <div class="col-12 col-sm-6 col-lg-3">
                <div class="card h-100 shadow-sm {{ $gestion['comprobantesConProblema'] > 0 ? 'border-danger' : '' }}">

                    <div class="card-body">

                        <p class="small text-muted mb-1">
                            Comprobantes sin CAE
                        </p>

                        <p class="h3 fw-semibold mb-1 {{ $gestion['comprobantesConProblema'] > 0 ? 'text-danger' : '' }}">
                            {{ $gestion['comprobantesConProblema'] }}
                        </p>

                        @if ($gestion['comprobantesConProblema'] > 0)
                        <a href="{{ route('gestion.comprobantes.index', ['estado' => 'rechazado']) }}" class="small text-danger">
                            Revisar
                        </a>
                        @endif

                    </div>
                </div>
            </div> -->



            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card h-100 shadow-sm
                    {{ $gestion['ordenesPendientes'] > 0 ? 'border-warning' : '' }}">

                    <div class="card-body">

                        <p class="small text-muted mb-1">
                            Pedidos por aprobar
                        </p>

                        <p class="h3 fw-semibold mb-1
                            {{ $gestion['ordenesPendientes'] > 0 ? 'text-warning' : '' }}">
                            {{ $gestion['ordenesPendientes'] }}
                        </p>

                        @if ($gestion['ordenesPendientes'] > 0)
                        <a href="{{ route('gestion.compras.index', ['estado' => 'borrador']) }}"
                            class="small">
                            Revisar
                        </a>
                        @else
                        <p class="small text-muted mb-0">
                            Ningún pedido esperando aprobación.
                        </p>
                        @endif

                    </div>
                </div>
            </div>

        </div>







        {{-- Compras y reposición --}}
        <div class="row g-3 mb-3">

            {{--
                Pedidos por aprobar. Es lo único accionable de este tablero:
                el sistema arma el borrador, pero no sale sin que un
                administrativo lo mire.
            --}}
            <!-- <div class="col-12 col-sm-6 col-lg-3">
                <div class="card h-100 shadow-sm
                    {{ $gestion['ordenesPendientes'] > 0 ? 'border-warning' : '' }}">

                    <div class="card-body">

                        <p class="small text-muted mb-1">
                            Pedidos por aprobar
                        </p>

                        <p class="h3 fw-semibold mb-1
                            {{ $gestion['ordenesPendientes'] > 0 ? 'text-warning' : '' }}">
                            {{ $gestion['ordenesPendientes'] }}
                        </p>

                        @if ($gestion['ordenesPendientes'] > 0)
                        <a href="{{ route('gestion.compras.index', ['estado' => 'borrador']) }}"
                            class="small">
                            Revisar
                        </a>
                        @else
                        <p class="small text-muted mb-0">
                            Ningún pedido esperando aprobación.
                        </p>
                        @endif

                    </div>
                </div>
            </div> -->


            {{--
                El agujero silencioso: reposición automática activada y sin
                proveedor. No falla, no avisa, y el producto se queda sin
                stock. Solo aparece cuando hay alguno.
            --}}
            @if ($gestion['sinProveedor']->isNotEmpty())
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card h-100 shadow-sm border-danger">
                    <div class="card-body">

                        <p class="small text-muted mb-1">
                            Sin proveedor
                        </p>

                        <p class="h3 fw-semibold mb-1 text-danger">
                            {{ $gestion['sinProveedor']->count() }}
                        </p>

                        <p class="small text-muted mb-0">
                            Tienen reposición automática y no se les puede emitir pedido.
                        </p>

                    </div>
                </div>
            </div>
            @endif

        </div>


        <div class="row g-3 mb-3 py-4">

            {{-- Bajo punto de reposición --}}
            <div class="col-12 col-lg-6">
                <div class="card h-100 shadow-sm">

                    <div class="card-header bg-white">

                        <h3 class="h6 mb-1">
                            Bajo el punto de reposición
                        </h3>

                        <p class="small text-muted mb-0">
                            Se compara contra el disponible, no contra lo que hay en depósito.
                        </p>

                    </div>

                    @if ($gestion['bajoPuntoReposicion']->isEmpty())

                    <div class="card-body">

                        <p class="fw-semibold mb-0">
                            Todo abastecido
                        </p>

                        <p class="text-muted small mb-0">
                            Ningún producto cayó por debajo de su punto de reposición.
                        </p>

                    </div>

                    @else

                    <ul class="list-group list-group-flush">

                        @foreach ($gestion['bajoPuntoReposicion'] as $producto)
                        <li class="list-group-item">

                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">

                                <a href="{{ route('gestion.stock.show', $producto) }}"
                                    class="text-dark text-decoration-underline">
                                    {{ $producto->nombre }}
                                </a>

                                {{--
                                    El estado del faltante. Los tres casos se
                                    ven igual en la columna de stock y no son
                                    lo mismo: uno ya está pedido, otro está por
                                    pedirse, y el tercero no se puede pedir.

                                    La orden abierta viene resuelta desde el
                                    controlador en una sola consulta: mirarla
                                    acá por producto serían veinte.
                                --}}
                                @php
                                $orden = $gestion['ordenesPorProducto']->get($producto->id);
                                @endphp

                                @if ($orden)
                                <a href="{{ route('gestion.compras.show', $orden) }}"
                                    class="badge text-bg-{{ $orden->estado->color() }} text-decoration-none">
                                    {{ $orden->numero }}
                                </a>
                                @elseif (! $producto->reposicion_automatica)
                                <span class="badge text-bg-light text-muted">
                                    Reposición manual
                                </span>
                                @elseif ($producto->proveedor_id === null)
                                <span class="badge text-bg-danger">
                                    Sin proveedor
                                </span>
                                @else
                                <span class="badge text-bg-secondary">
                                    Sin pedir
                                </span>
                                @endif

                            </div>

                            <div class="d-flex flex-wrap align-items-center gap-2 mt-1">

                                {{--
                                    Disponible en negativo = preventa: hay
                                    unidades vendidas y cobradas que todavía no
                                    están en el depósito. No es lo mismo que
                                    "queda poco", así que se marca distinto.
                                --}}
                                <span class="small">
                                    Disponible:
                                    <strong class="{{ $producto->stock_disponible < 0 ? 'text-danger' : 'text-warning' }}">
                                        {{ $producto->stock_disponible }}
                                    </strong>
                                    <span class="text-muted">
                                        / punto {{ $producto->punto_reposicion }}
                                    </span>
                                </span>

                                <span class="text-muted small">
                                    Físico: {{ $producto->stock_fisico }}
                                    · Comprometido: {{ $producto->stock_comprometido }}
                                </span>

                            </div>

                        </li>
                        @endforeach

                    </ul>

                    @endif

                </div>
            </div>


            {{-- Esperando reposición --}}
            <div class="col-12 col-lg-6">
                <div class="card h-100 shadow-sm">

                    <div class="card-header bg-white">

                        <h3 class="h6 mb-1">
                            Esperando reposición
                        </h3>

                        <p class="small text-muted mb-0">
                            Preventas facturadas. Se entregan por orden de antigüedad.
                        </p>

                    </div>

                    @if ($gestion['pendientesDeStock']->isEmpty())

                    <div class="card-body">

                        <p class="fw-semibold mb-0">
                            Ninguna venta está esperando mercadería
                        </p>

                    </div>

                    @else

                    <ul class="list-group list-group-flush">

                        @foreach ($gestion['pendientesDeStock'] as $venta)
                        <li class="list-group-item d-flex flex-wrap align-items-center gap-2">

                            <a href="{{ route('gestion.ventas.show', $venta) }}"
                                class="text-decoration-underline">
                                {{ $venta->numeroFormateado() }}
                            </a>

                            <span class="text-muted">
                                {{ $venta->cliente?->razon_social ?? 'Consumidor final' }}
                            </span>

                            <span class="text-muted small ms-lg-auto">
                                pagó el {{ $venta->fecha?->format('d/m') }}
                            </span>

                        </li>
                        @endforeach

                    </ul>

                    @endif

                </div>
            </div>

        </div>


        {{--
            Reposición automática sin proveedor.

            Va como lista aparte y no mezclado en el bloque de arriba porque el
            problema no es de stock: es de configuración del producto, y se
            arregla en otra pantalla.
        --}}
        @if ($gestion['sinProveedor']->isNotEmpty())
        <div class="card shadow-sm border-danger mb-3">

            <div class="card-header bg-white">

                <h3 class="h6 mb-1 text-danger">
                    Reposición automática sin proveedor
                </h3>

                <p class="small text-muted mb-0">
                    No generan ningún pedido y su faltante pasa desapercibido hasta que alguien lo note.
                </p>

            </div>

            <ul class="list-group list-group-flush">

                @foreach ($gestion['sinProveedor'] as $producto)
                <li class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-2">

                    <span>
                        <span class="text-muted small">
                            {{ $producto->codigo }}
                        </span>
                        {{ $producto->nombre }}
                    </span>

                    <a href="{{ route('gestion.items.edit', $producto) }}"
                        class="btn btn-sm btn-outline-danger">
                        Asignar proveedor
                    </a>

                </li>
                @endforeach

            </ul>

        </div>
        @endif


        {{-- Más vendidos --}}
        <div class="card shadow-sm">

            <div class="card-header bg-white">

                <h3 class="h6 mb-0">
                    Más vendidos del mes
                </h3>

            </div>

            @if ($gestion['masVendidos']->isEmpty())

            <div class="card-body">

                <p class="fw-semibold mb-0">
                    Todavía no hay ventas este mes
                </p>

            </div>

            @else

            <ul class="list-group list-group-flush">

                @foreach ($gestion['masVendidos'] as $fila)
                <li class="list-group-item d-flex justify-content-between align-items-center">

                    <span>
                        {{ $fila->producto?->nombre ?? 'Producto eliminado' }}
                    </span>

                    <span class="text-muted">
                        {{ $fila->unidades }} unidades
                    </span>

                </li>
                @endforeach

            </ul>

            @endif

        </div>

    </section>
    @endcan
    @endisset


</div>

@endsection