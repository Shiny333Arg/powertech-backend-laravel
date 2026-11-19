<?php

use App\Actions\RevocarTokenDeAcceso;
use App\Models\User;
use App\Support\SesionGestion;
use Database\Seeders\Maestros\CondicionIvaSeeder;
use Database\Seeders\Maestros\PermisoSeeder;
use Database\Seeders\Maestros\RolSeeder;
use Database\Seeders\Maestros\TipoDocumentoSeeder;

/*
|--------------------------------------------------------------------------
| F2 — Cierre de sesion: UN endpoint, dos modos
|--------------------------------------------------------------------------
|
| El requisito 6 de la consigna pide que Flutter y la administracion cierren
| sesion por "los mismos endpoints". POST /api/v1/auth/logout es el unico
| endpoint de revocacion del sistema y atiende a los dos:
|
|   con Authorization: Bearer   Flutter. Revoca el token del request.
|   sin Authorization           el panel. Se autentica por la cookie de sesion,
|                               revoca el token que guardo la sesion Y destruye
|                               la sesion, en la misma operacion.
|
| Lo que este archivo tiene que demostrar, ademas de que funciona:
|
|   · que NO quedo una via alternativa de revocacion (POST /logout no borra
|     tokens);
|   · que un Bearer invalido no cae en la sesion web;
|   · que si la revocacion falla, la sesion NO se destruye;
|   · que si el token ya estaba revocado, el cierre es idempotente.
|
*/

beforeEach(function () {
    // Convencion del proyecto: solo AssetsGestionTest usa el manifest real.
    $this->withoutVite();

    $this->seed([
        RolSeeder::class,
        PermisoSeeder::class,
        TipoDocumentoSeeder::class,
        CondicionIvaSeeder::class,
    ]);
});

function empleado(string $rol = 'administrativo'): User
{
    return User::factory()->empleado($rol)->create();
}

// ============================================================
// Modo Bearer (Flutter)
// ============================================================

it('revoca el token del request y no toca la sesion', function () {
    $cliente = clienteDeTienda();
    $token   = tokenDeTienda($cliente);

    $this->withToken($token->plainTextToken)
        ->postJson(route('api.v1.auth.logout'))
        ->assertOk();

    $this->assertDatabaseMissing('personal_access_tokens', [
        'id' => $token->accessToken->getKey(),
    ]);
});

it('atiende tambien a un token de gestion por el mismo endpoint', function () {
    $usuario = empleado();
    $token   = tokenDeGestion($usuario);

    $this->withToken($token->plainTextToken)
        ->postJson(route('api.v1.auth.logout'))
        ->assertOk();

    $this->assertDatabaseMissing('personal_access_tokens', [
        'id' => $token->accessToken->getKey(),
    ]);
});

it('revoca solo el token del dispositivo que cierra', function () {
    $cliente = clienteDeTienda();
    $este    = tokenDeTienda($cliente);
    $otro    = tokenDeTienda($cliente);

    $this->withToken($este->plainTextToken)
        ->postJson(route('api.v1.auth.logout'))
        ->assertOk();

    $this->assertDatabaseMissing('personal_access_tokens', ['id' => $este->accessToken->getKey()]);
    $this->assertDatabaseHas('personal_access_tokens', ['id' => $otro->accessToken->getKey()]);
});

// ============================================================
// Modo cookie (panel): revoca Y destruye la sesion
// ============================================================

it('revoca el token de la sesion y cierra la sesion en la misma operacion', function () {
    $usuario = empleado();
    $token   = tokenDeGestion($usuario);

    $this->actingAs($usuario)
        ->withSession(sesionDeGestion($token))
        ->postJson(route('api.v1.auth.logout'))
        ->assertOk()
        ->assertJsonPath('redirigir_a', route('gestion.login'));

    $this->assertGuest();

    $this->assertDatabaseMissing('personal_access_tokens', [
        'id' => $token->accessToken->getKey(),
    ]);
});

it('limpia las claves del token de la sesion', function () {
    $usuario = empleado();

    $this->actingAs($usuario)
        ->withSession(sesionDeGestion(tokenDeGestion($usuario)))
        ->postJson(route('api.v1.auth.logout'))
        ->assertOk();

    expect(session(SesionGestion::TOKEN))->toBeNull()
        ->and(session(SesionGestion::TOKEN_ID))->toBeNull()
        ->and(session(SesionGestion::EXPIRA_EL))->toBeNull();
});

// Idempotencia: que la fila ya no estuviera no es un fallo. Pasa cuando el
// empleado hace doble clic, cuando lo revoco otro dispositivo o cuando lo
// levanto el prune.
it('cierra la sesion aunque el token ya estuviera revocado', function () {
    $usuario = empleado();
    $token   = tokenDeGestion($usuario);
    $sesion  = sesionDeGestion($token);

    $token->accessToken->delete();

    $this->actingAs($usuario)
        ->withSession($sesion)
        ->postJson(route('api.v1.auth.logout'))
        ->assertOk();

    $this->assertGuest();
});

it('cierra la sesion de una cuenta dada de baja', function () {
    $usuario = empleado();
    $token   = tokenDeGestion($usuario);
    $sesion  = sesionDeGestion($token);

    $usuario->update(['activo' => false]);

    // La ruta no lleva 'api.activo' a proposito: a quien acaban de desactivar
    // hay que dejarlo cerrar su sesion.
    $this->actingAs($usuario)
        ->withSession($sesion)
        ->postJson(route('api.v1.auth.logout'))
        ->assertOk();

    $this->assertGuest();

    $this->assertDatabaseMissing('personal_access_tokens', [
        'id' => $token->accessToken->getKey(),
    ]);
});

it('revoca solo el token de esta sesion y deja los otros del empleado', function () {
    $usuario = empleado();
    $deEsta  = tokenDeGestion($usuario);
    $deOtra  = tokenDeGestion($usuario);

    $this->actingAs($usuario)
        ->withSession(sesionDeGestion($deEsta))
        ->postJson(route('api.v1.auth.logout'))
        ->assertOk();

    $this->assertDatabaseMissing('personal_access_tokens', ['id' => $deEsta->accessToken->getKey()]);
    $this->assertDatabaseHas('personal_access_tokens', ['id' => $deOtra->accessToken->getKey()]);
});

// La accion arranca de $user->tokens(), asi que un id ajeno no borra nada. Es
// lo que evita que una sesion manipulada le quite la credencial a un tercero.
it('no revoca el token de otro usuario aunque la sesion lo referencie', function () {
    $usuario = empleado();
    $ajeno   = tokenDeGestion(empleado('vendedor'));

    $this->actingAs($usuario)
        ->withSession([SesionGestion::TOKEN_ID => $ajeno->accessToken->getKey()])
        ->postJson(route('api.v1.auth.logout'))
        ->assertOk();

    $this->assertDatabaseHas('personal_access_tokens', ['id' => $ajeno->accessToken->getKey()]);
});

// ============================================================
// Garantias de la decision de diseño
// ============================================================

// Si la revocacion falla, la excepcion corta ANTES del logout: el empleado
// queda adentro y puede reintentar, en lugar de quedarse afuera con el token
// vivo. Es el orden de las operaciones lo que da la garantia.
it('no destruye la sesion si la revocacion falla', function () {
    $usuario = empleado();
    $token   = tokenDeGestion($usuario);

    $this->instance(RevocarTokenDeAcceso::class, new class extends RevocarTokenDeAcceso
    {
        public function __invoke(User $user, int|string $tokenId): bool
        {
            throw new RuntimeException('la base no responde');
        }
    });

    $this->withoutExceptionHandling()
        ->actingAs($usuario)
        ->withSession(sesionDeGestion($token))
        ->postJson(route('api.v1.auth.logout'));

})->throws(RuntimeException::class);

it('deja la sesion abierta cuando la revocacion lanza', function () {
    $usuario = empleado();

    $this->instance(RevocarTokenDeAcceso::class, new class extends RevocarTokenDeAcceso
    {
        public function __invoke(User $user, int|string $tokenId): bool
        {
            throw new RuntimeException('la base no responde');
        }
    });

    $this->actingAs($usuario)
        ->withSession(sesionDeGestion(tokenDeGestion($usuario)))
        ->postJson(route('api.v1.auth.logout'))
        ->assertStatus(500);

    // Lo que importa: NO quedo deslogueado con el token vivo.
    $this->assertAuthenticatedAs($usuario);
});

// Un Bearer invalido se resuelve con el guard 'sanctum' y nada mas. Si cayera
// en la cookie, un token vencido terminaria cerrando la sesion del navegador
// por la puerta de atras.
it('un Bearer invalido no cae en la sesion web', function () {
    $usuario = empleado();

    $this->actingAs($usuario)
        ->withSession(sesionDeGestion(tokenDeGestion($usuario)))
        ->withToken('1|estonoesuntoken')
        ->postJson(route('api.v1.auth.logout'))
        ->assertStatus(401)
        ->assertJsonPath('codigo', 'TOKEN_AUSENTE');

    $this->assertAuthenticatedAs($usuario);
});

it('un Bearer vencido tampoco cae en la sesion web', function () {
    $usuario = empleado();
    $vencido = tokenDeGestion($usuario, now()->subMinute());

    $this->actingAs($usuario)
        ->withSession(sesionDeGestion(tokenDeGestion($usuario)))
        ->withToken($vencido->plainTextToken)
        ->postJson(route('api.v1.auth.logout'))
        ->assertStatus(401);

    $this->assertAuthenticatedAs($usuario);
});

it('rechaza el cierre sin credencial de ningun tipo', function () {
    $this->postJson(route('api.v1.auth.logout'))
        ->assertStatus(401)
        ->assertJsonPath('codigo', 'TOKEN_AUSENTE');
});

// ============================================================
// POST /logout (web): NO es una via de revocacion
// ============================================================

// Esta es la prueba de que no quedo un mecanismo alternativo. Si alguna vez
// alguien vuelve a poner la revocacion aca, este test falla.
it('el logout web no borra ningun token', function () {
    $usuario = empleado();
    $token   = tokenDeGestion($usuario);

    $this->actingAs($usuario)
        ->withSession(sesionDeGestion($token))
        ->post(route('logout'))
        ->assertRedirect(route('gestion.login'));

    $this->assertGuest();

    $this->assertDatabaseHas('personal_access_tokens', [
        'id' => $token->accessToken->getKey(),
    ]);
});

it('el logout web manda al cliente a su propio ingreso', function () {
    $cliente = clienteDeTienda();

    $this->actingAs($cliente)
        ->post(route('logout'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

// El storefront se autentica por sesion y no tiene token que revocar: su
// circuito no cambio en nada.
it('el logout de un cliente no toca los tokens de Flutter', function () {
    $cliente = clienteDeTienda();
    $delApp  = tokenDeTienda($cliente);

    $this->actingAs($cliente)
        ->post(route('logout'))
        ->assertRedirect(route('login'));

    $this->assertDatabaseHas('personal_access_tokens', [
        'id' => $delApp->accessToken->getKey(),
    ]);
});
