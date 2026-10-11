@extends('layouts.gestion')

@section('title', 'PowerTech')

@section('content')

<!-- Título -->

<section class="mb-4">

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">

        <div>

            <h1 class="h3 fw-bold mb-2 d-flex flex-wrap align-items-center gap-2">

                Venta {{ $venta->numeroFormateado() }}

                @include('gestion.partials.estado', ['estado' => $venta->estado])

                @if ($venta->canal->value === 'online')
                <span class="badge text-bg-light border">Tienda online</span>
                @endif

            </h1>

            <p class="text-muted mb-0">
                {{ $venta->fecha?->format('d/m/Y H:i') }}
                · {{ $venta->modo_entrega->value === 'envio' ? 'Envío a domicilio' : 'Retiro en el local' }}
                @if ($venta->usuario)
                · Vendió {{ $venta->usuario->nombre_completo }}
                @endif
            </p>

        </div>


        <a
            href="{{ route('gestion.ventas.index') }}"
            class="btn btn-outline-secondary d-flex align-items-center gap-2">

            <svg xmlns="http://www.w3.org/2000/svg"
                width="16"
                height="16"
                fill="currentColor"
                class="bi bi-arrow-left"
                viewBox="0 0 16 16">

                <path d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8" />
            </svg>

            Volver al listado
        </a>

    </div>

</section>


@include('gestion.partials.alertas')


@if ($venta->estado->value === 'pendiente_stock')

<div class="alert alert-danger" role="alert">
    <strong>Preventa facturada.</strong>
    Las unidades están comprometidas y la entrega espera la reposición.
    Al recibirse mercadería se libera por orden de antigüedad: quien pagó primero se entrega primero.
</div>

@endif


<!-- Acciones del circuito -->

<section class="mb-4 p-3 rounded bg-light shadow-sm d-flex flex-wrap gap-2">

    @can('update', $venta)

    <a href="{{ route('gestion.ventas.edit', $venta) }}" class="btn btn-warning fw-semibold">
        Editar el presupuesto
    </a>

    @endcan


    @can('validar', $venta)

    <form action="{{ route('gestion.ventas.validar', $venta) }}" method="POST" class="d-inline">

        @csrf

        <button
            type="submit"
            class="btn btn-primary fw-semibold"
            onclick="return confirm('Validar confirma la venta y compromete el stock. ¿Continuar?');">
            Validar y confirmar
        </button>

    </form>

    @endcan


    @can('cobrar', $venta)

    <a href="{{ route('gestion.cobros.create', $venta) }}" class="btn btn-success fw-semibold">
        Cobrar
    </a>

    @endcan


    @can('entregar', $venta)

        @if ($venta->estado->value === 'en_preparacion' && $venta->modo_entrega->value === 'retiro')

        <form action="{{ route('gestion.entregas.lista-retiro', $venta) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-outline-primary fw-semibold">
                Marcar lista para retirar
            </button>
        </form>

        @endif


        @if ($venta->estado->value === 'en_preparacion' && $venta->modo_entrega->value === 'envio')

        <form action="{{ route('gestion.entregas.despachar', $venta) }}" method="POST" class="d-inline">
            @csrf
            <button
                type="submit"
                class="btn btn-primary fw-semibold"
                onclick="return confirm('Despachar descuenta el stock físico. ¿Continuar?');">
                Despachar
            </button>
        </form>

        @endif


        @if (in_array($venta->estado->value, ['pagada', 'en_preparacion', 'lista_retiro', 'despachada'], true))

        <form action="{{ route('gestion.entregas.entregar', $venta) }}" method="POST" class="d-inline">
            @csrf
            <button
                type="submit"
                class="btn btn-success fw-semibold"
                onclick="return confirm('¿Confirma la entrega de la mercadería?');">
                Entregar
            </button>
        </form>

        @endif

    @endcan


    @can('devolver', $venta)

    <a href="{{ route('gestion.devoluciones.create', $venta) }}" class="btn btn-warning fw-semibold">
        Devolver
    </a>

    @endcan


    @can('cancelar', $venta)

    <button
        type="button"
        class="btn btn-outline-danger fw-semibold ms-md-auto"
        data-bs-toggle="modal"
        data-bs-target="#modalCancelar">
        Cancelar la venta
    </button>

    @endcan

</section>


<div class="row g-4">

    <!-- Columna izquierda -->
    <div class="col-12 col-lg-8">

        <!-- Detalle -->
        <section class="mb-4">

            <h2 class="h5 fw-bold mb-3">Detalle</h2>

            <div class="table-responsive">

                <table class="table table-bordered table-hover align-middle text-center">

                    <thead class="table-dark">

                        <tr>
                            <th class="text-nowrap">Producto</th>
                            <th class="text-nowrap">Cantidad</th>
                            <th class="text-nowrap">Precio</th>
                            <th class="text-nowrap">Descuento</th>
                            <th class="text-nowrap">Importe</th>
                        </tr>

                    </thead>

                    <tbody>

                        @foreach ($venta->lineas as $linea)

                        <tr>

                            <td class="text-start">

                                <div class="fw-semibold">{{ $linea->descripcion }}</div>

                                @if ($linea->producto)
                                <div class="text-muted small">{{ $linea->producto->codigo }}</div>
                                @endif

                                @if ($linea->cantidad_devuelta > 0)
                                <div class="text-warning-emphasis small">
                                    {{ $linea->cantidad_devuelta }} de {{ $linea->cantidad }} devueltas
                                </div>
                                @endif

                            </td>

                            <td>{{ $linea->cantidad }}</td>

                            <td class="text-nowrap">
                                $ {{ number_format($linea->precio_unitario, 2, ',', '.') }}
                            </td>

                            <td class="text-nowrap">
                                @if ((float) $linea->descuento_promocion > 0)
                                <span class="text-success">
                                    − $ {{ number_format($linea->descuento_promocion, 2, ',', '.') }}
                                </span>
                                @else
                                <span class="text-muted">—</span>
                                @endif
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


        <!-- Promociones aplicadas -->
        @if ($venta->promocionesAplicadas->isNotEmpty())

        <section class="mb-4">

            <h2 class="h5 fw-bold mb-1">Promociones aplicadas</h2>

            <p class="text-muted small">Por qué cada línea salió más barata.</p>

            <ul class="list-group">

                @foreach ($venta->promocionesAplicadas as $aplicada)

                <li class="list-group-item d-flex justify-content-between align-items-center">

                    {{ $aplicada->promocion?->nombre ?? 'Promoción' }}

                    <span class="text-success fw-semibold text-nowrap">
                        − $ {{ number_format($aplicada->importe_descuento, 2, ',', '.') }}
                    </span>

                </li>

                @endforeach

            </ul>

        </section>

        @endif


        <!-- Cobros -->
        <section class="mb-4">

            <h2 class="h5 fw-bold mb-3">Cobros</h2>

            @if ($venta->pagos->isEmpty())

            <p class="text-muted">Todavía no se registraron cobros.</p>

            @else

            <ul class="list-group">

                @foreach ($venta->pagos as $pago)

                <li class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-2">

                    <span>
                        <span class="fw-semibold">{{ ucfirst($pago->metodo->value) }}</span>

                        @if ($pago->cuotas > 1)
                        <span class="text-muted">en {{ $pago->cuotas }} cuotas</span>
                        @endif

                        <span class="text-muted small d-block">
                            {{ $pago->fecha?->format('d/m/Y H:i') }}
                            @if ($pago->usuario) · {{ $pago->usuario->nombre_completo }} @endif
                        </span>
                    </span>

                    <span class="d-flex align-items-center gap-3">

                        @include('gestion.partials.estado', ['estado' => $pago->estado])

                        <span class="fw-semibold text-nowrap">
                            $ {{ number_format($pago->monto, 2, ',', '.') }}
                        </span>

                    </span>

                </li>

                @endforeach

            </ul>

            @endif

        </section>


        <!-- Comprobantes -->
        <section class="mb-4">

            <h2 class="h5 fw-bold mb-1">Comprobantes</h2>

            <p class="text-muted small">
                La numeración fiscal es propia del comprobante y no tiene relación con el número de la venta.
            </p>

            @if ($venta->comprobantes->isEmpty())

            <p class="text-muted">
                Sin comprobantes. La factura se emite al acreditarse el pago, nunca antes.
            </p>

            @else

            <ul class="list-group">

                @foreach ($venta->comprobantes as $comprobante)

                <li class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-2">

                    <span>

                        <a href="{{ route('gestion.comprobantes.show', $comprobante) }}" class="fw-semibold">
                            {{ $comprobante->tipoComprobante?->nombre }}
                            {{ str_pad((string) $comprobante->puntoVenta?->numero, 5, '0', STR_PAD_LEFT) }}-{{ str_pad((string) $comprobante->numero, 8, '0', STR_PAD_LEFT) }}
                        </a>

                        <span class="text-muted small d-block">
                            {{ $comprobante->fecha_emision?->format('d/m/Y') }}
                        </span>

                    </span>

                    <span class="d-flex align-items-center gap-3">

                        @include('gestion.partials.estado', ['estado' => $comprobante->estado])

                        <span class="fw-semibold text-nowrap">
                            $ {{ number_format($comprobante->total, 2, ',', '.') }}
                        </span>

                    </span>

                </li>

                @endforeach

            </ul>

            @endif

        </section>

    </div>


    <!-- Columna derecha -->
    <div class="col-12 col-lg-4">

        <!-- Totales -->
        <section class="p-4 rounded-4 bg-white shadow-sm border mb-4">

            <div class="d-flex justify-content-between">
                <span class="text-muted">Subtotal</span>
                <span class="text-nowrap">$ {{ number_format($venta->subtotal, 2, ',', '.') }}</span>
            </div>

            @if ((float) $venta->descuento_promociones > 0)
            <div class="d-flex justify-content-between mt-2">
                <span class="text-muted">Promociones</span>
                <span class="text-success text-nowrap">
                    − $ {{ number_format($venta->descuento_promociones, 2, ',', '.') }}
                </span>
            </div>
            @endif

            @if ((float) $venta->costo_envio > 0)
            <div class="d-flex justify-content-between mt-2">
                <span class="text-muted">Envío</span>
                <span class="text-nowrap">$ {{ number_format($venta->costo_envio, 2, ',', '.') }}</span>
            </div>
            @endif

            <div class="d-flex justify-content-between mt-2 text-muted small">
                <span>Neto gravado</span>
                <span class="text-nowrap">$ {{ number_format($venta->neto_gravado, 2, ',', '.') }}</span>
            </div>

            <div class="d-flex justify-content-between text-muted small">
                <span>IVA</span>
                <span class="text-nowrap">$ {{ number_format($venta->total_iva, 2, ',', '.') }}</span>
            </div>

            <hr>

            <div class="d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Total</span>
                <span class="h4 fw-bold mb-0 text-nowrap">
                    $ {{ number_format($venta->total, 2, ',', '.') }}
                </span>
            </div>

        </section>


        <!-- Cliente -->
        <section class="p-4 rounded-4 bg-light shadow-sm border mb-4">

            <h2 class="h6 fw-bold mb-2">Cliente</h2>

            @if ($venta->cliente)

            <div class="fw-semibold">{{ $venta->cliente->razon_social }}</div>
            <div class="text-muted">{{ $venta->cliente->nro_doc }}</div>
            <div class="text-muted small">{{ $venta->cliente->condicionIva?->nombre }}</div>

            <a href="{{ route('gestion.clientes.show', $venta->cliente) }}" class="small">
                Ver ficha
            </a>

            @else

            <p class="mb-1">Consumidor final, sin identificar.</p>
            <p class="text-muted small mb-0">Se factura B.</p>

            @endif

        </section>


        <!-- Presupuesto o validación -->
        @if ($venta->esPresupuesto())

        <section class="p-4 rounded-4 bg-light shadow-sm border mb-4">

            <h2 class="h6 fw-bold mb-2">Presupuesto</h2>

            <p class="mb-1">
                Vence el {{ $venta->validez_hasta?->format('d/m/Y') ?? '—' }}.
            </p>

            <p class="text-muted small mb-0">
                No compromete stock ni emite comprobante. Necesita la validación
                de un vendedor para poder cobrarse.
            </p>

        </section>

        @elseif ($venta->estaValidada())

        <section class="p-4 rounded-4 bg-light shadow-sm border mb-4">

            <h2 class="h6 fw-bold mb-2">Validación</h2>

            <div>{{ $venta->usuarioValido?->nombre_completo ?? 'Sin registrar' }}</div>
            <div class="text-muted small">{{ $venta->fecha_validacion?->format('d/m/Y H:i') }}</div>

        </section>

        @endif


        <!-- Envío -->
        @if ($venta->envio)

        <section class="p-4 rounded-4 bg-light shadow-sm border mb-4">

            <h2 class="h6 fw-bold mb-2">Envío</h2>

            <address class="mb-1">
                {{ $venta->envio->calle }} {{ $venta->envio->numero }}
                @if ($venta->envio->piso_depto) ({{ $venta->envio->piso_depto }}) @endif<br>
                {{ $venta->envio->localidad }}, {{ $venta->envio->provincia }}
                ({{ $venta->envio->codigo_postal }})
            </address>

            @if ($venta->envio->tracking_url)
            <a href="{{ $venta->envio->tracking_url }}" target="_blank" rel="noopener" class="small">
                Seguir el envío
            </a>
            @endif

        </section>

        @endif


        <!-- Observaciones -->
        @if ($venta->observaciones)

        <section class="p-4 rounded-4 bg-light shadow-sm border">

            <h2 class="h6 fw-bold mb-2">Observaciones</h2>

            <p class="mb-0" style="white-space: pre-line;">{{ $venta->observaciones }}</p>

        </section>

        @endif

    </div>

</div>


<!-- Modal de cancelación -->
@can('cancelar', $venta)

<div class="modal fade" id="modalCancelar" tabindex="-1" aria-labelledby="tituloModalCancelar" aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">

        <form action="{{ route('gestion.ventas.cancelar', $venta) }}" method="POST" class="modal-content">

            @csrf

            <div class="modal-header">

                <h5 class="modal-title" id="tituloModalCancelar">
                    Cancelar la venta {{ $venta->numeroFormateado() }}
                </h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>

            </div>

            <div class="modal-body">

                <p class="text-muted">
                    Cancelar libera las unidades comprometidas. La venta no se elimina:
                    conserva su número y su historial.
                </p>

                <label for="motivo" class="form-label fw-semibold">Motivo</label>

                <textarea
                    class="form-control rounded-3 @error('motivo') is-invalid @enderror"
                    id="motivo"
                    name="motivo"
                    rows="3"
                    minlength="5"
                    maxlength="255"
                    style="resize: none"
                    placeholder="El cliente desistió de la compra."
                    required>{{ old('motivo') }}</textarea>

                @error('motivo')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
                @enderror

            </div>

            <div class="modal-footer">

                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    Volver
                </button>

                <button type="submit" class="btn btn-danger fw-semibold">
                    Cancelar la venta
                </button>

            </div>

        </form>

    </div>

</div>

@endcan

@endsection
