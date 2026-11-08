
<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CarritoController;
use App\Http\Controllers\Api\V1\CatalogoController;
use App\Http\Controllers\Api\V1\CheckoutController;
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
        // CARRITO
        // ============================================================

        // Cuatro capas. Las tres primeras son las mismas que en el bloque de
        // autenticacion de arriba; la cuarta es propia del carrito:
        //
        //   auth:sanctum     el token existe, no vencio y no fue revocado
        //   api.activo       la cuenta no se dio de baja despues de emitirlo
        //   api.ambito       es una cuenta de tienda, no de gestion
        //   api.verificado   el correo esta confirmado
        //
        // <b>'api.verificado' replica la regla de la web.</b> routes/web.php
        // exige 'verified' en el bloque del carrito y en el del checkout, y
        // NO en "mis compras": navegar y mirar el historial no necesita
        // correo confirmado, armar un pedido si. La app aplica exactamente
        // eso. El registro entrega token antes de la verificacion a
        // proposito —sin token la app no podria ni mostrar el perfil ni pedir
        // el reenvio— y es este middleware el que marca hasta donde llega esa
        // cuenta.
        //
        // No es el 'verified' nativo porque aquel contesta en ingles y sin un
        // codigo que la app pueda mirar. Ver VerificarEmailVerificadoApi.
        //
        // <b>El carrito no lleva id en la URL.</b> Siempre es el abierto de
        // quien trae el token. Lo que si lleva id es la linea, y se valida
        // contra ese carrito: una linea ajena da 404.

        Route::middleware(['auth:sanctum', 'api.activo', 'api.ambito:tienda', 'api.verificado'])
            ->prefix('carrito')
            ->name('carrito.')
            ->group(function () {

                Route::get('/', [CarritoController::class, 'index'])
                    ->name('index');

                Route::post('/', [CarritoController::class, 'store'])
                    ->name('store');

                // Vaciar va ANTES de la ruta con parametro. Las dos son
                // DELETE sobre el mismo prefijo y Laravel resuelve por orden
                // de declaracion: al reves, '/carrito' seguiria entrando por
                // '/carrito/{linea}' solo si el segmento coincidiera, pero
                // dejarlo explicito evita que un cambio futuro de la
                // restriccion del parametro las cruce.
                Route::delete('/', [CarritoController::class, 'vaciar'])
                    ->name('vaciar');

                Route::patch('/{linea}', [CarritoController::class, 'update'])
                    ->whereNumber('linea')
                    ->name('update');

                Route::delete('/{linea}', [CarritoController::class, 'destroy'])
                    ->whereNumber('linea')
                    ->name('destroy');
            });

        // ============================================================
        // CHECKOUT
        // ============================================================

        // Misma cadena de middleware que el carrito, y por los mismos
        // motivos. La verificacion del correo se exige igual que en el
        // bloque 'cliente.checkout.*' de routes/web.php.
        //
        // <b>Dos verbos sobre un solo recurso.</b> El checkout no se lista ni
        // se crea: es la vista previa del carrito abierto de quien pide, asi
        // que no hay id que nombrar. GET lo muestra, PATCH guarda el modo de
        // entrega y las observaciones.
        //
        // <b>Nada de esto crea una venta.</b> La venta online nace pagada y
        // la crea CheckoutService::confirmar() cuando el pago se acredita,
        // por el webhook que ya funciona. La preferencia de pago
        // —POST /checkout/pagar y GET /checkout/estado— es el paso 10.

        Route::middleware(['auth:sanctum', 'api.activo', 'api.ambito:tienda', 'api.verificado'])
            ->prefix('checkout')
            ->name('checkout.')
            ->group(function () {

                Route::get('/', [CheckoutController::class, 'index'])
                    ->name('index');

                Route::patch('/', [CheckoutController::class, 'update'])
                    ->name('update');
            });


        // ============================================================
        // PROXIMAS ETAPAS
        // ============================================================

        // Pago con Mercado Pago (paso 10), compras y perfil. Ver
        // claude/plan-api-movil-flutter.md en la base de conocimientos.

    });
