{{--
    Ficha de producto de la tienda.

    Recibe:
      $producto      modelo Producto (publicado), con categoria cargada
      $precioFinal   float, precio unitario ya con promociones aplicadas
      $promociones   Collection<Promocion> que alcanzan a este producto (puede venir vacía)
      $relacionados  Collection<Producto> de la misma categoría (puede venir vacía)

    Dos roles, una sola vista:
      INVITADO  ve foto, datos, descripción y disponibilidad. NO ve precio ni
                bloque de compra: ninguno de los dos se renderiza.
      CLIENTE   ve todo. El bloque de compra exige además correo confirmado;
                el aviso de "confirmá tu correo" ya lo pone layouts.cliente.

    Muestra stock_disponible y no el fisico: es el unico valor que se le muestra
    al cliente. Disponible <= 0 no oculta el producto, lo marca como preventa,
    que esta admitida (Requerimientos 3.5 y 5.1).
--}}

@extends('layouts.cliente')

@section('title', $producto->nombre)

@section('content')

@php
    $disponible = (int) $producto->stock_disponible;
    $enPreventa = $disponible <= 0;

    $precioLista  = (float) $producto->precio;
    $precioFinal  = (float) ($precioFinal ?? $precioLista);
    $hayDescuento = $precioFinal < $precioLista && $precioLista > 0;
    $porcentaje   = $hayDescuento ? (int) round(100 - ($precioFinal * 100 / $precioLista)) : 0;

    $esCliente   = auth()->check() && auth()->user()->esCliente();
    $puedeComprar = $esCliente && auth()->user()->hasVerifiedEmail();
@endphp


<!-- Volver -->
<section class="mb-4">

    <a href="{{ url()->previous() != url()->current() ? url()->previous() : route('cliente.home') }}"
        class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">

        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-arrow-left" viewBox="0 0 16 16">
            <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8" />
        </svg>
        Volver

    </a>

</section>


<div class="row g-4">


    <!-- Imagen -->
    <div class="col-12 col-md-5 col-lg-5">

        <div class="card shadow-sm rounded-4 h-100 border-0">

            <div class="card-body d-flex align-items-center justify-content-center p-4" style="min-height: 380px;">

                @if ($producto->imagen)

                <img src="{{ asset('storage/'.$producto->imagen) }}"
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


    <!-- Datos y descripción -->
    <div class="col-12 col-md-7 col-lg-4">

        <div class="card shadow-sm rounded-4 border-0 p-3 h-100">

            <div class="card-body d-flex flex-column">

                <div class="text-muted small mb-2">
                    <span>{{ $producto->categoria?->nombre ?? 'Sin categoría' }}</span>
                    &middot;
                    <span class="fw-semibold">{{ $producto->marca ?: 'Genérica' }}</span>
                </div>

                <h1 class="h3 fw-bold mb-2">
                    {{ $producto->nombre }}
                </h1>

                @if ($producto->codigo)
                <div class="text-muted small mb-3">
                    Código {{ $producto->codigo }}
                </div>
                @endif

                <hr class="text-muted opacity-25">

                <div class="mt-2 flex-grow-1">

                    <h2 class="h6 fw-bold text-uppercase text-secondary mb-3">
                        Descripción del producto
                    </h2>

                    {{-- Las características técnicas no se modelan: van acá adentro. --}}
                    <div class="text-secondary small lh-lg" style="white-space: pre-line;">
                        {{ $producto->descripcion ?: 'No hay descripción detallada disponible para este producto.' }}
                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- Precio, disponibilidad y compra -->
    <div class="col-12 col-lg-3">

        <div class="card shadow-sm rounded-4 border p-3">

            <div class="card-body">


                {{-- PRECIO: solo autenticados, igual que la tarjeta de la vitrina. --}}
                @auth

                <div class="mb-3">

                    <span class="text-muted small d-block">Precio final</span>

                    <span class="display-6 fw-bold text-dark">
                        $ {{ number_format($precioFinal, 2, ',', '.') }}
                    </span>

                    @if ($hayDescuento)

                    <div class="d-flex align-items-center gap-2 mt-1">

                        <span class="text-muted text-decoration-line-through">
                            $ {{ number_format($precioLista, 2, ',', '.') }}
                        </span>

                        <span class="badge text-bg-success rounded-pill">
                            {{ $porcentaje }}% OFF
                        </span>

                    </div>

                    @endif

                </div>

                @if (! empty($promociones) && count($promociones))

                <div class="mb-3">

                    @foreach ($promociones as $promocion)
                    <div class="small fw-semibold text-success">
                        {{ $promocion->nombre }}
                    </div>
                    @endforeach

                </div>

                @endif

                <hr class="text-muted opacity-25">

                @endauth


                <!-- Disponibilidad -->
                {{-- Esto sí lo ve el invitado: es requerimiento explícito (3.5). --}}
                <div class="mb-4">

                    <span class="text-muted small fw-semibold text-uppercase d-block mb-1">
                        Disponibilidad
                    </span>

                    @if ($enPreventa)

                    <div class="d-flex flex-column gap-1 mb-2">

                        <div>
                            <span class="badge text-bg-warning rounded-pill px-3 py-2">
                                Preventa
                            </span>
                        </div>

                        <span class="text-muted small">
                            Sin stock inmediato. Podés reservar tu unidad por adelantado y te la entregamos cuando llegue la reposición.
                        </span>

                    </div>

                    @else

                    <div class="d-flex align-items-center gap-2 mb-2">

                        <span class="badge text-bg-success rounded-pill px-3 py-2">
                            Disponible
                        </span>

                        <span class="text-muted small">
                            ({{ $disponible }} {{ $disponible == 1 ? 'unidad' : 'unidades' }})
                        </span>

                    </div>

                    @endif

                </div>


                {{--
                    BLOQUE DE COMPRA
                    Solo para el rol cliente con correo confirmado. El invitado no
                    ve cantidad, ni botón, ni aviso: el bloque entero no existe.
                --}}
                @if ($puedeComprar)

                <form action="{{ route('cliente.carrito.store') }}" method="POST">

                    @csrf

                    <input type="hidden" name="producto_id" value="{{ $producto->id }}">

                    <div class="mb-3">

                        <label for="cantidad" class="form-label small fw-semibold text-uppercase text-muted">
                            Cantidad
                        </label>

                        <input type="number"
                            name="cantidad"
                            id="cantidad"
                            class="form-control @error('cantidad') is-invalid @enderror"
                            {{--
                                Sin tope por stock: se admite preventa, asi que
                                pedir mas unidades de las que hay es legitimo.
                                El 99 es un freno contra el tipeo.
                            --}}
                            value="{{ old('cantidad', 1) }}"
                            min="1"
                            max="99"
                            required>

                        @error('cantidad')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                        @enderror

                    </div>

                    <div class="d-grid gap-2">

                        <button type="submit"
                            class="btn btn-primary fw-semibold py-2 d-flex align-items-center justify-content-center gap-2 rounded-3">

                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cart3" viewBox="0 0 16 16">
                                <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .49.598l-1 5a.5.5 0 0 1-.465.402l-10.5.002-.236.945-.008.032A1.5 1.5 0 0 0 4.5 11h9a.5.5 0 0 1 0 1h-9a2.5 2.5 0 0 1-2.457-2.046L1.015 2H.5a.5.5 0 0 1-.5-.5z" />
                                <path d="M3.14 4l.8 4H13.26l.8-4H3.14zM5.5 12a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm7 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z" />
                            </svg>

                            @if ($enPreventa)
                            Reservar en preventa
                            @else
                            Agregar al carrito
                            @endif

                        </button>

                    </div>

                </form>

                @endif


            </div>

        </div>

    </div>

</div>


<!-- Relacionados -->
@if (! empty($relacionados) && count($relacionados))

<section class="mt-5">

    <div class="d-flex align-items-baseline justify-content-between mb-3">

        <h2 class="h4 fw-bold mb-0">
            También te puede interesar
        </h2>

        <span class="text-muted small">
            Más de {{ $producto->categoria?->nombre }}
        </span>

    </div>

    <div class="row g-3">

        @foreach ($relacionados as $relacionado)
        @include('home.partials.tarjeta-producto', ['producto' => $relacionado])
        @endforeach

    </div>

</section>

@endif

@endsection
