@extends('layouts.gestion')

@section('title', 'PowerTech')

@section('content')

<!-- Título principal -->

<section class="mb-4 text-center">
    <h1 class="display-6 fw-bold">Ventas para cobrar</h1>
    <p class="text-muted mb-0">
        Confirmadas y validadas, de la más antigua a la más reciente.
    </p>
</section>


@include('gestion.partials.alertas')


<!-- Botones -->

<section class="mb-4 d-flex flex-column flex-md-row justify-content-between align-items-stretch gap-3">

    <a href="{{ route('gestion.cobros.index') }}"
        class="btn btn-outline-primary d-flex align-items-center justify-content-center gap-2 flex-fill flex-md-grow-0">

        <svg xmlns="http://www.w3.org/2000/svg"
            width="18"
            height="18"
            fill="currentColor"
            class="bi bi-journal-text"
            viewBox="0 0 16 16">

            <path d="M5 10.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5m0-2a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5m0-2a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5m0-2a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5" />

            <path d="M3 0h10a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2v-1h1v1a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H3a1 1 0 0 0-1 1v1H1V2a2 2 0 0 1 2-2" />

            <path d="M1 5v-.5a.5.5 0 0 1 1 0V5h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1zm0 3v-.5a.5.5 0 0 1 1 0V8h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1zm0 3v-.5a.5.5 0 0 1 1 0v.5h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1z" />
        </svg>

        Ver historial de cobros
    </a>

</section>


<!-- Tabla -->

<section>

    <div class="table-responsive">

        <table class="table table-bordered table-hover align-middle text-center" id="tablaPorCobrar">

            <thead class="table-dark">

                <tr>
                    <th class="text-nowrap">Número</th>
                    <th class="text-nowrap">Fecha</th>
                    <th class="text-nowrap">Cliente</th>
                    <th class="text-nowrap">Vendedor</th>
                    <th class="text-nowrap">Ítems</th>
                    <th class="text-nowrap">Total</th>
                    <th class="text-nowrap">Opciones</th>
                </tr>

            </thead>

            <tbody>

                @forelse ($ventas as $venta)

                <tr>

                    <td class="text-nowrap fw-semibold">
                        <a href="{{ route('gestion.ventas.show', $venta) }}">
                            {{ $venta->numeroFormateado() }}
                        </a>
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
                        {{ $venta->lineas_count }}
                    </td>

                    <td class="text-nowrap fw-bold">
                        $ {{ number_format($venta->total, 2, ',', '.') }}
                    </td>

                    <td class="text-nowrap">

                        <a
                            href="{{ route('gestion.cobros.create', $venta) }}"
                            class="btn btn-sm btn-success fw-semibold">
                            Cobrar
                        </a>

                    </td>

                </tr>

                @empty

                <tr>

                    <td colspan="7" class="text-center py-4 text-muted">
                        No hay ventas esperando cobro.
                        Aparecen acá apenas un vendedor las confirma.
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
