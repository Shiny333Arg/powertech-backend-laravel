<?php

use App\Models\AlicuotaIva;
use App\Models\Permiso;
use App\Models\User;
use Database\Seeders\Maestros\AlicuotaIvaSeeder;
use Database\Seeders\Maestros\CondicionIvaSeeder;
use Database\Seeders\Maestros\PermisoSeeder;
use Database\Seeders\Maestros\RolSeeder;
use Database\Seeders\Maestros\TipoDocumentoSeeder;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;

/*
|--------------------------------------------------------------------------
| Las pantallas de productos despues de migrarlas a la API
|--------------------------------------------------------------------------
|
| Mismo contrato que fijo CategoriaPantallaApiTest para categorias: las tres
| paginas entregan la estructura visual, los desplegables del formulario y los
| permisos del rol, y ningun dato del producto. Los datos los pide el
| JavaScript a /api/v1/gestion/productos, que ya tiene su cobertura completa en
| tests/Feature/Api/V1/Gestion/ProductoTest.php.
|
| <b>Que se puede verificar desde Pest y que no.</b> El proyecto no tiene
| corredor de pruebas de front-end, asi que lo que se comprueba aca es la mitad
| que produce Laravel: que el producto NO viaje en la pagina —si volviera a
| viajar, la pantalla podria mostrarlo sin pasar por la API— y que los botones
| dependan de la policy. El comportamiento del navegador se demuestra a mano.
|
| `withoutVite()` porque las tres vistas extienden el layout de gestion.
|
*/

beforeEach(function () {
    $this->seed([
        RolSeeder::class,
        PermisoSeeder::class,
        AlicuotaIvaSeeder::class,
        TipoDocumentoSeeder::class,
        CondicionIvaSeeder::class,
    ]);

    $this->withoutVite();
});

function administrativoDePantalla(): User
{
    return User::factory()->empleado('administrativo')->create();
}

// ============================================================
// La pagina no trae datos de negocio
// ============================================================

it('entrega el listado sin los productos adentro', function () {
    productoDePrueba(['codigo' => 'MON-27', 'nombre' => 'Monitor curvo', 'marca' => 'Acme']);

    comoEmpleado(administrativoDePantalla())
        ->get(route('gestion.items.index'))
        ->assertOk()
        // La tabla esta, vacia y con el hueco que llena el JS.
        ->assertSee('contenidoTablaProductos')
        ->assertSee('paginadoProductos')
        // Los datos no.
        ->assertDontSee('MON-27')
        ->assertDontSee('Monitor curvo')
        ->assertDontSee('Acme');
});

it('entrega la edicion sin los datos del producto', function () {
    $producto = productoDePrueba([
        'codigo' => 'PLA-9',
        'nombre' => 'Placa de video',
        'marca' => 'Marca Testigo',
        'precio' => 987654.32,
    ]);

    comoEmpleado(administrativoDePantalla())
        ->get(route('gestion.items.edit', $producto))
        ->assertOk()
        // Solo el id, para que el JS sepa a que recurso pedirle los datos.
        ->assertSee('id: '.$producto->id, false)
        ->assertDontSee('PLA-9')
        ->assertDontSee('Placa de video')
        ->assertDontSee('Marca Testigo')
        ->assertDontSee('987654.32');
});

it('no filtra el costo ni los saldos de stock en la pagina de edicion', function () {
    // El costo es el dato que la tienda nunca ve: que no salga en el HTML de
    // la pagina es lo que garantiza que viaje por la API y bajo su permiso.
    $producto = productoDePrueba([
        'codigo' => 'COS-1',
        'costo' => 777777.77,
        'stock_fisico' => 4242,
    ]);

    comoEmpleado(administrativoDePantalla())
        ->get(route('gestion.items.edit', $producto))
        ->assertOk()
        ->assertDontSee('777777.77')
        ->assertDontSee('4242');
});

it('entrega el alta como un formulario vacio', function () {
    comoEmpleado(administrativoDePantalla())
        ->get(route('gestion.items.create'))
        ->assertOk()
        ->assertSee('formularioProducto')
        // Los desplegables siguen viniendo del servidor: son el catalogo de
        // opciones del formulario, no los datos del producto.
        ->assertSee('alicuota_iva_id');
});

// ============================================================
// Las rutas web de negocio ya no existen
// ============================================================
//
// No alcanza con que ninguna pantalla las use: mientras estuvieran
// publicadas seguian siendo una via alternativa para ejecutar operaciones de
// negocio desde un controlador web, que es lo que el requisito 3 pide cerrar.
// Se retiraron las seis y quedaron solo las tres que entregan paginas.

it('retiro las rutas web que ejecutaban operaciones de negocio', function () {
    foreach (['store', 'update', 'darDeBaja', 'reactivar', 'publicar', 'despublicar'] as $retirada) {
        expect(Route::has("gestion.items.{$retirada}"))
            ->toBeFalse("La ruta web gestion.items.{$retirada} sigue publicada");
    }
});

it('conserva las rutas web que entregan las paginas Blade', function () {
    foreach (['index', 'create', 'edit'] as $pagina) {
        expect(Route::has("gestion.items.{$pagina}"))->toBeTrue();
    }
});

it('responde 404 a las direcciones de negocio que ya no existen', function () {
    $producto = productoDePrueba(['codigo' => 'RUT-1', 'publicado_app' => true]);

    $empleado = administrativoDePantalla();

    // El administrativo tiene todos los permisos: si algo contestara 2xx o
    // 302, seria porque la ruta sigue viva, no porque le falte autorizacion.
    //
    // El alta contesta 405 y no 404 porque /gestion/item sigue existiendo para
    // el GET del listado: lo que ya no existe es el verbo. Para la operacion
    // da lo mismo —no se ejecuta—, pero la prueba dice cual es cual en vez de
    // aceptar cualquiera de los dos.
    comoEmpleado($empleado)->post('/gestion/item', [])->assertMethodNotAllowed();

    foreach ([
        ['put', "/gestion/item/{$producto->id}"],
        ['patch', "/gestion/item/{$producto->id}/baja"],
        ['patch', "/gestion/item/{$producto->id}/reactivar"],
        ['patch', "/gestion/item/{$producto->id}/publicar"],
        ['patch', "/gestion/item/{$producto->id}/despublicar"],
    ] as [$verbo, $direccion]) {
        comoEmpleado($empleado)->{$verbo}($direccion)->assertNotFound();
    }

    expect($producto->refresh()->activo)->toBeTrue()
        ->and($producto->publicado_app)->toBeTrue();
});

it('no deja ningun formulario apuntando al panel', function () {
    $producto = productoDePrueba(['codigo' => 'ACC-1']);

    $empleado = administrativoDePantalla();

    // Se mira el `action="..."` y no la URL suelta: el formulario de filtros
    // del listado sigue siendo un GET legitimo contra la propia pagina.
    comoEmpleado($empleado)
        ->get(route('gestion.items.index'))
        ->assertOk()
        ->assertDontSee('name="_method"', false);

    comoEmpleado($empleado)
        ->get(route('gestion.items.create'))
        ->assertOk()
        ->assertDontSee('action="'.url('/gestion/item').'"', false)
        ->assertDontSee('name="_method"', false);

    comoEmpleado($empleado)
        ->get(route('gestion.items.edit', $producto))
        ->assertOk()
        ->assertDontSee('action="'.url("/gestion/item/{$producto->id}").'"', false)
        ->assertDontSee('name="_method"', false);
});

// ============================================================
// Los botones salen de la policy
// ============================================================

it('le dice a la pantalla lo que el rol puede hacer', function () {
    comoEmpleado(administrativoDePantalla())
        ->get(route('gestion.items.index'))
        ->assertOk()
        ->assertSee('"crear":true', false)
        ->assertSee('"editar":true', false)
        ->assertSee('"cambiarEstado":true', false)
        ->assertSee('"publicar":true', false)
        ->assertSee('"verStock":true', false)
        ->assertSee('columnaOpciones');
});

it('al vendedor le deja mirar el catalogo y el historial, nada mas', function () {
    // El vendedor tiene 'producto.ver', 'categoria.ver' y 'stock.ver'.
    comoEmpleado(User::factory()->empleado('vendedor')->create())
        ->get(route('gestion.items.index'))
        ->assertOk()
        ->assertSee('"crear":false', false)
        ->assertSee('"editar":false', false)
        ->assertSee('"cambiarEstado":false', false)
        ->assertSee('"publicar":false', false)
        // El historial de stock es del modulo stock, y ese permiso si lo
        // tiene: la pantalla del kardex ya le estaba permitida por URL.
        ->assertSee('"verStock":true', false)
        ->assertDontSee(route('gestion.items.create'), false);
});

it('no le muestra el catalogo al cajero', function () {
    // El cajero no tiene ningun permiso del catalogo.
    comoEmpleado(User::factory()->empleado('cajero')->create())
        ->get(route('gestion.items.index'))
        ->assertForbidden();
});

// ============================================================
// Publicacion: permiso propio
// ============================================================

it('habilita el interruptor de publicacion con el permiso', function () {
    comoEmpleado(administrativoDePantalla())
        ->get(route('gestion.items.edit', productoDePrueba()))
        ->assertOk()
        ->assertSee('puedePublicar: true', false)
        ->assertSee('El producto podrá estar disponible para los clientes.');
});

it('deshabilita el interruptor de publicacion sin el permiso', function () {
    $empleado = administrativoDePantalla();

    $empleado->rol->permisos()->detach(
        Permiso::where('clave', 'producto.publicar')->value('id')
    );

    comoEmpleado($empleado)
        ->get(route('gestion.items.edit', productoDePrueba()))
        ->assertOk()
        ->assertSee('puedePublicar: false', false)
        ->assertSee('Tu rol no puede publicar ni despublicar productos.');
});

// ============================================================
// El contrato que usa la pantalla al guardar
// ============================================================
//
// <b>Esta es la prueba que explica una decision del front-end.</b>
// ProductoRequest::prepareForValidation() hace boolean('publicado_app')
// SIEMPRE, asi que un PUT que no manda el campo lo guarda en false: la edicion
// despublicaria el producto sin que nadie lo pida. Y si lo mandara con el
// valor nuevo, publicar quedaria al alcance de cualquiera con
// 'producto.editar', salteando 'producto.publicar'.
//
// Por eso la pantalla manda en el PUT el valor QUE YA TENIA el producto y
// resuelve el cambio con PATCH .../publicacion. Las dos pruebas de abajo fijan
// ese contrato: si alguien corrige ProductoRequest para que el PUT ignore la
// publicacion, la primera sigue pasando y el front-end no necesita cambios.

it('el alta que manda la pantalla crea el producto', function () {
    // Tal cual lo manda el navegador: todo cadenas, los interruptores en
    // '1'/'0' y los campos opcionales vacios. Lo que se fija aca es que el
    // backend entienda ese cuerpo —'' llega como null por
    // ConvertEmptyStringsToNull, y boolean() acepta '1' y '0'—, porque es de
    // lo que depende el formulario migrado.
    Sanctum::actingAs(administrativoDePantalla(), ['gestion']);

    $this->post(route('api.v1.gestion.productos.store'), [
        'codigo' => 'TEC-9',
        'nombre' => 'Teclado mecánico',
        'marca' => '',
        'descripcion' => '',
        'categoria_id' => (string) categoriaDePrueba()->id,
        'alicuota_iva_id' => (string) AlicuotaIva::query()->value('id'),
        'proveedor_id' => '',
        'precio' => '85000.50',
        'costo' => '',
        'punto_reposicion' => '0',
        'cantidad_reposicion' => '0',
        'peso_gramos' => '',
        'reposicion_automatica' => '0',
        'stock_inicial' => '5',
        'publicado_app' => '1',
    ])->assertCreated()
        ->assertJsonPath('data.publicado_app', true)
        ->assertJsonPath('data.marca', null)
        ->assertJsonPath('data.costo', null)
        // El stock inicial no es la columna: es el movimiento que lo explica.
        ->assertJsonPath('data.stock_fisico', 5);
});

it('el PUT que manda la pantalla deja la publicacion intacta', function () {
    $empleado = administrativoDePantalla();

    $producto = productoDePrueba(['codigo' => 'PUB-1']);
    $producto->update(['publicado_app' => true]);

    Sanctum::actingAs($empleado, ['gestion']);

    $this->put(route('api.v1.gestion.productos.update', $producto), [
        'codigo' => 'PUB-1',
        'nombre' => 'Monitor renombrado',
        'categoria_id' => $producto->categoria_id,
        'alicuota_iva_id' => AlicuotaIva::query()->value('id'),
        'precio' => 150000,
        'punto_reposicion' => 1,
        'cantidad_reposicion' => 1,

        // El valor guardado, que es lo que manda formulario.js.
        'publicado_app' => true,
    ])->assertOk();

    expect($producto->refresh()->publicado_app)->toBeTrue()
        ->and($producto->nombre)->toBe('Monitor renombrado');
});

it('la publicacion cambia por su endpoint y no por el de edicion', function () {
    $empleado = administrativoDePantalla();

    $producto = productoDePrueba(['codigo' => 'PUB-2']);
    $producto->update(['publicado_app' => true]);

    Sanctum::actingAs($empleado, ['gestion']);

    $this->patchJson(route('api.v1.gestion.productos.publicacion', $producto), [
        'publicado_app' => false,
    ])->assertOk();

    expect($producto->refresh()->publicado_app)->toBeFalse();
});

// ============================================================
// El JavaScript de las pantallas esta declarado
// ============================================================

it('declara en vite los modulos que piden las vistas de productos', function () {
    $configuracion = file_get_contents(base_path('vite.config.js'));

    $modulos = [
        'resources/js/gestion/productos/index.js',
        'resources/js/gestion/productos/formulario.js',
    ];

    foreach ($modulos as $modulo) {
        expect(file_exists(base_path($modulo)))->toBeTrue("Falta el modulo {$modulo}")
            ->and($configuracion)->toContain($modulo);
    }

    $vistas = [
        'views/gestion/item/index.blade.php' => $modulos[0],
        'views/gestion/item/create.blade.php' => $modulos[1],
        'views/gestion/item/edit.blade.php' => $modulos[1],
    ];

    foreach ($vistas as $vista => $modulo) {
        expect(file_get_contents(resource_path($vista)))->toContain($modulo);
    }
});
