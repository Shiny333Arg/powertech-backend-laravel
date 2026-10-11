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

<section class="mb-4 d-flex flex-column flex-md-row align-items-stretch gap-3
    @can('create', App\Models\Venta::class)
        justify-content-between
    @else
        justify-content-end
    @endcan">

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

    <!-- PDF -->
    <button
        class="btn btn-outline-primary d-flex align-items-center justify-content-center gap-2 flex-fill flex-md-grow-0"
        id="btnExportarPDF"
        type="button">

        <svg xmlns="http://www.w3.org/2000/svg"
            width="18"
            height="18"
            fill="currentColor"
            class="bi bi-file-earmark-pdf"
            viewBox="0 0 16 16">

            <path
                d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2M9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5z" />

            <path
                d="M4.603 14.087a.8.8 0 0 1-.438-.42c-.195-.388-.13-.776.08-1.102.198-.307.526-.568.897-.787a7.7 7.7 0 0 1 1.482-.645 20 20 0 0 0 1.062-2.227 7.3 7.3 0 0 1-.43-1.295c-.086-.4-.119-.796-.046-1.136.075-.354.274-.672.65-.823.192-.077.4-.12.602-.077a.7.7 0 0 1 .477.365c.088.164.12.356.127.538.007.188-.012.396-.047.614-.084.51-.27 1.134-.52 1.794a11 11 0 0 0 .98 1.686 5.8 5.8 0 0 1 1.334.05c.364.066.734.195.96.465.12.144.193.32.2.518.007.192-.047.382-.138.563a1.04 1.04 0 0 1-.354.416.86.86 0 0 1-.51.138c-.331-.014-.654-.196-.933-.417a5.7 5.7 0 0 1-.911-.95 11.7 11.7 0 0 0-1.997.406 11.3 11.3 0 0 0-1.02 1.51c-.292.35-.609.656-.927.787a.8.8 0 0 1-.58.029m1.379-1.901q-.25.115-.459.238c-.328-.194-.541-.383-.647-.547-.094-.145-.096-.25-.04-.361q.016-.032.026-.044l.035-.012c.137-.056.355-.235.635-.572a8 8 0 0 0 .45-.606m1.64-1.33a13 13 0 0 1 1.01-.193 12 12 0 0 1-.51-.858 21 21 0 0 1-.5 1.05zm2.446.45q.226.245.435.41c.24.19.407.253.498.256a.1.1 0 0 0 .07-.015.3.3 0 0 0 .094-.125.44.44 0 0 0 .059-.2.1.1 0 0 0-.026-.063c-.052-.062-.2-.152-.518-.209a4 4 0 0 0-.612-.053zM8.078 7.8a7 7 0 0 0 .2-.828q.046-.282.038-.465a.6.6 0 0 0-.032-.198.5.5 0 0 0-.145.04c-.087.035-.158.106-.196.283-.04.192-.03.469.046.822q.036.167.09.346z" />
        </svg>

        Exportar listado a PDF
    </button>

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
                    @selected(($filtros['estado'] ?? null)===$opcion->value)>

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
                    @selected(($filtros['canal'] ?? null)===$opcion->value)>

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