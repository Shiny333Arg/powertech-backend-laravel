<?php

use App\Exceptions\EntidadNoEliminableException;
use App\Exceptions\FacturacionException;
use App\Exceptions\PasarelaPagoException;
use App\Exceptions\StockInsuficienteException;
use App\Exceptions\TransicionNoPermitidaException;
use App\Http\Middleware\ForzarRespuestaJson;
use App\Http\Middleware\VerificarAmbito;
use App\Http\Middleware\VerificarAmbitoApi;
use App\Http\Middleware\VerificarCuentaActiva;
use App\Http\Middleware\VerificarCuentaActivaApi;
use App\Http\Middleware\VerificarEmailVerificadoApi;
use App\Http\Middleware\VerificarPermiso;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
//use Throwable;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',

        // La app movil. Laravel le pone el prefijo 'api' solo, asi que las
        // URL quedan /api/v1/... No comparte nada con el grupo 'web': ni
        // sesion, ni cookies, ni CSRF.
        api: __DIR__ . '/../routes/api.php',

        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {


        $middleware->trustProxies(at: '*');
        $middleware->validateCsrfTokens(except: ['checkout/webhook']);


        // En cada request web: si la cuenta se dio de baja con la sesion
        // abierta, se la cierra en ese momento.
        $middleware->web(append: [
            VerificarCuentaActiva::class,
        ]);

        // En cada request de la API: la respuesta siempre es JSON, pase lo
        // que pase. Va PREPEND porque tiene que correr antes que cualquier
        // middleware que pueda querer redirigir (ver el docblock de la
        // clase: sin esto, un 401 sale como 302 hacia /login).
        //
        // El grupo 'api' de Laravel trae SubstituteBindings y nada mas. No
        // tiene sesion ni CSRF, que es justo lo que corresponde para tokens.
        $middleware->api(prepend: [
            ForzarRespuestaJson::class,
        ]);

        // Uso en rutas:
        //   ->middleware(['auth', 'ambito:gestion'])   separa las dos apps
        //   ->middleware('can:update,producto')        policy + estado
        //   ->middleware('permiso:stock.ajustar')      clave suelta
        //
        // 'ambito' decide a que aplicacion entra cada rol; 'can' y 'permiso'
        // deciden que puede hacer adentro. Son capas distintas: un cajero
        // pertenece al ambito gestion y aun asi no abre /gestion/empleado.
        //
        // Los alias 'api.*' son los equivalentes para tokens. Son clases
        // aparte y no ramas de las de arriba porque cambia la respuesta, no
        // el criterio: la web redirige, la API revoca el token y contesta.
        $middleware->alias([
            'ambito'  => VerificarAmbito::class,
            'permiso' => VerificarPermiso::class,

            'api.activo' => VerificarCuentaActivaApi::class,
            'api.ambito' => VerificarAmbitoApi::class,

            // El 'verified' de la app. Mismo criterio que el nativo —y que el
            // bloque 'verified' de routes/web.php—, pero con mensaje propio y
            // un `codigo` del que Flutter pueda colgar la pantalla de
            // verificacion en vez de comparar textos.
            'api.verificado' => VerificarEmailVerificadoApi::class,
        ]);

        // Un invitado que pide una pagina protegida va al login (hay uno solo).
        //
        // Esto NO afecta a la API: Laravel solo usa este destino cuando el
        // request no espera JSON, y ForzarRespuestaJson garantiza que todo
        // request de /api/* lo espere. Ahi sale un 401 limpio.
        $middleware->redirectGuestsTo(fn() => route('login'));

        // Un usuario ya logueado que abre el login o el registro va al inicio
        // de su ambito.
        $middleware->redirectUsersTo(fn(Request $request) => route($request->user()->rutaInicio()));
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        // Para la WEB no hay nada que configurar: una AuthorizationException
        // ya sale como 403 y Laravel busca sola
        // resources/views/errors/403.blade.php, que es donde conviene poner
        // el layout de gestion y un enlace de vuelta al tablero. Sin esa
        // vista, el empleado que pega una URL que no le toca ve la pantalla
        // pelada del framework.


        // ------------------------------------------------------------------
        // API: excepciones de dominio como JSON
        // ------------------------------------------------------------------
        //
        // Las cuatro excepciones del dominio llevan mensajes escritos para el
        // cliente —"Tu carrito está vacío", "Como responsable inscripto
        // necesitás tener cargado tu CUIT"— asi que el mensaje viaja tal
        // cual. En la web esos mismos mensajes terminan en un
        // ->with('error', ...) y se muestran en la vista.
        //
        // <b>El filtro por 'api/*' es lo que garantiza que la web no cambie
        // de comportamiento.</b> Fuera de la API este render devuelve null y
        // Laravel sigue con su manejo de siempre. No hay forma de que esto
        // altere una pantalla Blade.
        //
        // Las que no estan en el match caen tambien en null: validacion,
        // autenticacion, 404 y 500 ya tienen su forma JSON correcta y no hay
        // motivo para interceptarlas.

        $exceptions->render(function (Throwable $e, Request $request): mixed {

            if (! $request->is('api/*')) {
                return null;
            }

            $codigo = match (true) {

                // El pedido es entendible pero el estado del sistema no lo
                // permite. La app muestra el mensaje y deja reintentar.
                $e instanceof TransicionNoPermitidaException => 422,
                $e instanceof FacturacionException           => 422,

                // Conflicto con el estado actual: hay que cambiar algo
                // (quitar una linea, esperar reposicion) antes de reintentar.
                $e instanceof StockInsuficienteException   => 409,
                $e instanceof EntidadNoEliminableException => 409,

                // 502 y no 500: el problema es de la pasarela, no nuestro ni
                // del cliente. Es la diferencia que le permite a la app
                // ofrecer "intentá de nuevo" en lugar de "algo salió mal".
                $e instanceof PasarelaPagoException => 502,

                default => null,
            };

            if ($codigo === null) {
                return null;
            }

            return response()->json([
                'message' => $e->getMessage(),
            ], $codigo);
        });
    })->create();