{{--
    "Confirmá tu correo". Llegan aca los clientes recien registrados y los que
    intentan entrar a una pantalla con el middleware 'verified'.
--}}
@extends('layouts.auth')

@section('title', 'Confirmá tu correo')
@section('titulo', 'Confirmá tu correo')

@section('content')

@php $usuario = auth()->user(); @endphp

<p>
    Te enviamos un enlace a <strong>{{ $usuario->email }}</strong>. Abrilo para confirmar que el correo
    es tuyo: lo necesitás para comprar en la tienda.
</p>

<p class="small text-muted">
    ¿No llegó? Revisá la carpeta de correo no deseado o pedí otro.
    @if ($usuario->esCliente())
        Si el correo está mal escrito, <a href="{{ route('profile.edit') }}">corregilo desde tu perfil</a>.
    @endif
</p>

<form method="POST" action="{{ route('verification.send') }}">
    @csrf
    <button type="submit" class="btn btn-primary w-100 rounded-3 fw-semibold">Reenviar enlace</button>
</form>

<a href="{{ route($usuario->rutaInicio()) }}" class="btn btn-outline-secondary w-100 rounded-3 fw-semibold mt-3">
    Seguir navegando
</a>

<form method="POST" action="{{ route('logout') }}" class="text-center mt-3">
    @csrf
    <button type="submit" class="btn btn-link btn-sm text-muted">Cerrar sesión</button>
</form>

@endsection
