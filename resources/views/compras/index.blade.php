{{--
    Mis compras. Historial del cliente.

    Recibe:
      $compras  LengthAwarePaginator<Venta>, con lineas_count

    Incluye los dos canales: una compra en el mostrador tambien es una compra
    del cliente. No incluye presupuestos, que no son compras.
--}}

@extends('layouts.cliente')

@section('title', 'Mis compras')

@section('content')

<div class="row justify-content-center">

    <div class="col-lg-10">

        <h1 class="h3 fw-bold mb-1">Mis compras</h1>

        <p class="text-muted mb-4">
            Todos tus pedidos, del más reciente al más antiguo.
        </p>


        @if ($compras->isEmpty())


        <!-- Sin compras -->
        <div class="card shadow-sm border-0 rounded-4">

            <div class="card-body text-center p-5">

                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" class="bi bi-bag-check text-muted opacity-50 mb-3" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M10.854 8.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 0 1 .708-.708L7.5 10.793l2.646-2.647a.5.5 0 0 1 .708 0z" />
                    <path d="M8 1a2.5 2.5 0 0 1 2.5 2.5V4h-5v-.5A2.5 2.5 0 0 1 8 1zm3.5 3v-.5a3.5 3.5 0 1 0-7 0V4H1v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V4h-3.5zM2 5h12v9a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V5z" />
                </svg>

                <h2 class="h5 fw-bold mb-2">Todavía no hiciste ninguna compra</h2>

                <p class="text-muted mb-4">
                    Cuando compres, acá vas a poder seguir el estado de tus pedidos.
                </p>

                <a href="{{ route('cliente.home') }}" class="btn btn-primary fw-semibold px-4">
                    Ver productos
                </a>

            </div>

        </div>


        @else


        @foreach ($compras as $compra)

        <a href="{{ route('cliente.compras.show', $compra) }}"
            class="card shadow-sm border-0 rounded-4 mb-3 text-decoration-none text-reset">

            <div class="card-body p-4">

                <div class="row g-3 align-items-center">


                    <!-- Pedido y fecha -->
                    <div class="col-12 col-md-4">

                        <div class="fw-bold h6 mb-1">
                            Pedido {{ $compra->numeroFormateado() }}
                        </div>

                        <div class="text-muted small">
                            {{ $compra->fecha?->format('d/m/Y') }}

                            @if ($compra->canal === \App\Enums\CanalVenta::Mostrador)
                            &middot; <span>Compra en el local</span>
                            @endif

                        </div>

                    </div>


                    <!-- Estado -->
                    <div class="col-7 col-md-4">

                        @include('compras.partials.estado', ['estado' => $compra->estado])

                        <div class="text-muted small mt-1">
                            {{ $compra->modo_entrega->etiqueta() }}
                        </div>

                    </div>


                    <!-- Total -->
                    <div class="col-5 col-md-4 text-md-end">

                        <div class="h5 fw-bold mb-0">
                            $ {{ number_format($compra->total, 2, ',', '.') }}
                        </div>

                        <div class="text-muted small">
                            {{ $compra->lineas_count }}
                            {{ $compra->lineas_count === 1 ? 'producto' : 'productos' }}
                        </div>

                    </div>


                </div>

            </div>

        </a>

        @endforeach


        @if ($compras->hasPages())
        <div class="mt-4">
            {{ $compras->onEachSide(2)->links() }}
        </div>
        @endif


        @endif

    </div>

</div>

@endsection
