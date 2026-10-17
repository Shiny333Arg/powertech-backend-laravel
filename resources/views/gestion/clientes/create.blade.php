@extends('layouts.gestion')

@section('title', 'PowerTech')

@section('content')

{{-- Título --}}
<section class="mb-4">
    <h1 class="h3 fw-bold mb-2">Alta de nuevo cliente</h1>
    <p class="text-muted mb-0">
        El cliente queda registrado como cualquier otro usuario de la tienda: con
        su cuenta y sus datos de facturación. Ya se le puede vender, cobrar y
        facturar desde el mostrador aunque todavía no haya activado la cuenta.
    </p>
</section>

@include('gestion.partials.alertas')

{{-- Qué pasa después de guardar. Va antes del formulario porque cambia lo que
     el empleado tiene que verificar con el cliente mientras lo completa: el
     correo deja de ser un dato de contacto y pasa a ser la llave de la cuenta. --}}
<div class="alert alert-info d-flex align-items-start gap-2 mb-4" role="alert">

    <svg xmlns="http://www.w3.org/2000/svg"
        width="18"
        height="18"
        fill="currentColor"
        viewBox="0 0 16 16"
        class="flex-shrink-0 mt-1">

        <path d="M.05 3.555A2 2 0 0 1 2 2h12a2 2 0 0 1 1.95 1.555L8 8.414zM0 4.697v7.104l5.803-3.558zM6.761 8.83l-6.57 4.027A2 2 0 0 0 2 14h12a2 2 0 0 0 1.808-1.144l-6.57-4.027L8 9.586zm3.436-.586L16 11.801V4.697z" />
    </svg>

    <div>
        <strong>Al guardar le mandamos un correo para que active su cuenta.</strong>
        <p class="mb-0 small">
            La contraseña la elige el cliente desde ese enlace: no se carga acá ni
            la conoce nadie del local. Si el correo quedó mal escrito, corregilo
            desde su ficha y reenviáselo.
        </p>
    </div>

</div>

{{-- Formulario --}}
<section class="mb-4">

    @include('gestion.clientes.partials.formulario', [
        'accion'  => route('gestion.clientes.store'),
        'metodo'  => 'POST',
        'cliente' => null,
        'usuario' => null,
    ])

</section>

@endsection