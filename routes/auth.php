<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\InvitacionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Autenticacion (empleados y clientes)
|--------------------------------------------------------------------------
|
| Un solo login para todos. Los nombres de ruta son los de Breeze, asi las
| vistas que genera siguen funcionando.
|
| Se quito la confirmacion de contraseña (password.confirm) porque ninguna
| pantalla la usa.
|
*/

Route::middleware('guest')->group(function () {

    // Registro: solo clientes. Los empleados los da de alta un administrativo.
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    // "Olvidé mi contraseña", para empleados y clientes.
    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');

    // Invitacion del empleado. La firma la valida el controlador (no el
    // middleware 'signed') para mostrar un mensaje util si el enlace vencio.
    Route::get('invitacion/{user}', [InvitacionController::class, 'show'])
        ->name('invitacion.show');

    Route::post('invitacion/{user}', [InvitacionController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('invitacion.store');
    
});

Route::middleware('auth')->group(function () {

    // Verificacion de correo (la exige el middleware 'verified' en la tienda).
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    // Cambio de la propia contraseña (empleados y clientes).
    Route::put('password', [PasswordController::class, 'update'])
        ->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
