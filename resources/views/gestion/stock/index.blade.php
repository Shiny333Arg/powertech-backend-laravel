@extends('layouts.gestion')

@section('title', 'PowerTech')

@section('content')

<!-- Título principal -->

<section class="mb-4 text-center">
    <h1 class="display-6 fw-bold">Movimientos de stock</h1>
    <p class="text-muted mb-0">
        Todo cambio de stock deja un movimiento. Un error no se edita:
        se corrige con un movimiento inverso.
    </p>
</section>


<!-- Botones -->

<section class="mb-4 d-flex flex-column flex-md-row align-items-stretch gap-3 justify-content-end">

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


@include('gestion.partials.alertas')


<!-- Filtros -->

<section class="mb-4">

    <form
        action="{{ route('gestion.stock.index') }}"
        method="GET"
        class="row g-3 align-items-end shadow-sm p-3 rounded bg-light"
        autocomplete="off">

        <div class="col-12 col-md-3">

            <label for="producto_id" class="form-label fw-semibold">Producto (id)</label>

            <input
                type="number"
                class="form-control"
                id="producto_id"
                name="producto_id"
                value="{{ $filtros['producto_id'] ?? '' }}"
                min="1">

        </div>

        <div class="col-12 col-md-3">

            <label for="tipo" class="form-label fw-semibold">Tipo de movimiento</label>

            <select id="tipo" name="tipo" class="form-select">

                <option value="">Todos</option>

                @foreach ($tipos as $opcion)

                <option value="{{ $opcion->value }}" @selected(($filtros['tipo'] ?? null)===$opcion->value)>
                    {{ ucfirst(str_replace('_', ' ', $opcion->value)) }}
                </option>

                @endforeach

            </select>

        </div>

        <div class="col-6 col-md-2">

            <label for="desde" class="form-label fw-semibold">Desde</label>

            <input type="date" class="form-control" id="desde" name="desde" value="{{ $filtros['desde'] ?? '' }}">

        </div>

        <div class="col-6 col-md-2">

            <label for="hasta" class="form-label fw-semibold">Hasta</label>

            <input type="date" class="form-control" id="hasta" name="hasta" value="{{ $filtros['hasta'] ?? '' }}">

        </div>

        <div class="col-12 col-md-2 d-flex">

            <button type="submit" class="btn btn-primary w-100 fw-semibold">
                Filtrar
            </button>

        </div>

    </form>

</section>


<!-- Tabla -->

<section>

    <div class="table-responsive">

        <table class="table table-bordered table-hover align-middle text-center" id="tablaMovimientos">

            <thead class="table-dark">

                <tr>
                    <th class="text-nowrap">Fecha</th>
                    <th class="text-nowrap">Producto</th>
                    <th class="text-nowrap">Tipo</th>
                    <th class="text-nowrap">Físico</th>
                    <th class="text-nowrap">Comprometido</th>
                    <th class="text-nowrap">Saldo</th>
                    <th class="text-nowrap">Responsable</th>
                    <th class="text-nowrap">Motivo</th>
                </tr>

            </thead>

            <tbody>

                @forelse ($movimientos as $movimiento)

                <tr>

                    <td class="text-nowrap">
                        {{ $movimiento->created_at?->format('d/m/Y H:i') }}
                    </td>

                    <td class="text-start">

                        <a href="{{ route('gestion.stock.show', $movimiento->producto_id) }}">
                            {{ $movimiento->producto?->nombre }}
                        </a>

                        <div class="text-muted small">{{ $movimiento->producto?->codigo }}</div>

                    </td>

                    <td>
                        @php
                        $claseTipo = match ($movimiento->tipo) {
                        \App\Enums\TipoMovimientoStock::Inicial => 'text-bg-primary',
                        \App\Enums\TipoMovimientoStock::Compra => 'text-bg-success',
                        \App\Enums\TipoMovimientoStock::Reserva => 'text-bg-warning',
                        \App\Enums\TipoMovimientoStock::Entrega => 'text-bg-danger',
                        \App\Enums\TipoMovimientoStock::Liberacion => 'text-bg-info',
                        \App\Enums\TipoMovimientoStock::Devolucion => 'text-bg-secondary',
                        \App\Enums\TipoMovimientoStock::Ajuste => 'text-bg-dark',
                        };
                        @endphp

                        <span class="badge {{ $claseTipo }}">
                            {{ $movimiento->tipo->etiqueta() }}
                        </span>
                    </td>

                    <td class="fw-semibold {{ $movimiento->cantidad_fisico < 0 ? 'text-danger' : ($movimiento->cantidad_fisico > 0 ? 'text-success' : 'text-muted') }}">
                        {{ $movimiento->cantidad_fisico > 0 ? '+' : '' }}{{ $movimiento->cantidad_fisico }}
                    </td>

                    <td class="fw-semibold {{ $movimiento->cantidad_comprometido < 0 ? 'text-danger' : ($movimiento->cantidad_comprometido > 0 ? 'text-warning-emphasis' : 'text-muted') }}">
                        {{ $movimiento->cantidad_comprometido > 0 ? '+' : '' }}{{ $movimiento->cantidad_comprometido }}
                    </td>

                    <td class="text-nowrap">
                        {{ $movimiento->stock_fisico_resultante }}
                        <span class="text-muted">/</span>
                        {{ $movimiento->stock_comprometido_resultante }}
                    </td>

                    <td>
                        {{ $movimiento->usuario?->nombre_completo ?? 'Sistema' }}
                    </td>

                    <td class="text-start">
                        {{ $movimiento->motivo ?: '—' }}
                    </td>

                </tr>

                @empty

                <tr>

                    <td colspan="8" class="text-center py-4 text-muted">
                        No hay movimientos en ese corte. Si el sistema recién arranca,
                        el primer movimiento de cada producto es la carga del conteo inicial.
                    </td>

                </tr>

                @endforelse

            </tbody>

        </table>

    </div>


    <!-- Paginación Laravel -->
    @if ($movimientos->hasPages())

    <div class="d-flex justify-content-center mt-3">

        {{ $movimientos->onEachSide(2)->links() }}

    </div>

    @endif


    <p class="text-muted small mt-3">
        El saldo se lee como físico / comprometido. El disponible es la resta de los
        dos y es el único que puede ser negativo.
    </p>

</section>

@endsection