<?php

use App\Models\Cliente;
use App\Models\CondicionIva;
use App\Models\Permiso;
use App\Models\TipoDocumento;
use App\Models\User;
use App\Models\Venta;
use Database\Seeders\Maestros\AlicuotaIvaSeeder;
use Database\Seeders\Maestros\CondicionIvaSeeder;
use Database\Seeders\Maestros\PermisoSeeder;
use Database\Seeders\Maestros\RolSeeder;
use Database\Seeders\Maestros\TipoDocumentoSeeder;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Las pantallas de clientes despues de migrarlas a la API
|--------------------------------------------------------------------------
|
| Mismo contrato que fijaron CategoriaPantallaApiTest, ProductoPantallaApiTest y
| ProveedorPantallaApiTest: las paginas entregan la estructura visual, los
| desplegables del formulario y los permisos del rol, y ningun dato del cliente.
| Los datos los pide el JavaScript a /api/v1/gestion/clientes, que tiene su
| cobertura completa en tests/Feature/Api/V1/Gestion/ClienteTest.php.
|
| <b>Este modulo tiene CUATRO pantallas y no tres: la ficha del cliente.</b> Es
| la que mas datos mostraba y por lo tanto la que mas tiene que dejar de
| mostrar: los datos fiscales, el estado de la cuenta en la tienda, las
| direcciones y el historial de compras. Que ninguno se filtre en el HTML es lo
| que garantiza que la pantalla los pida por la API y bajo su permiso.
|
| <b>Y es el unico modulo donde esconder botones cambia algo.</b> Segun la
| matriz de PermisoSeeder el vendedor y el cajero tienen 'cliente.ver',
| 'cliente.crear' y 'cliente.editar' y NO tienen 'cliente.baja': a diferencia de
| proveedores, entran a la pantalla y le falta exactamente una accion. Eso tiene
| sus propias pruebas al final.
|
| `withoutVite()` porque las cuatro vistas extienden el layout de gestion.
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

/** Rol con los cuatro permisos de clientes. */
function administrativoDePantallaDeClientes(): User
{
    return User::factory()->empleado('administrativo')->create();
}

/**
 * Cliente con cuenta, para ver que sus datos no se filtren en el HTML.
 *
 * Se arma a mano porque el proyecto solo tiene UserFactory y Cliente no usa
 * HasFactory. Los valores son a proposito distintos de los placeholders de las
 * vistas, que si estan en el marcado.
 */
function clienteDeFicha(array $atributos = [], array $cuenta = []): Cliente
{
    static $siguiente = 0;

    $usuario = User::factory()->cliente()->create($cuenta + [
        'email' => 'titular'.++$siguiente.'@casilla-testigo.test',
    ]);

    return Cliente::create(array_merge([
        'user_id'           => $usuario->id,
        'razon_social'      => 'Ficha Austral SA',
        'tipo_documento_id' => TipoDocumento::where('codigo_afip', 96)->value('id'),
        'nro_doc'           => (string) (31_000_000 + $siguiente),
        'condicion_iva_id'  => CondicionIva::where('codigo_afip', 5)->value('id'),
        'telefono'          => '2974777777',
        'activo'            => true,
    ], $atributos));
}

/** Una compra del cliente, para ver que el historial no venga en el HTML. */
function ventaDeFicha(Cliente $cliente): Venta
{
    static $siguiente = 0;

    return Venta::create([
        'numero'       => 9_100_000 + ++$siguiente,
        'canal'        => 'mostrador',
        'estado'       => 'entregada',
        'modo_entrega' => 'retiro',
        'cliente_id'   => $cliente->id,
        'fecha'        => now(),
        'subtotal'     => 123456,
        'total'        => 123456,
    ]);
}

// ============================================================
// Las paginas no traen datos de negocio
// ============================================================

it('entrega el listado sin los clientes adentro', function () {
    clienteDeFicha([
        'razon_social' => 'Distribuidora del Sur SRL',
        'telefono'     => '2974777777',
    ]);

    comoEmpleado(administrativoDePantallaDeClientes())
        ->get(route('gestion.clientes.index'))
        ->assertOk()
        // La tabla esta, vacia y con el hueco que llena el JS.
        ->assertSee('contenidoTablaClientes')
        ->assertSee('paginadoClientes')
        // Los datos no.
        ->assertDontSee('Distribuidora del Sur SRL')
        ->assertDontSee('2974777777')
        ->assertDontSee('casilla-testigo.test');
});

it('entrega la ficha sin los datos del cliente, su cuenta, sus direcciones ni sus compras', function () {
    $cliente = clienteDeFicha(['razon_social' => 'Ficha Austral SA']);

    $cliente->direcciones()->create([
        'tipo'              => 'fiscal',
        'calle'             => 'Pasaje Testigo',
        'numero'            => '742',
        'codigo_postal'     => '9011',
        'localidad'         => 'Pico Truncado',
        'provincia'         => 'Santa Cruz',
        'es_predeterminada' => true,
    ]);

    $venta = ventaDeFicha($cliente);

    comoEmpleado(administrativoDePantallaDeClientes())
        ->get(route('gestion.clientes.show', $cliente))
        ->assertOk()
        // Solo el id, para que el JS sepa a que recurso pedirle los datos.
        ->assertSee('id: '.$cliente->id, false)
        // Los huecos que llena el JS.
        ->assertSee('contenidoTablaVentas')
        ->assertSee('listaDirecciones')
        ->assertSee('estadoCuenta')
        // Nada del cliente.
        ->assertDontSee('Ficha Austral SA')
        ->assertDontSee($cliente->nro_doc)
        ->assertDontSee('2974777777')
        ->assertDontSee($cliente->user->email)
        // Ni de sus direcciones.
        ->assertDontSee('Pasaje Testigo')
        ->assertDontSee('Pico Truncado')
        // Ni de su historial de compras.
        ->assertDontSee($venta->numeroFormateado())
        ->assertDontSee('1.234,56');
});

it('entrega la edicion sin los datos del cliente ni de su cuenta', function () {
    $cliente = clienteDeFicha(['razon_social' => 'Mayorista del Sur SRL']);

    comoEmpleado(administrativoDePantallaDeClientes())
        ->get(route('gestion.clientes.edit', $cliente))
        ->assertOk()
        ->assertSee('id: '.$cliente->id, false)
        ->assertSee('formularioCliente')
        // Los desplegables siguen viniendo del servidor: son el catalogo de
        // opciones del formulario, no los datos del cliente.
        ->assertSee('tipo_documento_id')
        ->assertSee('condicion_iva_id')
        ->assertDontSee('Mayorista del Sur SRL')
        ->assertDontSee($cliente->nro_doc)
        ->assertDontSee($cliente->user->email)
        ->assertDontSee('2974777777');
});

it('entrega el alta como un formulario vacio y sin casillero de estado', function () {
    comoEmpleado(administrativoDePantallaDeClientes())
        ->get(route('gestion.clientes.create'))
        ->assertOk()
        ->assertSee('formularioCliente')
        ->assertSee('tipo_documento_id')
        // El alta por API deja al cliente activo y el cuerpo no decide el
        // estado: tiene su endpoint y su permiso.
        ->assertDontSee('name="activo"', false);
});

it('no publica el hash de la contraseña en ninguna pantalla', function () {
    // La contraseña no pasa por ninguna pantalla del panel: el cliente la elige
    // desde el enlace que recibe. Ni el formulario la pide ni la ficha la
    // muestra.
    $cliente = clienteDeFicha();

    $empleado = administrativoDePantallaDeClientes();

    foreach ([
        route('gestion.clientes.show', $cliente),
        route('gestion.clientes.edit', $cliente),
        route('gestion.clientes.create'),
    ] as $pagina) {
        comoEmpleado($empleado)
            ->get($pagina)
            ->assertOk()
            ->assertDontSee('name="password"', false)
            ->assertDontSee($cliente->user->password);
    }
});

// ============================================================
// Las rutas web de negocio ya no existen
// ============================================================

it('retiro las rutas web que ejecutaban operaciones de negocio', function () {
    foreach (['store', 'update', 'baja', 'reactivar', 'reenviarInvitacion'] as $retirada) {
        expect(Route::has("gestion.clientes.{$retirada}"))
            ->toBeFalse("La ruta web gestion.clientes.{$retirada} sigue publicada");
    }
});

it('retiro el grupo web de direcciones, que solo usaba esta ficha', function () {
    // El proveedor y el empleado mandan su domicilio anidado en el cuerpo de su
    // propio formulario y nunca usaron estas rutas: al migrar clientes el grupo
    // quedo sin nadie que lo llame.
    foreach (['store', 'update', 'destroy'] as $retirada) {
        expect(Route::has("gestion.direcciones.{$retirada}"))
            ->toBeFalse("La ruta web gestion.direcciones.{$retirada} sigue publicada");
    }
});

it('conserva las rutas web que entregan las paginas Blade', function () {
    foreach (['index', 'create', 'show', 'edit'] as $pagina) {
        expect(Route::has("gestion.clientes.{$pagina}"))->toBeTrue();
    }
});

it('responde 404 a las direcciones de negocio que ya no existen', function () {
    $cliente = clienteDeFicha();

    $direccion = $cliente->direcciones()->create([
        'tipo'          => 'fiscal',
        'calle'         => 'Pasaje Testigo',
        'codigo_postal' => '9011',
        'localidad'     => 'Pico Truncado',
        'provincia'     => 'Santa Cruz',
    ]);

    $empleado = administrativoDePantallaDeClientes();

    // El administrativo tiene los cuatro permisos: si algo contestara 2xx o
    // 302, seria porque la ruta sigue viva, no porque le falte autorizacion.
    //
    // <b>Dos de las retiradas contestan 405 y no 404</b>, y la diferencia es
    // informativa: su URI sigue existiendo para el GET que entrega la pagina
    // —/gestion/customer es el listado y /gestion/customer/{cliente} es la
    // ficha—, asi que lo que ya no existe es el VERBO. Es el mismo 405 que daba
    // el alta de proveedores.
    foreach ([
        ['post', '/gestion/customer'],
        ['put', "/gestion/customer/{$cliente->id}"],
    ] as [$verbo, $url]) {
        comoEmpleado($empleado)->{$verbo}($url, [])->assertMethodNotAllowed();
    }

    // Las otras cuatro no dejaron URI detras: se fueron enteras.
    foreach ([
        ['patch', "/gestion/customer/{$cliente->id}/baja"],
        ['patch', "/gestion/customer/{$cliente->id}/reactivar"],
        ['post', "/gestion/customer/{$cliente->id}/reenviar-invitacion"],
        ['post', "/gestion/clientes/{$cliente->id}/address"],
        ['delete', "/gestion/clientes/{$cliente->id}/address/{$direccion->id}"],
    ] as [$verbo, $url]) {
        comoEmpleado($empleado)->{$verbo}($url)->assertNotFound();
    }

    expect($cliente->refresh()->activo)->toBeTrue()
        ->and($direccion->refresh()->exists)->toBeTrue();
});

it('no deja ningun formulario apuntando al panel', function () {
    $cliente = clienteDeFicha();

    $empleado = administrativoDePantallaDeClientes();

    // Se mira el `name="_method"` y el `action`: el formulario de filtros del
    // listado sigue siendo un GET legitimo contra la propia pagina.
    comoEmpleado($empleado)
        ->get(route('gestion.clientes.index'))
        ->assertOk()
        ->assertDontSee('name="_method"', false);

    comoEmpleado($empleado)
        ->get(route('gestion.clientes.create'))
        ->assertOk()
        ->assertDontSee('action="'.url('/gestion/customer').'"', false)
        ->assertDontSee('name="_method"', false);

    comoEmpleado($empleado)
        ->get(route('gestion.clientes.edit', $cliente))
        ->assertOk()
        ->assertDontSee('action="'.url("/gestion/customer/{$cliente->id}").'"', false)
        ->assertDontSee('name="_method"', false);

    // La ficha tenia CUATRO formularios al panel: baja, reactivacion, reenvio
    // de la invitacion y alta de direccion, mas el de borrado de cada una.
    comoEmpleado($empleado)
        ->get(route('gestion.clientes.show', $cliente))
        ->assertOk()
        ->assertDontSee('name="_method"', false)
        ->assertDontSee('/reenviar-invitacion', false)
        ->assertDontSee('/address', false);
});

// ============================================================
// Los botones salen de la policy
// ============================================================

it('le dice a la pantalla lo que el rol puede hacer', function () {
    comoEmpleado(administrativoDePantallaDeClientes())
        ->get(route('gestion.clientes.index'))
        ->assertOk()
        ->assertSee('"crear":true', false)
        ->assertSee('"editar":true', false)
        ->assertSee('"baja":true', false)
        ->assertSee('Opciones');
});

it('esconde el alta al que no tiene el permiso de crear', function () {
    $empleado = administrativoDePantallaDeClientes();

    $empleado->rol->permisos()->detach(
        Permiso::where('clave', 'cliente.crear')->value('id')
    );

    comoEmpleado($empleado)
        ->get(route('gestion.clientes.index'))
        ->assertOk()
        ->assertSee('"crear":false', false)
        ->assertSee('"editar":true', false)
        ->assertDontSee(route('gestion.clientes.create'), false);
});

it('esconde el formulario de direcciones al que no puede editar', function () {
    $cliente = clienteDeFicha();

    $empleado = administrativoDePantallaDeClientes();

    $empleado->rol->permisos()->detach(
        Permiso::where('clave', 'cliente.editar')->value('id')
    );

    comoEmpleado($empleado)
        ->get(route('gestion.clientes.show', $cliente))
        ->assertOk()
        ->assertSee('"editar":false', false)
        // Sin 'cliente.editar' el acordeon no se dibuja, y el endpoint lo
        // rechazaria igual con un 403.
        ->assertDontSee('formularioDireccion');
});

it('no le muestra la pantalla al que no tiene cliente.ver', function () {
    $empleado = administrativoDePantallaDeClientes();

    $empleado->rol->permisos()->detach(
        Permiso::where('clave', 'cliente.ver')->value('id')
    );

    comoEmpleado($empleado)
        ->get(route('gestion.clientes.index'))
        ->assertForbidden();
});

// ============================================================
// Lo propio de clientes: el vendedor y el cajero entran
// ============================================================
//
// Es la diferencia con proveedores, donde ninguno de los dos tiene permiso para
// entrar. Aca entran, cargan y corrigen, y les falta exactamente una accion.

it('le deja ver y editar al vendedor pero le esconde la baja', function () {
    $cliente = clienteDeFicha();

    comoEmpleado(User::factory()->empleado('vendedor')->create())
        ->get(route('gestion.clientes.index'))
        ->assertOk()
        ->assertSee('"crear":true', false)
        ->assertSee('"editar":true', false)
        // No tiene 'cliente.baja': el JS no dibuja los botones de baja ni de
        // reactivacion, y el endpoint los rechazaria con un 403.
        ->assertSee('"baja":false', false);

    comoEmpleado(User::factory()->empleado('vendedor')->create())
        ->get(route('gestion.clientes.show', $cliente))
        ->assertOk()
        ->assertSee('"baja":false', false);
});

it('le deja ver y editar al cajero pero le esconde la baja', function () {
    comoEmpleado(User::factory()->empleado('cajero')->create())
        ->get(route('gestion.clientes.index'))
        ->assertOk()
        ->assertSee('"editar":true', false)
        ->assertSee('"baja":false', false);
});

it('deja entrar al vendedor al alta, que si puede crear', function () {
    comoEmpleado(User::factory()->empleado('vendedor')->create())
        ->get(route('gestion.clientes.create'))
        ->assertOk()
        ->assertSee('formularioCliente');
});

// ============================================================
// El JavaScript de las pantallas esta declarado
// ============================================================

it('declara en vite los modulos que piden las vistas de clientes', function () {
    $configuracion = file_get_contents(base_path('vite.config.js'));

    $modulos = [
        'resources/js/gestion/clientes/index.js',
        'resources/js/gestion/clientes/formulario.js',
        'resources/js/gestion/clientes/ficha.js',
    ];

    foreach ($modulos as $modulo) {
        expect(file_exists(base_path($modulo)))->toBeTrue("Falta el modulo {$modulo}")
            ->and($configuracion)->toContain($modulo);
    }

    $vistas = [
        'views/gestion/clientes/index.blade.php'  => $modulos[0],
        'views/gestion/clientes/create.blade.php' => $modulos[1],
        'views/gestion/clientes/edit.blade.php'   => $modulos[1],
        'views/gestion/clientes/show.blade.php'   => $modulos[2],
    ];

    foreach ($vistas as $vista => $modulo) {
        expect(file_get_contents(resource_path($vista)))->toContain($modulo);
    }
});
