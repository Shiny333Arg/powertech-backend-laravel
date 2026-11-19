<?php

use App\Models\Categoria;
use App\Models\User;
use Database\Seeders\Maestros\PermisoSeeder;
use Database\Seeders\Maestros\RolSeeder;

/*
|--------------------------------------------------------------------------
| El formulario Blade de categorias despues de compartir el FormRequest
|--------------------------------------------------------------------------
|
| CategoriaRequest pasa a servir a dos clientes: el formulario del panel y
| /api/v1/gestion/categorias. Los dos cambios que eso trajo tocan justo lo que
| el formulario manda:
|
|   - 'activo' solo se normaliza si vino en la peticion
|   - 'orden' se acepta si viene
|
| El formulario manda SIEMPRE 'activo' —tiene un input hidden en 0 junto al
| checkbox en 1, ver category/edit.blade.php— y nunca manda 'orden'. Estas dos
| pruebas fijan ese contrato: si alguien saca el hidden del formulario o cambia
| la normalizacion, el checkbox deja de poder dar de baja y se ve aca.
|
*/

beforeEach(function () {
    $this->seed([RolSeeder::class, PermisoSeeder::class]);
});

it('crea la categoria desde el formulario con el slug derivado del nombre', function () {
    comoEmpleado(User::factory()->empleado('administrativo')->create())
        ->post(route('gestion.categorias.store'), [
            'nombre' => 'Placas de video',
            'activo' => '1',
        ])
        ->assertRedirect(route('gestion.categorias.index'))
        ->assertSessionHas('exito');

    $this->assertDatabaseHas('categorias', [
        'nombre' => 'Placas de video',
        'slug'   => 'placas-de-video',
        'activo' => true,
        'orden'  => 0, // el formulario no manda 'orden': queda el default
    ]);
});

it('da de baja desde el checkbox del formulario de edicion', function () {
    $categoria = Categoria::create([
        'nombre' => 'Monitores',
        'slug'   => 'monitores',
        'orden'  => 1,
        'activo' => true,
    ]);

    // Checkbox destildado: el navegador manda solo el hidden en 0.
    comoEmpleado(User::factory()->empleado('administrativo')->create())
        ->put(route('gestion.categorias.update', $categoria), [
            'nombre' => 'Monitores',
            'activo' => '0',
        ])
        ->assertRedirect(route('gestion.categorias.index'));

    expect($categoria->refresh()->activo)->toBeFalse();
});
