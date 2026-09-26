@extends('layouts.gestion')

@section('title', 'PowerTech')

@section('content')

<!-- Título principal -->

<section class="mb-4 text-center">
    <h1 class="display-6 fw-bold">Gestión de ventas</h1>
    <p class="text-muted mb-0">Presupuestos y ventas del mostrador y de la tienda.</p>
</section>


@include('gestion.partials.alertas')


<!-- Botones -->

<section class="mb-4 d-flex flex-column flex-md-row justify-content-between align-items-stretch gap-3">

    @can('create', App\Models\Venta::class)

    <a href="{{ route('gestion.ventas.create') }}"
        class="btn btn-success text-white d-flex align-items-center justify-content-center gap-2 flex-fill flex-md-grow-0">

        <svg xmlns="http://www.w3.org/2000/svg"
            width="18"
            height="18"
            fill="currentColor"
            class="bi bi-plus-circle"
            viewBox="0 0 16 16">

            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16" />

            <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4" />
        </svg>

        Registrar nueva venta
    </a>

    @endcan

</section>


<!-- Filtros -->

<section class="mb-4">

    <form
        action="{{ route('gestion.ventas.index') }}"
        method="GET"
        class="row g-3 align-items-end shadow-sm p-3 rounded bg-light"
        autocomplete="off"
        id="formFiltros">

        <!-- Número interno -->
        <div class="col-12 col-md-4 col-lg-2">

            <label for="numero" class="form-label fw-semibold">
                Número
            </label>

            <input
                type="number"
                class="form-control"
                id="numero"
                name="numero"
                value="{{ $filtros['numero'] ?? '' }}"
                placeholder="Ej: 1204"
                min="1">

        </div>


        <!-- Estado -->
        <div class="col-12 col-md-4 col-lg-3">

            <label for="estado" class="form-label fw-semibold">
                Estado
            </label>

            <select id="estado" name="estado" class="form-select">

                <option value="">Todos</option>

                @foreach ($estados as $opcion)

                <option
                    value="{{ $opcion->value }}"
                    @selected(($filtros['estado'] ?? null) === $opcion->value)>

                    {{ method_exists($opcion, 'etiqueta') ? $opcion->etiqueta() : ucfirst(str_replace('_', ' ', $opcion->value)) }}

                </option>

                @endforeach

            </select>

        </div>


        <!-- Canal -->
        <div class="col-12 col-md-4 col-lg-2">

            <label for="canal" class="form-label fw-semibold">
                Canal
            </label>

            <select id="canal" name="canal" class="form-select">

                <option value="">Todos</option>

                @foreach ($canales as $opcion)

                <option
                    value="{{ $opcion->value }}"
                    @selected(($filtros['canal'] ?? null) === $opcion->value)>

                    {{ ucfirst($opcion->value) }}

                </option>

                @endforeach

            </select>

        </div>


        <!-- Desde -->
        <div class="col-6 col-md-4 col-lg-2">

            <label for="desde" class="form-label fw-semibold">
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
        <div class="col-6 col-md-4 col-lg-2">

            <label for="hasta" class="form-label fw-semibold">
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
        <div class="col-12 col-lg-1 d-flex">

            <button
                type="submit"
                class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2 fw-semibold">

                Filtrar

                <svg xmlns="http://www.w3.org/2000/svg"
                    width="16"
                    height="16"
                    fill="currentColor"
                    class="bi bi-funnel"
                    viewBox="0 0 16 16">

                    <path d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.128.334L10 8.692V13.5a.5.5 0 0 1-.342.474l-3 1A.5.5 0 0 1 6 14.5V8.692L1.628 3.834A.5.5 0 0 1 1.5 3.5zm1 .5v1.308l4.372 4.858A.5.5 0 0 1 7 8.5v5.306l2-.666V8.5a.5.5 0 0 1 .128-.334L13.5 3.308V2z" />

                </svg>

            </button>

        </div>

    </form>

</section>


<!-- Tabla de ventas -->

<section>

    <div class="table-responsive">

        <table
            class="table table-bordered table-hover align-middle text-center"
            id="tablaVentas">

            <thead class="table-dark">

                <tr>
                    <th class="text-nowrap" id="columnaNumero">Número</th>
                    <th class="text-nowrap" id="columnaFecha">Fecha</th>
                    <th class="text-nowrap" id="columnaCliente">Cliente</th>
                    <th class="text-nowrap" id="columnaVendedor">Vendedor</th>
                    <th class="text-nowrap" id="columnaEstado">Estado</th>
                    <th class="text-nowrap" id="columnaItems">Ítems</th>
                    <th class="text-nowrap" id="columnaTotal">Total</th>
                    <th class="text-nowrap" id="columnaOpciones">Opciones</th>
                </tr>

            </thead>


            <tbody id="contenidoTablaVentas">

                @forelse ($ventas as $venta)

                <tr>

                    <td class="text-nowrap fw-semibold">
                        {{ $venta->numeroFormateado() }}
                    </td>

                    <td class="text-nowrap">
                        {{ $venta->fecha?->format('d/m/Y H:i') }}
                    </td>

                    <td>
                        {{ $venta->cliente?->razon_social ?? 'Consumidor final' }}
                    </td>

                    <td>
                        {{ $venta->usuario?->nombre_completo ?? '—' }}
                    </td>

                    <td>

                        @include('gestion.partials.estado', ['estado' => $venta->estado])

                        @if ($venta->canal->value === 'online')

                        <span class="badge text-bg-light border mt-1">
                            Online
                        </span>

                        @endif

                    </td>

                    <td>
                        {{ $venta->lineas_count }}
                    </td>

                    <td class="text-nowrap fw-semibold">
                        $ {{ number_format($venta->total, 2, ',', '.') }}
                    </td>

                    <td class="text-nowrap">

                        <!-- Ver -->
                        <a
                            href="{{ route('gestion.ventas.show', $venta) }}"
                            class="btn btn-sm btn-primary me-1"
                            title="Ver la venta">

                            <svg xmlns="http://www.w3.org/2000/svg"
                                width="16"
                                height="16"
                                fill="currentColor"
                                class="bi bi-eye"
                                viewBox="0 0 16 16">

                                <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z" />

                                <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0" />
                            </svg>

                        </a>


                        @can('update', $venta)

                        <!-- Editar (solo presupuestos) -->
                        <a
                            href="{{ route('gestion.ventas.edit', $venta) }}"
                            class="btn btn-sm btn-warning me-1"
                            title="Editar el presupuesto">

                            <svg xmlns="http://www.w3.org/2000/svg"
                                width="16"
                                height="16"
                                fill="currentColor"
                                class="bi bi-pencil"
                                viewBox="0 0 16 16">

                                <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325" />
                            </svg>

                        </a>

                        @endcan


                        @can('cobrar', $venta)

                        <!-- Cobrar -->
                        <a
                            href="{{ route('gestion.cobros.create', $venta) }}"
                            class="btn btn-sm btn-success"
                            title="Cobrar">

                            <svg xmlns="http://www.w3.org/2000/svg"
                                width="16"
                                height="16"
                                fill="currentColor"
                                class="bi bi-cash"
                                viewBox="0 0 16 16">

                                <path d="M8 10a2 2 0 1 0 0-4 2 2 0 0 0 0 4" />

                                <path d="M0 4a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1zm3 0a2 2 0 0 1-2 2v4a2 2 0 0 1 2 2h10a2 2 0 0 1 2-2V6a2 2 0 0 1-2-2z" />
                            </svg>

                        </a>

                        @endcan

                    </td>

                </tr>

                @empty

                <tr>

                    <td
                        colspan="8"
                        class="text-center py-4 text-muted">

                        No se encontraron ventas con esos filtros.

                    </td>

                </tr>

                @endforelse

            </tbody>

        </table>

    </div>


    <!-- Paginación Laravel -->
    @if ($ventas->hasPages())

    <div class="d-flex justify-content-center mt-3">

        {{ $ventas->onEachSide(2)->links() }}

    </div>

    @endif

</section>

@endsection
