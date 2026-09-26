@extends('layouts.gestion')

@section('title', 'PowerTech')

@section('content')

<!-- Título -->

<section class="mb-4">

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">

        <div>

            <h1 class="h3 fw-bold mb-2 d-flex flex-wrap align-items-center gap-2">

                {{ $comprobante->tipoComprobante?->nombre }}

                @include('gestion.partials.estado', ['estado' => $comprobante->estado])

            </h1>

            <p class="text-muted mb-0">
                {{ str_pad((string) $comprobante->puntoVenta?->numero, 5, '0', STR_PAD_LEFT) }}-{{ str_pad((string) $comprobante->numero, 8, '0', STR_PAD_LEFT) }}
                · {{ $comprobante->fecha_emision?->format('d/m/Y H:i') }}
            </p>

        </div>


        <div class="d-flex flex-wrap gap-2">

            @can('reintentar', $comprobante)

            <form action="{{ route('gestion.comprobantes.reintentar', $comprobante) }}" method="POST">

                @csrf

                <button type="submit" class="btn btn-primary fw-semibold">
                    Reintentar la autorización
                </button>

            </form>

            @endcan

            <a href="{{ route('gestion.ventas.show', $comprobante->venta_id) }}" class="btn btn-outline-secondary">
                Ver la venta
            </a>

        </div>

    </div>

</section>


@include('gestion.partials.alertas')


@if ($comprobante->estado->value === 'rechazado' && $comprobante->motivo)

<div class="alert alert-danger" role="alert">
    <strong>ARCA rechazó el comprobante:</strong> {{ $comprobante->motivo }}
</div>

@endif


<div class="row g-4">

    <!-- Columna izquierda -->
    <div class="col-12 col-lg-8">

        <!-- Detalle -->
        <section class="mb-4">

            <h2 class="h5 fw-bold mb-3">Detalle</h2>

            <div class="table-responsive">

                <table class="table table-bordered align-middle text-center">

                    <thead class="table-dark">

                        <tr>
                            <th class="text-nowrap">Descripción</th>
                            <th class="text-nowrap">Cantidad</th>
                            <th class="text-nowrap">Precio</th>
                            <th class="text-nowrap">IVA</th>
                            <th class="text-nowrap">Neto</th>
                            <th class="text-nowrap">Total</th>
                        </tr>

                    </thead>

                    <tbody>

                        @foreach ($comprobante->lineas as $linea)

                        <tr>

                            <td class="text-start">{{ $linea->descripcion }}</td>

                            <td>{{ $linea->cantidad }}</td>

                            <td class="text-nowrap">
                                $ {{ number_format($linea->precio_unitario, 2, ',', '.') }}
                            </td>

                            <td class="text-nowrap">
                                {{ rtrim(rtrim(number_format((float) $linea->alicuota_iva, 2, ',', ''), '0'), ',') }}%
                            </td>

                            <td class="text-nowrap">
                                $ {{ number_format($linea->neto, 2, ',', '.') }}
                            </td>

                            <td class="text-nowrap fw-semibold">
                                $ {{ number_format($linea->total, 2, ',', '.') }}
                            </td>

                        </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

        </section>


        <!-- Solicitudes a ARCA -->
        <section>

            <h2 class="h5 fw-bold mb-1">Solicitudes a ARCA</h2>

            <p class="text-muted small">
                Una fila por intento. Es lo que permite saber si el comprobante quedó
                autorizado del otro lado cuando se cortó la conexión.
            </p>

            @if ($comprobante->solicitudes->isEmpty())

            <p class="text-muted">Sin intentos registrados.</p>

            @else

            <div class="accordion" id="acordeonSolicitudes">

                @foreach ($comprobante->solicitudes as $solicitud)

                <div class="accordion-item">

                    <h3 class="accordion-header" id="encabezadoSolicitud{{ $solicitud->id }}">

                        <button
                            class="accordion-button collapsed"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#solicitud{{ $solicitud->id }}"
                            aria-expanded="false"
                            aria-controls="solicitud{{ $solicitud->id }}">

                            <span class="d-flex flex-wrap align-items-center gap-2">

                                <span class="fw-semibold">Intento {{ $solicitud->intento }}</span>

                                <span class="badge text-bg-{{ $solicitud->resultado === 'A' ? 'success' : 'danger' }}">
                                    {{ $solicitud->resultado === 'A' ? 'Aprobado' : 'Rechazado' }}
                                </span>

                                <span class="text-muted small">
                                    {{ $solicitud->operacion }} · {{ $solicitud->created_at?->format('d/m/Y H:i:s') }}
                                </span>

                            </span>

                        </button>

                    </h3>

                    <div
                        id="solicitud{{ $solicitud->id }}"
                        class="accordion-collapse collapse"
                        aria-labelledby="encabezadoSolicitud{{ $solicitud->id }}"
                        data-bs-parent="#acordeonSolicitudes">

                        <div class="accordion-body">

                            @if ($solicitud->mensaje_error)
                            <p class="text-danger">
                                {{ $solicitud->codigo_error }} {{ $solicitud->mensaje_error }}
                            </p>
                            @endif

                            <div class="row g-3">

                                <div class="col-12 col-md-6">

                                    <div class="fw-semibold small mb-1">Enviado</div>

                                    <pre class="bg-dark text-light p-3 rounded small mb-0" style="overflow-x: auto;">{{ json_encode($solicitud->request_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>

                                </div>

                                <div class="col-12 col-md-6">

                                    <div class="fw-semibold small mb-1">Recibido</div>

                                    <pre class="bg-dark text-light p-3 rounded small mb-0" style="overflow-x: auto;">{{ json_encode($solicitud->response_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

                @endforeach

            </div>

            @endif

        </section>

    </div>


    <!-- Columna derecha -->
    <div class="col-12 col-lg-4">

        <!-- Totales -->
        <section class="p-4 rounded-4 bg-white shadow-sm border mb-4">

            <div class="d-flex justify-content-between">
                <span class="text-muted">Neto</span>
                <span class="text-nowrap">$ {{ number_format($comprobante->neto, 2, ',', '.') }}</span>
            </div>

            <div class="d-flex justify-content-between mt-2">
                <span class="text-muted">IVA</span>
                <span class="text-nowrap">$ {{ number_format($comprobante->iva, 2, ',', '.') }}</span>
            </div>

            <hr>

            <div class="d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Total</span>
                <span class="h4 fw-bold mb-0 text-nowrap">
                    $ {{ number_format($comprobante->total, 2, ',', '.') }}
                </span>
            </div>

        </section>


        <!-- CAE -->
        <section class="p-4 rounded-4 bg-light shadow-sm border mb-4">

            <h2 class="h6 fw-bold mb-2">CAE</h2>

            @if ($comprobante->cae)

            <div class="h5 mb-1">{{ $comprobante->cae }}</div>

            <div class="text-muted small">
                Vence el {{ $comprobante->cae_vencimiento?->format('d/m/Y') }}
            </div>

            @else

            <p class="mb-0 text-muted">Sin CAE todavía.</p>

            @endif

        </section>


        <!-- Receptor -->
        <section class="p-4 rounded-4 bg-light shadow-sm border mb-4">

            <h2 class="h6 fw-bold mb-2">Receptor</h2>

            <div class="fw-semibold">
                {{ $comprobante->cliente?->razon_social ?? 'Consumidor final' }}
            </div>

            <div class="text-muted">
                {{ $comprobante->tipoDocumento?->nombre }} {{ $comprobante->nro_doc }}
            </div>

            <div class="text-muted small">
                {{ $comprobante->condicionIva?->nombre }}
            </div>

            <div class="form-text mt-2">
                Datos tal como se informaron. No cambian si el cliente edita su ficha.
            </div>

        </section>


        <!-- Comprobante asociado -->
        @if ($comprobante->comprobanteAsociado)

        <section class="p-4 rounded-4 bg-light shadow-sm border mb-4">

            <h2 class="h6 fw-bold mb-2">Factura de origen</h2>

            <a href="{{ route('gestion.comprobantes.show', $comprobante->comprobanteAsociado) }}">
                {{ $comprobante->comprobanteAsociado->tipoComprobante?->nombre }}
                {{ str_pad((string) $comprobante->comprobanteAsociado->numero, 8, '0', STR_PAD_LEFT) }}
            </a>

        </section>

        @endif


        <!-- Notas emitidas -->
        @if ($comprobante->notasAsociadas->isNotEmpty())

        <section class="p-4 rounded-4 bg-light shadow-sm border">

            <h2 class="h6 fw-bold mb-2">Notas emitidas</h2>

            <ul class="list-unstyled mb-0">

                @foreach ($comprobante->notasAsociadas as $nota)

                <li class="mb-1">

                    <a href="{{ route('gestion.comprobantes.show', $nota) }}">
                        {{ $nota->tipoComprobante?->nombre }}
                        {{ str_pad((string) $nota->numero, 8, '0', STR_PAD_LEFT) }}
                    </a>

                    <span class="text-muted">
                        · $ {{ number_format($nota->total, 2, ',', '.') }}
                    </span>

                </li>

                @endforeach

            </ul>

        </section>

        @endif

    </div>

</div>

@endsection
