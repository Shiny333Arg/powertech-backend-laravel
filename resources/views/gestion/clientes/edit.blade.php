@extends('layouts.gestion')

@section('title', 'PowerTech')

@section('content')

{{-- Título --}}
<section class="mb-4">

    <h1 class="h3 fw-bold mb-2">Editar cliente</h1>

    <p class="text-muted mb-0">
        {{ $cliente->razon_social }}
        @if ($cliente->user)
            · {{ $cliente->user->email }}
        @endif
    </p>

</section>

@include('gestion.partials.alertas')

{{-- Cambiar el correo lo desverifica: el empleado tiene que saberlo antes de
     tocarlo, no después de que el cliente no pueda entrar a la tienda. --}}
@if ($cliente->user && ! $cliente->user->invitacionPendiente())

<div class="alert alert-warning d-flex align-items-start gap-2 mb-4" role="alert">

    <svg xmlns="http://www.w3.org/2000/svg"
        width="18"
        height="18"
        fill="currentColor"
        viewBox="0 0 16 16"
        class="flex-shrink-0 mt-1">

        <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5m.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2" />
    </svg>

    <div>
        Si cambiás el correo, la cuenta queda sin verificar hasta que el cliente
        confirme la casilla nueva. No le corta la operación de mostrador, pero no
        va a poder comprar en la tienda online mientras tanto.
    </div>

</div>

@endif

{{-- Formulario --}}
<section class="mb-4">

    @include('gestion.clientes.partials.formulario', [
        'accion'  => route('gestion.clientes.update', $cliente),
        'metodo'  => 'PUT',
        'cliente' => $cliente,
        'usuario' => $cliente->user,
    ])

</section>

@endsection