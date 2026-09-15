<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoriaController;


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
Route::get('/item', function () {
    return view('item.index');
});

Route::get('/item/create', function () {
    return view('item.create');
});

Route::get('/item/edit', function () {
    return view('item.edit');
});


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
Route::get('/user', function () {
    return view('user.index');
});

Route::get('/user/create', function () {
    return view('user.create');
});

Route::get('/user/edit', function () {
    return view('user.edit');
});

Route::get('/user/my-account', function () {
    return view('user.my-account');
});