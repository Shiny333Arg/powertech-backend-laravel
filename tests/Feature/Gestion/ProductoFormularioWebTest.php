<?php

use App\Models\AlicuotaIva;
use App\Models\Producto;
use App\Models\User;
use Database\Seeders\Maestros\AlicuotaIvaSeeder;
use Database\Seeders\Maestros\PermisoSeeder;
use Database\Seeders\Maestros\RolSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| El formulario Blade de productos despues de compartir el manejo de imagenes
|--------------------------------------------------------------------------
|
| Guardar y borrar la foto salieron de ProductoController (panel) a
| App\Support\ImagenProducto, porque ahora tambien las sube la API de gestion y
| el directorio de destino tiene que ser uno solo.
|
| Estas dos pruebas fijan que el panel siga haciendo exactamente lo mismo: el
| alta guarda en 'productos/' del disco publico y carga el stock inicial como
| movimiento, y la edicion reemplaza la foto y borra la anterior. No existian
| antes —lo unico cubierto de este controlador era la autorizacion de las
| acciones de estado—, asi que el cambio habria pasado sin red.
|
*/

beforeEach(function () {
    $this->seed([RolSeeder::class, PermisoSeeder::class, AlicuotaIvaSeeder::class]);
});

/** El cuerpo que manda el formulario de alta. */
function datosDelFormulario(array $cambios = []): array
{
    return array_merge([
        'codigo'              => 'WEB-001',
        'nombre'              => 'Monitor del formulario',
        'categoria_id'        => categoriaDePrueba()->id,
        'alicuota_iva_id'     => AlicuotaIva::query()->value('id'),
        'precio'              => 90000,
        'punto_reposicion'    => 2,
        'cantidad_reposicion' => 5,
    ], $cambios);
}

it('crea el producto desde el formulario con imagen y stock inicial', function () {
    Storage::fake('public');

    comoEmpleado(User::factory()->empleado('administrativo')->create())
        ->post(route('gestion.items.store'), datosDelFormulario([
            'stock_inicial' => 7,
            'imagen'        => UploadedFile::fake()->image('monitor.jpg'),
        ]))
        ->assertRedirect(route('gestion.items.index'))
        ->assertSessionHas('exito');

    $producto = Producto::where('codigo', 'WEB-001')->sole();

    expect($producto->imagen)->toStartWith('productos/')
        ->and($producto->stock_fisico)->toBe(7);

    Storage::disk('public')->assertExists($producto->imagen);

    $this->assertDatabaseHas('movimientos_stock', [
        'producto_id'     => $producto->id,
        'tipo'            => 'inicial',
        'cantidad_fisico' => 7,
    ]);
});

it('reemplaza la imagen desde el formulario de edición y borra la anterior', function () {
    Storage::fake('public');

    $producto = productoDePrueba(['codigo' => 'WEB-002']);

    $anterior = UploadedFile::fake()->image('vieja.jpg')->store('productos', 'public');
    $producto->update(['imagen' => $anterior]);

    comoEmpleado(User::factory()->empleado('administrativo')->create())
        ->put(route('gestion.items.update', $producto), datosDelFormulario([
            'codigo' => 'WEB-002',
            'imagen' => UploadedFile::fake()->image('nueva.jpg'),
        ]))
        ->assertRedirect(route('gestion.items.index'));

    $nueva = $producto->refresh()->imagen;

    expect($nueva)->not->toBe($anterior);
    Storage::disk('public')->assertExists($nueva);
    Storage::disk('public')->assertMissing($anterior);
});
