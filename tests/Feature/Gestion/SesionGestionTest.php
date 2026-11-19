<?php

use App\Models\User;
use App\Support\SesionGestion;
use Database\Seeders\Maestros\CondicionIvaSeeder;
use Database\Seeders\Maestros\PermisoSeeder;
use Database\Seeders\Maestros\RolSeeder;
use Database\Seeders\Maestros\TipoDocumentoSeeder;

/*
|--------------------------------------------------------------------------
| F2 — Intercambio de token por sesion y coherencia sesion ↔ token
|--------------------------------------------------------------------------
|
| Dos piezas que van juntas:
|
|   POST /gestion/sesion   cambia un token de gestion por una sesion Blade,
|                          para que sobreviva a las recargas completas de pagina
|                          que impone la navegacion tradicional.
|
|   gestion.token          en CADA request a /gestion/*, comprueba que la sesion
|                          y el token sigan siendo coherentes.
|
| Los tokens se emiten con createToken(): ver la nota de tests/Apoyo/fabricas.php.
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

function empleadoDeGestion(string $rol = 'administrativo', array $atributos = []): User
{
    return User::factory()->empleado($rol)->create($atributos);
}

// ============================================================
// Las dos puertas de entrada
// ============================================================

it('sirve el ingreso del panel a un invitado', function () {
    $this->get(route('gestion.login'))->assertOk()->assertSee('Panel de gestión');
});

it('manda al invitado que pide una URL de gestion al ingreso del panel', function () {
    $this->get('/gestion/item')->assertRedirect(route('gestion.login'));
});

it('manda al invitado que pide una URL de tienda al ingreso del storefront', function () {
    $this->get(route('cliente.compras.index'))->assertRedirect(route('login'));
});

// El storefront no emite tokens y sigue siendo un POST tradicional: ese
// circuito la Etapa 3 no pide tocarlo.
it('el login del storefront no emite ningun token', function () {
    $cliente = clienteDeTienda(['password' => 'Cambiar.Esta.Clave']);

    $this->post(route('login'), [
        'email'    => $cliente->email,
        'password' => 'Cambiar.Esta.Clave',
    ])->assertRedirect(route('cliente.home'));

    $this->assertAuthenticatedAs($cliente);

    expect($cliente->tokens()->count())->toBe(0);
});

// Un empleado por la puerta equivocada no entra al panel sin token: se lo
// deriva. Si se le abriera sesion, el middleware de coherencia lo sacaria en el
// primer request de todos modos.
it('deriva al empleado que entra por el login del storefront', function () {
    $empleado = empleadoDeGestion('vendedor', ['password' => 'Cambiar.Esta.Clave']);

    $this->post(route('login'), [
        'email'    => $empleado->email,
        'password' => 'Cambiar.Esta.Clave',
    ])->assertRedirect(route('gestion.login'));

    $this->assertGuest();

    expect($empleado->tokens()->count())->toBe(0);
});

// ============================================================
// POST /gestion/sesion — el intercambio
// ============================================================

it('abre la sesion del panel con un token de gestion valido', function () {
    $empleado = empleadoDeGestion();
    $token    = tokenDeGestion($empleado);

    $respuesta = $this->withToken($token->plainTextToken)
        ->postJson(route('gestion.sesion'));

    $respuesta->assertOk()->assertJsonPath('redirigir_a', route('gestion.home'));

    $this->assertAuthenticatedAs($empleado);

    expect(session(SesionGestion::TOKEN_ID))->toBe($token->accessToken->getKey())
        ->and(session(SesionGestion::TOKEN))->toBe($token->plainTextToken);
});

it('rota el identificador de sesion al intercambiar', function () {
    $empleado = empleadoDeGestion();

    $this->get(route('gestion.login'));
    $anterior = session()->getId();

    $this->withToken(tokenDeGestion($empleado)->plainTextToken)
        ->postJson(route('gestion.sesion'))
        ->assertOk();

    expect(session()->getId())->not->toBe($anterior);
});

it('respeta la pagina que el empleado quiso abrir', function () {
    $empleado = empleadoDeGestion();

    $this->withSession(['url.intended' => url('/gestion/item')])
        ->withToken(tokenDeGestion($empleado)->plainTextToken)
        ->postJson(route('gestion.sesion'))
        ->assertOk()
        ->assertJsonPath('redirigir_a', url('/gestion/item'));
});

it('no abre sesion sin token', function () {
    $this->postJson(route('gestion.sesion'))
        ->assertStatus(401)
        ->assertJsonPath('codigo', 'TOKEN_AUSENTE');

    $this->assertGuest();
});

it('no abre sesion con un token vencido y no lo revoca', function () {
    $empleado = empleadoDeGestion();
    $token    = tokenDeGestion($empleado, now()->subMinute());

    $this->withToken($token->plainTextToken)
        ->postJson(route('gestion.sesion'))
        ->assertStatus(401)
        ->assertJsonPath('codigo', 'TOKEN_INVALIDO');

    $this->assertGuest();

    // 401 por vigencia no revoca: la credencial ya no vale por si sola.
    $this->assertDatabaseHas('personal_access_tokens', [
        'id' => $token->accessToken->getKey(),
    ]);
});

it('no abre sesion con un token de tienda y lo deja intacto', function () {
    $cliente = clienteDeTienda();
    $token   = tokenDeTienda($cliente);

    $this->withToken($token->plainTextToken)
        ->postJson(route('gestion.sesion'))
        ->assertStatus(403)
        ->assertJsonPath('codigo', 'AMBITO_INCORRECTO');

    $this->assertGuest();

    $this->assertDatabaseHas('personal_access_tokens', [
        'id' => $token->accessToken->getKey(),
    ]);
});

it('revoca el token de una cuenta dada de baja', function () {
    $empleado = empleadoDeGestion();
    $token    = tokenDeGestion($empleado);

    $empleado->update(['activo' => false]);

    $this->withToken($token->plainTextToken)
        ->postJson(route('gestion.sesion'))
        ->assertStatus(401)
        ->assertJsonPath('codigo', 'CUENTA_INACTIVA');

    $this->assertGuest();

    // Es el unico rechazo del intercambio que revoca.
    $this->assertDatabaseMissing('personal_access_tokens', [
        'id' => $token->accessToken->getKey(),
    ]);
});

// La ruta recibe un token y NADA mas: las credenciales viajan a un solo lugar
// en todo el sistema, que es el endpoint compartido de la API.
it('no acepta credenciales en lugar de un token', function () {
    $empleado = empleadoDeGestion(atributos: ['password' => 'Cambiar.Esta.Clave']);

    $this->postJson(route('gestion.sesion'), [
        'email'    => $empleado->email,
        'password' => 'Cambiar.Esta.Clave',
    ])->assertStatus(401);

    $this->assertGuest();
});

it('exige CSRF en el intercambio', function () {
    $empleado = empleadoDeGestion();

    $this->withMiddleware()
        ->withToken(tokenDeGestion($empleado)->plainTextToken)
        ->post(route('gestion.sesion'))
        ->assertStatus(419);

    $this->assertGuest();
})->skip(fn () => true, 'El TestCase desactiva el middleware de CSRF; se verifica en navegador.');

// ============================================================
// Revocacion del token superado dentro de la misma sesion
// ============================================================

it('revoca el token anterior de esta misma sesion al reemplazarlo', function () {
    $empleado = empleadoDeGestion();
    $viejo    = tokenDeGestion($empleado);
    $nuevo    = tokenDeGestion($empleado);

    $this->withSession(sesionDeGestion($viejo))
        ->withToken($nuevo->plainTextToken)
        ->postJson(route('gestion.sesion'))
        ->assertOk();

    $this->assertDatabaseMissing('personal_access_tokens', ['id' => $viejo->accessToken->getKey()]);
    $this->assertDatabaseHas('personal_access_tokens', ['id' => $nuevo->accessToken->getKey()]);
});

// La consigna aclara que usar el mismo mecanismo "no significa compartir un
// unico token entre dispositivos o interfaces": entrar desde el mostrador no
// tiene por que echar al mismo empleado de la computadora del deposito.
it('no toca los tokens de otros dispositivos del mismo empleado', function () {
    $empleado = empleadoDeGestion();
    $otro     = tokenDeGestion($empleado);
    $nuevo    = tokenDeGestion($empleado);

    $this->withToken($nuevo->plainTextToken)
        ->postJson(route('gestion.sesion'))
        ->assertOk();

    $this->assertDatabaseHas('personal_access_tokens', ['id' => $otro->accessToken->getKey()]);
});

// Si A entra sobre la sesion abierta de B, el token referenciado es de B:
// revocarlo lo echaria de su propia sesion en otra maquina.
it('no revoca el token de otro usuario al reemplazar la sesion', function () {
    $empleadoA = empleadoDeGestion();
    $empleadoB = empleadoDeGestion('vendedor');

    $deB = tokenDeGestion($empleadoB);
    $deA = tokenDeGestion($empleadoA);

    $this->withSession(sesionDeGestion($deB))
        ->withToken($deA->plainTextToken)
        ->postJson(route('gestion.sesion'))
        ->assertOk();

    $this->assertDatabaseHas('personal_access_tokens', ['id' => $deB->accessToken->getKey()]);
    $this->assertAuthenticatedAs($empleadoA);
});

it('no falla si el token anterior ya no existe', function () {
    $empleado = empleadoDeGestion();
    $viejo    = tokenDeGestion($empleado);
    $sesion   = sesionDeGestion($viejo);

    $viejo->accessToken->delete();

    $this->withSession($sesion)
        ->withToken(tokenDeGestion($empleado)->plainTextToken)
        ->postJson(route('gestion.sesion'))
        ->assertOk();
});

// ============================================================
// gestion.token — coherencia en cada request
// ============================================================

it('deja pasar con sesion y token validos', function () {
    $empleado = empleadoDeGestion();

    $this->actingAs($empleado)
        ->withSession(sesionDeGestion(tokenDeGestion($empleado)))
        ->get(route('gestion.home'))
        ->assertOk();
});

it('corta la sesion cuando el token vencio', function () {
    $empleado = empleadoDeGestion();

    $this->actingAs($empleado)
        ->withSession(sesionDeGestion(tokenDeGestion($empleado, now()->subMinute())))
        ->get(route('gestion.home'))
        ->assertRedirect(route('gestion.login'));

    $this->assertGuest();
});

it('corta la sesion cuando el token fue revocado desde otro proceso', function () {
    $empleado = empleadoDeGestion();
    $token    = tokenDeGestion($empleado);
    $sesion   = sesionDeGestion($token);

    $token->accessToken->delete();

    $this->actingAs($empleado)
        ->withSession($sesion)
        ->get(route('gestion.home'))
        ->assertRedirect(route('gestion.login'));

    $this->assertGuest();
});

it('corta la sesion de una cuenta dada de baja', function () {
    $empleado = empleadoDeGestion();
    $sesion   = sesionDeGestion(tokenDeGestion($empleado));

    $empleado->update(['activo' => false]);

    $this->actingAs($empleado)
        ->withSession($sesion)
        ->get(route('gestion.home'))
        ->assertRedirect();

    $this->assertGuest();
});

it('corta la sesion cuyo token es de otro usuario y NO lo revoca', function () {
    $empleado = empleadoDeGestion();
    $ajeno    = tokenDeGestion(empleadoDeGestion('vendedor'));

    $this->actingAs($empleado)
        ->withSession(sesionDeGestion($ajeno))
        ->get(route('gestion.home'))
        ->assertRedirect(route('gestion.login'));

    $this->assertGuest();

    $this->assertDatabaseHas('personal_access_tokens', ['id' => $ajeno->accessToken->getKey()]);
});

it('corta la sesion cuyo token tiene ability de tienda', function () {
    $empleado = empleadoDeGestion();
    $token    = $empleado->createToken('Mal emitido', [config('movil.ability')], now()->addHour());

    $this->actingAs($empleado)
        ->withSession(sesionDeGestion($token))
        ->get(route('gestion.home'))
        ->assertRedirect(route('gestion.login'));

    $this->assertGuest();
});

// Cubre tambien cualquier sesion abierta antes de F2: no tienen la clave.
it('corta una sesion sin token asociado', function () {
    $empleado = empleadoDeGestion();

    $this->actingAs($empleado)
        ->get(route('gestion.home'))
        ->assertRedirect(route('gestion.login'));

    $this->assertGuest();
});

// Escenario 7 del analisis: navegacion tradicional, sin AJAX previo. Queda
// cubierto por construccion porque el middleware corre en TODO request del
// grupo, GET incluido.
it('corta una navegacion tradicional con token vencido', function () {
    $empleado = empleadoDeGestion();

    $this->actingAs($empleado)
        ->withSession(sesionDeGestion(tokenDeGestion($empleado, now()->subHour())))
        ->get('/gestion/item')
        ->assertRedirect(route('gestion.login'));
});

// Si esto contestara 302, el autocompletado de ventas y de promociones —que ya
// esta en produccion y manda Accept: application/json— seguiria la redireccion
// y trataria de parsear el HTML del login: se romperia en silencio.
it('responde 401 JSON al AJAX del panel en lugar de redirigir', function () {
    $empleado = empleadoDeGestion();

    $this->actingAs($empleado)
        ->withSession(sesionDeGestion(tokenDeGestion($empleado, now()->subMinute())))
        ->getJson(route('gestion.buscar.productos', ['q' => 'monitor']))
        ->assertStatus(401)
        ->assertJsonPath('codigo', 'SESION_GESTION_INVALIDA');
});

it('no entra en bucle: el ingreso del panel sigue respondiendo tras cortar', function () {
    $empleado = empleadoDeGestion();

    $this->actingAs($empleado)
        ->withSession(sesionDeGestion(tokenDeGestion($empleado, now()->subMinute())))
        ->get(route('gestion.home'))
        ->assertRedirect(route('gestion.login'));

    $this->get(route('gestion.login'))->assertOk();
});

// ============================================================
// El token en la cascara
// ============================================================

it('inyecta el token en la pagina del panel', function () {
    $empleado = empleadoDeGestion();
    $token    = tokenDeGestion($empleado);

    $respuesta = $this->actingAs($empleado)
        ->withSession(sesionDeGestion($token))
        ->get(route('gestion.home'));

    $respuesta->assertOk()->assertSee($token->plainTextToken, false);
});

it('no escribe el token en localStorage, sessionStorage ni una cookie propia', function () {
    $empleado = empleadoDeGestion();
    $token    = tokenDeGestion($empleado);

    $respuesta = $this->actingAs($empleado)
        ->withSession(sesionDeGestion($token))
        ->get(route('gestion.home'));

    $html = $respuesta->getContent();

    expect($html)->not->toContain('localStorage')
        ->and($html)->not->toContain('sessionStorage');

    foreach ($respuesta->headers->getCookies() as $cookie) {
        expect($cookie->getValue())->not->toContain($token->plainTextToken);
    }
});
