<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="icon" type="image/ico" href="images/icon.ico">
    <title>{{ config('app.name', 'PowerTech') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-primary bg-gradient d-flex align-items-center justify-content-center min-vh-100">

    <main>
        @yield('content')
    </main>

</body>
</html>