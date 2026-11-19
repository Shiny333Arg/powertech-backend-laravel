<?php

use App\Models\Empleado;
use App\Models\Producto;
use App\Models\TipoDocumento;
use App\Models\User;
use Database\Seeders\Maestros\AlicuotaIvaSeeder;
use Database\Seeders\Maestros\CondicionIvaSeeder;
use Database\Seeders\Maestros\PermisoSeeder;
use Database\Seeders\Maestros\RolSeeder;
use Database\Seeders\Maestros\TipoDocumentoSeeder;

/*
|--------------------------------------------------------------------------
| Autorizacion de las acciones de estado de la administracion
|--------------------------------------------------------------------------
|
| <b>Por que existe este archivo.</b> `authorizeResource()` cubre unicamente
| los siete metodos de recurso —index, show, create, store, edit, update,
| destroy—. Las acciones de estado (dar de baja, reactivar, publicar,
| despublicar, reenviar invitacion) NO quedan cubiertas, y hasta la etapa 03
| ninguna de las cinco pantallas las autorizaba: lo unico que las protegia era
| el middleware 'ambito:gestion', que deja pasar a cualquier empleado. Un
| cajero podia despublicar un producto de la tienda.
|
| Las policies ya tenian los metodos escritos —ProductoPolicy::cambiarEstado()
| y ::publicar() entre ellos— pero nadie los invocaba. La correccion fue
| llamarlos; estas pruebas son las que impiden que el agujero vuelva.
|
| <b>El actor sin permiso es el CAJERO y no un invitado.</b> Un invitado lo
| frena 'auth' y un cliente lo frena 'ambito', asi que ninguno de los dos
| prueba nada sobre la autorizacion fina. El cajero pertenece al ambito
| gestion, esta autenticado, y segun la matriz real de rol_permiso no tiene
| ninguno de los permisos que se prueban aca: es el unico actor que distingue
| "autenticado en la aplicacion correcta" de "habilitado para esta operacion".
|
| Los permisos no se inventan para la prueba: se siembran con PermisoSeeder,
| que trae la misma matriz que la base de produccion.
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
});

// ==================================================================
// Helpers
// ==================================================================

/** Empleado del rol indicado, autenticado por sesion web. */
function actuandoComo(string $rol): User
{
    $user = User::factory()->empleado($rol)->create();

    comoEmpleado($user);

    return $user;
}

/*
 * proveedorDePrueba() se movio a tests/Apoyo/fabricas.php en la entrega D: la
 * pasaron a usar tambien las pruebas de la API de proveedores, y dos copias de
 * la misma fabrica divergen.
 */

/**
 * Empleado al que se le puede cambiar el estado.
 *
 * Es vendedor a proposito: `motivoParaNoDarDeBaja()` bloquea la baja del
 * unico administrador activo, y ese bloqueo de negocio taparia lo que se
 * quiere probar, que es la autorizacion.
 */
function empleadoDePrueba(): Empleado
{
    $user = User::factory()->empleado('vendedor')->create();

    return Empleado::create([
        'user_id' => $user->id,
        'legajo' => 'EMP-'.fake()->unique()->numberBetween(1000, 9999),
        'tipo_documento_id' => TipoDocumento::query()->value('id'),
        'nro_doc' => (string) fake()->unique()->numberBetween(10_000_000, 99_999_999),
        'cuil' => (string) fake()->unique()->numberBetween(20_000_000_000, 29_999_999_999),
        'fecha_ingreso' => now()->toDateString(),
    ]);
}

// ==================================================================
// 1. Productos: baja, reactivacion y publicacion
// ==================================================================

test('el cajero no puede dar de baja un producto', function () {
    actuandoComo('cajero');

    $producto = productoDePrueba();

    $this->patch("/gestion/item/{$producto->id}/baja")->assertForbidden();

    expect($producto->fresh()->activo)->toBeTrue();
});

test('el administrativo puede dar de baja un producto', function () {
    actuandoComo('administrativo');

    $producto = productoDePrueba();

    $this->patch("/gestion/item/{$producto->id}/baja")->assertRedirect();

    expect($producto->fresh()->activo)->toBeFalse();
});

test('el cajero no puede reactivar un producto', function () {
    actuandoComo('cajero');

    $producto = productoDePrueba(['activo' => false]);

    $this->patch("/gestion/item/{$producto->id}/reactivar")->assertForbidden();

    expect($producto->fresh()->activo)->toBeFalse();
});

test('el administrativo puede reactivar un producto', function () {
    actuandoComo('administrativo');

    $producto = productoDePrueba(['activo' => false]);

    $this->patch("/gestion/item/{$producto->id}/reactivar")->assertRedirect();

    expect($producto->fresh()->activo)->toBeTrue();
});

/**
 * El caso que motivo la correccion: despublicar saca el producto de la tienda
 * online, y lo hacia cualquier empleado.
 */
test('el cajero no puede despublicar un producto de la tienda', function () {
    actuandoComo('cajero');

    $producto = productoDePrueba(['publicado_app' => true]);

    $this->patch("/gestion/item/{$producto->id}/despublicar")->assertForbidden();

    expect($producto->fresh()->publicado_app)->toBeTrue();
});

test('el vendedor no puede publicar un producto en la tienda', function () {
    actuandoComo('vendedor');

    $producto = productoDePrueba(['publicado_app' => false]);

    $this->patch("/gestion/item/{$producto->id}/publicar")->assertForbidden();

    expect($producto->fresh()->publicado_app)->toBeFalse();
});

test('el administrativo puede publicar y despublicar un producto', function () {
    actuandoComo('administrativo');

    $producto = productoDePrueba(['publicado_app' => false]);

    $this->patch("/gestion/item/{$producto->id}/publicar")->assertRedirect();

    expect($producto->fresh()->publicado_app)->toBeTrue();

    $this->patch("/gestion/item/{$producto->id}/despublicar")->assertRedirect();

    expect($producto->fresh()->publicado_app)->toBeFalse();
});

/** La regla de negocio que ya existia sigue corriendo DESPUES del permiso. */
test('un producto dado de baja no se publica aunque haya permiso', function () {
    actuandoComo('administrativo');

    $producto = productoDePrueba(['activo' => false, 'publicado_app' => false]);

    $this->patch("/gestion/item/{$producto->id}/publicar")
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($producto->fresh()->publicado_app)->toBeFalse();
});

// ==================================================================
// 2. Categorias
// ==================================================================

test('el cajero no puede dar de baja una categoria', function () {
    actuandoComo('cajero');

    $categoria = categoriaDePrueba();

    $this->patch("/gestion/category/{$categoria->id}/baja")->assertForbidden();

    expect($categoria->fresh()->activo)->toBeTrue();
});

test('el administrativo puede dar de baja y reactivar una categoria', function () {
    actuandoComo('administrativo');

    $categoria = categoriaDePrueba();

    $this->patch("/gestion/category/{$categoria->id}/baja")->assertRedirect();

    expect($categoria->fresh()->activo)->toBeFalse();

    $this->patch("/gestion/category/{$categoria->id}/reactivar")->assertRedirect();

    expect($categoria->fresh()->activo)->toBeTrue();
});

test('el cajero no puede reactivar una categoria', function () {
    actuandoComo('cajero');

    $categoria = categoriaDePrueba();
    $categoria->update(['activo' => false]);

    $this->patch("/gestion/category/{$categoria->id}/reactivar")->assertForbidden();

    expect($categoria->fresh()->activo)->toBeFalse();
});

// ==================================================================
// 3. Promociones
// ==================================================================

test('el cajero no puede dar de baja una promocion', function () {
    actuandoComo('cajero');

    $promocion = promocionSobre(productoDePrueba());

    $this->patch("/gestion/promotion/{$promocion->id}/baja")->assertForbidden();

    expect($promocion->fresh()->activo)->toBeTrue();
});

test('el administrativo puede dar de baja y reactivar una promocion', function () {
    actuandoComo('administrativo');

    $promocion = promocionSobre(productoDePrueba());

    $this->patch("/gestion/promotion/{$promocion->id}/baja")->assertRedirect();

    expect($promocion->fresh()->activo)->toBeFalse();

    $this->patch("/gestion/promotion/{$promocion->id}/reactivar")->assertRedirect();

    expect($promocion->fresh()->activo)->toBeTrue();
});

test('el vendedor no puede reactivar una promocion', function () {
    actuandoComo('vendedor');

    $promocion = promocionSobre(productoDePrueba(), ['activo' => false]);

    $this->patch("/gestion/promotion/{$promocion->id}/reactivar")->assertForbidden();

    expect($promocion->fresh()->activo)->toBeFalse();
});

// ==================================================================
// 4. Proveedores
// ==================================================================

test('el cajero no puede dar de baja un proveedor', function () {
    actuandoComo('cajero');

    $proveedor = proveedorDePrueba();

    $this->patch("/gestion/proveedor/{$proveedor->id}/baja")->assertForbidden();

    expect($proveedor->fresh()->activo)->toBeTrue();
});

test('el administrativo puede dar de baja y reactivar un proveedor', function () {
    actuandoComo('administrativo');

    $proveedor = proveedorDePrueba();

    $this->patch("/gestion/proveedor/{$proveedor->id}/baja")->assertRedirect();

    expect($proveedor->fresh()->activo)->toBeFalse();

    $this->patch("/gestion/proveedor/{$proveedor->id}/reactivar")->assertRedirect();

    expect($proveedor->fresh()->activo)->toBeTrue();
});

test('el vendedor no puede reactivar un proveedor', function () {
    actuandoComo('vendedor');

    $proveedor = proveedorDePrueba(['activo' => false]);

    $this->patch("/gestion/proveedor/{$proveedor->id}/reactivar")->assertForbidden();

    expect($proveedor->fresh()->activo)->toBeFalse();
});

// ==================================================================
// 5. Empleados: baja, reactivacion y reenvio de invitacion
// ==================================================================

test('el cajero no puede dar de baja un empleado', function () {
    actuandoComo('cajero');

    $empleado = empleadoDePrueba();

    $this->patch("/gestion/empleado/{$empleado->id}/baja")->assertForbidden();

    expect($empleado->fresh()->fecha_baja)->toBeNull()
        ->and($empleado->user->fresh()->activo)->toBeTrue();
});

test('el administrativo puede dar de baja y reactivar un empleado', function () {
    actuandoComo('administrativo');

    $empleado = empleadoDePrueba();

    $this->patch("/gestion/empleado/{$empleado->id}/baja")->assertRedirect();

    expect($empleado->fresh()->fecha_baja)->not->toBeNull()
        ->and($empleado->user->fresh()->activo)->toBeFalse();

    $this->patch("/gestion/empleado/{$empleado->id}/reactivar")->assertRedirect();

    expect($empleado->fresh()->fecha_baja)->toBeNull()
        ->and($empleado->user->fresh()->activo)->toBeTrue();
});

test('el vendedor no puede reactivar un empleado', function () {
    actuandoComo('vendedor');

    $empleado = empleadoDePrueba();
    $empleado->user->update(['activo' => false]);
    $empleado->update(['fecha_baja' => now()->toDateString()]);

    $this->patch("/gestion/empleado/{$empleado->id}/reactivar")->assertForbidden();

    expect($empleado->user->fresh()->activo)->toBeFalse();
});

/**
 * Reenviar la invitacion es dar acceso al sistema, asi que la policy pide
 * 'empleado.crear' y no 'empleado.editar'.
 */
test('el cajero no puede reenviar la invitacion de un empleado', function () {
    actuandoComo('cajero');

    $empleado = empleadoDePrueba();

    $this->post("/gestion/empleado/{$empleado->id}/reenviar-invitacion")->assertForbidden();
});

test('el administrativo puede pedir el reenvio de la invitacion de un empleado', function () {
    actuandoComo('administrativo');

    // Con la contraseña ya elegida el controlador corta con un aviso, pero
    // DESPUES de autorizar: lo que se prueba aca es que no responde 403.
    $empleado = empleadoDePrueba();

    $this->post("/gestion/empleado/{$empleado->id}/reenviar-invitacion")
        ->assertRedirect()
        ->assertSessionMissing('errors');
});

// ==================================================================
// 6. El administrador conserva todo
// ==================================================================

test('el administrador puede cambiar el estado de cualquier recurso', function () {
    actuandoComo('administrador');

    $producto = productoDePrueba();
    $categoria = categoriaDePrueba();
    $proveedor = proveedorDePrueba();

    $this->patch("/gestion/item/{$producto->id}/baja")->assertRedirect();
    $this->patch("/gestion/item/{$producto->id}/reactivar")->assertRedirect();
    $this->patch("/gestion/item/{$producto->id}/despublicar")->assertRedirect();
    $this->patch("/gestion/category/{$categoria->id}/baja")->assertRedirect();
    $this->patch("/gestion/proveedor/{$proveedor->id}/baja")->assertRedirect();

    expect($producto->fresh()->publicado_app)->toBeFalse()
        ->and($categoria->fresh()->activo)->toBeFalse()
        ->and($proveedor->fresh()->activo)->toBeFalse();
});

// ==================================================================
// 7. Las capas anteriores siguen en su lugar
// ==================================================================

test('un invitado no llega a las acciones de estado', function () {
    $producto = productoDePrueba();

    // Desde F2 el panel tiene su propia puerta de ingreso y redirectGuestsTo
    // decide el destino segun la URL pedida: un empleado que pega una URL de
    // /gestion/... cae en el formulario del panel y no en el del storefront,
    // que no emite token y lo rebotaria.
    $this->patch("/gestion/item/{$producto->id}/baja")->assertRedirect(route('gestion.login'));
});

test('un cliente no entra al ambito de gestion', function () {
    $this->actingAs(clienteDeTienda());

    $producto = productoDePrueba();

    $this->patch("/gestion/item/{$producto->id}/baja")->assertRedirect();

    expect($producto->fresh()->activo)->toBeTrue();
});
