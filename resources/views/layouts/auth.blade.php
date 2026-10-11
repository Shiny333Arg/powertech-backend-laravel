{{--
    Pantallas de acceso: login, registro, recuperar contraseña, verificar
    correo y aceptar invitacion. Todas comparten la tarjeta centrada con el
    logo; cada vista define:

      @section('title')   titulo de la pestaña
      @section('titulo')  encabezado dentro de la tarjeta
      @section('ancho')   opcional, ancho maximo de la tarjeta (por defecto 420px)
      @section('content') el formulario
--}}
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" type="image/ico" href="{{ asset('images/icon.ico') }}">
    <title>@yield('title', 'Ingresar') | {{ config('app.name', 'PowerTech') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-primary bg-gradient min-vh-100 d-flex align-items-center py-5">

    <main class="container">
        <div class="card shadow border-0 rounded-4 mx-auto" style="max-width: @yield('ancho', '420px');">
            <div class="card-body p-4 p-md-5">

                <a href="{{ route('cliente.home') }}" class="d-block text-center text-decoration-none mb-4">
                    <img src="{{ asset('images/Logo_Primary.png') }}" alt="" height="72">
                    <span class="d-block fw-bold fs-3 text-primary mt-1">POWERTECH</span>
                </a>

                <h1 class="h4 fw-bold text-center mb-4">@yield('titulo')</h1>

                <x-alertas />

                @yield('content')

            </div>
        </div>
    </main>

</body>

</html>
