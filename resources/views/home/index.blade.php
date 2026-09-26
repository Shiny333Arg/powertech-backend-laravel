@extends('layouts.cliente')

@section('title', 'Inicio')

@section('content')

<!-- Carrusel de promociones -->

@if ($promociones->isNotEmpty())



<!-- Saludo -->
<section class="w-100 mb-4 container-fluid">
    <h5 class="mb-0 fw-bold">
        @auth
        Hola {{ auth()->user()->apellido }}, {{ auth()->user()->nombres }}
        @else
        Bienvenido a PowerTech
        @endauth
    </h5>
</section>



<section class="mb-5">

    <div
        id="carruselPromos"
        class="carousel slide rounded-4 overflow-hidden shadow-sm"
        data-bs-ride="carousel">

        @if ($promociones->count() > 1)

        <div class="carousel-indicators">

            @foreach ($promociones as $i => $promocion)

            <button
                type="button"
                data-bs-target="#carruselPromos"
                data-bs-slide-to="{{ $i }}"
                @class(['active'=> $i === 0])
                @if ($i === 0) aria-current="true" @endif
                aria-label="Promoción {{ $i + 1 }}">
            </button>

            @endforeach

        </div>

        @endif


        <div class="carousel-inner">

            @foreach ($promociones as $i => $promocion)

            <div @class(['carousel-item', 'active'=> $i === 0])>

                <div class="ratio" style="--bs-aspect-ratio: 33%;">

                    <img
                        src="{{ asset('storage/'.$promocion->banner) }}"
                        alt="{{ $promocion->nombre }}"
                        class="w-100 h-100"
                        style="object-fit: cover;">

                </div>

                <div class="carousel-caption d-none d-md-block text-start bg-dark bg-opacity-50 rounded-3 p-3">

                    <h2 class="h4 fw-bold mb-1">
                        {{ $promocion->nombre }}
                    </h2>

                    @if ($promocion->descripcion)
                    <p class="mb-0 small">
                        {{ $promocion->descripcion }}
                    </p>
                    @endif

                </div>

            </div>

            @endforeach

        </div>


        @if ($promociones->count() > 1)

        <button
            class="carousel-control-prev"
            type="button"
            data-bs-target="#carruselPromos"
            data-bs-slide="prev">

            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Anterior</span>

        </button>

        <button
            class="carousel-control-next"
            type="button"
            data-bs-target="#carruselPromos"
            data-bs-slide="next">

            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Siguiente</span>

        </button>

        @endif

    </div>

</section>

@endif





<!-- Periféricos -->

<section class="mb-5">

    <div class="d-flex align-items-baseline justify-content-between mb-3">

        <h2 class="h4 fw-bold mb-0">
            Periféricos más vendidos
        </h2>

        <span class="text-muted small">
            Teclados, mouses, parlantes y auriculares
        </span>

    </div>

    @if ($perifericos->isEmpty())

    <p class="text-muted">
        Todavía no hay productos publicados en estas categorías.
    </p>

    @else

    <div class="row g-3">

        @foreach ($perifericos as $producto)
        @include('home.partials.tarjeta-producto', ['producto' => $producto])
        @endforeach

    </div>

    @endif

</section>


<!-- Componentes -->

<section class="mb-5">

    <div class="d-flex align-items-baseline justify-content-between mb-3">

        <h2 class="h4 fw-bold mb-0">
            Componentes más vendidos
        </h2>

        <span class="text-muted small">
            Procesadores y placas de video
        </span>

    </div>

    @if ($componentes->isEmpty())

    <p class="text-muted">
        Todavía no hay productos publicados en estas categorías.
    </p>

    @else

    <div class="row g-3">

        @foreach ($componentes as $producto)
        @include('home.partials.tarjeta-producto', ['producto' => $producto])
        @endforeach

    </div>

    @endif

</section>


<!-- Monitores -->

<section class="mb-5">

    <div class="d-flex align-items-baseline justify-content-between mb-3">

        <h2 class="h4 fw-bold mb-0">
            Monitores más vendidos
        </h2>

    </div>

    @if ($monitores->isEmpty())

    <p class="text-muted">
        Todavía no hay monitores publicados.
    </p>

    @else

    <div class="row g-3">

        @foreach ($monitores as $producto)
        @include('home.partials.tarjeta-producto', ['producto' => $producto])
        @endforeach

    </div>

    @endif

</section>

@endsection