<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="icon" type="image/ico" href="images/icon.ico">
    <title>{{ config('app.name', 'PowerTech') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="d-flex flex-column min-vh-100">

    @include('components.header')

    <main class="container flex-grow-1 mt-5 mb-5">
        @yield('content')
    </main>

    @include('components.footer')

</body>

</html>