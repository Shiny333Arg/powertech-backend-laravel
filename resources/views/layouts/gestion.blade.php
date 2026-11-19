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
        El token de gestion, en memoria y nada mas.

        Esto es lo que hace que la navegacion Blade tradicional siga
        funcionando: cada recarga completa vacia la variable del modulo, y cada
        pagina la vuelve a crear leyendola de la sesion del SERVIDOR. El
        empleado no vuelve a ingresar en cada clic y no hubo que reescribir un
        solo enlace del menu.

        En el navegador el token sigue estando solo en memoria, que es lo que
        pide el requisito 6: no se escribe en localStorage, ni en
        sessionStorage, ni en una cookie propia, ni en un atributo del DOM. Se
        pasa por argumento al modulo y no queda en ninguna variable global.

        La cascara entrega la credencial y nada mas: los datos los trae el JS
        llamando a la API. El requisito 8 pide justamente que las paginas
        entregadas por Laravel no incluyan datos privados que eludan la
        proteccion de la API.

        Cuando llega aca, VerificarTokenDeSesionGestion ya comprobo que el token
        siga vivo y sea coherente con la sesion, asi que no se renderiza una
        pantalla cuyas llamadas van a fallar con 401.
    --}}
    @if (session()->has(\App\Support\SesionGestion::TOKEN))
        <script type="module">
            import { iniciar } from '{{ Vite::asset('resources/js/gestion/api.js') }}';

            iniciar(@json(session(\App\Support\SesionGestion::TOKEN)));
        </script>
    @endif

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
