<?php

use App\Models\User;
use Database\Seeders\Maestros\AlicuotaIvaSeeder;
use Database\Seeders\Maestros\CondicionIvaSeeder;
use Database\Seeders\Maestros\PermisoSeeder;
use Database\Seeders\Maestros\RolSeeder;
use Database\Seeders\Maestros\TipoDocumentoSeeder;

/*
|--------------------------------------------------------------------------
| Los cinco listados de gestion se renderizan con los assets compilados
|--------------------------------------------------------------------------
|
| <b>Por que existe este archivo.</b> Las cinco vistas `index` de gestion
| declaraban `@vite('resources/css/gestion/<modulo>/index.css')` y ninguna de
| las cinco hojas estaba en el `input` de `vite.config.js`, asi que no
| entraban al manifest. Tres de los archivos ademas no existian.
|
| El sintoma dependia de como se sirviera la aplicacion, y por eso pasaba
| desapercibido: con `npm run dev` el servidor de Vite resuelve por ruta y
| las pantallas abren; con los assets compilados —como en una entrega— las
| cinco tiraban `Unable to locate file in Vite manifest`.
|
| La correccion registro los dos CSS que existen y quito las tres directivas
| colgadas.
|
| <b>Este es el unico archivo de pruebas que NO usa `withoutVite()`</b>, a
| proposito: es justamente el manifest lo que se quiere probar. Como eso
| exige haber corrido `npm run build`, las pruebas se saltean solas cuando no
| hay manifest, en lugar de fallar por una razon ajena a lo que miden.
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

    $this->actingAs(User::factory()->empleado('administrativo')->create());
});

$faltaElManifest = fn (): bool => ! file_exists(public_path('build/manifest.json'));

$sinManifest = 'Requiere los assets compilados: correr `npm run build` antes.';

test('los cinco listados de gestion se renderizan con el manifest de Vite', function () {
    collect([
        'gestion.categorias.index',
        'gestion.items.index',
        'gestion.promociones.index',
        'gestion.proveedores.index',
        'gestion.empleados.index',
    ])->each(function (string $ruta) {
        $this->get(route($ruta))->assertOk();
    });
})->skip($faltaElManifest, $sinManifest);

test('el manifest incluye los dos CSS de gestion que existen', function () {
    $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);

    expect($manifest)->toHaveKeys([
        'resources/css/app.css',
        'resources/css/components/header.css',
        'resources/css/gestion/category/index.css',
        'resources/css/gestion/item/index.css',
        'resources/js/app.js',
    ]);
})->skip($faltaElManifest, $sinManifest);

/**
 * Las tres hojas que no existen no deben volver por ninguna via: ni como
 * directiva en la vista ni como entrada en `vite.config.js`.
 */
test('ninguna vista pide las tres hojas de estilo inexistentes', function () {
    $vistas = glob(resource_path('views/gestion/*/*.blade.php'));

    $configuracion = file_get_contents(base_path('vite.config.js'));

    foreach (['promotion', 'proveedors', 'user'] as $modulo) {
        $hoja = "resources/css/gestion/{$modulo}/index.css";

        expect(file_exists(base_path($hoja)))->toBeFalse()
            ->and($configuracion)->not->toContain($hoja);

        foreach ($vistas as $vista) {
            expect(file_get_contents($vista))->not->toContain($hoja);
        }
    }
});
