@extends('layouts.gestion')

@section('title', 'PowerTech')

@section('content')

{{-- Título --}}
<section class="mb-4">
    <h1 class="h3 fw-bold mb-2">
        Presupuesto {{ $venta->numeroFormateado() }}
    </h1>
    <p class="text-muted">
        El número no cambia al editar: es la identidad del documento, no una etiqueta de su estado.
    </p>
</section>

@include('gestion.partials.alertas')

{{-- Formulario --}}
<section class="mb-4">

    @include('gestion.ventas.partials.formulario', [
        'accion' => route('gestion.ventas.update', $venta),
        'metodo' => 'PUT',
    ])

</section>

@endsection
