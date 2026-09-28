@extends('layouts.gestion')

@section('title', 'PowerTech')

@vite('resources/css/gestion/item/movimientos.css')

@section('content')

@php
// Etiqueta y color de cada tipo de movimiento.
// Los valores son los del ENUM de movimientos_stock.
$tipos = [
    'inicial' => [
        'label' => 'Carga inicial',
        'clase' => 'text-bg-secondary'
    ],
    'compra' => [
        'label' => 'Recepción de compra',
        'clase' => 'text-bg-success'
    ],
    'reserva' => [
        'label' => 'Reserva',
        'clase' => 'text-bg-warning'
    ],
    'entrega' => [
        'label' => 'Entrega',
        'clase' => 'text-bg-primary'
    ],
    'liberacion' => [
        'label' => 'Liberación',
        'clase' => 'text-bg-info'
    ],
    'devolucion' => [
        'label' => 'Devolución',
        'clase' => 'text-bg-success'
    ],
    'ajuste' => [
        'label' => 'Ajuste',
        'clase' => 'text-bg-dark'
    ],
];
@endphp


<!-- Botones -->

<section class="mb-4 d-flex flex-column flex-md-row align-items-stretch justify-content-between gap-3">

    <a
        href="{{ route('gestion.items.index') }}"
        class="btn btn-outline-secondary d-flex align-items-center justify-content-center gap-2 flex-fill flex-md-grow-0">

        <svg
            xmlns="http://www.w3.org/2000/svg"
            width="18"
            height="18"
            fill="currentColor"
            class="bi bi-arrow-left"
            viewBox="0 0 16 16">

            <path
                fill-rule="evenodd"
                d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8" />

        </svg>

        Volver al listado

    </a>


    <a
        href="{{ route('gestion.items.edit', $producto) }}"
        class="btn btn-warning d-flex align-items-center justify-content-center gap-2 flex-fill flex-md-grow-0">

        <svg
            xmlns="http://www.w3.org/2000/svg"
            width="18"
            height="18"
            fill="currentColor"
            class="bi bi-pencil"
            viewBox="0 0 16 16">

            <path
                d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325" />

        </svg>

        Editar producto

    </a>

</section>


<!-- Ficha del producto -->

<section class="mb-4">

    <div class="card shadow-sm">

        <div class="card-body">

            <div class="row g-3 align-items-center">

                <!-- Foto -->

                <div class="col-4 col-md-2 col-lg-1 mx-auto mx-md-0">

                    @if ($producto->imagen)

                    <img
                        src="{{ asset('storage/'.$producto->imagen) }}"
                        alt="{{ $producto->nombre }}"
                        class="w-100"
                        style="object-fit: contain; max-height: 90px;"
                        loading="lazy">

                    @else

                    <img
                        src="{{ asset('images/Sin_Imagen.png') }}"
                        alt="Sin imagen"
                        class="w-100"
                        style="object-fit: contain; max-height: 90px;"
                        loading="lazy">

                    @endif

                </div>


                <!-- Identificación -->

                <div class="col-12 col-md-6 col-lg-7 text-center text-md-start">

                    <h2 class="h4 fw-bold mb-1">
                        {{ $producto->nombre }}
                    </h2>

                    <p class="text-muted mb-2">
                        Código
                        <span class="fw-semibold">
                            {{ $producto->codigo }}
                        </span>

                        &middot;

                        {{ $producto->marca ?: 'Sin marca' }}

                        &middot;

                        {{ $producto->categoria?->nombre ?? 'Sin categoría' }}
                    </p>


                    @if ($producto->activo)

                    <span class="badge text-bg-success">
                        Activo
                    </span>

                    @else

                    <span class="badge text-bg-secondary">
                        Inactivo
                    </span>

                    @endif


                    @if ($producto->publicado_app)

                    <span class="badge text-bg-primary">
                        Publicado
                    </span>

                    @endif

                </div>


                <!-- Reposición -->

                <div class="col-12 col-md-4 col-lg-4 text-center text-md-end">

                    <p class="text-muted mb-1">
                        Punto de reposición:
                        <span class="fw-semibold">
                            {{ $producto->punto_reposicion }}
                        </span>
                    </p>

                    <p class="text-muted mb-0">
                        Cantidad de reposición:
                        <span class="fw-semibold">
                            {{ $producto->cantidad_reposicion }}
                        </span>
                    </p>


                    @if ($producto->stock_disponible < $producto->punto_reposicion)

                    <span class="badge text-bg-warning mt-2">
                        Bajo el punto de reposición
                    </span>

                    @endif

                </div>

            </div>

        </div>

    </div>

</section>


<!-- Saldos actuales -->

<section class="mb-4">

    <div class="row g-3">

        <!-- Físico -->

        <div class="col-12 col-md-4">

            <div class="card shadow-sm h-100 text-center">

                <div class="card-body">

                    <p class="text-muted text-uppercase small fw-semibold mb-1">
                        Stock físico
                    </p>

                    <p class="display-6 fw-bold mb-1">
                        {{ $producto->stock_fisico }}
                    </p>

                    <p class="text-muted small mb-0">
                        Unidades en el depósito.
                    </p>

                </div>

            </div>

        </div>


        <!-- Comprometido -->

        <div class="col-12 col-md-4">

            <div class="card shadow-sm h-100 text-center">

                <div class="card-body">

                    <p class="text-muted text-uppercase small fw-semibold mb-1">
                        Stock comprometido
                    </p>

                    <p class="display-6 fw-bold mb-1">
                        {{ $producto->stock_comprometido }}
                    </p>

                    <p class="text-muted small mb-0">
                        Vendidas, pendientes de entrega.
                    </p>

                </div>

            </div>

        </div>


        <!-- Disponible -->

        <div class="col-12 col-md-4">

            <div class="card shadow-sm h-100 text-center">

                <div class="card-body">

                    <p class="text-muted text-uppercase small fw-semibold mb-1">
                        Stock disponible
                    </p>

                    <p class="display-6 fw-bold mb-1">

                        @if ($producto->stock_disponible < 0)

                        <span class="badge text-bg-danger">
                            {{ $producto->stock_disponible }}
                        </span>

                        @elseif ($producto->stock_disponible == 0)

                        <span class="badge text-bg-warning">
                            {{ $producto->stock_disponible }}
                        </span>

                        @else

                        <span class="badge text-bg-success">
                            {{ $producto->stock_disponible }}
                        </span>

                        @endif

                    </p>


                    <p class="text-muted small mb-0">

                        @if ($producto->stock_disponible < 0)

                        En preventa:
                        {{ abs($producto->stock_disponible) }}
                        unidades adeudadas.

                        @else

                        Físico menos comprometido.

                        @endif

                    </p>

                </div>

            </div>

        </div>

    </div>

</section>





<!-- Filtros -->

<section class="mb-4">

    <form
        action="{{ route('gestion.items.movimientos', $producto) }}"
        method="GET"
        class="row g-3 align-items-end shadow-sm p-3 rounded bg-light"
        autocomplete="off"
        id="formFiltros">

        <!-- Filtro por tipo de movimiento -->

        <div class="col-12 col-md-4 col-lg-3">

            <label
                for="tipo"
                class="form-label fw-semibold">

                Tipo de movimiento

            </label>


            <select
                id="tipo"
                name="tipo"
                class="form-select">

                <option value="">
                    Todos
                </option>


                @foreach ($tipos as $clave => $tipo)

                <option
                    value="{{ $clave }}"
                    @selected(($filtros['tipo'] ?? null) === $clave)>

                    {{ $tipo['label'] }}

                </option>

                @endforeach

            </select>

        </div>


        <!-- Desde -->

        <div class="col-12 col-md-4 col-lg-3">

            <label
                for="desde"
                class="form-label fw-semibold">

                Desde

            </label>


            <input
                type="date"
                class="form-control"
                id="desde"
                name="desde"
                value="{{ $filtros['desde'] ?? '' }}">

        </div>


        <!-- Hasta -->

        <div class="col-12 col-md-4 col-lg-3">

            <label
                for="hasta"
                class="form-label fw-semibold">

                Hasta

            </label>


            <input
                type="date"
                class="form-control"
                id="hasta"
                name="hasta"
                value="{{ $filtros['hasta'] ?? '' }}">

        </div>


        <!-- Botón filtrar -->

        <div class="col-12 col-md-6 col-lg-2 d-flex">

            <button
                type="submit"
                class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2 fw-semibold">

                Filtrar

                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="16"
                    height="16"
                    fill="currentColor"
                    class="bi bi-funnel"
                    viewBox="0 0 16 16">

                    <path
                        d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.128.334L10 8.692V13.5a.5.5 0 0 1-.342.474l-3 1A.5.5 0 0 1 6 14.5V8.692L1.628 3.834A.5.5 0 0 1 1.5 3.5zm1 .5v1.308l4.372 4.858A.5.5 0 0 1 7 8.5v5.306l2-.666V8.5a.5.5 0 0 1 .128-.334L13.5 3.308V2z" />

                </svg>

            </button>

        </div>

    </form>

</section>


<!-- Tabla de movimientos -->

<section>

    <div class="table-responsive">

        <table
            class="table table-bordered table-hover align-middle text-center"
            id="tablaMovimientos">

            <thead class="table-dark">

                <tr>

                    <th
                        class="text-nowrap"
                        id="columnaFecha">

                        Fecha

                    </th>

                    <th
                        class="text-nowrap"
                        id="columnaTipo">

                        Tipo

                    </th>

                    <th
                        class="text-nowrap"
                        id="columnaDeltaFisico">

                        Δ Físico

                    </th>

                    <th
                        class="text-nowrap"
                        id="columnaDeltaComprometido">

                        Δ Comprometido

                    </th>

                    <th
                        class="text-nowrap"
                        id="columnaSaldoFisico">

                        Físico

                    </th>

                    <th
                        class="text-nowrap"
                        id="columnaSaldoComprometido">

                        Comprometido

                    </th>

                    <th
                        class="text-nowrap"
                        id="columnaSaldoDisponible">

                        Disponible

                    </th>

                    <th
                        class="text-nowrap"
                        id="columnaOrigen">

                        Origen

                    </th>

                    <th
                        class="text-nowrap"
                        id="columnaUsuario">

                        Responsable

                    </th>

                    <th
                        class="text-nowrap"
                        id="columnaMotivo">

                        Motivo

                    </th>

                </tr>

            </thead>


            <tbody id="contenidoTablaMovimientos">

                @forelse ($movimientos as $movimiento)

                @php

                $disponible =
                    $movimiento->stock_fisico_resultante
                    - $movimiento->stock_comprometido_resultante;

                $origen =
                    $movimiento->origen_type
                    ? class_basename($movimiento->origen_type)
                    : null;

                $tipoMovimiento =
                    $movimiento->tipo instanceof \BackedEnum
                    ? $movimiento->tipo->value
                    : $movimiento->tipo;

                @endphp


                <tr>

                    <!-- Fecha -->

                    <td class="text-nowrap">

                        {{ $movimiento->created_at->format('d/m/Y H:i') }}

                    </td>


                    <!-- Tipo -->

                    <td class="text-nowrap">

                        <span
                            class="badge {{ $tipos[$tipoMovimiento]['clase'] ?? 'text-bg-secondary' }}">

                            {{ $tipos[$tipoMovimiento]['label'] ?? $tipoMovimiento }}

                        </span>

                    </td>


                    <!-- Delta físico -->

                    <td class="text-nowrap">

                        @if ($movimiento->cantidad_fisico > 0)

                        <span class="text-success fw-semibold">

                            +{{ $movimiento->cantidad_fisico }}

                        </span>

                        @elseif ($movimiento->cantidad_fisico < 0)

                        <span class="text-danger fw-semibold">

                            {{ $movimiento->cantidad_fisico }}

                        </span>

                        @else

                        <span class="text-muted">
                            —
                        </span>

                        @endif

                    </td>


                    <!-- Delta comprometido -->

                    <td class="text-nowrap">

                        @if ($movimiento->cantidad_comprometido > 0)

                        <span class="text-success fw-semibold">

                            +{{ $movimiento->cantidad_comprometido }}

                        </span>

                        @elseif ($movimiento->cantidad_comprometido < 0)

                        <span class="text-danger fw-semibold">

                            {{ $movimiento->cantidad_comprometido }}

                        </span>

                        @else

                        <span class="text-muted">
                            —
                        </span>

                        @endif

                    </td>


                    <!-- Stock físico -->

                    <td>

                        {{ $movimiento->stock_fisico_resultante }}

                    </td>


                    <!-- Stock comprometido -->

                    <td>

                        {{ $movimiento->stock_comprometido_resultante }}

                    </td>


                    <!-- Stock disponible -->

                    <td>

                        @if ($disponible < 0)

                        <span class="badge text-bg-danger">

                            {{ $disponible }}

                        </span>

                        @elseif ($disponible == 0)

                        <span class="badge text-bg-warning">

                            {{ $disponible }}

                        </span>

                        @else

                        <span class="badge text-bg-success">

                            {{ $disponible }}

                        </span>

                        @endif

                    </td>


                    <!-- Origen -->

                    <td class="text-nowrap">

                        @if ($origen === 'Venta' && $movimiento->origen_id)

                        <a
                            href="{{ route('gestion.ventas.show', ['venta' => $movimiento->origen_id]) }}">

                            Venta #{{ $movimiento->origen_id }}

                        </a>


                        @elseif ($origen === 'Venta')

                        <span class="text-muted">

                            Venta sin referencia

                        </span>


                        @elseif ($origen === 'Comprobante' && $movimiento->origen_id)

                        <a
                            href="{{ route('gestion.comprobantes.show', ['comprobante' => $movimiento->origen_id]) }}">

                            Comprobante #{{ $movimiento->origen_id }}

                        </a>


                        @elseif ($origen === 'Comprobante')

                        <span class="text-muted">

                            Comprobante sin referencia

                        </span>


                        @elseif ($origen === 'OrdenCompra' && $movimiento->origen_id)

                        <span>

                            Orden de compra #{{ $movimiento->origen_id }}

                        </span>


                        @elseif ($origen === 'OrdenCompra')

                        <span class="text-muted">

                            Orden de compra sin referencia

                        </span>


                        @elseif ($origen && $movimiento->origen_id)

                        <span>

                            {{ $origen }} #{{ $movimiento->origen_id }}

                        </span>


                        @elseif ($origen)

                        <span class="text-muted">

                            {{ $origen }} sin referencia

                        </span>


                        @else

                        <span class="text-muted">

                            Manual

                        </span>

                        @endif

                    </td>


                    <!-- Responsable -->

                    <td class="text-nowrap">

                        @if ($movimiento->usuario)

                        {{ $movimiento->usuario->nombres }}
                        {{ $movimiento->usuario->apellido }}

                        @else

                        <span class="text-muted">

                            Sistema

                        </span>

                        @endif

                    </td>


                    <!-- Motivo -->

                    <td class="text-start">

                        {{ $movimiento->motivo ?: '—' }}

                    </td>

                </tr>


                @empty

                <tr>

                    <td
                        colspan="10"
                        class="text-center py-4 text-muted">

                        No se registraron movimientos de stock para este producto.

                    </td>

                </tr>

                @endforelse

            </tbody>

        </table>

    </div>


    <!-- Paginación Laravel -->

    @if ($movimientos->hasPages())

    <div class="d-flex justify-content-center mt-3">

        {{ $movimientos->withQueryString()->onEachSide(2)->links() }}

    </div>

    @endif

</section>

@endsection