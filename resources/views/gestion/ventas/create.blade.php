@extends('layouts.gestion')

@section('title', 'PowerTech')

@section('content')

{{-- Título --}}
<section class="mb-4">
    <h1 class="h3 fw-bold mb-2">Nueva venta</h1>
    <p class="text-muted">
        Cargue los productos y decida si se guarda como presupuesto o se confirma en el acto.
    </p>
</section>

@include('gestion.partials.alertas')

{{-- Formulario --}}
<section class="mb-4">

    @include('gestion.ventas.partials.formulario', [
        'accion' => route('gestion.ventas.store'),
        'metodo' => 'POST',
        'venta'  => null,
    ])

</section>

@endsection
