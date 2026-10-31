{{--
    Checkout. Resumen previo al pago.

    Recibe:
      $carrito              modelo Carrito en estado en_checkout, con lineas.producto cargado
      $cliente              modelo Cliente del usuario autenticado (datos de facturacion)
      $precios              Collection<int, float> producto_id => precio unitario con promociones
      $total                float
      $problemaFacturacion  ?string, motivo por el que todavia no se puede facturar

    Si $problemaFacturacion no es null, el boton de pago no se renderiza: se
    avisa ANTES de cobrar. Descubrirlo al emitir el comprobante significa
    revertir una venta ya cobrada.

    El detalle es de SOLO LECTURA: para cambiar cantidades se vuelve al
    carrito. Un formulario que permita editar acá obliga a recalcular el
    importe a cobrar entre que se muestra y se paga.

    Solo retiro en el local: el circuito de envio y tracking no esta
    implementado. El selector existe igual, con una sola opcion, para que el
    formulario no cambie de forma cuando se habiliten los envios.
--}}

@extends('layouts.cliente')

@section('title', 'Confirmar pedido')

@section('content')

<div class="row justify-content-center">

    <div class="col-lg-10">

        <h1 class="h3 fw-bold mb-1">Confirmar pedido</h1>

        <p class="text-muted mb-4">
            Revisá el detalle antes de pagar. El pedido se aparta recién cuando se acredita el pago.
        </p>


        <div class="row g-4">


            <!-- Detalle y datos -->
            <div class="col-12 col-lg-7">


                <!-- Qué llevás -->
                <div class="card shadow-sm border-0 rounded-4 mb-4">

                    <div class="card-body p-4">

                        <div class="d-flex align-items-baseline justify-content-between mb-3">

                            <h2 class="h5 fw-bold mb-0">Tu pedido</h2>

                            <a href="{{ route('cliente.carrito.index') }}" class="small fw-semibold text-decoration-none">
                                Modificar
                            </a>

                        </div>

                        @foreach ($carrito->lineas as $linea)

                        @php
                            $producto   = $linea->producto;
                            $precio     = (float) ($precios[$producto->id] ?? 0);
                            $enPreventa = (int) $producto->stock_disponible < $linea->cantidad;
                        @endphp

                        <div class="d-flex align-items-start gap-3 @unless ($loop->last) border-bottom pb-3 mb-3 @endunless">

                            <div class="flex-shrink-0" style="width: 56px;">

                                <div class="ratio ratio-1x1 bg-white rounded-3 overflow-hidden border">

                                    @if ($producto->imagen)

                                    <img src="{{ asset('storage/'.$producto->imagen) }}"
                                        alt="{{ $producto->nombre }}"
                                        class="w-100 h-100 p-1"
                                        style="object-fit: contain;"
                                        loading="lazy">

                                    @else

                                    <div class="d-flex align-items-center justify-content-center bg-light"></div>

                                    @endif

                                </div>

                            </div>

                            <div class="flex-grow-1">

                                <div class="fw-semibold lh-sm">
                                    {{ $producto->nombre }}
                                </div>

                                <div class="text-muted small">
                                    {{ $linea->cantidad }} &times; $ {{ number_format($precio, 2, ',', '.') }}
                                </div>

                                @if ($enPreventa)
                                <span class="badge text-bg-warning rounded-pill mt-1">
                                    Preventa
                                </span>
                                @endif

                            </div>

                            <div class="fw-bold text-end">
                                $ {{ number_format($precio * $linea->cantidad, 2, ',', '.') }}
                            </div>

                        </div>

                        @endforeach

                    </div>

                </div>


                <!-- Datos de facturación -->
                <div class="card shadow-sm border-0 rounded-4 mb-4">

                    <div class="card-body p-4">

                        <div class="d-flex align-items-baseline justify-content-between mb-3">

                            <h2 class="h5 fw-bold mb-0">Datos de facturación</h2>

                            <a href="{{ route('profile.edit') }}" class="small fw-semibold text-decoration-none">
                                Editar
                            </a>

                        </div>

                        @if ($problemaFacturacion)

                        <div class="alert alert-warning d-flex flex-wrap align-items-center gap-2 small" role="alert">

                            <span>{{ $problemaFacturacion }}</span>

                            <a href="{{ route('profile.edit') }}" class="btn btn-sm btn-outline-dark ms-md-auto">
                                Ir a mi perfil
                            </a>

                        </div>

                        @endif

                        <dl class="row mb-0 small">

                            <dt class="col-5 col-sm-4 text-muted fw-normal">Razón social</dt>
                            <dd class="col-7 col-sm-8 fw-semibold">
                                {{ $cliente->razon_social ?: auth()->user()->apellido.', '.auth()->user()->nombres }}
                            </dd>

                            <dt class="col-5 col-sm-4 text-muted fw-normal">Documento</dt>
                            <dd class="col-7 col-sm-8 fw-semibold">
                                {{ $cliente->tipoDocumento?->nombre }} {{ $cliente->nro_doc }}
                            </dd>

                            <dt class="col-5 col-sm-4 text-muted fw-normal">Condición IVA</dt>
                            <dd class="col-7 col-sm-8 fw-semibold mb-0">
                                {{ $cliente->condicionIva?->nombre }}
                            </dd>

                        </dl>

                    </div>

                </div>

            </div>


            <!-- Confirmación -->
            <div class="col-12 col-lg-5">

                <form method="POST" action="{{ route('cliente.checkout.store') }}"
                    class="card shadow-sm border rounded-4">

                    @csrf

                    <div class="card-body p-4">


                        <!-- Modo de entrega -->
                        <h2 class="h6 fw-bold text-uppercase text-secondary mb-3">
                            Cómo lo recibís
                        </h2>

                        <div class="form-check border rounded-3 p-3 mb-2 bg-light">

                            <input type="radio"
                                name="modo_entrega"
                                id="modo-retiro"
                                value="retiro"
                                class="form-check-input"
                                checked
                                required>

                            <label for="modo-retiro" class="form-check-label d-block ms-1">

                                <span class="fw-semibold d-block">Retiro en el local</span>

                                <span class="text-muted small">
                                    Te avisamos cuando esté preparado. Llevá tu DNI y el número de pedido.
                                </span>

                            </label>

                        </div>

                        <div class="form-check border rounded-3 p-3 mb-3 opacity-50">

                            <input type="radio" class="form-check-input" disabled>

                            <label class="form-check-label d-block ms-1">

                                <span class="fw-semibold d-block">Envío a domicilio</span>

                                <span class="text-muted small">Todavía no disponible.</span>

                            </label>

                        </div>

                        @error('modo_entrega')
                        <div class="text-danger small mb-3">{{ $message }}</div>
                        @enderror


                        <hr class="text-muted opacity-25">


                        <!-- Observaciones -->
                        <div class="mb-3">

                            <label for="observaciones" class="form-label small fw-semibold text-uppercase text-muted">
                                Aclaraciones <span class="fw-normal text-lowercase">(opcional)</span>
                            </label>

                            <textarea name="observaciones"
                                id="observaciones"
                                rows="2"
                                maxlength="500"
                                class="form-control rounded-3 @error('observaciones') is-invalid @enderror"
                                placeholder="Algo que debamos saber sobre tu pedido">{{ old('observaciones') }}</textarea>

                            @error('observaciones')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror

                        </div>


                        <hr class="text-muted opacity-25">


                        <!-- Total -->
                        <div class="d-flex justify-content-between align-items-baseline mb-1">

                            <span class="h6 fw-bold mb-0">Total a pagar</span>

                            <span class="h3 fw-bold mb-0">
                                $ {{ number_format($total, 2, ',', '.') }}
                            </span>

                        </div>

                        <p class="text-muted small mb-3">
                            Retiro en el local: sin costo de envío.
                        </p>


                        {{--
                            Sin datos fiscales facturables no se ofrece pagar:
                            el comprobante fallaria despues de cobrar.
                        --}}
                        @if ($problemaFacturacion)

                        <div class="d-grid gap-2">

                            <a href="{{ route('profile.edit') }}"
                                class="btn btn-primary fw-semibold py-2 rounded-3">
                                Completar mis datos de facturación
                            </a>

                            <a href="{{ route('cliente.carrito.index') }}"
                                class="btn btn-outline-secondary fw-semibold py-2 rounded-3">
                                Volver al carrito
                            </a>

                        </div>

                        @else

                        <!-- Conformidad -->
                        <div class="form-check mb-3">

                            <input type="checkbox"
                                name="confirma"
                                id="confirma"
                                value="1"
                                class="form-check-input @error('confirma') is-invalid @enderror"
                                required>

                            <label for="confirma" class="form-check-label small">
                                Confirmo el pedido y los datos de facturación.
                            </label>

                            @error('confirma')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror

                        </div>

                        <div class="d-grid gap-2">

                            <button type="submit" class="btn btn-primary fw-semibold py-2 rounded-3">
                                Pagar y confirmar pedido
                            </button>

                            <a href="{{ route('cliente.carrito.index') }}"
                                class="btn btn-outline-secondary fw-semibold py-2 rounded-3">
                                Volver al carrito
                            </a>

                        </div>

                        @endif

                    </div>

                </form>

            </div>


        </div>

    </div>

</div>

@endsection
