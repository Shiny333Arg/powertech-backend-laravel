@extends('layouts.gestion')

@section('title', 'PowerTech')

@section('content')

<!-- Título principal -->

<section class="mb-4 text-center">
    <h1 class="display-6 fw-bold">Comprobantes</h1>
    <p class="text-muted mb-0">
        Facturas y notas de crédito. Un comprobante con CAE no se edita ni se borra:
        se corrige con una nota.
    </p>
</section>


@include('gestion.partials.alertas')


<!-- Filtros -->

<section class="mb-4">

    <form
        action="{{ route('gestion.comprobantes.index') }}"
        method="GET"
        class="row g-3 align-items-end shadow-sm p-3 rounded bg-light"
        autocomplete="off">

        <div class="col-12 col-md-4">

            <label for="estado" class="form-label fw-semibold">Estado</label>

            <select id="estado" name="estado" class="form-select">

                <option value="">Todos</option>

                @foreach ($estados as $opcion)

                <option value="{{ $opcion->value }}" @selected(($filtros['estado'] ?? null) === $opcion->value)>
                    {{ ucfirst($opcion->value) }}
                </option>

                @endforeach

            </select>

        </div>

        <div class="col-6 col-md-3">

            <label for="desde" class="form-label fw-semibold">Desde</label>

            <input type="date" class="form-control" id="desde" name="desde" value="{{ $filtros['desde'] ?? '' }}">

        </div>

        <div class="col-6 col-md-3">

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

        <table class="table table-bordered table-hover align-middle text-center" id="tablaComprobantes">

            <thead class="table-dark">

                <tr>
                    <th class="text-nowrap">Tipo</th>
                    <th class="text-nowrap">Número</th>
                    <th class="text-nowrap">Emisión</th>
                    <th class="text-nowrap">Cliente</th>
                    <th class="text-nowrap">Venta</th>
                    <th class="text-nowrap">Estado</th>
                    <th class="text-nowrap">Total</th>
                    <th class="text-nowrap">Opciones</th>
                </tr>

            </thead>

            <tbody>

                @forelse ($comprobantes as $comprobante)

                <tr>

                    <td>
                        {{ $comprobante->tipoComprobante?->nombre }}
                    </td>

                    <td class="text-nowrap">
                        {{ str_pad((string) $comprobante->puntoVenta?->numero, 5, '0', STR_PAD_LEFT) }}-{{ str_pad((string) $comprobante->numero, 8, '0', STR_PAD_LEFT) }}
                    </td>

                    <td class="text-nowrap">
                        {{ $comprobante->fecha_emision?->format('d/m/Y') }}
                    </td>

                    <td>
                        {{ $comprobante->cliente?->razon_social ?? 'Consumidor final' }}
                    </td>

                    <td class="text-nowrap">
                        <a href="{{ route('gestion.ventas.show', $comprobante->venta_id) }}">
                            V-{{ str_pad((string) $comprobante->venta?->numero, 7, '0', STR_PAD_LEFT) }}
                        </a>
                    </td>

                    <td>
                        @include('gestion.partials.estado', ['estado' => $comprobante->estado])
                    </td>

                    <td class="text-nowrap fw-semibold">
                        $ {{ number_format($comprobante->total, 2, ',', '.') }}
                    </td>

                    <td class="text-nowrap">

                        <a
                            href="{{ route('gestion.comprobantes.show', $comprobante) }}"
                            class="btn btn-sm btn-primary"
                            title="Ver el comprobante">

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

                    </td>

                </tr>

                @empty

                <tr>

                    <td colspan="8" class="text-center py-4 text-muted">
                        Todavía no se emitió ningún comprobante.
                        Las facturas se emiten solas al acreditarse el pago de una venta.
                    </td>

                </tr>

                @endforelse

            </tbody>

        </table>

    </div>


    <!-- Paginación Laravel -->
    @if ($comprobantes->hasPages())

    <div class="d-flex justify-content-center mt-3">

        {{ $comprobantes->onEachSide(2)->links() }}

    </div>

    @endif

</section>

@endsection
