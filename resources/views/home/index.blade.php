@extends('layouts.cliente')

@section('title', 'Inicio')

@section('content')

<div class="container d-flex flex-column justify-content-center align-items-center" style="min-height: 400px;">
    <div class="p-4 rounded-4 bg-light shadow text-center w-100 w-md-75 w-lg-50">
        <h1 class="display-6 mb-3 fw-bold">
            @auth
                Hola {{ auth()->user()->apellido }}, {{ auth()->user()->nombres }}
            @else
                Bienvenido a PowerTech
            @endauth
        </h1>
        <p class="lead mb-4">
            Tu Tienda Informática de <span class="fw-bold text-dark">confianza</span>.
        </p>

        @guest
            <div class="d-flex flex-wrap justify-content-center gap-2">
                <a href="{{ route('register') }}" class="btn btn-primary fw-semibold px-4">Crear cuenta</a>
                <a href="{{ route('login') }}" class="btn btn-outline-primary fw-semibold px-4">Ingresar</a>
            </div>
        @endguest
    </div>
</div>

@endsection
