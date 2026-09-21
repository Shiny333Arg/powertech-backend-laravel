<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';


// ============================================================
// CONTROLLERS - GENERALES
// ============================================================

use App\Http\Controllers\HelpController;


// ============================================================
// CONTROLLERS - GESTIÓN
// ============================================================

use App\Http\Controllers\gestion\CategoriaController;
use App\Http\Controllers\gestion\EmpleadoController;
use App\Http\Controllers\gestion\ProductoController;
use App\Http\Controllers\gestion\PromocionController;
use App\Http\Controllers\gestion\ProveedorController;


// ============================================================
// AUTENTICACIÓN
// ============================================================

Route::prefix('authentication')
    ->name('authentication.')
    ->group(function () {

        Route::get('/', function () {
            return view('authentication.index');
        })->name('index');

        Route::get('/logout', function () {
            return view('authentication.logout');
        })->name('logout');

        Route::get('/reset-password', function () {
            return view('authentication.reset-password');
        })->name('reset-password');
    });


// ============================================================
// CLIENTE
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
// GESTIÓN
// ============================================================

Route::prefix('gestion')
    ->name('gestion.')
    ->group(function () {


        // ========================================================
        // HOME
        // ========================================================

        Route::get('/', function () {
            return view('gestion.home.index');
        })->name('home');


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

                Route::patch('/{empleado}/resetear-password', [EmpleadoController::class, 'resetearPassword'])
                    ->name('resetearPassword');
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