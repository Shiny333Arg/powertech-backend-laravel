@extends('layouts.gestion')

@section('title', 'PowerTech')

@section('content')

<!-- Título principal -->

<section class="mb-4 text-center">
    <h1 class="display-6 fw-bold">Cobros</h1>
    <p class="text-muted mb-0">Arqueo por medio de pago e historial.</p>
</section>


@include('gestion.partials.alertas')


<!-- Botones -->

<section class="mb-4 d-flex flex-column flex-md-row justify-content-between align-items-stretch gap-3">

    <a href="{{ route('gestion.cobros.pendientes') }}"
        class="btn btn-success text-white d-flex align-items-center justify-content-center gap-2 flex-fill flex-md-grow-0">

        <svg xmlns="http://www.w3.org/2000/svg"
            width="18"
            height="18"
            fill="currentColor"
            class="bi bi-cash"
            viewBox="0 0 16 16">

            <path d="M8 10a2 2 0 1 0 0-4 2 2 0 0 0 0 4" />

            <path d="M0 4a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1zm3 0a2 2 0 0 1-2 2v4a2 2 0 0 1 2 2h10a2 2 0 0 1 2-2V6a2 2 0 0 1-2-2z" />
        </svg>

        Ir a la cola de cobro
    </a>

</section>


<!-- Filtros -->

<section class="mb-4">

    <form
        action="{{ route('gestion.cobros.index') }}"
        method="GET"
        class="row g-3 align-items-end shadow-sm p-3 rounded bg-light"
        autocomplete="off">

        <div class="col-6 col-md-3">

            <label for="desde" class="form-label fw-semibold">Desde</label>

            <input
                type="date"
                class="form-control"
                id="desde"
                name="desde"
                value="{{ $filtros['desde'] ?? now()->toDateString() }}">

        </div>

        <div class="col-6 col-md-3">

            <label for="hasta" class="form-label fw-semibold">Hasta</label>

            <input
                type="date"
                class="form-control"
                id="hasta"
                name="hasta"
                value="{{ $filtros['hasta'] ?? now()->toDateString() }}">

        </div>

        <div class="col-12 col-md-4">

            <label for="metodo" class="form-label fw-semibold">Medio de pago</label>

            <select id="metodo" name="metodo" class="form-select">

                <option value="">Todos</option>

                @foreach (['efectivo', 'tarjeta', 'transferencia'] as $opcion)

                <option value="{{ $opcion }}" @selected(($filtros['metodo'] ?? null) === $opcion)>
                    {{ ucfirst($opcion) }}
                </option>

                @endforeach

            </select>

        </div>

        <div class="col-12 col-md-2 d-flex">

            <button type="submit" class="btn btn-primary w-100 fw-semibold">
                Filtrar
            </button>

        </div>

    </form>

</section>


<!-- Arqueo -->

@if ($totalPorMetodo->isNotEmpty())

<section class="mb-4">

    <div class="row g-3">

        @foreach ($totalPorMetodo as $fila)

        <div class="col-12 col-md-4">

            <div class="p-4 rounded-4 bg-white shadow-sm border h-100">

                <div class="text-muted">
                    {{ ucfirst(is_object($fila->metodo) ? $fila->metodo->value : $fila->metodo) }}
                </div>

                <div class="h3 fw-bold mb-0 mt-1 text-nowrap">
                    $ {{ number_format($fila->total, 2, ',', '.') }}
                </div>

                <div class="text-muted small">
                    {{ $fila->cantidad }} {{ $fila->cantidad === 1 ? 'cobro' : 'cobros' }}
                </div>

            </div>

        </div>

        @endforeach

    </div>

</section>

@endif


<!-- Tabla -->

<section>

    <div class="table-responsive">

        <table class="table table-bordered table-hover align-middle text-center" id="tablaCobros">

            <thead class="table-dark">

                <tr>
                    <th class="text-nowrap">Fecha</th>
                    <th class="text-nowrap">Venta</th>
                    <th class="text-nowrap">Medio</th>
                    <th class="text-nowrap">Cajero</th>
                    <th class="text-nowrap">Importe</th>
                </tr>

            </thead>

            <tbody>

                @forelse ($pagos as $pago)

                <tr>

                    <td class="text-nowrap">
                        {{ $pago->fecha?->format('d/m/Y H:i') }}
                    </td>

                    <td class="text-nowrap">
                        <a href="{{ route('gestion.ventas.show', $pago->venta_id) }}">
                            V-{{ str_pad((string) $pago->venta?->numero, 7, '0', STR_PAD_LEFT) }}
                        </a>
                    </td>

                    <td>
                        {{ ucfirst($pago->metodo->value) }}

                        @if ($pago->cuotas > 1)
                        <span class="text-muted small">· {{ $pago->cuotas }} cuotas</span>
                        @endif
                    </td>

                    <td>
                        {{ $pago->usuario?->nombre_completo ?? '—' }}
                    </td>

                    <td class="text-nowrap fw-semibold">
                        $ {{ number_format($pago->monto, 2, ',', '.') }}
                    </td>

                </tr>

                @empty

                <tr>

                    <td colspan="5" class="text-center py-4 text-muted">
                        No hubo cobros en ese período.
                    </td>

                </tr>

                @endforelse

            </tbody>

        </table>

    </div>


    <!-- Paginación Laravel -->
    @if ($pagos->hasPages())

    <div class="d-flex justify-content-center mt-3">

        {{ $pagos->onEachSide(2)->links() }}

    </div>

    @endif

</section>

@endsection
