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


    {{-- Avisos --}}
    @if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}

        <button type="button"
            class="btn-close"
            data-bs-dismiss="alert"
            aria-label="Cerrar">
        </button>
    </div>
    @endif

    @if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}

        <button type="button"
            class="btn-close"
            data-bs-dismiss="alert"
            aria-label="Cerrar">
        </button>
    </div>
    @endif

    @if (session('warning'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        {{ session('warning') }}

        <button type="button"
            class="btn-close"
            data-bs-dismiss="alert"
            aria-label="Cerrar">
        </button>
    </div>
    @endif

    @if (session('info'))
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        {{ session('info') }}

        <button type="button"
            class="btn-close"
            data-bs-dismiss="alert"
            aria-label="Cerrar">
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
                        <a href="{{ route('gestion.ventas.show', $pago->venta_id) }}"
                            class="text-decoration-underline">
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

                            <a href="{{ route('gestion.ventas.show', $venta) }}"
                                class="text-decoration-underline">
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
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card h-100 shadow-sm
                    {{ $gestion['comprobantesConProblema'] > 0 ? 'border-danger' : '' }}">

                    <div class="card-body">

                        <p class="small text-muted mb-1">
                            Comprobantes sin CAE
                        </p>

                        <p class="h3 fw-semibold mb-1
                            {{ $gestion['comprobantesConProblema'] > 0 ? 'text-danger' : '' }}">
                            {{ $gestion['comprobantesConProblema'] }}
                        </p>

                        @if ($gestion['comprobantesConProblema'] > 0)
                        <a href="{{ route('gestion.comprobantes.index', ['estado' => 'rechazado']) }}"
                            class="small text-danger">
                            Revisar
                        </a>
                        @endif

                    </div>
                </div>
            </div>

        </div>


        <div class="row g-3 mb-3">

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
                        <li class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-2">

                            <a href="{{ route('gestion.stock.show', $producto) }}"
                                class="text-dark text-decoration-underline">
                                {{ $producto->nombre }}
                            </a>

                            <span class="small">
                                Disponible:
                                <strong>
                                    {{ $producto->stock_disponible }}
                                </strong>
                            </span>

                            <span class="text-muted small">
                                Físico: {{ $producto->stock_fisico }}
                                · Comprometido: {{ $producto->stock_comprometido }}
                            </span>

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
    @endisset


</div>

@endsection