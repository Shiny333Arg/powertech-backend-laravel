<?php

use App\Http\Middleware\VerificarAmbito;
use App\Http\Middleware\VerificarCuentaActiva;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // En cada request web: si la cuenta se dio de baja con la sesion
        // abierta, se la cierra en ese momento.
        $middleware->web(append: [
            VerificarCuentaActiva::class,
        ]);

        // Uso en rutas: ->middleware(['auth', 'ambito:gestion'])
        $middleware->alias([
            'ambito' => VerificarAmbito::class,
        ]);

        // Un invitado que pide una pagina protegida va al login (hay uno solo).
        $middleware->redirectGuestsTo(fn () => route('login'));

        // Un usuario ya logueado que abre el login o el registro va al inicio
        // de su ambito.
        $middleware->redirectUsersTo(fn (Request $request) => route($request->user()->rutaInicio()));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
