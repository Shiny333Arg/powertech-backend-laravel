@extends('layouts.gestion')

@section('title', $producto->nombre . ' - PowerTech')

@section('content')

<!-- Navegación / Botón volver -->
<section class="mb-4">
    <a href="{{ url()->previous() != url()->current() ? url()->previous() : route('gestion.items.index') }}"
        class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-arrow-left" viewBox="0 0 16 16">
            <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8" />
        </svg>
        Volver
    </a>
</section>

<!-- Ficha de producto para clientes -->
<div class="row g-4">

    <!-- Columna izquierda: Imagen principal -->
    <div class="col-12 col-md-5 col-lg-5">
        <div class="card shadow-sm rounded-4 h-100 border-0">
            <div class="card-body d-flex align-items-center justify-content-center p-4" style="min-height: 380px;">
                @if ($producto->imagen)
                <img src="{{ asset('storage/' . $producto->imagen) }}"
                    alt="{{ $producto->nombre }}"
                    class="img-fluid rounded-3"
                    style="max-height: 400px; object-fit: contain;"
                    loading="lazy">
                @else
                <img src="{{ asset('images/Sin_Imagen.png') }}"
                    alt="Sin imagen"
                    class="img-fluid opacity-75"
                    style="max-height: 250px; object-fit: contain;"
                    loading="lazy">
                @endif
            </div>
        </div>
    </div>

    <!-- Columna central: Datos del producto y descripción -->
    <div class="col-12 col-md-7 col-lg-4">
        <div class="card shadow-sm rounded-4 border-0 p-3 h-100">
            <div class="card-body d-flex flex-column">

                <!-- Categoría y Marca -->
                <div class="text-muted small mb-2">
                    <span>{{ $producto->categoria?->nombre ?? 'Sin categoría' }}</span>
                    &middot;
                    <span class="fw-semibold">{{ $producto->marca ?: 'Genérica' }}</span>
                </div>

                <!-- Nombre -->
                <h1 class="h3 fw-bold mb-3">
                    {{ $producto->nombre }}
                </h1>

                <hr class="text-muted opacity-25">

                <!-- Lo que tenés que saber / Descripción -->
                <div class="mt-2 flex-grow-1">
                    <h2 class="h6 fw-bold text-uppercase text-secondary mb-3">
                        Descripción del producto
                    </h2>

                    <div class="text-secondary small lh-lg" style="white-space: pre-line;">
                        {{ $producto->descripcion ?: 'No hay descripción detallada disponible para este producto.' }}
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Columna derecha: Precio, Estado de Stock y Acciones -->
    <div class="col-12 col-lg-3">
        <div class="card shadow-sm rounded-4 border p-3">
            <div class="card-body">

                <!-- Precio -->
                <div class="mb-3">
                    <span class="text-muted small d-block">Precio final</span>
                    <span class="display-6 fw-bold text-dark">
                        ${{ number_format($producto->precio ?? 0, 2, ',', '.') }}
                    </span>
                </div>

                <hr class="text-muted opacity-25">

                <!-- Stock Real / Preventa -->
                <div class="mb-4">
                    <span class="text-muted small fw-semibold text-uppercase d-block mb-1">
                        Disponibilidad
                    </span>

                    @if ($producto->stock_fisico > 0)
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge text-bg-success rounded-pill px-3 py-2">
                            En Stock
                        </span>
                        <span class="text-muted small">
                            ({{ $producto->stock_fisico }} {{ $producto->stock_fisico == 1 ? 'unidad disponible' : 'unidades disponibles' }})
                        </span>
                    </div>
                    @else
                    <div class="d-flex flex-column gap-1 mb-2">
                        <div>
                            <span class="badge text-bg-warning rounded-pill px-3 py-2">
                                Preventa
                            </span>
                        </div>
                        <span class="text-muted small">
                            Sin stock físico inmediato. Podés reservar tu unidad por adelantado.
                        </span>
                    </div>
                    @endif
                </div>

                <!-- Botones de Acción -->
                <div class="d-grid gap-2">
                    <button type="button"
                        class="btn btn-primary fw-semibold py-2 d-flex align-items-center justify-content-center gap-2 rounded-3"
                        {{-- Deshabilitado o con acción según lógica comercial --}}>
                        @if ($producto->stock_fisico > 0)
                        Comprar ahora
                        @else
                        Reservar en preventa
                        @endif
                    </button>

                    <button type="button"
                        class="btn btn-outline-secondary fw-semibold py-2 d-flex align-items-center justify-content-center gap-2 rounded-3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cart3" viewBox="0 0 16 16">
                            <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .49.598l-1 5a.5.5 0 0 1-.465.402l-10.5.002-.236.945-.008.032A1.5 1.5 0 0 0 4.5 11h9a.5.5 0 0 1 0 1h-9a2.5 2.5 0 0 1-2.457-2.046L1.015 2H.5a.5.5 0 0 1-.5-.5zM3.14 4l.8 4H13.26l.8-4H3.14zM5.5 12a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm7 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z" />
                        </svg>
                        Agregar al carrito
                    </button>
                </div>

            </div>
        </div>
    </div>

</div>

@endsection