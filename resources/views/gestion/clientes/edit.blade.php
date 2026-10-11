@extends('layouts.gestion')

@section('title', 'PowerTech')

@section('content')

{{-- Título --}}
<section class="mb-4">
    <h1 class="h3 fw-bold mb-2">Editar cliente</h1>
    <p class="text-muted">{{ $cliente->razon_social }}</p>
</section>

@include('gestion.partials.alertas')

{{-- Formulario --}}
<section class="mb-4">

    @include('gestion.clientes.partials.formulario', [
        'accion' => route('gestion.clientes.update', $cliente),
        'metodo' => 'PUT',
    ])

</section>

@endsection
