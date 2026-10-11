{{-- Tienda (clientes y visitantes). home, help y el perfil del cliente extienden este layout. --}}
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" type="image/ico" href="{{ asset('images/icon.ico') }}">
    <title>@yield('title', 'Tienda') | {{ config('app.name', 'PowerTech') }}</title>

    @vite(['resources/css/app.css', 'resources/css/components/header.css', 'resources/js/app.js'])
</head>

<body class="d-flex flex-column min-vh-100">

    @include('components.headerCliente')

    <main class="container flex-grow-1 mt-5 mb-5">

        {{-- Llega aca desde el enlace del mail de verificacion (VerifyEmailController). --}}
        @if (request()->boolean('verified'))
            <div class="alert alert-success" role="status">
                Tu correo quedó confirmado.
            </div>
        @endif

        {{-- Un cliente con el correo sin confirmar puede navegar, pero no comprar. --}}
        @auth
            @if (auth()->user()->esCliente() && ! auth()->user()->hasVerifiedEmail())
                <div class="alert alert-warning d-flex flex-wrap align-items-center gap-2" role="status">
                    <span>Confirmá tu correo <strong>{{ auth()->user()->email }}</strong> para poder comprar.</span>
                    <form method="POST" action="{{ route('verification.send') }}" class="ms-md-auto">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-dark">Reenviar enlace</button>
                    </form>
                </div>
            @endif
        @endauth

        <x-alertas />

        @yield('content')
    </main>

    @include('components.footerCliente')

</body>

</html>
