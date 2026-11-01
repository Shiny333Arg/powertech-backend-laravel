<?php

use App\Http\Middleware\VerificarAmbito;
use App\Http\Middleware\VerificarCuentaActiva;
use App\Http\Middleware\VerificarPermiso;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {


        $middleware->trustProxies(at: '*');
        $middleware->validateCsrfTokens(except: ['checkout/webhook']);



        // En cada request web: si la cuenta se dio de baja con la sesion
        // abierta, se la cierra en ese momento.
        $middleware->web(append: [
            VerificarCuentaActiva::class,
        ]);

        // Uso en rutas:
        //   ->middleware(['auth', 'ambito:gestion'])   separa las dos apps
        //   ->middleware('can:update,producto')        policy + estado
        //   ->middleware('permiso:stock.ajustar')      clave suelta
        //
        // 'ambito' decide a que aplicacion entra cada rol; 'can' y 'permiso'
        // deciden que puede hacer adentro. Son capas distintas: un cajero
        // pertenece al ambito gestion y aun asi no abre /gestion/empleado.
        $middleware->alias([
            'ambito'  => VerificarAmbito::class,
            'permiso' => VerificarPermiso::class,
        ]);

        // Un invitado que pide una pagina protegida va al login (hay uno solo).
        $middleware->redirectGuestsTo(fn() => route('login'));

        // Un usuario ya logueado que abre el login o el registro va al inicio
        // de su ambito.
        $middleware->redirectUsersTo(fn(Request $request) => route($request->user()->rutaInicio()));

        $middleware->validateCsrfTokens(except: ['checkout/webhook']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Nada que configurar: una AuthorizationException ya sale como 403 y
        // Laravel busca sola resources/views/errors/403.blade.php, que es
        // donde conviene poner el layout de gestion y un enlace de vuelta al
        // tablero. Sin esa vista, el empleado que pega una URL que no le toca
        // ve la pantalla pelada del framework.
    })->create();
