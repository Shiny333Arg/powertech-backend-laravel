{{--
    Resultados de busqueda del catalogo.

    Recibe:
      $productos   LengthAwarePaginator<Producto>  24 por pagina (grilla 4x6)
      $filtros     array con los filtros ya saneados ('orden' es un OrdenCatalogo)
      $categorias  Collection<Categoria>  solo las que tienen productos publicados
      $marcas      Collection<string>
      $ordenes     array<string,string>   value => etiqueta
      $promocion   ?Promocion             si se llego desde un banner
      $precios     array<int,float>       producto_id => precio final con promo

    Ojo con el nombre de la variable que se le pasa a la tarjeta: se llama
    $precioConPromo y no $precioFinal porque @include hereda el scope del
    padre, y item/show.blade.php ya define $precioFinal para el producto de la
    ficha. Si la tarjeta leyera ese nombre, los relacionados de la ficha
    mostrarian el precio del producto principal.

    La grilla es 4x6 en lg+ (el panel de filtros ocupa 3 columnas y las tarjetas
    col-lg-3 dentro del area restante dan 4 por fila). En md baja a 3 por fila
    con los filtros arriba, y en mobile a 2.
--}}

@extends('layouts.cliente')

@section('title', $filtros['q'] ? 'Resultados para "'.$filtros['q'].'"' : 'Productos')

@section('content')

    {{-- Encabezado --}}
    <div class="mb-4">

        <h1 class="h4 fw-bold mb-1">
            @if ($promocion)
                {{ $promocion->nombre }}
            @elseif ($filtros['q'])
                Resultados para "{{ $filtros['q'] }}"
            @else
                Productos
            @endif
        </h1>

        <p class="text-muted small mb-0">
            {{ $productos->total() }}
            {{ \Illuminate\Support\Str::plural('producto', $productos->total()) }}
            @if ($productos->hasPages())
                &middot; página {{ $productos->currentPage() }} de {{ $productos->lastPage() }}
            @endif
        </p>

        @if ($promocion && $promocion->descripcion)
            <p class="text-muted small mt-2 mb-0">{{ $promocion->descripcion }}</p>
        @endif

    </div>


    <div class="row g-4">

        {{-- Panel de filtros --}}
        <div class="col-12 col-lg-3">
            @include('item.partials.filtros')
        </div>


        {{-- Grilla --}}
        <div class="col-12 col-lg-9">

            @if ($productos->isEmpty())

                <div class="text-center bg-light rounded-4 py-5 px-3">

                    <h2 class="h6 fw-semibold mb-2">No encontramos productos</h2>

                    <p class="text-muted small mb-3">
                        Probá con menos filtros o revisá si el nombre está bien escrito.
                    </p>

                    <a href="{{ route('cliente.items.index') }}" class="btn btn-sm btn-outline-primary fw-semibold">
                        Ver todo el catálogo
                    </a>

                </div>

            @else

                <div class="row g-3">

                    @foreach ($productos as $producto)
                        @include('home.partials.tarjeta-producto', [
                            'producto'       => $producto,
                            'precioConPromo' => $precios[$producto->id] ?? null,
                        ])
                    @endforeach

                </div>

                @if ($productos->hasPages())
                    <div class="mt-4 d-flex justify-content-center">
                        {{ $productos->links() }}
                    </div>
                @endif

            @endif

        </div>

    </div>

@endsection
