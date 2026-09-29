{{-- Aplicacion de gestion (empleados). Todas las vistas de resources/views/gestion extienden este layout. --}}
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" type="image/ico" href="{{ asset('images/icon.ico') }}">
    <title>@yield('title', 'Gestión') | {{ config('app.name', 'PowerTech') }}</title>

    @vite(['resources/css/app.css', 'resources/css/components/header.css', 'resources/js/app.js'])
</head>

<body class="d-flex flex-column min-vh-100">

    @include('components.header')

    <main class="container flex-grow-1 mt-5 mb-5">
        @yield('content')
    </main>

    @include('components.footer')

    {{--
        Scripts propios de una vista. Va DESPUES de @vite para que el JS de la
        pagina encuentre cargado lo que app.js expone (Bootstrap, entre otras
        cosas), y al final del body para que el DOM ya exista: el formulario de
        pedidos consulta sus renglones apenas arranca.

        Una vista lo usa con @push('scripts') ... @endpush.
    --}}
    @stack('scripts')

</body>

</html>
