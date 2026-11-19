<?php

use App\Console\Commands\RevisarReposicion;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


// ============================================================
// TAREAS PROGRAMADAS
// ============================================================
//
// En Laravel 11+ el scheduler vive acá: no hay app/Console/Kernel.php.
//
// Para que esto corra, el servidor necesita una sola entrada de cron:
//   * * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1

// ------------------------------------------------------------
// Reposición de stock
// ------------------------------------------------------------
//
// Barrido diario del catálogo. El camino reactivo (el listener sobre los
// movimientos de stock) ya cubre lo urgente; esto cubre lo que ningún
// movimiento puede señalar: que alguien suba el punto de reposición de un
// producto que ya estaba por debajo, un producto cargado en falta, o eventos
// perdidos porque el worker de colas estaba caído.
//
// A las 7:00 y no a la medianoche: la idea es que el administrativo encuentre
// los pedidos ya armados cuando abre, y no que compitan con los procesos
// nocturnos.
//
// withoutOverlapping() porque el barrido recorre todo el catálogo: si un día
// tarda más de lo previsto, la corrida siguiente no tiene que encimarse y
// evaluar los mismos productos en paralelo.
Schedule::command(RevisarReposicion::class)
    ->dailyAt('07:00')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('queue:work --stop-when-empty --queue=reposicion,default')
    ->everyFiveMinutes()
    ->withoutOverlapping();

// ------------------------------------------------------------
// Limpieza de tokens vencidos
// ------------------------------------------------------------
//
// <b>Es contabilidad, no control de acceso, y la diferencia importa.</b> Un
// token vencido hace diez minutos ya esta rechazado antes de que este cron
// exista: en la API por Guard::isValidAccessToken(), en el intercambio por la
// validacion de expires_at de SesionController y en el panel por el chequeo 4
// de VerificarTokenDeSesionGestion. Si la fila fuera lo unico que corta el
// acceso, cada carga de pagina del panel dependeria de la ultima corrida de una
// tarea programada.
//
// Lo unico que hace es que la tabla no crezca para siempre.
//
// <b>No puede borrar un token vigente.</b> La unica pasada que corre siempre es
// where('expires_at', '<', now()->subHours($hours))->delete(). Hay una segunda,
// por created_at, que se ejecuta solo si config('sanctum.expiration') no es
// null, y ese valor esta en null a proposito (ver config/sanctum.php). Asi que
// ni los 30 dias de Flutter ni las 8 horas de gestion corren riesgo.
//
// --hours=24 deja las filas vencidas un dia mas: alcanza para responder una
// consulta de soporte del tipo "¿cuándo se le venció la sesión?". Diaria basta,
// con el mismo onOneServer() que la reposicion.
Schedule::command('sanctum:prune-expired --hours=24')
    ->daily()
    ->withoutOverlapping()
    ->onOneServer();
