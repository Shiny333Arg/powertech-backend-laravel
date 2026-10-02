@extends('layouts.gestion')

@section('title', 'Pedidos a proveedores')

@section('content')

@include('gestion.partials.alertas')

<!-- Título principal -->
<section class="mb-4 text-center">
    <h1 class="display-6 fw-bold">Pedidos a proveedores</h1>
    <p class="text-muted mb-0">
        Gestione los pedidos de compra realizados a los proveedores.
    </p>
</section>

<!-- Botones -->
<section class="mb-4 d-flex
    @can('create', App\Models\OrdenCompra::class)
        justify-content-between
    @else
        justify-content-end
    @endcan">

    @can('create', App\Models\OrdenCompra::class)

    <a href="{{ route('gestion.compras.create') }}"
        class="btn btn-success text-white d-flex align-items-center justify-content-center gap-2">

        <svg xmlns="http://www.w3.org/2000/svg"
            width="18"
            height="18"
            fill="currentColor"
            viewBox="0 0 16 16">

            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16" />

            <path
                d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4" />
        </svg>

        Nuevo pedido
    </a>

    @endcan

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

            <path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2M9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5z" />

            <path d="M4.603 14.087a.8.8 0 0 1-.438-.42c-.195-.388-.13-.776.08-1.102.198-.307.526-.568.897-.787a7.7 7.7 0 0 1 1.482-.645 20 20 0 0 0 1.062-2.227 7.3 7.3 0 0 1-.43-1.295c-.086-.4-.119-.796-.046-1.136.075-.354.274-.672.65-.823.192-.077.4-.12.602-.077a.7.7 0 0 1 .477.365c.088.164.12.356.127.538.007.188-.012.396-.047.614-.084.51-.27 1.134-.52 1.794a11 11 0 0 0 .98 1.686 5.8 5.8 0 0 1 1.334.05c.364.066.734.195.96.465.12.144.193.32.2.518.007.192-.047.382-.138.563a1.04 1.04 0 0 1-.354.416.86.86 0 0 1-.51.138c-.331-.014-.654-.196-.933-.417a5.7 5.7 0 0 1-.911-.95 11.7 11.7 0 0 0-1.997.406 11.3 11.3 0 0 0-1.02 1.51c-.292.35-.609.656-.927.787a.8.8 0 0 1-.58.029m1.379-1.901q-.25.115-.459.238c-.328.194-.541.383-.647.547-.094.145-.096.25-.04.361q.016.032.026.044l.035-.012c.137-.056.355-.235.635-.572a8 8 0 0 0 .45-.606m1.64-1.33a13 13 0 0 1 1.01-.193 12 12 0 0 1-.51-.858 21 21 0 0 1-.5 1.05zm2.446.45q.226.245.435.41c.24.19.407.253.498.256a.1.1 0 0 0 .07-.015.3.3 0 0 0 .094-.125.44.44 0 0 0 .059-.2.1.1 0 0 0-.026-.063c-.052-.062-.2-.152-.518-.209a4 4 0 0 0-.612-.053zM8.078 7.8a7 7 0 0 0 .2-.828q.046-.282.038-.465a.6.6 0 0 0-.032-.198.5.5 0 0 0-.145.04c-.087.035-.158.106-.196.283-.04.192-.03.469.046.822q.036.167.09.346z" />
        </svg>

        Exportar listado a PDF
    </button>
</section>

<!-- Filtros -->
<section class="mb-4">
    <form
        method="GET"
        action="{{ route('gestion.compras.index') }}"
        class="row g-3 align-items-end shadow-sm p-3 rounded bg-light"
        autocomplete="off">

        <!-- Búsqueda -->
        <div class="col-12 col-md-5 col-lg-5">
            <label for="q" class="form-label fw-semibold">
                Buscar por número o proveedor
            </label>

            <input
                type="search"
                id="q"
                name="q"
                value="{{ $filtros['q'] ?? '' }}"
                class="form-control"
                placeholder="Ej: OC-0001 o proveedor"
                maxlength="150">
        </div>

        <!-- Estado -->
        <div class="col-12 col-md-3 col-lg-3">
            <label for="estado" class="form-label fw-semibold">
                Estado
            </label>

            <select id="estado" name="estado" class="form-select">
                <option value="">Todos</option>

                @foreach ($estados as $estado)
                <option
                    value="{{ $estado->value }}"
                    @selected(($filtros['estado'] ?? '' )===$estado->value)>
                    {{ $estado->etiqueta() }}
                </option>
                @endforeach
            </select>
        </div>

        <!-- Origen -->
        <div class="col-12 col-md-3 col-lg-3">
            <label for="origen" class="form-label fw-semibold">
                Origen
            </label>

            <select id="origen" name="origen" class="form-select">
                <option value="">Todos</option>

                @foreach ($origenes as $origen)
                <option
                    value="{{ $origen->value }}"
                    @selected(($filtros['origen'] ?? '' )===$origen->value)>
                    {{ $origen->etiqueta() }}
                </option>
                @endforeach
            </select>
        </div>

        <!-- Botón filtrar -->
        <div class="col-12 col-md-1 d-flex">
            <button
                type="submit"
                class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2 fw-semibold">

                Filtrar

                <svg xmlns="http://www.w3.org/2000/svg"
                    width="16"
                    height="16"
                    fill="currentColor"
                    viewBox="0 0 16 16">

                    <path
                        d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.128.334L10 8.692V13.5a.5.5 0 0 1-.342.474l-3 1A.5.5 0 0 1 6 13.5V8.692L1.628 3.834A.5.5 0 0 1 1.5 3.5zm1 .5v1.308l4.372 4.858A.5.5 0 0 1 7 8.5v5.306l2-.666V8.5a.5.5 0 0 1 .128-.334L13.5 3.308V2z" />
                </svg>
            </button>
        </div>

    </form>
</section>

<!-- Tabla de pedidos -->
<section>

    @if ($ordenes->isEmpty())

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle text-center">

            <thead class="table-dark">
                <tr>
                    <th>Número</th>
                    <th>Proveedor</th>
                    <th>Estado</th>
                    <th>Origen</th>
                    <th>Renglones</th>
                    <th>Estimado</th>
                    <th>Emitido</th>
                    <th>Opciones</th>
                </tr>
            </thead>

            <tbody>
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">
                        No se encontraron pedidos.
                    </td>
                </tr>
            </tbody>

        </table>
    </div>

    @else

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle text-center">

            <thead class="table-dark">
                <tr>
                    <th class="text-nowrap">Número</th>
                    <th class="text-nowrap">Proveedor</th>
                    <th class="text-nowrap">Estado</th>
                    <th class="text-nowrap">Origen</th>
                    <th class="text-nowrap">Renglones</th>
                    <th class="text-nowrap">Estimado</th>
                    <th class="text-nowrap">Emitido</th>
                    <th class="text-nowrap">Opciones</th>
                </tr>
            </thead>

            <tbody>

                @foreach ($ordenes as $orden)

                <tr>

                    <td class="fw-semibold text-nowrap">
                        {{ $orden->numero }}
                    </td>

                    <td>
                        {{ $orden->proveedor->razon_social }}
                    </td>

                    <td>
                        <span class="badge text-bg-{{ $orden->estado->color() }}">
                            {{ $orden->estado->etiqueta() }}
                        </span>
                    </td>

                    <td>
                        <span class="badge text-bg-secondary">
                            {{ $orden->origen->etiqueta() }}
                        </span>
                    </td>

                    <td class="text-nowrap">
                        {{ $orden->lineas_count }}
                    </td>

                    <td class="text-nowrap">
                        $ {{ number_format((float) $orden->total_estimado, 2, ',', '.') }}
                    </td>

                    <td class="text-nowrap text-muted">
                        {{ $orden->created_at->format('d/m/Y') }}
                    </td>

                    <td class="text-nowrap">

                        <!-- Ver -->
                        <a
                            href="{{ route('gestion.compras.show', $orden) }}"
                            class="btn btn-sm btn-info text-white"
                            title="Ver pedido">

                            <svg xmlns="http://www.w3.org/2000/svg"
                                width="16"
                                height="16"
                                fill="currentColor"
                                viewBox="0 0 16 16">

                                <path
                                    d="M16 8s-3-5-8-5-8 5-8 5 3 5 8 5 8-5 8-5m-8 3a3 3 0 1 1 0-6 3 3 0 0 1 0 6" />

                                <path
                                    d="M8 10.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5" />
                            </svg>

                        </a>

                    </td>

                </tr>

                @endforeach

            </tbody>

        </table>
    </div>

    <!-- Paginación Laravel -->
    @if ($ordenes->hasPages())
    <div class="d-flex justify-content-center mt-3">
        {{ $ordenes->onEachSide(2)->links() }}
    </div>
    @endif

    @endif

</section>

@endsection