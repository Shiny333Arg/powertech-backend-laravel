<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\PromocionController;


//authentication
Route::get('/authentication', function () {
    return view('authentication.index');
});

Route::get('/authentication/logout', function () {
    return view('authentication.logout');
});

Route::get('/authentication/reset-password', function () {
    return view('authentication.reset-password');
});


//home
Route::get('/', function () {
    return view('home.index');
});


//category
Route::get('/category', [CategoriaController::class, 'index'])
->name('categorias.index');

Route::get('/category/create', [CategoriaController::class, 'create'])
->name('categorias.create');

Route::post('/category', [CategoriaController::class, 'store'])
->name('categorias.store');

Route::get('/category/{categoria}/edit', [CategoriaController::class, 'edit'])
->name('categorias.edit');

Route::put('/category/{categoria}', [CategoriaController::class, 'update'])
->name('categorias.update');

Route::patch('/category/{categoria}/baja', [CategoriaController::class, 'darDeBaja'])
->name('categorias.baja');

Route::patch('/category/{categoria}/reactivar', [CategoriaController::class, 'reactivar'])
->name('categorias.reactivar');

Route::delete('/category/{categoria}', [CategoriaController::class, 'destroy'])
->name('categorias.destroy');



//item
Route::get('/item', [ProductoController::class, 'index'])
->name('items.index');

Route::get('/item/create', [ProductoController::class, 'create'])
->name('items.create');

Route::post('/item', [ProductoController::class, 'store'])
->name('items.store');

Route::get('/item/{producto}/edit', [ProductoController::class, 'edit'])
->name('items.edit');

Route::put('/item/{producto}', [ProductoController::class, 'update'])
->name('items.update');

Route::patch('/item/{producto}/baja', [ProductoController::class, 'darDeBaja'])
->name('items.darDeBaja');

Route::patch('/item/{producto}/reactivar', [ProductoController::class, 'reactivar'])
->name('items.reactivar');

Route::patch('/item/{producto}/publicar', [ProductoController::class, 'publicar'])
->name('items.publicar');

Route::patch('/item/{producto}/despublicar', [ProductoController::class, 'despublicar'])
->name('items.despublicar');

Route::patch('/item/{producto}/stock', [ProductoController::class, 'ajustarStock'])
->name('items.ajustarStock');

Route::get('/item/{producto}/movimientos', [ProductoController::class, 'movimientos'])
->name('items.movimientos');


//sale
Route::get('/sale', function () {
    return view('sale.index');
});

Route::get('/sale/create', function () {
    return view('sale.create');
});

Route::get('/sale/view', function () {
    return view('sale.view');
});


//user
Route::get('/empleado', [EmpleadoController::class, 'index'])
    ->name('user.index');

Route::get('/empleado/create', [EmpleadoController::class, 'create'])
    ->name('user.create');

Route::post('/empleado', [EmpleadoController::class, 'store'])
    ->name('user.store');

Route::get('/empleado/{empleado}/edit', [EmpleadoController::class, 'edit'])
    ->name('user.edit');

Route::put('/empleado/{empleado}', [EmpleadoController::class, 'update'])
    ->name('user.update');

Route::patch('/empleado/{empleado}/baja', [EmpleadoController::class, 'darDeBaja'])
    ->name('user.darDeBaja');

Route::patch('/empleado/{empleado}/reactivar', [EmpleadoController::class, 'reactivar'])
    ->name('user.reactivar');

Route::patch('/empleado/{empleado}/resetear-password', [EmpleadoController::class, 'resetearPassword'])
    ->name('user.resetearPassword');


//proveedor
Route::get('/proveedor', [ProveedorController::class, 'index'])
->name('proveedors.index');

Route::get('/proveedor/create', [ProveedorController::class, 'create'])
->name('proveedors.create');

Route::post('/proveedor', [ProveedorController::class, 'store'])
->name('proveedors.store');

Route::get('/proveedor/{proveedor}/edit', [ProveedorController::class, 'edit'])
->name('proveedors.edit');

Route::put('/proveedor/{proveedor}', [ProveedorController::class, 'update'])
->name('proveedors.update');

Route::patch('/proveedor/{proveedor}/baja', [ProveedorController::class, 'darDeBaja'])
->name('proveedors.darDeBaja');

Route::patch('/proveedor/{proveedor}/reactivar', [ProveedorController::class, 'reactivar'])
->name('proveedors.reactivar');



//help
Route::get('/help', [HelpController::class, 'index'])
->name('help.index');


//promotions

Route::get('/promotion', [PromocionController::class, 'index'])
    ->name('promociones.index');

Route::get('/promotion/create', [PromocionController::class, 'create'])
    ->name('promociones.create');

Route::post('/promotion', [PromocionController::class, 'store'])
    ->name('promociones.store');

Route::get('/promotion/{promocion}/edit', [PromocionController::class, 'edit'])
    ->name('promociones.edit');

Route::put('/promotion/{promocion}', [PromocionController::class, 'update'])
    ->name('promociones.update');

Route::patch('/promotion/{promocion}/baja', [PromocionController::class, 'darDeBaja'])
    ->name('promociones.darDeBaja');

Route::patch('/promotion/{promocion}/reactivar', [PromocionController::class, 'reactivar'])
    ->name('promociones.reactivar');