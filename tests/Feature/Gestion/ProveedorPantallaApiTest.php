<?php

use App\Models\CondicionIva;
use App\Models\Permiso;
use App\Models\Proveedor;
use App\Models\User;
use Database\Seeders\Maestros\AlicuotaIvaSeeder;
use Database\Seeders\Maestros\CondicionIvaSeeder;
use Database\Seeders\Maestros\PermisoSeeder;
use Database\Seeders\Maestros\RolSeeder;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;

/*
|--------------------------------------------------------------------------
| Las pantallas de proveedores despues de migrarlas a la API
|--------------------------------------------------------------------------
|
| Mismo contrato que fijaron CategoriaPantallaApiTest y ProductoPantallaApiTest:
| las tres paginas entregan la estructura visual, los desplegables del
| formulario y los permisos del rol, y ningun dato del proveedor. Los datos los
| pide el JavaScript a /api/v1/gestion/proveedores, que ya tiene su cobertura
| completa —44 pruebas— en tests/Feature/Api/V1/Gestion/ProveedorTest.php.
|
| <b>Lo propio de este modulo es el domicilio fiscal.</b> Viaja anidado bajo
| 'direccion' en el mismo cuerpo del alta y de la edicion, y vive en una tabla
| polimorfica. Que no se filtre en el HTML de la pagina de edicion es lo que
| garantiza que la pantalla lo pida por la API y bajo su permiso.
|
| `withoutVite()` porque las tres vistas extienden el layout de gestion.
|
*/

beforeEach(function () {
    $this->seed([
        RolSeeder::class,
        PermisoSeeder::class,
        CondicionIvaSeeder::class,
        AlicuotaIvaSeeder::class,
    ]);

    $this->withoutVite();
});

function administrativoDePantallaDeProveedores(): User
{
    return User::factory()->empleado('administrativo')->create();
}

/** Un proveedor con domicilio fiscal cargado, para ver que no se filtre. */
function proveedorConDomicilio(array $atributos = []): Proveedor
{
    $proveedor = proveedorDePrueba($atributos);

    $proveedor->direcciones()->create([
        'tipo' => 'fiscal',
        'calle' => 'Pasaje Testigo',
        'numero' => '742',
        'codigo_postal' => '9011',
        'localidad' => 'Pico Truncado',
        'provincia' => 'Santa Cruz',
        'es_predeterminada' => true,
    ]);

    return $proveedor;
}

// ============================================================
// La pagina no trae datos de negocio
// ============================================================

it('entrega el listado sin los proveedores adentro', function () {
    proveedorDePrueba([
        'razon_social' => 'Distribuidora Austral SA',
        'contacto' => 'Marina Ruiz',
        'email' => 'ventas@austral.test',
    ]);

    comoEmpleado(administrativoDePantallaDeProveedores())
        ->get(route('gestion.proveedores.index'))
        ->assertOk()
        // La tabla esta, vacia y con el hueco que llena el JS.
        ->assertSee('contenidoTablaProveedores')
        ->assertSee('paginadoProveedores')
        // Los datos no.
        ->assertDontSee('Distribuidora Austral SA')
        ->assertDontSee('Marina Ruiz')
        ->assertDontSee('ventas@austral.test');
});

it('entrega la edicion sin los datos del proveedor ni su domicilio', function () {
    $proveedor = proveedorConDomicilio([
        'razon_social' => 'Mayorista del Sur SRL',
        'contacto' => 'Pedro Lamas',
    ]);

    comoEmpleado(administrativoDePantallaDeProveedores())
        ->get(route('gestion.proveedores.edit', $proveedor))
        ->assertOk()
        // Solo el id, para que el JS sepa a que recurso pedirle los datos.
        ->assertSee('id: '.$proveedor->id, false)
        ->assertDontSee('Mayorista del Sur SRL')
        ->assertDontSee('Pedro Lamas')
        ->assertDontSee($proveedor->cuit)
        // El domicilio fiscal tampoco: lo trae GET /{id}. Los valores son a
        // proposito distintos de los placeholders de la vista, que si estan
        // en el marcado.
        ->assertDontSee('Pasaje Testigo')
        ->assertDontSee('Pico Truncado');
});

it('entrega el alta como un formulario vacio y sin el casillero de estado', function () {
    comoEmpleado(administrativoDePantallaDeProveedores())
        ->get(route('gestion.proveedores.create'))
        ->assertOk()
        ->assertSee('formularioProveedor')
        // Los desplegables siguen viniendo del servidor: son el catalogo de
        // opciones del formulario, no los datos del proveedor.
        ->assertSee('condicion_iva_id')
        // El alta por API deja al proveedor activo y descarta el 'activo' del
        // cuerpo, asi que el casillero dejo de existir.
        ->assertDontSee('name="activo"', false);
});

// ============================================================
// Las rutas web de negocio ya no existen
// ============================================================

it('retiro las rutas web que ejecutaban operaciones de negocio', function () {
    foreach (['store', 'update', 'darDeBaja', 'reactivar'] as $retirada) {
        expect(Route::has("gestion.proveedores.{$retirada}"))
            ->toBeFalse("La ruta web gestion.proveedores.{$retirada} sigue publicada");
    }
});

it('conserva las rutas web que entregan las paginas Blade', function () {
    foreach (['index', 'create', 'edit'] as $pagina) {
        expect(Route::has("gestion.proveedores.{$pagina}"))->toBeTrue();
    }
});

it('responde 404 a las direcciones de negocio que ya no existen', function () {
    $proveedor = proveedorDePrueba();

    $empleado = administrativoDePantallaDeProveedores();

    // El administrativo tiene los cuatro permisos: si algo contestara 2xx o
    // 302, seria porque la ruta sigue viva, no porque le falte autorizacion.
    //
    // El alta contesta 405 y no 404 porque /gestion/proveedor sigue existiendo
    // para el GET del listado: lo que ya no existe es el verbo.
    comoEmpleado($empleado)->post('/gestion/proveedor', [])->assertMethodNotAllowed();

    foreach ([
        ['put', "/gestion/proveedor/{$proveedor->id}"],
        ['patch', "/gestion/proveedor/{$proveedor->id}/baja"],
        ['patch', "/gestion/proveedor/{$proveedor->id}/reactivar"],
    ] as [$verbo, $direccion]) {
        comoEmpleado($empleado)->{$verbo}($direccion)->assertNotFound();
    }

    expect($proveedor->refresh()->activo)->toBeTrue();
});

it('no deja ningun formulario apuntando al panel', function () {
    $proveedor = proveedorDePrueba();

    $empleado = administrativoDePantallaDeProveedores();

    // Se mira el `action="..."` y no la URL suelta: el formulario de filtros
    // del listado sigue siendo un GET legitimo contra la propia pagina.
    comoEmpleado($empleado)
        ->get(route('gestion.proveedores.index'))
        ->assertOk()
        ->assertDontSee('name="_method"', false);

    comoEmpleado($empleado)
        ->get(route('gestion.proveedores.create'))
        ->assertOk()
        ->assertDontSee('action="'.url('/gestion/proveedor').'"', false)
        ->assertDontSee('name="_method"', false);

    comoEmpleado($empleado)
        ->get(route('gestion.proveedores.edit', $proveedor))
        ->assertOk()
        ->assertDontSee('action="'.url("/gestion/proveedor/{$proveedor->id}").'"', false)
        ->assertDontSee('name="_method"', false);
});

// ============================================================
// Los botones salen de la policy
// ============================================================

it('le dice a la pantalla lo que el rol puede hacer', function () {
    comoEmpleado(administrativoDePantallaDeProveedores())
        ->get(route('gestion.proveedores.index'))
        ->assertOk()
        ->assertSee('"crear":true', false)
        ->assertSee('"editar":true', false)
        ->assertSee('"cambiarEstado":true', false)
        ->assertSee('Opciones');
});

it('esconde el alta al que no tiene el permiso de crear', function () {
    // El vendedor y el cajero no tienen ningun permiso de proveedores, asi que
    // el actor se arma quitandole uno al administrativo: es la misma tecnica
    // que usa ProveedorTest, con la matriz real como punto de partida.
    $empleado = administrativoDePantallaDeProveedores();

    $empleado->rol->permisos()->detach(
        Permiso::where('clave', 'proveedor.crear')->value('id')
    );

    comoEmpleado($empleado)
        ->get(route('gestion.proveedores.index'))
        ->assertOk()
        ->assertSee('"crear":false', false)
        ->assertSee('"editar":true', false)
        ->assertDontSee(route('gestion.proveedores.create'), false);
});

it('esconde las acciones de fila al que solo puede mirar', function () {
    $empleado = administrativoDePantallaDeProveedores();

    $empleado->rol->permisos()->detach(
        Permiso::whereIn('clave', ['proveedor.crear', 'proveedor.editar', 'proveedor.baja'])
            ->pluck('id')
            ->all()
    );

    comoEmpleado($empleado)
        ->get(route('gestion.proveedores.index'))
        ->assertOk()
        ->assertSee('"editar":false', false)
        ->assertSee('"cambiarEstado":false', false)
        // Sin acciones no hay columna de opciones.
        ->assertDontSee('Opciones');
});

it('no le muestra la pantalla al vendedor', function () {
    // Segun la matriz real, vendedor y cajero no tienen 'proveedor.ver'.
    comoEmpleado(User::factory()->empleado('vendedor')->create())
        ->get(route('gestion.proveedores.index'))
        ->assertForbidden();
});

// ============================================================
// El contrato que usa la pantalla al guardar
// ============================================================
//
// El formulario manda un JSON con el domicilio anidado y los campos vacios
// como cadena vacia, que es lo que produce un <form> leido campo por campo.
// Lo que se fija aca es que el backend entienda ese cuerpo: '' llega como null
// por ConvertEmptyStringsToNull —que tambien limpia el JSON—, y sin calle no
// se crea domicilio.

it('el alta que manda la pantalla crea el proveedor con su domicilio', function () {
    Sanctum::actingAs(administrativoDePantallaDeProveedores(), ['gestion']);

    $this->postJson(route('api.v1.gestion.proveedores.store'), [
        'razon_social' => 'Distribuidora Austral SA',
        'cuit' => cuitValido(),
        'condicion_iva_id' => (string) CondicionIva::query()->value('id'),
        'contacto' => '',
        'email' => '',
        'telefono' => '',
        'canal_pedido' => 'email',
        'portal_url' => '',
        'plazo_entrega_dias' => '',
        'direccion' => [
            'calle' => 'Av. Siempreviva',
            'numero' => '742',
            'piso_depto' => '',
            'codigo_postal' => '9011',
            'localidad' => 'Caleta Olivia',
            'provincia' => 'Santa Cruz',
        ],
    ])
        ->assertCreated()
        ->assertJsonPath('data.razon_social', 'Distribuidora Austral SA')
        ->assertJsonPath('data.activo', true)
        ->assertJsonPath('data.email', null)
        ->assertJsonPath('data.portal_url', null)
        ->assertJsonPath('data.domicilio_fiscal.localidad', 'Caleta Olivia');
});

it('el alta sin domicilio no crea ninguna direccion', function () {
    Sanctum::actingAs(administrativoDePantallaDeProveedores(), ['gestion']);

    $this->postJson(route('api.v1.gestion.proveedores.store'), [
        'razon_social' => 'Sin domicilio SRL',
        'cuit' => cuitValido(),
        'condicion_iva_id' => (string) CondicionIva::query()->value('id'),
        'canal_pedido' => 'email',
        // Todos vacios: es lo que manda la pantalla cuando nadie completa el
        // domicilio, y la calle es la que decide si hay direccion.
        'direccion' => [
            'calle' => '',
            'numero' => '',
            'piso_depto' => '',
            'codigo_postal' => '',
            'localidad' => '',
            'provincia' => '',
        ],
    ])
        ->assertCreated()
        ->assertJsonPath('data.domicilio_fiscal', null);
});

// ============================================================
// El JavaScript de las pantallas esta declarado
// ============================================================

it('declara en vite los modulos que piden las vistas de proveedores', function () {
    $configuracion = file_get_contents(base_path('vite.config.js'));

    $modulos = [
        'resources/js/gestion/proveedores/index.js',
        'resources/js/gestion/proveedores/formulario.js',
    ];

    foreach ($modulos as $modulo) {
        expect(file_exists(base_path($modulo)))->toBeTrue("Falta el modulo {$modulo}")
            ->and($configuracion)->toContain($modulo);
    }

    $vistas = [
        'views/gestion/proveedors/index.blade.php' => $modulos[0],
        'views/gestion/proveedors/create.blade.php' => $modulos[1],
        'views/gestion/proveedors/edit.blade.php' => $modulos[1],
    ];

    foreach ($vistas as $vista => $modulo) {
        expect(file_get_contents(resource_path($vista)))->toContain($modulo);
    }
});
