{{--
    Detalle y seguimiento de un pedido.

    Recibe:
      $venta        modelo Venta con lineas.producto, pagos, comprobantes
      $seguimiento  list<array{etiqueta, detalle, situacion}>, vacio si el
                    pedido no tiene seguimiento (cancelado o devuelto)
      $factura      ?Comprobante de signo positivo y autorizado, o null

    El detalle sale de venta_lineas y NO del catalogo: descripcion, precio y
    alicuota estan congelados ahi. Si el producto se renombro o cambio de
    precio despues, el pedido sigue mostrando lo que el cliente compro.

    El seguimiento se deriva del estado actual, no de un historial de
    transiciones: `ventas` no guarda cuando ocurrio cada paso, asi que no hay
    fechas por paso. Ver CompraController::seguimientoDe().
--}}

@extends('layouts.cliente')

@section('title', 'Pedido '.$venta->numeroFormateado())

@section('content')

<!-- Volver -->
<section class="mb-4">

    <a href="{{ route('cliente.compras.index') }}"
        class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">

        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-arrow-left" viewBox="0 0 16 16">
            <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8" />
        </svg>
        Mis compras

    </a>

</section>


<div class="d-flex flex-wrap align-items-center gap-3 mb-4">

    <h1 class="h3 fw-bold mb-0">
        Pedido {{ $venta->numeroFormateado() }}
    </h1>

    @include('compras.partials.estado', ['estado' => $venta->estado])

</div>


<div class="row g-4">


    <!-- Seguimiento y detalle -->
    <div class="col-12 col-lg-7">


        <!-- Seguimiento -->
        <div class="card shadow-sm border-0 rounded-4 mb-4">

            <div class="card-body p-4">

                <h2 class="h5 fw-bold mb-4">Seguimiento</h2>

                @if (empty($seguimiento))

                {{-- Cancelada, devuelta o devuelta parcial: no hay circuito que
                     mostrar, hay un desenlace. --}}
                <div class="alert alert-secondary mb-0" role="status">

                    <div class="fw-semibold mb-1">
                        {{ $venta->estado->etiqueta() }}
                    </div>

                    <div class="small mb-0">
                        @switch($venta->estado)
                            @case(\App\Enums\EstadoVenta::Cancelada)
                                Este pedido fue cancelado. Si pagaste, comunicate con PowerTech por la devolución.
                                @break
                            @case(\App\Enums\EstadoVenta::Devuelta)
                                Devolvimos este pedido completo y emitimos la nota de crédito.
                                @break
                            @case(\App\Enums\EstadoVenta::DevueltaParcial)
                                Devolvimos parte de este pedido y emitimos la nota de crédito correspondiente.
                                @break
                            @default
                                Consultá con PowerTech por el estado de este pedido.
                        @endswitch
                    </div>

                </div>

                @else

                <ol class="list-unstyled mb-0">

                    @foreach ($seguimiento as $paso)

                    @php
                        $color = match ($paso['situacion']) {
                            'hecho'   => 'success',
                            'actual'  => 'primary',
                            default   => 'secondary',
                        };
                    @endphp

                    <li class="d-flex gap-3 @unless ($loop->last) pb-4 @endunless position-relative">

                        {{-- Linea vertical que une los pasos. --}}
                        @unless ($loop->last)
                        <span class="position-absolute bg-light"
                            style="left: 11px; top: 26px; bottom: 6px; width: 2px;"></span>
                        @endunless

                        <span class="flex-shrink-0 rounded-circle d-flex align-items-center justify-content-center
                            @if ($paso['situacion'] === 'pendiente') bg-light text-muted @else text-bg-{{ $color }} @endif"
                            style="width: 24px; height: 24px; z-index: 1;">

                            @if ($paso['situacion'] === 'hecho')

                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z" />
                            </svg>

                            @else

                            <span class="rounded-circle bg-current" style="width: 7px; height: 7px; background-color: currentColor;"></span>

                            @endif

                        </span>

                        <div class="@if ($paso['situacion'] === 'pendiente') opacity-50 @endif">

                            <div class="fw-semibold lh-sm @if ($paso['situacion'] === 'actual') text-primary @endif">
                                {{ $paso['etiqueta'] }}
                            </div>

                            <div class="text-muted small">
                                {{ $paso['detalle'] }}
                            </div>

                        </div>

                    </li>

                    @endforeach

                </ol>

                @endif

            </div>

        </div>


        <!-- Detalle -->
        <div class="card shadow-sm border-0 rounded-4">

            <div class="card-body p-4">

                <h2 class="h5 fw-bold mb-3">Detalle</h2>

                @foreach ($venta->lineas as $linea)

                <div class="d-flex align-items-start gap-3 @unless ($loop->last) border-bottom pb-3 mb-3 @endunless">

                    <div class="flex-shrink-0" style="width: 56px;">

                        <div class="ratio ratio-1x1 bg-white rounded-3 overflow-hidden border">

                            @if ($linea->producto?->imagen)

                            <img src="{{ asset('storage/'.$linea->producto->imagen) }}"
                                alt="{{ $linea->descripcion }}"
                                class="w-100 h-100 p-1"
                                style="object-fit: contain;"
                                loading="lazy">

                            @else

                            <div class="d-flex align-items-center justify-content-center bg-light"></div>

                            @endif

                        </div>

                    </div>

                    <div class="flex-grow-1">

                        {{--
                            La descripcion viene congelada en la linea, no del
                            producto. Se enlaza a la ficha solo si el producto
                            sigue publicado: un enlace a un producto
                            despublicado lleva a un 404.
                        --}}
                        @if ($linea->producto && $linea->producto->activo && $linea->producto->publicado_app)

                        <a href="{{ route('cliente.items.show', $linea->producto) }}"
                            class="fw-semibold lh-sm d-block text-decoration-none text-dark">
                            {{ $linea->descripcion }}
                        </a>

                        @else

                        <div class="fw-semibold lh-sm">
                            {{ $linea->descripcion }}
                        </div>

                        @endif

                        <div class="text-muted small">
                            {{ $linea->cantidad }} &times;
                            $ {{ number_format($linea->precio_unitario, 2, ',', '.') }}
                        </div>

                        @if ((float) $linea->descuento_promocion > 0)
                        <div class="text-success small fw-semibold">
                            Descuento: &minus;$ {{ number_format($linea->descuento_promocion, 2, ',', '.') }}
                        </div>
                        @endif

                        @if ($linea->cantidad_devuelta > 0)
                        <span class="badge text-bg-secondary rounded-pill mt-1">
                            {{ $linea->cantidad_devuelta }}
                            {{ $linea->cantidad_devuelta === 1 ? 'unidad devuelta' : 'unidades devueltas' }}
                        </span>
                        @endif

                    </div>

                    <div class="fw-bold text-end">
                        $ {{ number_format($linea->total, 2, ',', '.') }}
                    </div>

                </div>

                @endforeach

            </div>

        </div>

    </div>


    <!-- Resumen -->
    <div class="col-12 col-lg-5">


        <!-- Importes -->
        <div class="card shadow-sm border rounded-4 mb-4">

            <div class="card-body p-4">

                <h2 class="h6 fw-bold text-uppercase text-secondary mb-3">
                    Resumen
                </h2>

                <div class="d-flex justify-content-between small mb-2">
                    <span class="text-muted">Subtotal</span>
                    <span>$ {{ number_format($venta->subtotal, 2, ',', '.') }}</span>
                </div>

                @if ((float) $venta->descuento_promociones > 0)
                <div class="d-flex justify-content-between small mb-2 text-success fw-semibold">
                    <span>Descuentos</span>
                    <span>&minus;$ {{ number_format($venta->descuento_promociones, 2, ',', '.') }}</span>
                </div>
                @endif

                @if ((float) $venta->costo_envio > 0)
                <div class="d-flex justify-content-between small mb-2">
                    <span class="text-muted">Envío</span>
                    <span>$ {{ number_format($venta->costo_envio, 2, ',', '.') }}</span>
                </div>
                @endif

                <hr class="text-muted opacity-25">

                <div class="d-flex justify-content-between align-items-baseline">
                    <span class="h6 fw-bold mb-0">Total</span>
                    <span class="h4 fw-bold mb-0">
                        $ {{ number_format($venta->total, 2, ',', '.') }}
                    </span>
                </div>

                <div class="text-muted small mt-1">
                    IVA incluido: $ {{ number_format($venta->total_iva, 2, ',', '.') }}
                </div>

            </div>

        </div>


        <!-- Entrega -->
        <div class="card shadow-sm border-0 rounded-4 mb-4">

            <div class="card-body p-4">

                <h2 class="h6 fw-bold text-uppercase text-secondary mb-3">
                    Entrega
                </h2>

                <div class="fw-semibold mb-1">
                    {{ $venta->modo_entrega->etiqueta() }}
                </div>

                @if ($venta->modo_entrega === \App\Enums\ModoEntrega::Retiro)

                <p class="text-muted small mb-0">
                    Cuando el pedido esté listo, pasá a buscarlo por el local.
                    Llevá tu DNI y el número de pedido
                    <strong>{{ $venta->numeroFormateado() }}</strong>.
                </p>

                @else

                <p class="text-muted small mb-0">
                    Te vamos a avisar cuando el pedido salga del depósito.
                </p>

                @endif

                @if ($venta->observaciones)

                <hr class="text-muted opacity-25">

                <div class="text-muted small" style="white-space: pre-line;">{{ $venta->observaciones }}</div>

                @endif

            </div>

        </div>


        <!-- Facturación -->
        <div class="card shadow-sm border-0 rounded-4">

            <div class="card-body p-4">

                <h2 class="h6 fw-bold text-uppercase text-secondary mb-3">
                    Facturación
                </h2>

                @if ($factura)

                <dl class="row mb-0 small">

                    <dt class="col-5 text-muted fw-normal">Comprobante</dt>
                    <dd class="col-7 fw-semibold">
                        {{ (int) $factura->tipoComprobante?->signo === 1 ? 'Factura' : 'Nota de crédito' }}
                        {{ $factura->tipoComprobante?->letra?->value }}
                    </dd>

                    <dt class="col-5 text-muted fw-normal">Número</dt>
                    <dd class="col-7 fw-semibold">
                        {{ str_pad((string) ($factura->puntoVenta?->numero ?? 0), 5, '0', STR_PAD_LEFT) }}-{{ str_pad((string) $factura->numero, 8, '0', STR_PAD_LEFT) }}
                    </dd>

                    <dt class="col-5 text-muted fw-normal">Fecha</dt>
                    <dd class="col-7 fw-semibold">
                        {{ $factura->fecha_emision?->format('d/m/Y') }}
                    </dd>

                    {{-- estaAutorizado() ya exige cae no nulo: si hay factura, hay CAE. --}}
                    <dt class="col-5 text-muted fw-normal">CAE</dt>
                    <dd class="col-7 fw-semibold mb-0">
                        {{ $factura->cae }}

                        @if ($factura->cae_vencimiento)
                        <span class="d-block fw-normal text-muted">
                            Vence el {{ $factura->cae_vencimiento->format('d/m/Y') }}
                        </span>
                        @endif
                    </dd>

                </dl>

                @else

                {{--
                    Sin factura autorizada. Puede ser que el pago todavia no se
                    acredito, o que ARCA la rechazo: un rechazo no invalida la
                    compra, se resuelve reintentando desde gestion. En los dos
                    casos el cliente no tiene nada que hacer, asi que el mensaje
                    no lo distingue.
                --}}
                <p class="text-muted small mb-0">
                    La factura de este pedido todavía no está disponible.
                    Si ya pagaste, la vas a ver acá en breve.
                </p>

                @endif

            </div>

        </div>

    </div>

</div>

@endsection
