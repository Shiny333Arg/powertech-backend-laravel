@extends('layouts.auth')

@section('title', 'Iniciar Sesión')

@section('content')


<div class="container d-flex justify-content-center align-items-center min-vh-100">
    <div class="card p-4 logout-card text-center">
        <div class="mb-3">
            <svg xmlns="app/assets/icons/box-arrow-right.svg" width="55" height="55" fill="#2991ff" class="bi bi-box-arrow-right logout-icon" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0z" />
                <path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708z" />
            </svg>
        </div>
        <h3 class="fw-bold text-primary mb-2">Cerrando sesión</h3>
        <p class="mb-4"><b>Redirigiendo en 5 segundos...</b></p>
    </div>
</div>


@endsection