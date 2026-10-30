@extends('layouts.cliente')

@section('title', 'Mi carrito')

@section('content')

<div class="container py-4 py-lg-5">

    @php
        $cantidadProductos = $carrito->lineas->sum('cantidad');
    @endphp

    {{-- Encabezado --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h1 class="fw-bold mb-1">Mi carrito</h1>
            <p class="text-muted mb-0">
                Revisá tus productos antes de continuar.
            </p>
        </div>
    </div>


    @if ($carrito->lineas->isEmpty())

        {{-- Carrito vacío --}}
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body text-center py-5 px-3">

                <h4 class="fw-bold mb-2">Tu carrito está vacío</h4>

                <p class="text-muted mb-4">
                    Todavía no agregaste productos. Explorá nuestra tienda y encontrá lo que necesitás.
                </p>

                <a href="{{ route('cliente.home') }}"
                   class="btn btn-primary rounded-3 px-4 py-2 fw-semibold">
                    <i class="bi bi-shop me-2"></i>
                    Explorar productos
                </a>

            </div>
        </div>

    @else

        <div class="row g-4">

            {{-- Lista de productos --}}
            <div class="col-lg-8">

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">Productos seleccionados</h5>
                    <span class="text-muted small">
                        {{ $carrito->lineas->count() }} artículos diferentes
                    </span>
                </div>

                @foreach ($carrito->lineas as $linea)

                    @php
                        $producto = $linea->producto;
                        $precio = (float) ($precios[$producto->id] ?? 0);
                        $disponible = (int) $producto->stock_disponible;
                        $enPreventa = $disponible < $linea->cantidad;
                    @endphp

                    <div class="card border-0 shadow-sm rounded-4 mb-3">
                        <div class="card-body p-3 p-md-4">

                            <div class="row align-items-center g-3">

                                {{-- Imagen --}}
                                <div class="col-4 col-sm-3 col-md-3">

                                    <a href="{{ route('cliente.items.show', $producto) }}"
                                       class="d-block bg-light rounded-3 overflow-hidden">

                                        <div class="ratio ratio-1x1">

                                            @if ($producto->imagen)
                                                <img
                                                    src="{{ asset('storage/'.$producto->imagen) }}"
                                                    alt="{{ $producto->nombre }}"
                                                    class="w-100 h-100 p-2"
                                                    style="object-fit: contain;"
                                                    loading="lazy">
                                            @else
                                                <div class="d-flex align-items-center justify-content-center text-muted">
                                                    <i class="bi bi-image fs-2"></i>
                                                </div>
                                            @endif

                                        </div>
                                    </a>

                                </div>

                                {{-- Información del producto --}}
                                <div class="col-8 col-sm-9 col-md-9">

                                    <div class="d-flex justify-content-between align-items-start gap-2">

                                        <div class="flex-grow-1">

                                            @if ($producto->marca)
                                                <span class="text-muted text-uppercase fw-semibold"
                                                      style="font-size: 0.7rem; letter-spacing: 0.7px;">
                                                    {{ $producto->marca }}
                                                </span>
                                            @endif

                                            <a href="{{ route('cliente.items.show', $producto) }}"
                                               class="d-block text-decoration-none text-dark fw-bold mb-1">
                                                {{ $producto->nombre }}
                                            </a>

                                            <div class="text-muted small">
                                                $ {{ number_format($precio, 2, ',', '.') }} por unidad
                                            </div>

                                            @if ($enPreventa)
                                                <span class="badge bg-warning-subtle text-warning-emphasis rounded-pill mt-2">
                                                    <i class="bi bi-clock-history me-1"></i>
                                                    Preventa
                                                </span>
                                            @endif

                                        </div>

                                        {{-- Subtotal --}}
                                        <div class="text-end d-none d-md-block">
                                            <span class="text-muted small d-block">Subtotal</span>
                                            <span class="fw-bold fs-5 text-nowrap">
                                                $ {{ number_format($precio * $linea->cantidad, 2, ',', '.') }}
                                            </span>
                                        </div>

                                    </div>

                                    <hr class="my-3">

                                    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3">

                                        {{-- Actualizar cantidad --}}
                                        <form method="POST"
                                              action="{{ route('cliente.carrito.update', $linea) }}"
                                              class="d-flex align-items-end gap-2">

                                            @csrf
                                            @method('PATCH')

                                            <div>
                                                <label for="cantidad-{{ $linea->id }}"
                                                       class="form-label small text-muted fw-semibold mb-1">
                                                    Cantidad
                                                </label>

                                                <input
                                                    type="number"
                                                    name="cantidad"
                                                    id="cantidad-{{ $linea->id }}"
                                                    class="form-control form-control-sm rounded-3 @error('cantidad') is-invalid @enderror"
                                                    style="width: 75px;"
                                                    value="{{ old('cantidad', $linea->cantidad) }}"
                                                    min="1"
                                                    max="99"
                                                    required>

                                                @error('cantidad')
                                                    <div class="invalid-feedback">
                                                        {{ $message }}
                                                    </div>
                                                @enderror
                                            </div>

                                            <button type="submit"
                                                    class="btn btn-sm btn-outline-primary rounded-3">
                                                <i class="bi bi-arrow-repeat me-1"></i>
                                                Actualizar
                                            </button>

                                        </form>

                                        {{-- Eliminar --}}
                                        <form method="POST"
                                              action="{{ route('cliente.carrito.destroy', $linea) }}">

                                            @csrf
                                            @method('DELETE')

                                            <button type="submit"
                                                    class="btn btn-sm btn-link text-danger text-decoration-none px-0">
                                                <i class="bi bi-trash3 me-1"></i>
                                                Quitar
                                            </button>

                                        </form>

                                    </div>

                                    {{-- Subtotal en móviles --}}
                                    <div class="d-md-none mt-3 pt-2 border-top d-flex justify-content-between align-items-center">
                                        <span class="text-muted small">Subtotal</span>
                                        <span class="fw-bold">
                                            $ {{ number_format($precio * $linea->cantidad, 2, ',', '.') }}
                                        </span>
                                    </div>

                                </div>

                            </div>

                        </div>
                    </div>

                @endforeach

            </div>


            {{-- Resumen de compra --}}
            <div class="col-lg-4">

                <div class="card border-0 shadow-sm rounded-4 sticky-top"
                     style="top: 100px;">

                    <div class="card-body p-4">

                        <h5 class="fw-bold mb-4">
                            Resumen de compra
                        </h5>

                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted">Productos</span>
                            <span class="fw-semibold">
                                {{ $cantidadProductos }}
                            </span>
                        </div>

                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted">Subtotal</span>
                            <span class="fw-semibold">
                                $ {{ number_format($total, 2, ',', '.') }}
                            </span>
                        </div>

                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted">Envío</span>
                            <span class="text-muted small">A calcular</span>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold">Total</span>
                            <span class="fs-4 fw-bold text-primary text-nowrap">
                                $ {{ number_format($total, 2, ',', '.') }}
                            </span>
                        </div>

                        <p class="text-muted small mb-4">
                            Los precios y promociones corresponden a los valores vigentes.
                            El costo de envío se calculará al elegir cómo recibir tu pedido.
                        </p>

                        {{-- Checkout pendiente --}}
                        <button type="button"
                                class="btn btn-primary w-100 fw-semibold rounded-3 py-2"
                                disabled>
                            <i class="bi bi-lock me-2"></i>
                            Finalizar compra
                        </button>

                        <div class="text-center mt-3">
                            <small class="text-muted">
                                <i class="bi bi-shield-check me-1"></i>
                                Compra segura en PowerTech
                            </small>
                        </div>

                    </div>

                </div>

            </div>

        </div>

    @endif

</div>

@endsection
