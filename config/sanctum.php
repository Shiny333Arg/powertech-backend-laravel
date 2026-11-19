<?php

use Laravel\Sanctum\Sanctum;

return [

    /*
    |--------------------------------------------------------------------------
    | Dominios con estado
    |--------------------------------------------------------------------------
    |
    | Irrelevante con 'guard' => [] (ver abajo): esta lista solo la usa
    | EnsureFrontendRequestsAreStateful, middleware que este proyecto NO aplica
    | al grupo 'api'. Se deja el valor por defecto del paquete para no
    | sorprender a quien venga de la documentacion.
    |
    */

    'stateful' => explode(',', (string) env('SANCTUM_STATEFUL_DOMAINS', sprintf(
        '%s%s',
        'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
        Sanctum::currentApplicationUrlWithPort(),
    ))),

    /*
    |--------------------------------------------------------------------------
    | Guards que Sanctum consulta ANTES de mirar el Bearer
    |--------------------------------------------------------------------------
    |
    | <b>Vacio a proposito, y es un requisito de seguridad, no una
    | preferencia.</b>
    |
    | El valor por defecto del paquete es ['web'], y se aplica igual sin
    | publicar este archivo porque SanctumServiceProvider hace mergeConfigFrom.
    | Con ese default, Guard::__invoke() recorre el guard 'web' ANTES de leer
    | la cabecera Authorization y, si ese guard devuelve un usuario, le adjunta
    | un TransientToken. Eso trae dos problemas concretos:
    |
    |   1. TransientToken::can() devuelve true para CUALQUIER ability. Como
    |      CheckAbilities solo exige que currentAccessToken() no sea null y
    |      despues pregunta tokenCan(), un usuario autenticado por sesion
    |      pasaria 'abilities:gestion' sin tener ninguna ability.
    |
    |   2. TransientToken no tiene delete(). Cualquier middleware que haga
    |      currentAccessToken()?->delete() revienta con un Error de PHP, o sea
    |      500 en lugar de 401/403.
    |
    | Hoy eso no se dispara en produccion porque el grupo 'api' no tiene
    | StartSession ni EncryptCookies, asi que ni la sesion ni la cookie
    | "recordarme" son legibles ahi. Pero es un riesgo latente que se vuelve
    | real el dia que alguien agregue EnsureFrontendRequestsAreStateful al
    | grupo —tentacion natural ahora que el panel llama a su propia API— y ya
    | es real en los tests, donde $this->actingAs($user)->getJson('/api/...')
    | autentica por sesion.
    |
    | Con la lista vacia, auth:sanctum es Bearer-only por construccion y los
    | dos problemas desaparecen de raiz en lugar de parchearse middleware por
    | middleware.
    |
    | El panel administrativo NO depende de esto para autenticarse: usa el
    | guard 'web' de forma explicita en el unico lugar donde hace falta, el
    | cierre de sesion. Ver AutenticarCierreDeSesion.
    |
    */

    'guard' => [],

    /*
    |--------------------------------------------------------------------------
    | Expiracion global
    |--------------------------------------------------------------------------
    |
    | <b>null a proposito.</b> La vigencia se fija POR TOKEN al emitirlo:
    | 30 dias para la tienda (config/movil.php) y 8 horas para gestion
    | (config/gestion.php).
    |
    | Si aca hubiera un numero, sobrescribiria el expires_at de cada token
    | —Guard::isValidAccessToken() compara tambien contra created_at— y
    | ademas habilitaria la segunda pasada de 'sanctum:prune-expired', que
    | borra por created_at y si podria alcanzar tokens vigentes.
    |
    */

    'expiration' => null,

    /*
    |--------------------------------------------------------------------------
    | Prefijo de los tokens
    |--------------------------------------------------------------------------
    |
    | Sirve para que los escaneres de secretos de los repositorios reconozcan
    | un token filtrado. Vacio mantiene el comportamiento actual.
    |
    */

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | Solo los usa EnsureFrontendRequestsAreStateful, que este proyecto no
    | aplica. El pipeline condicional del cierre de sesion arma su propia
    | lista; ver AutenticarCierreDeSesion.
    |
    */

    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
        'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ],

];
