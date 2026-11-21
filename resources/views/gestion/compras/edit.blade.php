@extends('layouts.gestion')

@section('title', 'Editar pedido')

@section('content')

    {{--
        El número del pedido, los destinatarios y el aviso de los pedidos
        automáticos los escribe el módulo con lo que devuelve
        GET /api/v1/gestion/compras/{orden}: son datos del documento y la
        página ya no los trae.
    --}}
    <div class="mb-4">
        <a href="{{ route('gestion.compras.show', $orden) }}" class="small text-secondary text-decoration-none">
            &larr; <span id="numeroVolver">Volver al pedido</span>
        </a>
        <h1 class="h3 mt-1 mb-1">Editar el detalle de <span id="numeroPedido">este pedido</span></h1>
        <p class="text-secondary mb-0 small">
            Solo se edita mientras está en borrador. Una vez aprobada, el detalle queda fijo: si hay que
            cambiar algo, se cancela y se emite otro pedido.
        </p>
    </div>

    {{--
        El destinatario ya no se muestra como un dato fijo del pedido, porque
        dejó de serlo: sale de los productos que tenga el detalle. Agregar uno
        de otro proveedor convierte este pedido en uno repartido, y sacarle
        todos los renglones de un proveedor lo devuelve a ser una orden común
        dirigida al que queda.

        No es una excepción a la vieja regla de «el destinatario no se cambia»:
        lo que se edita sigue siendo el detalle. El destinatario es la
        consecuencia.

        Se dibuja desde el JavaScript, con los proveedores que trae el detalle,
        y se vuelve a dibujar cuando los renglones cambian: antes era un dato
        del servidor que quedaba viejo en cuanto se agregaba un producto.
    --}}
    <div id="destinoDelPedido"></div>

    {{-- El aviso de los pedidos que generó la reposición automática. --}}
    <div id="avisoOrigenAutomatico"></div>

    @include('gestion.compras.partials.formulario', [
        'volverA' => route('gestion.compras.show', $orden),
        'textoGuardar' => 'Guardar cambios',
    ])

@endsection

@push('scripts')
<script type="module">
    import { iniciar } from '{{ Vite::asset('resources/js/gestion/compras/formulario.js') }}';

    iniciar({
        // Con id: el módulo pide el pedido a la API, llena el detalle y guarda
        // con PUT /api/v1/gestion/compras/{orden}.
        id: {{ $orden->id }},

        urlDetalle: '{{ route('gestion.compras.show', ['orden' => '__ID__']) }}',
    });
</script>
@endpush
