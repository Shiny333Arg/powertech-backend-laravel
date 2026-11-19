<?php

use App\Models\User;
use Database\Seeders\Maestros\CondicionIvaSeeder;
use Database\Seeders\Maestros\PermisoSeeder;
use Database\Seeders\Maestros\RolSeeder;
use Database\Seeders\Maestros\TipoDocumentoSeeder;

/*
|--------------------------------------------------------------------------
| F2 — Autenticacion compartida: login y /auth/yo
|--------------------------------------------------------------------------
|
| El requisito 6 de la consigna pide que Flutter y la administracion usen "los
| mismos endpoints" para iniciar sesion, consultar el usuario y sus permisos, y
| cerrar sesion. Este archivo cubre los dos primeros; el tercero esta en
| tests/Feature/Gestion/CierreDeSesionTest.php.
|
| Todos los tokens se emiten con createToken(): ver la nota de tests/Apoyo/fabricas.php.
|
*/

beforeEach(function () {
    $this->seed([
        RolSeeder::class,
        PermisoSeeder::class,
        TipoDocumentoSeeder::class,
        CondicionIvaSeeder::class,
    ]);
});

// ============================================================
// Login: el ambito y la vigencia que corresponden al rol
// ============================================================

it('emite token de tienda a 30 dias cuando Flutter manda ambito tienda', function () {
    $cliente = clienteDeTienda(['password' => 'Cambiar.Esta.Clave']);

    $respuesta = $this->postJson(route('api.v1.auth.login'), [
        'email'    => $cliente->email,
        'password' => 'Cambiar.Esta.Clave',
        'ambito'   => 'tienda',
    ]);

    $respuesta->assertOk()->assertJsonPath('ambito', 'tienda');

    $token = $cliente->tokens()->sole();

    expect($token->abilities)->toBe(['tienda'])
        ->and($token->expires_at->diffInDays(now()->addDays(30), true))->toBeLessThan(1);
});

it('emite token de gestion a 8 horas cuando el panel no manda ambito', function () {
    $empleado = User::factory()->empleado('administrativo')->create([
        'password' => 'Cambiar.Esta.Clave',
    ]);

    $respuesta = $this->postJson(route('api.v1.auth.login'), [
        'email'    => $empleado->email,
        'password' => 'Cambiar.Esta.Clave',
    ]);

    $respuesta->assertOk()->assertJsonPath('ambito', 'gestion');

    $token = $empleado->tokens()->sole();

    expect($token->abilities)->toBe(['gestion'])
        ->and($token->expires_at->diffInHours(now()->addHours(8), true))->toBeLessThan(1);
});

it('deriva ambito tienda del rol cuando un cliente no lo manda', function () {
    $cliente = clienteDeTienda(['password' => 'Cambiar.Esta.Clave']);

    $this->postJson(route('api.v1.auth.login'), [
        'email'    => $cliente->email,
        'password' => 'Cambiar.Esta.Clave',
    ])->assertOk()->assertJsonPath('ambito', 'tienda');

    expect($cliente->tokens()->sole()->abilities)->toBe(['tienda']);
});

it('rechaza al empleado que pide ambito tienda y no le emite token', function () {
    $empleado = User::factory()->empleado('vendedor')->create([
        'password' => 'Cambiar.Esta.Clave',
    ]);

    $this->postJson(route('api.v1.auth.login'), [
        'email'    => $empleado->email,
        'password' => 'Cambiar.Esta.Clave',
        'ambito'   => 'tienda',
    ])->assertStatus(422)->assertJsonValidationErrors('email');

    expect($empleado->tokens()->count())->toBe(0);
});

it('rechaza al cliente que pide ambito gestion y no le emite token', function () {
    $cliente = clienteDeTienda(['password' => 'Cambiar.Esta.Clave']);

    $this->postJson(route('api.v1.auth.login'), [
        'email'    => $cliente->email,
        'password' => 'Cambiar.Esta.Clave',
        'ambito'   => 'gestion',
    ])->assertStatus(422)->assertJsonValidationErrors('email');

    expect($cliente->tokens()->count())->toBe(0);
});

it('rechaza un ambito que no existe', function () {
    $cliente = clienteDeTienda(['password' => 'Cambiar.Esta.Clave']);

    $this->postJson(route('api.v1.auth.login'), [
        'email'    => $cliente->email,
        'password' => 'Cambiar.Esta.Clave',
        'ambito'   => 'inventado',
    ])->assertStatus(422)->assertJsonValidationErrors('ambito');
});

// La cuenta de baja falla en la consulta de Auth::validate(), que filtra por
// activo, asi que sale 'auth.failed' y no 'auth.inactive'. El mensaje de cuenta
// inactiva aparece despues, al USAR un token ya emitido.
it('rechaza una cuenta de baja en el login sin emitir token', function () {
    $empleado = User::factory()->empleado('cajero')->inactivo()->create([
        'password' => 'Cambiar.Esta.Clave',
    ]);

    $this->postJson(route('api.v1.auth.login'), [
        'email'    => $empleado->email,
        'password' => 'Cambiar.Esta.Clave',
    ])->assertStatus(422)->assertJsonValidationErrors('email');

    expect($empleado->tokens()->count())->toBe(0);
});

// ============================================================
// /auth/yo: un solo endpoint para los dos ambitos
// ============================================================

it('devuelve rol y permisos al token de gestion', function () {
    $empleado = User::factory()->empleado('administrativo')->create();

    $respuesta = $this->withToken(tokenDeGestion($empleado)->plainTextToken)
        ->getJson(route('api.v1.auth.yo'));

    $respuesta->assertOk()
        ->assertJsonPath('data.rol.ambito', 'gestion')
        ->assertJsonStructure(['data' => ['rol' => ['id', 'nombre', 'ambito'], 'permisos']]);

    expect($respuesta->json('data.permisos'))->toBeArray()->not->toBeEmpty();
});

// Antes de F2 estas dos rutas vivian dentro de 'api.ambito:tienda', que las
// volvia inalcanzables para una cuenta de gestion: la llamada que sirve para
// identificar al usuario era la que lo dejaba afuera, y encima revocandole el
// token.
it('ya no rechaza al token de gestion en el endpoint compartido', function () {
    $empleado = User::factory()->empleado('vendedor')->create();
    $token    = tokenDeGestion($empleado);

    $this->withToken($token->plainTextToken)
        ->getJson(route('api.v1.auth.yo'))
        ->assertOk();

    $this->assertDatabaseHas('personal_access_tokens', [
        'id' => $token->accessToken->getKey(),
    ]);
});

it('no devuelve permisos a un token de tienda', function () {
    $cliente = clienteDeTienda();

    $this->withToken(tokenDeTienda($cliente)->plainTextToken)
        ->getJson(route('api.v1.auth.yo'))
        ->assertOk()
        ->assertJsonPath('data.rol.ambito', 'tienda')
        ->assertJsonMissingPath('data.permisos');
});

// Requisito 7: "los cambios de permisos deberan reflejarse en las solicitudes
// posteriores, aunque el usuario conserve un token valido".
it('refleja un permiso quitado en la llamada siguiente con el mismo token', function () {
    $empleado = User::factory()->empleado('administrativo')->create();
    $token    = tokenDeGestion($empleado)->plainTextToken;

    $antes = $this->withToken($token)->getJson(route('api.v1.auth.yo'))->json('data.permisos');

    expect($antes)->not->toBeEmpty();

    $empleado->rol->permisos()->detach();

    // Dos peticiones en un mismo test comparten la instancia de la aplicacion,
    // y el RequestGuard de Sanctum cachea el usuario que ya resolvio —con su
    // relacion rol.permisos ya cargada—. En produccion cada peticion arranca
    // limpia; esto emula ese limite. Sin la linea, el test mediria el cache del
    // guard y no el comportamiento real.
    $this->app['auth']->forgetGuards();

    $despues = $this->withToken($token)->getJson(route('api.v1.auth.yo'))->json('data.permisos');

    expect($despues)->toBe([]);
});

// ============================================================
// 401 y 403: la credencial no sirve vs. no alcanza
// ============================================================

it('rechaza con 403 y SIN revocar un token de gestion en un endpoint de tienda', function () {
    $empleado = User::factory()->empleado('vendedor')->create();
    $token    = tokenDeGestion($empleado);

    $this->withToken($token->plainTextToken)
        ->getJson(route('api.v1.carrito.index'))
        ->assertStatus(403)
        ->assertJsonPath('codigo', 'AMBITO_INCORRECTO');

    // Lo importante del caso: el token sigue sirviendo para el panel.
    $this->assertDatabaseHas('personal_access_tokens', [
        'id' => $token->accessToken->getKey(),
    ]);
});

it('revoca el token cuando la cuenta se dio de baja despues de emitirlo', function () {
    $cliente = clienteDeTienda();
    $token   = tokenDeTienda($cliente);

    $cliente->update(['activo' => false]);

    $this->withToken($token->plainTextToken)
        ->getJson(route('api.v1.auth.yo'))
        ->assertStatus(401)
        ->assertJsonPath('codigo', 'CUENTA_INACTIVA');

    $this->assertDatabaseMissing('personal_access_tokens', [
        'id' => $token->accessToken->getKey(),
    ]);
});

it('rechaza un token vencido aunque el prune no haya corrido', function () {
    $cliente = clienteDeTienda();
    $token   = tokenDeTienda($cliente, now()->subMinute());

    $this->withToken($token->plainTextToken)
        ->getJson(route('api.v1.auth.yo'))
        ->assertStatus(401);

    // La fila sigue ahi: la rechaza la validacion de vigencia, no la limpieza.
    $this->assertDatabaseHas('personal_access_tokens', [
        'id' => $token->accessToken->getKey(),
    ]);
});

it('rechaza un token inventado', function () {
    $this->withToken('1|estonoesuntoken')
        ->getJson(route('api.v1.auth.yo'))
        ->assertStatus(401);
});

// ============================================================
// guard => [] : la API es Bearer-only
// ============================================================

// Es la prueba de regresion de config/sanctum.php y la unica que demuestra que
// 'guard' => [] sirve para algo. Con el default del paquete (['web']) este
// request autenticaria por sesion, Sanctum adjuntaria un TransientToken y
// VerificarAmbitoApi terminaba llamando delete() sobre una clase que no lo
// implementa: 500 en lugar de 401.
it('no autentica la API por sesion web', function () {
    $empleado = User::factory()->empleado('administrativo')->create();

    $this->actingAs($empleado)
        ->getJson(route('api.v1.auth.yo'))
        ->assertStatus(401);
});

// ============================================================
// Limpieza de tokens vencidos
// ============================================================

it('el prune borra lo vencido hace mas de 24 horas y respeta el resto', function () {
    $cliente  = clienteDeTienda();
    $empleado = User::factory()->empleado('vendedor')->create();

    $viejo    = tokenDeTienda($cliente, now()->subHours(48));
    $reciente = tokenDeTienda($cliente, now()->subHour());
    $vigente  = tokenDeTienda($cliente);
    $gestion  = tokenDeGestion($empleado);

    $this->artisan('sanctum:prune-expired --hours=24')->assertSuccessful();

    $this->assertDatabaseMissing('personal_access_tokens', ['id' => $viejo->accessToken->getKey()]);

    // Vencido hace una hora: todavia no entra en la ventana de retencion.
    $this->assertDatabaseHas('personal_access_tokens', ['id' => $reciente->accessToken->getKey()]);

    // Y lo vigente no se toca, ni de tienda ni de gestion.
    $this->assertDatabaseHas('personal_access_tokens', ['id' => $vigente->accessToken->getKey()]);
    $this->assertDatabaseHas('personal_access_tokens', ['id' => $gestion->accessToken->getKey()]);
});
