
<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CarritoController;
use App\Http\Controllers\Api\V1\CatalogoController;
use App\Http\Controllers\Api\V1\CheckoutController;
use App\Http\Controllers\Api\V1\CompraController;
use App\Http\Controllers\Api\V1\DatosFiscalesController;
use App\Http\Controllers\Api\V1\PasswordRecuperacionController;
use App\Http\Controllers\Api\V1\PerfilController;
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

        // Las unicas rutas sin token. Llevan throttle propio, mas estricto
        // que el del grupo: son las que un atacante golpea.
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

        // ------------------------------------------------------------
        // Contraseña olvidada
        // ------------------------------------------------------------
        //
        // Publicas por definicion: quien olvido la contraseña no puede
        // iniciar sesion, asi que no hay token que traer. Es el mismo
        // criterio que routes/auth.php, donde 'forgot-password' y
        // 'reset-password' estan dentro del grupo 'guest'.
        //
        // <b>Reutilizan el mecanismo web, no lo repiten.</b> Las dos
        // delegan en App\Services\RecuperacionPassword, que es la clase
        // que hoy usan tambien Auth\PasswordResetLinkController y
        // Auth\NewPasswordController. El token es el del broker de
        // Laravel: hasheado en password_reset_tokens, vence en 60 minutos
        // y se consume al usarse (config/auth.php, passwords.users).
        //
        // <b>'throttle:6,1' es el mismo numero que la web</b> le pone a
        // 'password.email' en routes/auth.php. Van las dos rutas y no solo
        // la primera: la segunda recibe intentos de token, y aunque el
        // token es largo y vence rapido, no hay razon para dejarla contar
        // hasta 60 por minuto como el resto del grupo.
        //
        // <b>No emiten token de Sanctum.</b> Restablecer la contraseña
        // termina en la pantalla de ingreso, igual que en la web. Ver
        // PasswordRecuperacionController.

        Route::post('auth/password/olvide', [PasswordRecuperacionController::class, 'enviarEnlace'])
            ->middleware('throttle:6,1')
            ->name('auth.password.olvide');

        Route::post('auth/password/restablecer', [PasswordRecuperacionController::class, 'restablecer'])
            ->middleware('throttle:6,1')
            ->name('auth.password.restablecer');

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

                // Crea la orden de pago en Mercado Pago y devuelve el link.
                // No crea la venta ni reserva stock: eso ocurre cuando el
                // pago se acredita, por el webhook.
                Route::post('/pagar', [CheckoutController::class, 'pagar'])
                    ->name('pagar');

                // Que paso con el ultimo intento de pago. La app lo consulta
                // al volver del navegador. Lee lo que dejo el webhook; no
                // consulta a Mercado Pago ni cree nada de la query string
                // con la que el cliente vuelve.
                Route::get('/estado', [CheckoutController::class, 'estado'])
                    ->name('estado');
            });


        // ============================================================
        // MIS COMPRAS
        // ============================================================

        // Tres capas, no cuatro: aca NO va 'api.verificado'.
        //
        // routes/web.php exige 'verified' en el bloque del carrito y en el del
        // checkout, y no en "mis compras". Mirar el historial no necesita
        // correo confirmado; armar un pedido si. Un cliente que se registro,
        // compro en el mostrador y todavia no confirmo el correo tiene que
        // poder ver esa compra. Es la misma regla que la web, y esta anotada
        // tambien en el bloque del carrito de arriba.
        //
        // <b>Solo lectura.</b> No hay POST, PATCH ni DELETE: el cliente no
        // cancela ni modifica su pedido desde la app. Cancelar una venta
        // pagada libera stock comprometido y, con factura autorizada, exige
        // nota de credito.
        //
        // <b>El historial es siempre el de quien trae el token.</b> La lista
        // no lleva parametros de cliente. El detalle si lleva id de venta, y
        // una venta ajena responde 404 —no 403—: un 403 confirmaria que ese
        // numero de pedido existe. Un presupuesto recibe el mismo 404, porque
        // no es una compra. Ver App\Support\CompraDelCliente.

        Route::middleware(['auth:sanctum', 'api.activo', 'api.ambito:tienda'])
            ->prefix('compras')
            ->name('compras.')
            ->group(function () {

                Route::get('/', [CompraController::class, 'index'])
                    ->name('index');

                Route::get('/{venta}', [CompraController::class, 'show'])
                    ->whereNumber('venta')
                    ->name('show');
            });


        // ============================================================
        // PERFIL
        // ============================================================

        // Tres capas, sin 'api.verificado', y acá es donde más importa:
        // routes/web.php deja el bloque de perfil bajo 'auth' + 'ambito:tienda'
        // y exige 'verified' recién en el carrito y el checkout. Si editar el
        // perfil pidiera el correo confirmado, un cliente que se equivocó al
        // escribirlo al registrarse no podría corregirlo nunca: esta es la
        // puerta de salida de ese callejón.
        //
        // <b>Nunca lleva id.</b> Ni en la URL ni en el cuerpo: el perfil es
        // siempre el de quien trae el token. Los FormRequest devuelven listas
        // blancas de columnas (datosDelUsuario / datosDelCliente), así que un
        // `user_id` o `cliente_id` de más no hace nada.
        //
        // <b>Dos recursos y no uno.</b> Los datos y la contraseña son los dos
        // formularios independientes de cliente/perfil/edit.blade.php, con
        // validaciones distintas: una puede fallar sin arrastrar a la otra. El
        // verbo de la contraseña es PUT, igual que en routes/auth.php.

        Route::middleware(['auth:sanctum', 'api.activo', 'api.ambito:tienda'])
            ->prefix('perfil')
            ->name('perfil.')
            ->group(function () {

                Route::get('/', [PerfilController::class, 'show'])
                    ->name('show');

                Route::patch('/', [PerfilController::class, 'update'])
                    ->name('update');

                // Cambia la credencial: throttle propio, más estricto que el
                // del grupo, igual que las rutas de autenticación.
                Route::put('/password', [PerfilController::class, 'password'])
                    ->middleware('throttle:6,1')
                    ->name('password');
            });

    });
