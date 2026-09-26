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

                <option value="{{ $opcion->value }}" @selected(($filtros['tipo'] ?? null) === $opcion->value)>
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
                    <th class="text-nowrap">Δ físico</th>
                    <th class="text-nowrap">Δ comprometido</th>
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
                        <span class="badge text-bg-light border">
                            {{ ucfirst(str_replace('_', ' ', $movimiento->tipo->value)) }}
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
