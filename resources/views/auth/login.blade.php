{{-- Login unico: empleados y clientes entran por aca con su correo. --}}
@extends('layouts.auth')

@section('title', 'Ingresar')
@section('titulo', 'Ingresá a tu cuenta')

@section('content')

<form method="POST" action="{{ route('login') }}">
    @csrf

    <div class="mb-3">
        <label for="email" class="form-label fw-semibold">Correo electrónico</label>
        <input
            type="email"
            id="email"
            name="email"
            value="{{ old('email') }}"
            class="form-control rounded-3 @error('email') is-invalid @enderror"
            required
            autofocus
            autocomplete="username">

        {{-- Aca llegan tambien "cuenta desactivada" y "demasiados intentos". --}}
        @error('email')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <x-password-input name="password" label="Contraseña" autocomplete="current-password" />

    <div class="d-flex flex-wrap justify-content-center align-items-center gap-2 mb-4">
        <!-- <div class="form-check">
            <input class="form-check-input" type="checkbox" id="remember" name="remember" @checked(old('remember'))
                aria-describedby="remember-ayuda">
            <label class="form-check-label" for="remember">Recordarme</label>
            <div id="remember-ayuda" class="form-text mt-0">No lo marques en una computadora compartida.</div>
        </div> -->

        <a href="{{ route('password.request') }}" class="small">¿Olvidaste tu contraseña?</a>
    </div>

    <button type="submit" class="btn btn-primary w-100 rounded-3 fw-semibold">Ingresar</button>
</form>

<hr class="my-4">

<p class="text-center small mb-0">
    ¿Todavía no tenés cuenta en la tienda?
    <a href="{{ route('register') }}" class="fw-semibold">Creá una</a>
</p>
<div>
    <hr class="my-4">
    <p class="fw-semibold text-center my-4">Cuentas de prueba:</p>
    <p class="fw-semibold">Administrador: Administrador PowerTech</p>
    <p>admin@powertech.com.ar</p>
    <p>Cambiar.Esta.Clave</p>
    <hr class="my-4">
    <p class="fw-semibold">Administrativo: Luis Alberto Spinetta</p>
    <p>flaco@mail.com</p>
    <p>Cambiar.Esta.Clave</p>
    <hr class="my-4">
    <p class="fw-semibold">Vendedor: Gustavo Edgardo Cordera</p>
    <p>pelado@mail.com</p>
    <p>Cambiar.Esta.Clave</p>
    <hr class="my-4">
    <p class="fw-semibold">Cajero: Carlos Alberto Solari</p>
    <p>indio@mail.com</p>
    <p>Cambiar.Esta.Clave</p>
    <hr class="my-4">
    <p class="fw-semibold">Cliente: Patricio Rogelio Fontanet</p>
    <p>fontanet@mail.com</p>
    <p>Cambiar.Esta.Clave</p>
</div>


@endsection