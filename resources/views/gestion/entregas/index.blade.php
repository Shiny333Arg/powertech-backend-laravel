@extends('layouts.gestion')

@section('title', 'PowerTech')

@section('content')

<!-- Título principal -->

<section class="mb-4 text-center">
    <h1 class="display-6 fw-bold">Depósito</h1>
    <p class="text-muted mb-0">
        Acá sale la mercadería. El stock físico baja cuando se despacha o se entrega,
        no cuando se cobra.
    </p>
</section>


@include('gestion.partials.alertas')


<!-- En curso -->

<section class="mb-5">

    <h2 class="h5 fw-bold mb-3">En curso</h2>

    <div class="table-responsive">

        <table class="table table-bordered table-hover align-middle text-center" id="tablaEnCurso">

            <thead class="table-dark">

                <tr>
                    <th class="text-nowrap">Número</th>
                    <th class="text-nowrap">Fecha</th>
                    <th class="text-nowrap">Cliente</th>
                    <th class="text-nowrap">Entrega</th>
                    <th class="text-nowrap">Estado</th>
                    <th class="text-nowrap">Detalle</th>
                    <th class="text-nowrap">Opciones</th>
                </tr>

            </thead>

            <tbody>

                @forelse ($enCurso as $venta)

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
                        <span class="badge text-bg-light border">
                            {{ $venta->modo_entrega->value === 'envio' ? 'Envío' : 'Retiro' }}
                        </span>
                    </td>

                    <td>
                        @include('gestion.partials.estado', ['estado' => $venta->estado])
                    </td>

                    <td class="text-start small">

                        @foreach ($venta->lineas as $linea)
                        <div>{{ $linea->cantidad }} × {{ $linea->descripcion }}</div>
                        @endforeach

                    </td>

                    <td class="text-nowrap">

                        @if ($venta->estado->value === 'en_preparacion' && $venta->modo_entrega->value === 'retiro')

                        <form
                            action="{{ route('gestion.entregas.lista-retiro', $venta) }}"
                            method="POST"
                            class="d-inline">

                            @csrf

                            <button
                                type="submit"
                                class="btn btn-sm btn-outline-primary mb-1"
                                title="Marcar lista para retirar">
                                Lista
                            </button>

                        </form>

                        @endif


                        @if ($venta->estado->value === 'en_preparacion' && $venta->modo_entrega->value === 'envio')

                        <form
                            action="{{ route('gestion.entregas.despachar', $venta) }}"
                            method="POST"
                            class="d-inline">

                            @csrf

                            <button
                                type="submit"
                                class="btn btn-sm btn-primary mb-1"
                                title="Despachar"
                                onclick="return confirm('Despachar descuenta el stock físico. ¿Continuar?');">
                                Despachar
                            </button>

                        </form>

                        @endif


                        <form
                            action="{{ route('gestion.entregas.entregar', $venta) }}"
                            method="POST"
                            class="d-inline">

                            @csrf

                            <button
                                type="submit"
                                class="btn btn-sm btn-success mb-1"
                                title="Entregar"
                                onclick="return confirm('¿Confirma la entrega de la mercadería?');">
                                Entregar
                            </button>

                        </form>

                    </td>

                </tr>

                @empty

                <tr>

                    <td colspan="7" class="text-center py-4 text-muted">
                        Nada para preparar. Las ventas aparecen acá apenas se cobran
                        y hay stock para armarlas.
                    </td>

                </tr>

                @endforelse

            </tbody>

        </table>

    </div>


    <!-- Paginación Laravel -->
    @if ($enCurso->hasPages())

    <div class="d-flex justify-content-center mt-3">

        {{ $enCurso->onEachSide(2)->links() }}

    </div>

    @endif

</section>


<!-- Esperando reposición -->

<section>

    <h2 class="h5 fw-bold mb-1">Esperando reposición</h2>

    <p class="text-muted small">
        Preventas ya facturadas. Se liberan por antigüedad: quien pagó primero se entrega primero.
    </p>

    <div class="table-responsive">

        <table class="table table-bordered table-hover align-middle text-center" id="tablaPendientesStock">

            <thead class="table-dark">

                <tr>
                    <th class="text-nowrap" style="width: 4rem;">Orden</th>
                    <th class="text-nowrap">Número</th>
                    <th class="text-nowrap">Pagó</th>
                    <th class="text-nowrap">Cliente</th>
                    <th class="text-nowrap">Qué falta</th>
                    <th class="text-nowrap">Total</th>
                </tr>

            </thead>

            <tbody>

                @forelse ($pendientesDeStock as $posicion => $venta)

                <tr>

                    <td class="fw-bold">
                        {{ $posicion + 1 }}
                    </td>

                    <td class="text-nowrap fw-semibold">
                        <a href="{{ route('gestion.ventas.show', $venta) }}">
                            {{ $venta->numeroFormateado() }}
                        </a>
                    </td>

                    <td class="text-nowrap">
                        {{ $venta->fecha?->format('d/m/Y') }}
                    </td>

                    <td>
                        {{ $venta->cliente?->razon_social ?? 'Consumidor final' }}
                    </td>

                    <td class="text-start small">

                        @foreach ($venta->lineas as $linea)

                            @php
                                $falta = $linea->cantidad - (int) ($linea->producto?->stock_fisico ?? 0);
                            @endphp

                            <div class="{{ $falta > 0 ? 'text-danger' : '' }}">
                                {{ $linea->cantidad }} × {{ $linea->descripcion }}
                                @if ($falta > 0)
                                <span class="fw-semibold">— faltan {{ $falta }}</span>
                                @endif
                            </div>

                        @endforeach

                    </td>

                    <td class="text-nowrap fw-semibold">
                        $ {{ number_format($venta->total, 2, ',', '.') }}
                    </td>

                </tr>

                @empty

                <tr>

                    <td colspan="6" class="text-center py-4 text-muted">
                        Ninguna venta está esperando mercadería.
                    </td>

                </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</section>

@endsection
