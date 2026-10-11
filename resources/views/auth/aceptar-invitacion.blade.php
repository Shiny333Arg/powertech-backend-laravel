{{--
    El empleado llega desde el mail de InvitacionEmpleado y elige su contraseña.

    El formulario se envia a la MISMA URL firmada (request()->fullUrl()): la
    firma viaja en la query string y InvitacionController la vuelve a validar
    al guardar. Con route('invitacion.store', $user) se perderia.
--}}
@extends('layouts.auth')

@section('title', 'Activar cuenta')
@section('titulo', 'Activá tu cuenta')

@section('content')

<p class="text-center">
    Hola, <strong>{{ $user->nombres }}</strong>. Elegí la contraseña con la que vas a ingresar
    al sistema de gestión.
</p>

<form method="POST" action="{{ request()->fullUrl() }}">
    @csrf

    <div class="mb-3">
        <label for="email" class="form-label fw-semibold">Correo electrónico</label>
        {{-- Sin name: no se envia. Esta para que el navegador guarde el usuario junto con la contraseña. --}}
        <input type="email" id="email" value="{{ $user->email }}" class="form-control rounded-3"
            readonly autocomplete="username">
        <div class="form-text">Es el que vas a usar para ingresar.</div>
    </div>

    <x-password-input name="password" label="Contraseña" autocomplete="new-password"
        ayuda="Al menos 8 caracteres." />

    <x-password-input name="password_confirmation" label="Repetí la contraseña" autocomplete="new-password"
        class="mb-4" />

    <button type="submit" class="btn btn-primary w-100 rounded-3 fw-semibold">Activar mi cuenta</button>
</form>

@endsection
