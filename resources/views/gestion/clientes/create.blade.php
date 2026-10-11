@extends('layouts.gestion')

@section('title', 'PowerTech')

@section('content')

{{-- Título --}}
<section class="mb-4">
    <h1 class="h3 fw-bold mb-2">Alta de nuevo cliente</h1>
    <p class="text-muted">
        Complete los datos para registrar un nuevo cliente en el sistema.
        El documento y la condición frente al IVA son requisito de la facturación
        electrónica, no datos opcionales.
    </p>
</section>

@include('gestion.partials.alertas')

{{-- Formulario --}}
<!-- <section class="mb-4"> -->

    <!-- @'i'n'clude('gestion.clientes.partials.formulario', [
        'accion'  => route('gestion.clientes.store'),
        'metodo'  => 'POST',
        'cliente' => null,
    ]) -->

<!-- </section> -->

@endsection
