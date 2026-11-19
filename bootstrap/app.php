<?php

use App\Exceptions\EntidadNoEliminableException;
use App\Exceptions\FacturacionException;
use App\Exceptions\PasarelaPagoException;
use App\Exceptions\StockInsuficienteException;
use App\Exceptions\TransicionNoPermitidaException;
use App\Enums\AmbitoRol;
use App\Http\Middleware\ForzarRespuestaJson;
use App\Http\Middleware\VerificarAmbito;
use App\Http\Middleware\VerificarAmbitoApi;
use App\Http\Middleware\VerificarCuentaActiva;
use App\Http\Middleware\VerificarCuentaActivaApi;
use App\Http\Middleware\VerificarEmailVerificadoApi;
use App\Http\Middleware\VerificarPermiso;
use App\Http\Middleware\VerificarTokenDeSesionGestion;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Exceptions\MissingAbilityException;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
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

        // El ambito de gestion de la API. withRouting() acepta UN solo archivo
        // en 'api:', asi que el segundo se carga desde este cierre, que corre
        // despues de registrar los anteriores.
        //
        // Se le pone a mano lo que 'api:' pone solo: el grupo de middleware
        // 'api' —y con el ForzarRespuestaJson, que va prepend mas abajo— y el
        // prefijo 'api'. Por eso routes/api_gestion.php empieza en 'v1/gestion'
        // y las URL finales quedan /api/v1/gestion/...
        //
        // Va aca y no con un require dentro de routes/api.php: el archivo de la
        // tienda no tiene por que saber que existe el de gestion, y una
        // dependencia escondida entre dos archivos de rutas es de las que
        // aparecen cuando alguien reordena un grupo.
        then: function (): void {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api_gestion.php'));
        },
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

            // En cada request a /gestion/*: la sesion web y el token que la
            // origino siguen siendo coherentes, o la sesion se cierra. Va
            // despues de 'auth' y de 'ambito:gestion'; ver la clase.
            'gestion.token' => VerificarTokenDeSesionGestion::class,

            'api.activo' => VerificarCuentaActivaApi::class,
            'api.ambito' => VerificarAmbitoApi::class,

            // Las abilities de Sanctum. <b>Hay que registrarlas a mano:</b>
            // SanctumServiceProvider no llama a aliasMiddleware en ninguna
            // parte —solo registra la ruta sanctum.csrf-cookie—, asi que las
            // clases existen pero sin estos alias no se pueden usar desde una
            // ruta. Es lo que deja cerrado el ambito ajeno: 'abilities:tienda'
            // rechaza un token de gestion sin enumerar que no puede hacer.
            'abilities' => CheckAbilities::class,
            'ability'   => CheckForAnyAbility::class,

            // El 'verified' de la app. Mismo criterio que el nativo —y que el
            // bloque 'verified' de routes/web.php—, pero con mensaje propio y
            // un `codigo` del que Flutter pueda colgar la pantalla de
            // verificacion en vez de comparar textos.
            'api.verificado' => VerificarEmailVerificadoApi::class,
        ]);

        // Un invitado que pide una pagina protegida va al login de SU ambito.
        //
        // Desde F2 hay dos puertas —/login para el storefront y /gestion/login
        // para el panel—, asi que el destino se decide por la URL pedida con el
        // mismo enum que ya usa AuthenticatedSessionController para url.intended.
        // Sin esto, un empleado que pega una URL de /gestion/... caeria en el
        // formulario del storefront, que no emite token y lo rebotaria.
        //
        // Esto NO afecta a la API: Laravel solo usa este destino cuando el
        // request no espera JSON, y ForzarRespuestaJson garantiza que todo
        // request de /api/* lo espere. Ahi sale un 401 limpio.
        $middleware->redirectGuestsTo(fn(Request $request) => AmbitoRol::deUrl($request->fullUrl()) === AmbitoRol::Gestion
            ? route('gestion.login')
            : route('login'));

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

            // ----------------------------------------------------------
            // Los dos 403 de autorizacion, con 'codigo' estable
            // ----------------------------------------------------------
            //
            // <b>Hay que mirar AccessDeniedHttpException y no las clases de
            // autorizacion.</b> Handler::render() llama a prepareException()
            // ANTES de recorrer estos cierres, y ahi tanto AuthorizationException
            // como MissingAbilityException —que extiende a la primera— quedan
            // convertidas en AccessDeniedHttpException, con la original colgada
            // como previous. Una rama escrita contra MissingAbilityException no
            // se ejecuta nunca: el framework ya no esta pasando ese objeto.
            // (Es lo que pasaba desde F2; se descubrio al cubrirlo con una
            // prueba en F3, y de ahi que la respuesta saliera en ingles.)
            //
            // Son dos casos distintos y por eso llevan codigos distintos:
            //
            //   ABILITY_INSUFICIENTE  el token no se emitio para esta app
            //   PERMISO_INSUFICIENTE  el rol no tiene el permiso de la policy
            //
            // Los dos responden 403 y NINGUNO revoca: la credencial es buena,
            // lo que no alcanza es su alcance. Flutter ya trata cualquier 403
            // desconocido como acceso denegado, asi que agregar el codigo no le
            // cambia el comportamiento; el panel, en cambio, muestra el mensaje
            // tal cual —ver tratar() en resources/js/gestion/api.js—, y un
            // "This action is unauthorized." en pantalla no sirve a nadie.
            //
            // El 403 de ambito (AMBITO_INCORRECTO) no pasa por aca: lo devuelve
            // VerificarAmbitoApi como JsonResponse, sin excepcion.
            if ($e instanceof AccessDeniedHttpException) {
                if ($e->getPrevious() instanceof MissingAbilityException) {
                    return response()->json([
                        'message' => 'Tu credencial no tiene permiso para esta operación.',
                        'codigo'  => 'ABILITY_INSUFICIENTE',
                    ], 403);
                }

                return response()->json([
                    'message' => 'No tenés permiso para esta operación.',
                    'codigo'  => 'PERMISO_INSUFICIENTE',
                ], 403);
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