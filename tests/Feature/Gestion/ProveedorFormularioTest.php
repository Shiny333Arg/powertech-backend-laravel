<?php

use App\Models\CondicionIva;
use App\Models\User;
use Database\Seeders\Maestros\AlicuotaIvaSeeder;
use Database\Seeders\Maestros\CondicionIvaSeeder;
use Database\Seeders\Maestros\PermisoSeeder;
use Database\Seeders\Maestros\RolSeeder;

/*
|--------------------------------------------------------------------------
| Entrega D — el formulario de proveedores del panel sigue funcionando
|--------------------------------------------------------------------------
|
| <b>Dos motivos para que exista este archivo.</b>
|
| El primero es un error encontrado al inspeccionar el modulo para la API: el
| redirect de ProveedorController@update apuntaba a 'gestion.proveedors.index',
| que no es el nombre de ninguna ruta —el prefijo name() de routes/web.php dice
| 'proveedores.'; 'proveedors' es la carpeta de VISTAS—, asi que guardar la
| edicion de un proveedor terminaba en RouteNotFoundException. Ninguna prueba
| llegaba a ese redirect: AutorizacionEstadoTest cubre /baja y /reactivar, y el
| alta redirige a otra linea, que si estaba bien escrita.
|
| El segundo es que el guardado del domicilio se movio del controlador a
| App\Support\DomicilioFiscal para que la API no lo duplique. El comportamiento
| del formulario no tenia que cambiar, y esto es lo que lo demuestra.
|
| Las acciones de estado y la autorizacion de este modulo ya estan cubiertas en
| tests/Feature/Gestion/AutorizacionEstadoTest.php; aca solo esta el circuito
| del formulario.
|
| `withoutVite()` porque estas pruebas llegan a vistas del panel, que compilan
| assets con @vite: sin el manifest de produccion la vista no renderiza. Que el
| manifest incluya lo que el panel pide es lo que verifica AssetsGestionTest,
| que es el unico archivo que a proposito no lo desactiva.
|
*/

beforeEach(function () {
    $this->withoutVite();

    $this->seed([
        RolSeeder::class,
        PermisoSeeder::class,
        CondicionIvaSeeder::class,
        AlicuotaIvaSeeder::class,
    ]);
});

/**
 * Cuerpo del formulario, con el domicilio bajo la clave 'direccion' tal como lo
 * manda create.blade.php / edit.blade.php.
 *
 * @return array<string, mixed>
 */
function formularioDeProveedor(array $cambios = []): array
{
    return array_merge([
        'razon_social'       => 'Distribuidora Austral SA',
        'cuit'               => cuitValido(),
        'condicion_iva_id'   => CondicionIva::query()->value('id'),
        'email'              => 'ventas@austral.test',
        'telefono'           => '2974000000',
        'contacto'           => 'Marina Ruiz',
        'canal_pedido'       => 'email',
        'plazo_entrega_dias' => 7,
        'activo'             => '1',
        'direccion'          => [
            'calle'         => 'Av. Siempreviva',
            'numero'        => '742',
            'codigo_postal' => '9011',
            'localidad'     => 'Caleta Olivia',
            'provincia'     => 'Santa Cruz',
        ],
    ], $cambios);
}

it('guarda el alta con su domicilio y vuelve al listado', function () {
    comoEmpleado(User::factory()->empleado('administrativo')->create());

    $datos = formularioDeProveedor();

    $this->post('/gestion/proveedor', $datos)
        ->assertRedirect(route('gestion.proveedores.index'))
        ->assertSessionHas('exito');

    $this->assertDatabaseHas('proveedores', [
        'razon_social' => 'Distribuidora Austral SA',
        'cuit'         => $datos['cuit'],
    ]);

    $this->assertDatabaseHas('direcciones', [
        'tipo'              => 'fiscal',
        'localidad'         => 'Caleta Olivia',
        'es_predeterminada' => true,
    ]);
});

it('guarda la edicion y vuelve al listado', function () {
    // Es el redirect que estaba roto. Sin la correccion, esta llamada responde
    // 500 por RouteNotFoundException en lugar de redirigir.
    comoEmpleado(User::factory()->empleado('administrativo')->create());

    $proveedor = proveedorDePrueba(['razon_social' => 'Austral SA']);

    $this->put("/gestion/proveedor/{$proveedor->id}", formularioDeProveedor([
        'razon_social' => 'Austral Mayorista SA',
        'cuit'         => $proveedor->cuit,
    ]))
        ->assertRedirect(route('gestion.proveedores.index'))
        ->assertSessionHas('exito');

    expect($proveedor->refresh()->razon_social)->toBe('Austral Mayorista SA');
});

it('reemplaza el domicilio fiscal en lugar de acumular otro', function () {
    // La regla vive ahora en App\Support\DomicilioFiscal, compartida con la
    // API: un solo domicilio fiscal por proveedor.
    comoEmpleado(User::factory()->empleado('administrativo')->create());

    $proveedor = proveedorDePrueba();

    $datos = formularioDeProveedor(['cuit' => $proveedor->cuit]);

    $this->put("/gestion/proveedor/{$proveedor->id}", $datos)->assertRedirect();

    $datos['direccion']['localidad'] = 'Comodoro Rivadavia';

    $this->put("/gestion/proveedor/{$proveedor->id}", $datos)->assertRedirect();

    expect($proveedor->direcciones()->count())->toBe(1);
    expect($proveedor->direcciones()->first()->localidad)->toBe('Comodoro Rivadavia');
});

it('no borra el domicilio cargado si el formulario no trae calle', function () {
    comoEmpleado(User::factory()->empleado('administrativo')->create());

    $proveedor = proveedorDePrueba();

    $datos = formularioDeProveedor(['cuit' => $proveedor->cuit]);

    $this->put("/gestion/proveedor/{$proveedor->id}", $datos)->assertRedirect();

    $datos['direccion'] = ['calle' => '', 'numero' => '', 'piso_depto' => '', 'codigo_postal' => '', 'localidad' => '', 'provincia' => ''];

    $this->put("/gestion/proveedor/{$proveedor->id}", $datos)->assertRedirect();

    expect($proveedor->direcciones()->count())->toBe(1);
    expect($proveedor->direcciones()->first()->localidad)->toBe('Caleta Olivia');
});

it('muestra el formulario de edicion con el domicilio cargado', function () {
    // edit() lee el domicilio con DomicilioFiscal::de(): la pantalla tiene que
    // seguir llegando con los campos completos.
    comoEmpleado(User::factory()->empleado('administrativo')->create());

    $proveedor = proveedorDePrueba();

    $proveedor->direcciones()->create([
        'tipo'              => 'fiscal',
        'calle'             => 'Av. Siempreviva',
        'numero'            => '742',
        'codigo_postal'     => '9011',
        'localidad'         => 'Caleta Olivia',
        'provincia'         => 'Santa Cruz',
        'es_predeterminada' => true,
    ]);

    $this->get("/gestion/proveedor/{$proveedor->id}/edit")
        ->assertOk()
        ->assertSee('Av. Siempreviva', false)
        ->assertSee('Caleta Olivia', false);
});
