<?php

use App\Models\Empleado;
use App\Models\TipoDocumento;
use App\Models\User;
use App\Notifications\InvitacionEmpleado;
use Database\Seeders\Maestros\CondicionIvaSeeder;
use Database\Seeders\Maestros\PermisoSeeder;
use Database\Seeders\Maestros\RolSeeder;
use Database\Seeders\Maestros\TipoDocumentoSeeder;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Circuito de invitacion
|--------------------------------------------------------------------------
|
| <b>Por que existe este archivo.</b> La invitacion es un circuito de
| seguridad —URL firmada, un solo uso, verificacion implicita del correo— y
| no tenia ninguna prueba. Al quitar de `routes/auth.php` las dos
| declaraciones sobrantes (`invitacion.create`, que apuntaba a un metodo
| inexistente, y un POST sin parametro que duplicaba el nombre
| `invitacion.store`) hacia falta dejar fijado que el circuito real sigue
| entero.
|
| <b>El enlace no se construye a mano.</b> Se le pide a la notificacion, que
| es quien lo arma en produccion: asi la prueba recorre notificacion → ruta
| → controlador, y no una URL inventada que podria no coincidir.
|
| <b>Las tres cosas que hacen segura a la invitacion</b>, y que cada prueba
| cubre por separado:
|
|   1. La firma. Cambiar el id del usuario en el enlace lo invalida.
|   2. El uso unico. Se acepta solo mientras la cuenta no tenga contraseña;
|      aceptarla le asigna una, y eso la cierra sin necesidad de ninguna
|      tabla de tokens.
|   3. La verificacion del correo. Completar la invitacion prueba que la
|      direccion que cargo el administrativo es real, porque el enlace solo
|      pudo haber llegado ahi.
|
*/

beforeEach(function () {
    $this->seed([
        RolSeeder::class,
        PermisoSeeder::class,
        TipoDocumentoSeeder::class,
        CondicionIvaSeeder::class,
    ]);

    $this->withoutVite();
});

/** Cuenta recien dada de alta: sin contraseña y sin correo verificado. */
function empleadoInvitado(string $rol = 'vendedor'): User
{
    return User::factory()->empleado($rol)->create([
        'password' => null,
        'email_verified_at' => null,
    ]);
}

/** El enlace tal como lo arma la notificacion que recibe el empleado. */
function enlaceDeInvitacion(User $user): string
{
    return (new InvitacionEmpleado)->toMail($user)->actionUrl;
}

// ==================================================================
// Las rutas que quedaron
// ==================================================================

test('solo existen las dos rutas de invitacion que se usan', function () {
    expect(Route::has('invitacion.show'))->toBeTrue()
        ->and(Route::has('invitacion.store'))->toBeTrue()
        ->and(Route::has('invitacion.create'))->toBeFalse();
});

/**
 * Con el nombre duplicado, `route('invitacion.store', $user)` resolvia a la
 * declaracion sin parametro y devolvia la URL equivocada.
 */
test('route invitacion.store genera la URL con el usuario', function () {
    $user = empleadoInvitado();

    expect(route('invitacion.store', $user, absolute: false))->toBe("/invitacion/{$user->id}");
});

// ==================================================================
// Aceptacion
// ==================================================================

test('el enlace de la invitacion abre el formulario', function () {
    $user = empleadoInvitado();

    $this->get(enlaceDeInvitacion($user))
        ->assertOk()
        ->assertSee($user->email);
});

test('aceptar la invitacion asigna la contraseña, verifica el correo e inicia sesion', function () {
    $user = empleadoInvitado();

    $this->post(enlaceDeInvitacion($user), [
        'password' => 'Clave-Segura-123',
        'password_confirmation' => 'Clave-Segura-123',
    ])->assertRedirect(route($user->rutaInicio()));

    $user->refresh();

    expect($user->password)->not->toBeNull()
        ->and($user->email_verified_at)->not->toBeNull();

    $this->assertAuthenticatedAs($user);
});

test('la contraseña tiene que venir confirmada', function () {
    $user = empleadoInvitado();

    $this->post(enlaceDeInvitacion($user), [
        'password' => 'Clave-Segura-123',
        'password_confirmation' => 'otra-cosa',
    ])->assertSessionHasErrors('password');

    expect($user->fresh()->password)->toBeNull();
    $this->assertGuest();
});

// ==================================================================
// Las tres defensas
// ==================================================================

test('un enlace sin firma no sirve', function () {
    $user = empleadoInvitado();

    $this->get(route('invitacion.show', $user))
        ->assertRedirect(route('login'))
        ->assertSessionHas('error');

    expect($user->fresh()->password)->toBeNull();
});

test('cambiar el usuario en el enlace invalida la firma', function () {
    $invitado = empleadoInvitado();
    $otro = empleadoInvitado('cajero');

    $enlaceAjeno = str_replace(
        "/invitacion/{$invitado->id}?",
        "/invitacion/{$otro->id}?",
        enlaceDeInvitacion($invitado),
    );

    $this->get($enlaceAjeno)
        ->assertRedirect(route('login'))
        ->assertSessionHas('error');

    expect($otro->fresh()->password)->toBeNull();
});

test('la invitacion sirve una sola vez', function () {
    $user = empleadoInvitado();
    $enlace = enlaceDeInvitacion($user);

    $this->post($enlace, [
        'password' => 'Clave-Segura-123',
        'password_confirmation' => 'Clave-Segura-123',
    ])->assertRedirect();

    $claveAsignada = $user->fresh()->password;

    $this->flushSession();
    auth()->logout();

    $this->get($enlace)
        ->assertRedirect(route('login'))
        ->assertSessionHas('info');

    $this->post($enlace, [
        'password' => 'Otra-Clave-456',
        'password_confirmation' => 'Otra-Clave-456',
    ])->assertRedirect(route('login'));

    expect($user->fresh()->password)->toBe($claveAsignada);
});

test('una cuenta dada de baja no puede aceptar la invitacion', function () {
    $user = empleadoInvitado();
    $enlace = enlaceDeInvitacion($user);

    $user->update(['activo' => false]);

    $this->get($enlace)
        ->assertRedirect(route('login'))
        ->assertSessionHas('error');

    expect($user->fresh()->password)->toBeNull();
});

// ==================================================================
// Reenvio
// ==================================================================

test('el administrativo puede reenviar una invitacion pendiente', function () {
    Notification::fake();

    $invitado = empleadoInvitado();

    $empleado = Empleado::create([
        'user_id' => $invitado->id,
        'legajo' => 'EMP-'.fake()->unique()->numberBetween(1000, 9999),
        'tipo_documento_id' => TipoDocumento::query()->value('id'),
        'nro_doc' => (string) fake()->unique()->numberBetween(10_000_000, 99_999_999),
        'cuil' => (string) fake()->unique()->numberBetween(20_000_000_000, 29_999_999_999),
        'fecha_ingreso' => now()->toDateString(),
    ]);

    comoEmpleado(User::factory()->empleado('administrativo')->create());

    $this->post(route('gestion.empleados.reenviarInvitacion', $empleado))
        ->assertRedirect()
        ->assertSessionHas('exito');

    Notification::assertSentTo($invitado, InvitacionEmpleado::class);
});

test('no se reenvia la invitacion de una cuenta ya activada', function () {
    Notification::fake();

    $activado = User::factory()->empleado('vendedor')->create();

    $empleado = Empleado::create([
        'user_id' => $activado->id,
        'legajo' => 'EMP-'.fake()->unique()->numberBetween(1000, 9999),
        'tipo_documento_id' => TipoDocumento::query()->value('id'),
        'nro_doc' => (string) fake()->unique()->numberBetween(10_000_000, 99_999_999),
        'cuil' => (string) fake()->unique()->numberBetween(20_000_000_000, 29_999_999_999),
        'fecha_ingreso' => now()->toDateString(),
    ]);

    comoEmpleado(User::factory()->empleado('administrativo')->create());

    $this->post(route('gestion.empleados.reenviarInvitacion', $empleado))
        ->assertRedirect()
        ->assertSessionHas('error');

    Notification::assertNothingSent();
});
