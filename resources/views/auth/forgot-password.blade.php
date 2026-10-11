{{-- "Olvidé mi contraseña": sirve para empleados y clientes. --}}
@extends('layouts.auth')

@section('title', 'Recuperar contraseña')
@section('titulo', 'Recuperar contraseña')

@section('content')

<p class="text-muted small mb-4">
    Escribí el correo con el que ingresás y te enviamos un enlace para elegir una contraseña nueva.
</p>

<form method="POST" action="{{ route('password.email') }}">
    @csrf

    <div class="mb-4">
        <label for="email" class="form-label fw-semibold">Correo electrónico</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}"
            class="form-control rounded-3 @error('email') is-invalid @enderror"
            required autofocus autocomplete="username">
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <button type="submit" class="btn btn-primary w-100 rounded-3 fw-semibold">Enviar enlace</button>

    <a href="{{ route('login') }}" class="btn btn-outline-secondary w-100 rounded-3 fw-semibold mt-3">
        Volver a ingresar
    </a>
</form>

@endsection
