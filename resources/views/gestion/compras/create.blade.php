@extends('layouts.gestion')

@section('title', 'Nuevo pedido')

@section('content')

{{-- Título principal --}}
<section class="mb-4">
    <h1 class="h3 fw-bold mb-2">Nuevo pedido</h1>
    <p class="text-muted mb-0">
        Cargá todo lo que haga falta pedir, sin importar de qué proveedor sea.
        Al aprobar el pedido se emite una orden para cada proveedor, y a cada uno
        le llega por correo solamente lo que le corresponde.
    </p>
</section>

@include('gestion.compras.partials.formulario', [
'volverA' => route('gestion.compras.index'),
'textoGuardar' => 'Guardar borrador',
])

@endsection

@push('scripts')
<script type="module">
    import { iniciar } from '{{ Vite::asset('resources/js/gestion/compras/formulario.js') }}';

    iniciar({
        // Sin id: es el alta. El módulo arranca con el detalle vacío y guarda
        // con POST /api/v1/gestion/compras.
        id: null,

        // A dónde se va después de guardar: al detalle del pedido que acaba de
        // nacer, con el id que devuelve el 201. Es el mismo destino al que
        // redirigía el controlador web.
        urlDetalle: '{{ route('gestion.compras.show', ['orden' => '__ID__']) }}',
    });
</script>
@endpush
