<?php

use App\Http\Controllers\HelpController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\gestion\CategoriaController;
use App\Http\Controllers\gestion\EmpleadoController;
use App\Http\Controllers\gestion\ProductoController;
use App\Http\Controllers\gestion\PromocionController;
use App\Http\Controllers\gestion\ProveedorController;
use Illuminate\Support\Facades\Route;


// ============================================================
// AUTENTICACIÓN
// ============================================================
//
// Login, registro, "olvidé mi contraseña", invitación de empleados,
// verificación de correo, cambio de contraseña y logout.

require __DIR__.'/auth.php';


// ============================================================
// TIENDA - PÚBLICO
// ============================================================

// ========================================================
// HOME
// ========================================================

Route::get('/', function () {
    return view('home.index');
})->name('cliente.home');


// ========================================================
// AYUDA
// ========================================================

Route::prefix('help')
    ->name('cliente.help.')
    ->group(function () {

        Route::get('/', [HelpController::class, 'index'])
            ->name('index');
    });


// ============================================================
// TIENDA - CLIENTE LOGUEADO
// ============================================================
//
// Cuando exista el checkout, sus rutas llevan ademas 'verified':
// no se compra con un correo sin confirmar.

Route::middleware(['auth', 'ambito:tienda'])
    ->group(function () {

        // ========================================================
        // PERFIL (datos personales y fiscales)
        // ========================================================
        // Los nombres profile.* son los de Breeze. La contraseña se
        // cambia con password.update (routes/auth.php).

        Route::get('/perfil', [ProfileController::class, 'edit'])
            ->name('profile.edit');

        Route::patch('/perfil', [ProfileController::class, 'update'])
            ->name('profile.update');
    });


// ============================================================
// GESTIÓN
// ============================================================
//
// Solo empleados logueados. Un cliente que abre /gestion vuelve a la tienda.

Route::prefix('gestion')
    ->name('gestion.')
    ->middleware(['auth', 'ambito:gestion'])
    ->group(function () {


        // ========================================================
        // HOME
        // ========================================================

        Route::get('/', function () {
            return view('gestion.home.index');
        })->name('home');


        // ========================================================
        // MI CUENTA
        // ========================================================
        // El empleado solo puede cambiar su contraseña: el formulario
        // de esta vista envia a password.update (routes/auth.php).

        Route::view('/mi-cuenta', 'gestion.user.my-account')
            ->name('mi-cuenta');


        // ========================================================
        // CATEGORÍAS
        // ========================================================

        Route::prefix('category')
            ->name('categorias.')
            ->group(function () {

                Route::get('/', [CategoriaController::class, 'index'])
                    ->name('index');

                Route::get('/create', [CategoriaController::class, 'create'])
                    ->name('create');

                Route::post('/', [CategoriaController::class, 'store'])
                    ->name('store');

                Route::get('/{categoria}/edit', [CategoriaController::class, 'edit'])
                    ->name('edit');

                Route::put('/{categoria}', [CategoriaController::class, 'update'])
                    ->name('update');

                Route::patch('/{categoria}/baja', [CategoriaController::class, 'darDeBaja'])
                    ->name('baja');

                Route::patch('/{categoria}/reactivar', [CategoriaController::class, 'reactivar'])
                    ->name('reactivar');

                Route::delete('/{categoria}', [CategoriaController::class, 'destroy'])
                    ->name('destroy');
            });


        // ========================================================
        // PRODUCTOS
        // ========================================================

        Route::prefix('item')
            ->name('items.')
            ->group(function () {

                Route::get('/', [ProductoController::class, 'index'])
                    ->name('index');

                Route::get('/create', [ProductoController::class, 'create'])
                    ->name('create');

                Route::post('/', [ProductoController::class, 'store'])
                    ->name('store');

                Route::get('/{producto}/edit', [ProductoController::class, 'edit'])
                    ->name('edit');

                Route::put('/{producto}', [ProductoController::class, 'update'])
                    ->name('update');

                Route::patch('/{producto}/baja', [ProductoController::class, 'darDeBaja'])
                    ->name('darDeBaja');

                Route::patch('/{producto}/reactivar', [ProductoController::class, 'reactivar'])
                    ->name('reactivar');

                Route::patch('/{producto}/publicar', [ProductoController::class, 'publicar'])
                    ->name('publicar');

                Route::patch('/{producto}/despublicar', [ProductoController::class, 'despublicar'])
                    ->name('despublicar');

                Route::patch('/{producto}/stock', [ProductoController::class, 'ajustarStock'])
                    ->name('ajustarStock');

                Route::get('/{producto}/movimientos', [ProductoController::class, 'movimientos'])
                    ->name('movimientos');
            });


        // ========================================================
        // VENTAS
        // ========================================================

        Route::prefix('sale')
            ->name('ventas.')
            ->group(function () {

                Route::get('/', function () {
                    return view('gestion.sale.index');
                })->name('index');

                Route::get('/create', function () {
                    return view('gestion.sale.create');
                })->name('create');

                Route::get('/view', function () {
                    return view('gestion.sale.view');
                })->name('view');
            });


        // ========================================================
        // EMPLEADOS
        // ========================================================

        Route::prefix('empleado')
            ->name('empleados.')
            ->group(function () {

                Route::get('/', [EmpleadoController::class, 'index'])
                    ->name('index');

                Route::get('/create', [EmpleadoController::class, 'create'])
                    ->name('create');

                Route::post('/', [EmpleadoController::class, 'store'])
                    ->name('store');

                Route::get('/{empleado}/edit', [EmpleadoController::class, 'edit'])
                    ->name('edit');

                Route::put('/{empleado}', [EmpleadoController::class, 'update'])
                    ->name('update');

                Route::patch('/{empleado}/baja', [EmpleadoController::class, 'darDeBaja'])
                    ->name('darDeBaja');

                Route::patch('/{empleado}/reactivar', [EmpleadoController::class, 'reactivar'])
                    ->name('reactivar');

                // Envia un mail: es una accion, no una modificacion del recurso.
                Route::post('/{empleado}/reenviar-invitacion', [EmpleadoController::class, 'reenviarInvitacion'])
                    ->name('reenviarInvitacion');
            });


        // ========================================================
        // PROVEEDORES
        // ========================================================

        Route::prefix('proveedor')
            ->name('proveedores.')
            ->group(function () {

                Route::get('/', [ProveedorController::class, 'index'])
                    ->name('index');

                Route::get('/create', [ProveedorController::class, 'create'])
                    ->name('create');

                Route::post('/', [ProveedorController::class, 'store'])
                    ->name('store');

                Route::get('/{proveedor}/edit', [ProveedorController::class, 'edit'])
                    ->name('edit');

                Route::put('/{proveedor}', [ProveedorController::class, 'update'])
                    ->name('update');

                Route::patch('/{proveedor}/baja', [ProveedorController::class, 'darDeBaja'])
                    ->name('darDeBaja');

                Route::patch('/{proveedor}/reactivar', [ProveedorController::class, 'reactivar'])
                    ->name('reactivar');
            });


        // ========================================================
        // PROMOCIONES
        // ========================================================

        Route::prefix('promotion')
            ->name('promociones.')
            ->group(function () {

                Route::get('/', [PromocionController::class, 'index'])
                    ->name('index');

                Route::get('/create', [PromocionController::class, 'create'])
                    ->name('create');

                Route::post('/', [PromocionController::class, 'store'])
                    ->name('store');

                Route::get('/{promocion}/edit', [PromocionController::class, 'edit'])
                    ->name('edit');

                Route::put('/{promocion}', [PromocionController::class, 'update'])
                    ->name('update');

                Route::patch('/{promocion}/baja', [PromocionController::class, 'darDeBaja'])
                    ->name('darDeBaja');

                Route::patch('/{promocion}/reactivar', [PromocionController::class, 'reactivar'])
                    ->name('reactivar');
            });
    });
