{{--
    Tarjeta de producto de la vitrina.

    Recibe:
      $producto        modelo Producto, con unidades_vendidas si viene del ranking
      $precioConPromo  (opcional) precio final ya calculado por el MotorPromociones.
                       Si viene y es menor al de lista, se muestra tachado el de
                       lista. Si no viene, se muestra solo el precio de lista.
                       Se llama asi y no $precioFinal para no chocar con el
                       $precioFinal que item/show.blade.php define para la ficha:
                       @include hereda el scope del padre.

    Muestra stock_disponible y no el fisico: es el unico valor que se le
    muestra al cliente. Disponible <= 0 no oculta el producto, lo marca como
    preventa, que esta admitida.
--}}

@php
    $disponible = (int) $producto->stock_disponible;
    $enPreventa = $disponible <= 0;

    $precioLista = (float) $producto->precio;
    $precioPromo = isset($precioConPromo) ? (float) $precioConPromo : null;
    $tieneDescuento = $precioPromo !== null && $precioPromo < $precioLista;
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
                @if ($tieneDescuento)

                <div class="mb-2">

                    <span class="text-muted text-decoration-line-through small">
                        $ {{ number_format($precioLista, 2, ',', '.') }}
                    </span>

                    <div class="h5 fw-bold text-success mb-0">
                        $ {{ number_format($precioPromo, 2, ',', '.') }}
                    </div>

                </div>

                @else

                <div class="h5 fw-bold mb-2">
                    $ {{ number_format($precioLista, 2, ',', '.') }}
                </div>

                @endif
                @endauth

                <a href="{{ route('cliente.items.show', $producto) }}" class="btn btn-sm btn-primary w-100 fw-semibold">
                    Ver producto
                </a>

            </div>

        </div>

    </div>

</div>
