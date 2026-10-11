{{--
    "Mi cuenta" del empleado. Sus datos los administra el area administrativa
    (EmpleadoController); lo unico que puede cambiar el mismo es la contraseña,
    con PasswordController (bolsa de errores 'updatePassword').
--}}
@extends('layouts.gestion')

@section('title', 'Mi cuenta')

@section('content')

@php
    $usuario  = auth()->user();
    $empleado = $usuario->empleado; // el administrador inicial no tiene ficha
@endphp

<div class="row justify-content-center">
    <div class="col-lg-7 col-md-9">

        <x-alertas />

        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4">

                <h1 class="h3 fw-bold mb-1">Mi cuenta</h1>
                <p class="text-muted mb-4">
                    Tus datos son gestionados por el área administrativa: si alguno es incorrecto, contactá con un administrativo.
                    Solo tienes permitido cambiar tu contraseña
                </p>

                <dl class="row mb-0">
                    <dt class="col-sm-4 fw-normal text-muted">Nombre</dt>
                    <dd class="col-sm-8 fw-semibold">{{ $usuario->nombre_completo }}</dd>

                    <dt class="col-sm-4 fw-normal text-muted">Correo</dt>
                    <dd class="col-sm-8 fw-semibold">{{ $usuario->email }}</dd>

                    <dt class="col-sm-4 fw-normal text-muted">Cargo</dt>
                    <dd class="col-sm-8 fw-semibold">{{ ucfirst($usuario->rol->nombre) }}</dd>

                    @if ($empleado)
                        <dt class="col-sm-4 fw-normal text-muted">Legajo</dt>
                        <dd class="col-sm-8 fw-semibold">{{ $empleado->legajo }}</dd>

                        <dt class="col-sm-4 fw-normal text-muted">Fecha de ingreso</dt>
                        <dd class="col-sm-8 fw-semibold">{{ $empleado->fecha_ingreso?->format('d/m/Y') }}</dd>
                    @endif
                </dl>

                <hr class="my-4">

                <h2 class="h5 fw-bold mb-3">Cambiar contraseña</h2>

                <form method="POST" action="{{ route('password.update') }}">
                    @csrf
                    @method('PUT')

                    {{-- Para que el navegador asocie la contraseña nueva con esta cuenta. --}}
                    <input type="email" value="{{ $usuario->email }}" autocomplete="username" hidden>

                    <x-password-input name="current_password" label="Contraseña actual"
                        autocomplete="current-password" bag="updatePassword" />

                    <x-password-input name="password" label="Nueva contraseña"
                        autocomplete="new-password" bag="updatePassword" ayuda="Al menos 8 caracteres." />

                    <x-password-input name="password_confirmation" label="Repetí la nueva contraseña"
                        autocomplete="new-password" bag="updatePassword" class="mb-4" />

                    <button type="submit" class="btn btn-primary w-100 fw-semibold">Cambiar contraseña</button>
                </form>

            </div>
        </div>

    </div>
</div>

@endsection
