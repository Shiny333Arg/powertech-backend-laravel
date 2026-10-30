{{--
    Tarjeta de producto de la vitrina.

    Recibe:
      $producto  modelo Producto, con unidades_vendidas si viene del ranking

    Muestra stock_disponible y no el fisico: es el unico valor que se le
    muestra al cliente. Disponible <= 0 no oculta el producto, lo marca como
    preventa, que esta admitida.
--}}

@php
$disponible = (int) $producto->stock_disponible;
$enPreventa = $disponible <= 0;
    @endphp

    <div class="col-6 col-md-4 col-lg-3">

    <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden">

        <!-- Imagen -->
        <div class="ratio ratio-4x3 bg-white">

            @if ($producto->imagen)

            <img
                src="{{ asset('storage/'.$producto->imagen) }}"
                alt="{{ $producto->nombre }}"
                class="w-100 h-100 p-3"
                style="object-fit: contain;"
                loading="lazy">

            @else

            <div class="d-flex align-items-center justify-content-center text-muted small bg-light">
                Sin imagen
            </div>

            @endif

        </div>


        <div class="card-body d-flex flex-column p-3">

            @if ($producto->marca)
            <div class="text-uppercase text-muted small fw-semibold mb-1">
                {{ $producto->marca }}
            </div>
            @endif

            <h3 class="h6 fw-semibold mb-2 lh-sm">
                {{ $producto->nombre }}
            </h3>

            <div class="mb-2">

                @if ($enPreventa)

                <span class="badge text-bg-warning">
                    Preventa
                </span>

                @else

                <span class="badge text-bg-success">
                    Disponible
                </span>

                @endif

            </div>

            <div class="mt-auto">

                @auth
                <div class="h5 fw-bold mb-2">
                    $ {{ number_format($producto->precio, 2, ',', '.') }}
                </div>
                @endauth

                {{-- TODO: apuntar a la ficha del producto cuando exista la ruta de tienda --}}
                <a href="{{ route('cliente.items.show', $producto) }}" class="btn btn-sm btn-primary w-100 fw-semibold">
                    Ver producto
                </a>

            </div>

        </div>

    </div>

    </div>