@extends('layouts.gestion')

@section('title', 'Nuevo pedido')

@section('content')

@include('gestion.partials.alertas')

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
'accion' => route('gestion.compras.store'),
'metodo' => 'POST',
])

@endsection
