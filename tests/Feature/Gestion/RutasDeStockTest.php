<?php

use App\Models\User;
use Database\Seeders\Maestros\AlicuotaIvaSeeder;
use Database\Seeders\Maestros\CondicionIvaSeeder;
use Database\Seeders\Maestros\PermisoSeeder;
use Database\Seeders\Maestros\RolSeeder;
use Database\Seeders\Maestros\TipoDocumentoSeeder;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Las rutas de stock, despues de resolver la superposicion
|--------------------------------------------------------------------------
|
| routes/web.php declaraba `items.ajustarStock` e `items.movimientos`
| apuntando a dos metodos de ProductoController que estaban COMENTADOS: las
| rutas resolvian, la URL se generaba bien y recien al invocarlas Laravel
| fallaba por metodo inexistente. O sea, 500.
|
| Y no eran rutas huerfanas: dos vistas las usaban. El boton "Historial de
| stock" de la pantalla de edicion, y —mas llamativo— el formulario de
| filtros del propio kardex, que al aplicar cualquier filtro mandaba al
| usuario a la ruta rota.
|
| <b>Por eso el arreglo no fue borrar y listo.</b> Primero se reapuntaron las
| dos vistas a `gestion.stock.show`, que es la pantalla que de verdad muestra
| el historial, y despues se quitaron las rutas y los metodos comentados.
| Borrarlas antes de reapuntar las vistas habria cambiado el 500 por un
| RouteNotFoundException al renderizar, que es peor.
|
| <b>Lo que estas pruebas cuidan</b> es justamente eso: que ninguna vista
| quede apuntando a una ruta que no existe. Un `route()` roto no se nota
| hasta que alguien abre la pantalla.
|
| `withoutVite()` esta porque las dos vistas extienden el layout de gestion,
| que compila assets: sin eso la prueba exigiria haber corrido `npm run
| build` y fallaria por una razon que no tiene nada que ver con lo que mide.
|
*/

beforeEach(function () {
    $this->seed([
        RolSeeder::class,
        PermisoSeeder::class,
        TipoDocumentoSeeder::class,
        CondicionIvaSeeder::class,
        AlicuotaIvaSeeder::class,
    ]);

    $this->withoutVite();
});

test('las rutas rotas de item ya no existen', function () {
    expect(Route::has('gestion.items.movimientos'))->toBeFalse()
        ->and(Route::has('gestion.items.ajustarStock'))->toBeFalse();
});

test('el stock sigue teniendo su kardex, su ajuste y su carga inicial', function () {
    expect(Route::has('gestion.stock.show'))->toBeTrue()
        ->and(Route::has('gestion.stock.ajustar'))->toBeTrue()
        ->and(Route::has('gestion.stock.carga-inicial'))->toBeTrue();
});

/** Si el enlace "Historial de stock" quedara colgado, esto explota al renderizar. */
test('la pantalla de edicion de un producto se renderiza', function () {
    $this->actingAs(User::factory()->empleado('administrativo')->create());

    $producto = productoDePrueba();

    $this->get("/gestion/item/{$producto->id}/edit")
        ->assertOk()
        ->assertSee(route('gestion.stock.show', $producto), false);
});

/** Lo mismo para el formulario de filtros del kardex. */
test('el kardex de un producto se renderiza', function () {
    $this->actingAs(User::factory()->empleado('administrativo')->create());

    $producto = productoDePrueba();

    $this->get("/gestion/stock/{$producto->id}")->assertOk();
});

/**
 * El caso que antes daba 500: aplicar un filtro en el kardex.
 *
 * Responde 200. Los parametros `tipo`, `desde` y `hasta` del formulario
 * todavia no los lee ningun controlador —ver el resumen de la etapa—, asi que
 * la pantalla vuelve sin filtrar; lo que esta prueba fija es que ya no se
 * cae.
 */
test('aplicar un filtro en el kardex no rompe', function () {
    $this->actingAs(User::factory()->empleado('administrativo')->create());

    $producto = productoDePrueba();

    $this->get("/gestion/stock/{$producto->id}?tipo=ajuste&desde=2026-01-01&hasta=2026-12-31")
        ->assertOk();
});

test('el cajero no entra al kardex', function () {
    $this->actingAs(User::factory()->empleado('cajero')->create());

    $producto = productoDePrueba();

    $this->get("/gestion/stock/{$producto->id}")->assertForbidden();
});
