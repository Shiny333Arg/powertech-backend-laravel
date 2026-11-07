
<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CatalogoController;
use App\Http\Controllers\Api\V1\DatosFiscalesController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API DE LA APLICACION MOVIL (TIENDA)
|--------------------------------------------------------------------------
|
| Canal paralelo a routes/web.php, no su reemplazo. Las rutas web siguen
| devolviendo Blade y no cambian: lo que hay aca es la misma logica de
| negocio —los mismos servicios— con JSON como salida.
|
| <b>Alcance: solo la tienda.</b> Catalogo, promociones, carrito, checkout y
| compras del cliente. Nada de gestion: ni stock, ni proveedores, ni
| empleados, ni usuarios. Si manana hace falta una app de gestion, va en su
| propio prefijo y con su propia ability, no colgada de esta.
|
| <b>Autenticacion:</b> Sanctum con tokens Bearer. El token se emite en
| /auth/login y /auth/registro, y muere en /auth/logout o al vencer (ver
| config/movil.php). No hay refresh: vencido, se vuelve a ingresar.
|
| El prefijo 'api' lo agrega bootstrap/app.php, asi que las URL finales
| quedan /api/v1/...
|
*/

Route::prefix('v1')
    ->name('api.v1.')
    ->middleware('throttle:60,1')
    ->group(function () {

        // ============================================================
        // AUTENTICACION - PUBLICO
        // ============================================================

        // Las unicas dos rutas sin token. Llevan throttle propio, mas
        // estricto que el del grupo: son las que un atacante golpea.
        //
        // Ojo: el throttle de aca es por IP. El de cinco intentos por
        // correo + IP esta adentro, en LoginMovilRequest, y es el que
        // comparte contador con el login web.

        Route::post('auth/login', [AuthController::class, 'login'])
            ->middleware('throttle:10,1')
            ->name('auth.login');

        Route::post('auth/registro', [AuthController::class, 'registro'])
            ->middleware('throttle:5,1')
            ->name('auth.registro');


        // ============================================================
        // AUTENTICADO
        // ============================================================

        // Tres capas, en este orden y no en otro:
        //
        //   auth:sanctum   el token existe, no vencio y no fue revocado
        //   api.activo     la cuenta no se dio de baja despues de emitirlo
        //   api.ambito     es una cuenta de tienda, no de gestion
        //
        // Es la misma separacion que usa la web ('auth' + 'ambito'), con la
        // diferencia de que alla VerificarCuentaActiva se aplica al grupo
        // 'web' entero desde bootstrap/app.php y aca se declara en la ruta:
        // el grupo 'api' no tiene por que mirar la cuenta en endpoints
        // publicos donde no hay usuario.
        //
        // 'ambito' y 'activo' son preguntas distintas y por eso son dos
        // middlewares: una cuenta puede estar activa y no corresponder a
        // esta aplicacion, o corresponder y estar dada de baja.

        Route::middleware(['auth:sanctum', 'api.activo', 'api.ambito:tienda'])
            ->group(function () {

                Route::get('auth/yo', [AuthController::class, 'yo'])
                    ->name('auth.yo');

                Route::post('auth/logout', [AuthController::class, 'logout'])
                    ->name('auth.logout');

                // Manda un correo: throttle propio, igual que verification.send
                // en routes/auth.php.
                Route::post('auth/verificacion/reenviar', [AuthController::class, 'reenviarVerificacion'])
                    ->middleware('throttle:6,1')
                    ->name('auth.verificacion.reenviar');
            });


        // ============================================================
        // CATALOGO - PUBLICO, CON AUTENTICACION OPCIONAL
        // ============================================================

        // Sin 'auth:sanctum', y es deliberado. La vitrina debe poder
        // consultarse sin iniciar sesion.

        Route::get('home', [CatalogoController::class, 'home'])
            ->name('home');

        Route::get('productos', [CatalogoController::class, 'index'])
            ->name('productos.index');

        Route::get('productos/{producto}', [CatalogoController::class, 'show'])
            ->whereNumber('producto')
            ->name('productos.show');

        Route::get('categorias', [CatalogoController::class, 'categorias'])
            ->name('categorias');

        Route::get('marcas', [CatalogoController::class, 'marcas'])
            ->name('marcas');

        Route::get('promociones', [CatalogoController::class, 'promociones'])
            ->name('promociones');


        // ============================================================
        // CATALOGOS FISCALES - PUBLICO
        // ============================================================
        //
        // Datos necesarios para los desplegables del registro.
        // No requieren token: el usuario todavía no tiene cuenta.
        //
        // Solo lectura. Los datos provienen de los seeders.

        Route::get('datos-fiscales', DatosFiscalesController::class)
            ->name('datos-fiscales');


        // ============================================================
        // PROXIMAS ETAPAS
        // ============================================================

        // Carrito y checkout (con 'verified'), compras y perfil. Ver
        // claude/plan-api-movil-flutter.md en la base de conocimientos.

    });
