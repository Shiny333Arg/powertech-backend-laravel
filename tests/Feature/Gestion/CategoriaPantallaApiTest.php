<?php

use App\Models\Categoria;
use App\Models\Permiso;
use App\Models\User;
use Database\Seeders\Maestros\PermisoSeeder;
use Database\Seeders\Maestros\RolSeeder;

/*
|--------------------------------------------------------------------------
| Las pantallas de categorias despues de migrarlas a la API
|--------------------------------------------------------------------------
|
| Lo que estas pruebas fijan es el contrato NUEVO de las tres paginas: entregan
| la estructura visual y los permisos del rol, y ningun dato de negocio. Los
| datos los pide el JavaScript a /api/v1/gestion/categorias, que ya tiene su
| propia cobertura en tests/Feature/Api/V1/Gestion/CategoriaTest.php.
|
| <b>Por que mirar el HTML y no el JavaScript.</b> El proyecto no tiene corredor
| de pruebas de front-end, asi que lo que se puede verificar desde Pest es la
| mitad que produce Laravel: que la categoria NO viaje en la pagina —si volviera
| a viajar, la pantalla podria mostrarla sin pasar por la API y el requisito 3
| dejaria de cumplirse sin que nadie se entere— y que los botones que se
| dibujan dependan de la policy. Lo que el navegador hace con eso se demuestra
| a mano, y lo que la API decide esta cubierto por las pruebas de la API.
|
| `withoutVite()` porque las tres vistas extienden el layout de gestion, que
| compila assets; es la misma razon que explican RutasDeStockTest y los demas.
| Las pruebas del manifiesto —las que SI necesitan el build— viven aparte, en
| AssetsGestionTest.
|
*/

beforeEach(function () {
    $this->seed([RolSeeder::class, PermisoSeeder::class]);

    $this->withoutVite();
});

function categoriaCargada(string $nombre = 'Monitores', bool $activo = true): Categoria
{
    return Categoria::create([
        'nombre' => $nombre,
        'slug' => str($nombre)->slug()->value(),
        'orden' => 1,
        'activo' => $activo,
    ]);
}

// ============================================================
// La pagina no trae datos de negocio
// ============================================================

it('entrega el listado sin las categorias adentro', function () {
    categoriaCargada('Monitores');
    categoriaCargada('Teclados', activo: false);

    $respuesta = comoEmpleado(User::factory()->empleado('administrativo')->create())
        ->get(route('gestion.categorias.index'));

    $respuesta->assertOk()
        // La tabla esta, vacia y con el hueco que llena el JS.
        ->assertSee('contenidoTablaCategorias')
        ->assertSee('paginadoCategorias')
        // Los datos no.
        ->assertDontSee('Monitores')
        ->assertDontSee('Teclados');
});

it('entrega la edicion sin los datos de la categoria', function () {
    $categoria = categoriaCargada('Placas de video');

    comoEmpleado(User::factory()->empleado('administrativo')->create())
        ->get(route('gestion.categorias.edit', $categoria))
        ->assertOk()
        // Solo el id, para que el JS sepa a que recurso pedirle los datos.
        ->assertSee('id: '.$categoria->id, false)
        ->assertDontSee('Placas de video')
        ->assertDontSee('placas-de-video');
});

it('entrega el alta como un formulario vacio', function () {
    comoEmpleado(User::factory()->empleado('administrativo')->create())
        ->get(route('gestion.categorias.create'))
        ->assertOk()
        ->assertSee('formularioCategoria');
});

// ============================================================
// Los formularios ya no apuntan a las rutas web de negocio
// ============================================================
//
// Las rutas siguen publicadas —se retiran en el paso siguiente del plan, junto
// con las de los demas modulos—, pero ninguna pantalla las usa: si alguien
// vuelve a poner un <form action="..."> tradicional, estas dos fallan.

it('no deja ningun formulario apuntando a las rutas web de categorias', function () {
    $categoria = categoriaCargada();

    $empleado = User::factory()->empleado('administrativo')->create();

    // El `action="..."` es lo que se mira y no la URL suelta: POST store y GET
    // index comparten direccion —cambia el verbo—, y el formulario de filtros
    // sigue siendo un GET legitimo contra el listado.
    comoEmpleado($empleado)
        ->get(route('gestion.categorias.index'))
        ->assertOk()
        ->assertDontSee('action="'.route('gestion.categorias.destroy', $categoria).'"', false)
        ->assertDontSee('action="'.route('gestion.categorias.baja', $categoria).'"', false)
        ->assertDontSee('name="_method"', false);

    comoEmpleado($empleado)
        ->get(route('gestion.categorias.create'))
        ->assertOk()
        ->assertDontSee('action="'.route('gestion.categorias.store').'"', false)
        ->assertDontSee('name="_method"', false);

    comoEmpleado($empleado)
        ->get(route('gestion.categorias.edit', $categoria))
        ->assertOk()
        ->assertDontSee('action="'.route('gestion.categorias.update', $categoria).'"', false)
        ->assertDontSee('name="_method"', false);
});

// ============================================================
// Los botones salen de la policy
// ============================================================

it('le dice a la pantalla lo que el rol puede hacer', function () {
    comoEmpleado(User::factory()->empleado('administrativo')->create())
        ->get(route('gestion.categorias.index'))
        ->assertOk()
        ->assertSee('"crear":true', false)
        ->assertSee('"editar":true', false)
        ->assertSee('"cambiarEstado":true', false)
        ->assertSee('"eliminar":true', false);
});

it('no le ofrece ninguna accion al rol que solo puede mirar', function () {
    // El vendedor tiene 'categoria.ver' y nada mas.
    $respuesta = comoEmpleado(User::factory()->empleado('vendedor')->create())
        ->get(route('gestion.categorias.index'));

    $respuesta->assertOk()
        ->assertSee('"crear":false', false)
        ->assertSee('"editar":false', false)
        ->assertSee('"cambiarEstado":false', false)
        ->assertSee('"eliminar":false', false)
        // Sin acciones no hay columna de opciones ni boton de alta.
        ->assertDontSee('columnaOpciones')
        ->assertDontSee(route('gestion.categorias.create'), false);
});

it('deshabilita el casillero de estado sin el permiso de baja', function () {
    $empleado = User::factory()->empleado('administrativo')->create();

    $empleado->rol->permisos()->detach(
        Permiso::where('clave', 'categoria.baja')->value('id')
    );

    comoEmpleado($empleado)
        ->get(route('gestion.categorias.edit', categoriaCargada()))
        ->assertOk()
        ->assertSee('Tu rol no puede cambiar el estado de una categoría.')
        ->assertSee('puedeCambiarEstado: false', false);
});

it('deja el casillero de estado habilitado con el permiso de baja', function () {
    comoEmpleado(User::factory()->empleado('administrativo')->create())
        ->get(route('gestion.categorias.edit', categoriaCargada()))
        ->assertOk()
        ->assertSee('puedeCambiarEstado: true', false);
});

// ============================================================
// El JavaScript de las pantallas esta declarado
// ============================================================
//
// Es el mismo descuido que corrigio AssetsGestionTest con los CSS: una vista
// que pide con Vite::asset() un modulo que no esta en el input de
// vite.config.js abre bien con `npm run dev` y revienta con los assets
// compilados. Esto no necesita el build: compara las dos listas.

it('declara en vite los modulos que piden las vistas de categorias', function () {
    $configuracion = file_get_contents(base_path('vite.config.js'));

    $modulos = [
        'resources/js/gestion/categorias/index.js',
        'resources/js/gestion/categorias/formulario.js',
    ];

    foreach ($modulos as $modulo) {
        expect(file_exists(base_path($modulo)))->toBeTrue("Falta el modulo {$modulo}")
            ->and($configuracion)->toContain($modulo);
    }

    $vistas = [
        'views/gestion/category/index.blade.php' => $modulos[0],
        'views/gestion/category/create.blade.php' => $modulos[1],
        'views/gestion/category/edit.blade.php' => $modulos[1],
    ];

    foreach ($vistas as $vista => $modulo) {
        expect(file_get_contents(resource_path($vista)))->toContain($modulo);
    }
});
