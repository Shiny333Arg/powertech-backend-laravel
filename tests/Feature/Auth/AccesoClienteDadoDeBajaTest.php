<?php

use App\Models\Cliente;
use App\Models\CondicionIva;
use App\Models\TipoDocumento;
use App\Models\User;
use Database\Seeders\Maestros\AlicuotaIvaSeeder;
use Database\Seeders\Maestros\CondicionIvaSeeder;
use Database\Seeders\Maestros\PermisoSeeder;
use Database\Seeders\Maestros\RolSeeder;
use Database\Seeders\Maestros\TipoDocumentoSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;

/*
|--------------------------------------------------------------------------
| Un cliente dado de baja no entra por ningun punto de acceso
|--------------------------------------------------------------------------
|
| <b>El bug que cubre este archivo.</b> La baja de un cliente escribia solo
| `clientes.activo`, y en todo el sistema esa columna tiene exactamente dos
| lectores, los dos de mostrador: BusquedaController::clientes y VentaRequest.
| La credencial vive en `users.activo` y nadie la tocaba, asi que un cliente
| dado de baja seguia ingresando —por la web, por Flutter y por el ingreso del
| panel— y podia comprar online. El arreglo fue que la baja escriba las dos
| columnas en una transaccion, como ya hacia EmpleadoController::darDeBaja().
|
| <b>Lo que se prueba aca es el EFECTO, no la implementacion.</b> Se da de baja
| por el endpoint de gestion —el camino real de la pantalla— y despues se golpea
| cada puerta. Por eso estas pruebas estan en tests/Feature/Auth y no junto a
| las del modulo: lo que fijan es que la autenticacion no deje pasar a quien fue
| dado de baja, y seguirian valiendo si manana cambiara la forma de darlo.
|
| <b>No se agrego ninguna validacion nueva a la autenticacion.</b> Los cinco
| puntos leen `users.activo` como siempre; lo unico que cambio es que esa
| columna ahora se escribe. Estas pruebas lo demuestran de a una puerta.
|
| El contraste del empleado al final es la garantia de que no se toco el
| comportamiento de los demas roles.
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

/** La contraseña de todas las cuentas de este archivo. */
const CLAVE_DE_ACCESO = 'Clave#Segura1';

/**
 * Cliente con cuenta, activo, listo para ingresar.
 *
 * Se arma a mano porque el proyecto solo tiene UserFactory y Cliente no usa
 * HasFactory. Mismo criterio que las fabricas de tests/Apoyo/fabricas.php.
 */
function clienteConAcceso(array $cuenta = []): Cliente
{
    static $siguiente = 0;

    $usuario = User::factory()->cliente()->create($cuenta + [
        'email'    => 'acceso'.++$siguiente.'@correo.test',
        'password' => CLAVE_DE_ACCESO,
    ]);

    return Cliente::create([
        'user_id'           => $usuario->id,
        'razon_social'      => 'Cliente con acceso '.$siguiente,
        'tipo_documento_id' => TipoDocumento::where('codigo_afip', 96)->value('id'),
        'nro_doc'           => (string) (35_000_000 + $siguiente),
        'condicion_iva_id'  => CondicionIva::where('codigo_afip', 5)->value('id'),
        'activo'            => true,
    ]);
}

/**
 * Lo da de baja por el endpoint de gestion, que es el camino de la pantalla.
 *
 * Se usa un token real y no Sanctum::actingAs() a proposito: actingAs sustituye
 * el guard por defecto por el de Sanctum para todo el test, y el login que
 * viene despues necesita el guard de sesion —Auth::validate() reventaria con
 * "Undefined array key request", porque RequestGuard::validate() espera una
 * credencial que el login no manda—.
 *
 * <b>Y despues hay que devolver el estado de autenticacion a como estaba.</b>
 * Atravesar 'auth:sanctum' deja dos rastros para el resto del test: el guard de
 * Sanctum como predeterminado (shouldUse) y el empleado que hizo la baja
 * resuelto en cache. Sin limpiarlos, el request siguiente lo haria el
 * administrativo y no el cliente —da 403 AMBITO_INCORRECTO donde se esperaba un
 * 401— y el login reventaria. Es artefacto del test, no del sistema: en
 * produccion cada request resuelve su guard de cero.
 */
function darDeBajaPorGestion(Cliente $cliente, bool $activo = false): void
{
    // Antes, no solo despues: si el test ya hizo un request como cliente, su
    // usuario quedo resuelto en cache y la baja la intentaria el cliente en
    // lugar del empleado —403 AMBITO_INCORRECTO—.
    olvidarLaAutenticacion();

    $empleado = User::factory()->empleado('administrativo')->create();

    test()->withToken(tokenDeGestion($empleado)->plainTextToken)
        ->patchJson(route('api.v1.gestion.clientes.estado', $cliente), ['activo' => $activo])
        ->assertOk();

    olvidarLaAutenticacion();
}

/**
 * Borra los guards resueltos y restituye 'web' como predeterminado.
 *
 * Va 'web' escrito y no config('auth.defaults.guard') porque para cuando esto
 * corre ese valor ya no sirve: AuthManager::shouldUse() —que es lo que llama
 * el middleware de Sanctum— SOBREESCRIBE la clave de config, asi que leerla
 * devolveria 'sanctum' y no restituiria nada. 'web' es el predeterminado real
 * del proyecto (config/auth.php).
 */
function olvidarLaAutenticacion(): void
{
    app('auth')->forgetGuards();
    app('auth')->shouldUse('web');
}

// ============================================================
// Ingreso por la API: Flutter y el ingreso del panel
// ============================================================

it('un cliente activo ingresa por la API y recibe su token', function () {
    $cliente = clienteConAcceso();

    $respuesta = $this->postJson(route('api.v1.auth.login'), [
        'email'    => $cliente->user->email,
        'password' => CLAVE_DE_ACCESO,
    ])->assertOk();

    expect($respuesta->json('token'))->not->toBeNull();
});

it('un cliente dado de baja NO ingresa por la API', function () {
    $cliente = clienteConAcceso();

    darDeBajaPorGestion($cliente);

    // Lo rechaza LoginMovilRequest::usuarioValidado() leyendo users.activo,
    // igual que antes: es el mismo camino que usa Flutter. 422 y sin token.
    $respuesta = $this->postJson(route('api.v1.auth.login'), [
        'email'    => $cliente->user->email,
        'password' => CLAVE_DE_ACCESO,
    ])->assertStatus(422)->assertJsonValidationErrors('email');

    expect($respuesta->json('token'))->toBeNull()
        // <b>El mensaje es el generico y no 'auth.inactive'.</b> El 'activo'
        // viaja como credencial extra a Auth::validate(), asi que el provider
        // lo agrega como where y la cuenta de baja ni se recupera: falla antes
        // de llegar a la rama que informa la desactivacion. Es mas conservador
        // que lo que el comentario de ese Request describe —no revela siquiera
        // que el correo existe— y es comportamiento previo a esta correccion,
        // no algo que introduzca. Se fija como esta para que un cambio ahi se
        // note.
        ->and($respuesta->json('errors.email.0'))->toBe(trans('auth.failed'));
});

it('vuelve a ingresar por la API despues de reactivarlo', function () {
    $cliente = clienteConAcceso();

    darDeBajaPorGestion($cliente);
    darDeBajaPorGestion($cliente, activo: true);

    $this->postJson(route('api.v1.auth.login'), [
        'email'    => $cliente->user->email,
        'password' => CLAVE_DE_ACCESO,
    ])->assertOk();
});

// ============================================================
// Ingreso por sesion web
// ============================================================

it('un cliente activo ingresa por la web', function () {
    $cliente = clienteConAcceso();

    $this->post('/login', [
        'email'    => $cliente->user->email,
        'password' => CLAVE_DE_ACCESO,
    ])->assertRedirect();

    $this->assertAuthenticatedAs($cliente->user);
});

it('un cliente dado de baja NO ingresa por la web', function () {
    $cliente = clienteConAcceso();

    darDeBajaPorGestion($cliente);

    $this->post('/login', [
        'email'    => $cliente->user->email,
        'password' => CLAVE_DE_ACCESO,
    ])->assertSessionHasErrors('email');

    // Es la puerta que el bug dejaba abierta de la forma mas visible: antes del
    // arreglo esto respondia 302 y quedaba autenticado.
    $this->assertGuest();
});

// ============================================================
// Tokens emitidos ANTES de la baja
// ============================================================

it('revoca los tokens que el cliente ya tenia', function () {
    $cliente = clienteConAcceso();

    $telefono = $cliente->user->createToken('Telefono', ['tienda'])->plainTextToken;
    $cliente->user->createToken('Tablet', ['tienda']);

    darDeBajaPorGestion($cliente);

    // Borrados, no solo inservibles: es la segunda decision del arreglo.
    expect($cliente->user->tokens()->count())->toBe(0);

    // Y el que estaba en el telefono ya no autentica a nadie.
    $this->withToken($telefono)
        ->getJson(route('api.v1.perfil.show'))
        ->assertUnauthorized();
});

it('corta el token incluso si la revocacion no hubiera llegado', function () {
    // Defensa en profundidad: el corte NO depende de haber borrado los tokens.
    // Se simula el token sobreviviente dando de baja la cuenta sin pasar por el
    // endpoint, para comprobar que el middleware 'api.activo' lo rechaza igual.
    $cliente = clienteConAcceso();

    $token = $cliente->user->createToken('Telefono', ['tienda'])->plainTextToken;

    $cliente->user->darDeBaja();

    $this->withToken($token)
        ->getJson(route('api.v1.perfil.show'))
        ->assertUnauthorized()
        ->assertJsonPath('codigo', 'CUENTA_INACTIVA');
});

// ============================================================
// Sesion web abierta ANTES de la baja
// ============================================================

it('corta la sesion web que estaba abierta cuando lo dieron de baja', function () {
    $cliente = clienteConAcceso();

    // El cliente ya esta navegando.
    $this->actingAs($cliente->user);
    $this->assertAuthenticatedAs($cliente->user);

    // Se lo da de baja desde otra pantalla. La baja va por la base y no por el
    // endpoint: este test mira el middleware del grupo 'web', no el controlador.
    $cliente->user->darDeBaja();

    // En el proximo clic, VerificarCuentaActiva lo saca.
    $this->get(route('cliente.carrito.index'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

// ============================================================
// Compras online
// ============================================================

it('un cliente dado de baja no puede comprar online', function () {
    $cliente = clienteConAcceso(['email_verified_at' => now()]);

    $token = $cliente->user->createToken('Telefono', ['tienda'])->plainTextToken;

    // Con la cuenta activa el carrito le responde.
    $this->withToken($token)
        ->getJson(route('api.v1.carrito.index'))
        ->assertOk();

    darDeBajaPorGestion($cliente);

    // Ya sin token: ni ve el carrito ni llega al checkout. Lo corta
    // 'api.activo', que esta en todas las rutas de tienda.
    foreach ([
        ['getJson', route('api.v1.carrito.index')],
        ['getJson', route('api.v1.checkout.index')],
        ['postJson', route('api.v1.checkout.pagar')],
    ] as [$metodo, $url]) {
        $this->withToken($token)->{$metodo}($url)->assertUnauthorized();
    }

    // Y tampoco puede conseguir un token nuevo para intentarlo.
    $this->postJson(route('api.v1.auth.login'), [
        'email'    => $cliente->user->email,
        'password' => CLAVE_DE_ACCESO,
    ])->assertStatus(422);
});

it('un cliente dado de baja tampoco puede comprar desde la tienda web', function () {
    $cliente = clienteConAcceso(['email_verified_at' => now()]);

    $this->actingAs($cliente->user);

    darDeBajaPorGestion($cliente);

    $this->get(route('cliente.carrito.index'))->assertRedirect(route('login'));

    $this->assertGuest();
});

// ============================================================
// Recuperar la contraseña y aceptar la invitacion
// ============================================================

it('no le manda el enlace de restablecimiento a un cliente dado de baja', function () {
    Notification::fake();

    $cliente = clienteConAcceso();

    darDeBajaPorGestion($cliente);

    // <b>Contesta 200 a proposito, y eso no es un agujero.</b>
    // RecuperacionPassword::enviarEnlace() no devuelve nada y responde igual
    // exista o no la cuenta y este activa o no: si el formulario distinguiera
    // los casos pasaria a confirmar que un correo esta registrado. Lo que se
    // comprueba es lo que importa —que NO sale ningun correo—, no el codigo.
    $this->postJson(route('api.v1.auth.password.olvide'), [
        'email' => $cliente->user->email,
    ])->assertOk();

    Notification::assertNothingSent();
});

it('un cliente dado de baja no puede cambiar su contraseña con un token viejo', function () {
    Notification::fake();

    $cliente = clienteConAcceso();

    // Pide el enlace mientras la cuenta esta activa y se queda con el token.
    $this->postJson(route('api.v1.auth.password.olvide'), [
        'email' => $cliente->user->email,
    ])->assertOk();

    $token = Password::createToken($cliente->user);

    darDeBajaPorGestion($cliente);

    // Lo decide RecuperacionPassword::restablecer(), que ya leia users.activo y
    // contesta INVALID_TOKEN para que la cuenta de baja sea indistinguible de
    // un enlace vencido. No hizo falta ninguna regla nueva.
    $this->postJson(route('api.v1.auth.password.restablecer'), [
        'email'                 => $cliente->user->email,
        'token'                 => $token,
        'password'              => 'OtraClave#9',
        'password_confirmation' => 'OtraClave#9',
    ])->assertStatus(422);

    // Y la contraseña vieja sigue siendo la que estaba.
    expect(Hash::check(CLAVE_DE_ACCESO, $cliente->user->refresh()->password))->toBeTrue();
});

it('no se puede aceptar la invitacion de un cliente dado de baja', function () {
    // El caso del cliente recien cargado al que le dan de baja antes de que
    // active su cuenta: el enlace del correo sigue siendo valido y firmado.
    $cliente = clienteConAcceso(['password' => null, 'email_verified_at' => null]);

    $enlace = URL::temporarySignedRoute(
        'invitacion.store',
        now()->addHours(48),
        ['user' => $cliente->user->id]
    );

    darDeBajaPorGestion($cliente);

    // Lo decide InvitacionController::rechazo(), que ya leia users.activo.
    $this->post($enlace, [
        'password'              => CLAVE_DE_ACCESO,
        'password_confirmation' => CLAVE_DE_ACCESO,
    ])->assertRedirect(route('login'));

    $this->assertGuest();

    expect($cliente->user->refresh()->password)->toBeNull();
});

// ============================================================
// Contraste: los demas roles no cambiaron
// ============================================================

it('el empleado sigue con su propio circuito de baja', function () {
    // La baja del empleado no pasa por el endpoint de clientes: tiene su
    // permiso, su regla del ultimo administrador y su fecha_baja. Lo que se
    // comprueba aca es que sigue cortando el acceso como antes.
    $empleado = User::factory()->empleado('vendedor')->create([
        'email'    => 'empleado@correo.test',
        'password' => CLAVE_DE_ACCESO,
    ]);

    $this->postJson(route('api.v1.auth.login'), [
        'email'    => 'empleado@correo.test',
        'password' => CLAVE_DE_ACCESO,
    ])->assertOk();

    $empleado->darDeBaja();

    $this->postJson(route('api.v1.auth.login'), [
        'email'    => 'empleado@correo.test',
        'password' => CLAVE_DE_ACCESO,
    ])->assertStatus(422);
});

it('dar de baja un cliente no le toca la cuenta a ningun empleado', function () {
    $cliente = clienteConAcceso();

    $empleado = User::factory()->empleado('vendedor')->create();

    $empleado->createToken('Panel', ['gestion']);

    darDeBajaPorGestion($cliente);

    expect($empleado->refresh()->activo)->toBeTrue()
        ->and($empleado->tokens()->count())->toBe(1);
});
