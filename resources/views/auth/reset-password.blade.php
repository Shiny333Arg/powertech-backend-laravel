{{-- Destino del enlace de "olvidé mi contraseña" (NewPasswordController). --}}
@extends('layouts.auth')

@section('title', 'Nueva contraseña')
@section('titulo', 'Elegí tu nueva contraseña')

@section('content')

<form method="POST" action="{{ route('password.store') }}">
    @csrf

    <input type="hidden" name="token" value="{{ $request->route('token') }}">

    <div class="mb-3">
        <label for="email" class="form-label fw-semibold">Correo electrónico</label>
        <input type="email" id="email" name="email" value="{{ old('email', $request->email) }}"
            class="form-control rounded-3 @error('email') is-invalid @enderror"
            required autocomplete="username">

        {{-- "El enlace no es valido o ya vencio" tambien aparece aca. --}}
        @error('email')
            <div class="invalid-feedback">
                {{ $message }}
                <a href="{{ route('password.request') }}">Pedí un enlace nuevo.</a>
            </div>
        @enderror
    </div>

    <x-password-input name="password" label="Nueva contraseña" autocomplete="new-password"
        ayuda="Al menos 8 caracteres." />

    <x-password-input name="password_confirmation" label="Repetí la nueva contraseña" autocomplete="new-password"
        class="mb-4" />

    <button type="submit" class="btn btn-primary w-100 rounded-3 fw-semibold">Guardar contraseña</button>
</form>

@endsection
