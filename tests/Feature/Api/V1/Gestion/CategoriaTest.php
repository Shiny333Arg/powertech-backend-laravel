<?php

use App\Models\Categoria;
use App\Models\Permiso;
use App\Models\User;
use Database\Seeders\Maestros\AlicuotaIvaSeeder;
use Database\Seeders\Maestros\PermisoSeeder;
use Database\Seeders\Maestros\RolSeeder;
use Laravel\Sanctum\Sanctum;

/*
|--------------------------------------------------------------------------
| F3/F4 — API de gestion: categorias
|--------------------------------------------------------------------------
|
| Cubre los seis endpoints y las tres capas que los protegen: el token (401),
| el ambito y la ability (403), y el permiso del rol (403). Las dos ultimas se
| prueban por separado a proposito: son preguntas distintas y fallan por
| motivos distintos.
|
| <b>Se usa Sanctum::actingAs y no createToken().</b> Aca no se afirma nada
| sobre revocacion ni vigencia —de eso se ocupa AutenticacionCompartidaTest—,
| asi que el doble alcanza y las pruebas corren mas rapido. La nota de
| tests/Apoyo/fabricas.php explica cuando NO alcanza.
|
*/

beforeEach(function () {
    $this->seed([
        RolSeeder::class,
        PermisoSeeder::class,
        AlicuotaIvaSeeder::class,
    ]);
});

/** Rol con todos los permisos del catalogo: ver, crear, editar, baja y eliminar. */
function administrativoDeCatalogo(): User
{
    return User::factory()->empleado('administrativo')->create();
}

// ============================================================
// Las tres capas de acceso
// ============================================================

it('rechaza la consulta sin token', function () {
    $this->getJson(route('api.v1.gestion.categorias.index'))
        ->assertUnauthorized();
});

it('rechaza un token de un cliente de la tienda', function () {
    // El usuario es de otro ambito: lo frena 'api.ambito:gestion', que mira
    // quien es el usuario AHORA.
    Sanctum::actingAs(User::factory()->cliente()->create(), ['tienda']);

    $this->getJson(route('api.v1.gestion.categorias.index'))
        ->assertForbidden()
        ->assertJsonPath('codigo', 'AMBITO_INCORRECTO');
});

it('rechaza un token de empleado emitido para la tienda', function () {
    // El usuario es correcto y el token no: lo frena 'abilities:gestion', que
    // mira PARA QUE se emitio. Es el caso que justifica las dos capas.
    Sanctum::actingAs(administrativoDeCatalogo(), ['tienda']);

    $this->getJson(route('api.v1.gestion.categorias.index'))
        ->assertForbidden()
        ->assertJsonPath('codigo', 'ABILITY_INSUFICIENTE');
});

// ============================================================
// Listado
// ============================================================

it('lista las categorias con el conteo de productos', function () {
    Sanctum::actingAs(administrativoDeCatalogo(), ['gestion']);

    $conProductos = categoriaDePrueba();              // 'Monitores', orden 1
    productoDePrueba(['categoria_id' => $conProductos->id]);

    Categoria::create(['nombre' => 'Teclados', 'slug' => 'teclados', 'orden' => 2, 'activo' => true]);

    $respuesta = $this->getJson(route('api.v1.gestion.categorias.index'));

    $respuesta->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.nombre', 'Monitores')
        ->assertJsonPath('data.0.productos_count', 1)
        ->assertJsonPath('data.1.productos_count', 0)
        ->assertJsonPath('meta.total', 2)
        ->assertJsonPath('filtros.estado', 'todas');
});

it('muestra las inactivas y filtra por estado y por nombre', function () {
    Sanctum::actingAs(administrativoDeCatalogo(), ['gestion']);

    categoriaDePrueba();
    Categoria::create(['nombre' => 'Teclados', 'slug' => 'teclados', 'orden' => 2, 'activo' => false]);

    // Por defecto vienen las dos: es la pantalla desde donde se reactiva.
    $this->getJson(route('api.v1.gestion.categorias.index'))
        ->assertJsonCount(2, 'data');

    $this->getJson(route('api.v1.gestion.categorias.index', ['estado' => 'inactivas']))
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.nombre', 'Teclados');

    $this->getJson(route('api.v1.gestion.categorias.index', ['nombre' => 'monit']))
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.nombre', 'Monitores');
});

it('rechaza un estado que no existe', function () {
    Sanctum::actingAs(administrativoDeCatalogo(), ['gestion']);

    $this->getJson(route('api.v1.gestion.categorias.index', ['estado' => 'cualquiera']))
        ->assertStatus(422)
        ->assertJsonValidationErrors('estado');
});

it('devuelve el detalle de una categoria', function () {
    Sanctum::actingAs(administrativoDeCatalogo(), ['gestion']);

    $categoria = categoriaDePrueba();

    $this->getJson(route('api.v1.gestion.categorias.show', $categoria))
        ->assertOk()
        ->assertJsonPath('data.id', $categoria->id)
        ->assertJsonPath('data.slug', 'monitor')
        ->assertJsonPath('data.activo', true);
});

// ============================================================
// Permisos del rol
// ============================================================

it('deja mirar al vendedor pero no crear', function () {
    // El vendedor tiene 'categoria.ver' y nada mas: es la operacion permitida
    // y la rechazada que pide la demostracion de la consigna.
    Sanctum::actingAs(User::factory()->empleado('vendedor')->create(), ['gestion']);

    $this->getJson(route('api.v1.gestion.categorias.index'))->assertOk();

    $this->postJson(route('api.v1.gestion.categorias.store'), [
        'nombre' => 'Notebooks',
    ])
        ->assertForbidden()
        // En castellano y con codigo propio: el panel muestra este mensaje tal
        // cual. Sin la rama del render seria "This action is unauthorized.".
        ->assertJsonPath('codigo', 'PERMISO_INSUFICIENTE');

    expect(Categoria::count())->toBe(0);
});

it('refleja el permiso quitado al rol con el mismo token', function () {
    // <b>Token real y withToken(), no Sanctum::actingAs.</b> El doble deja
    // clavada la MISMA instancia de User entre las dos llamadas, con su
    // relacion de permisos ya cargada: el detach no se veria y la prueba
    // pasaria sin probar nada. Con un token de verdad, cada request resuelve
    // el usuario desde la base, que es lo que hace el sistema real.
    $empleado = administrativoDeCatalogo();

    $token = tokenDeGestion($empleado)->plainTextToken;

    $this->withToken($token)
        ->postJson(route('api.v1.gestion.categorias.store'), ['nombre' => 'Notebooks'])
        ->assertCreated();

    // Sin reemitir el token: 'tienePermiso()' consulta la base en cada request.
    $empleado->rol->permisos()->detach(
        Permiso::where('clave', 'categoria.crear')->value('id')
    );

    // En produccion cada request resuelve el usuario de cero; en un test las
    // dos llamadas comparten el mismo proceso y el guard se queda con el
    // usuario de la primera, con sus permisos ya cargados. forgetGuards() es
    // lo que reproduce el request siguiente de verdad.
    $this->app['auth']->forgetGuards();

    $this->withToken($token)
        ->postJson(route('api.v1.gestion.categorias.store'), ['nombre' => 'Impresoras'])
        ->assertForbidden()
        ->assertJsonPath('codigo', 'PERMISO_INSUFICIENTE');
});

// ============================================================
// Alta y edicion
// ============================================================

it('crea una categoria y le deriva el slug del nombre', function () {
    Sanctum::actingAs(administrativoDeCatalogo(), ['gestion']);

    $this->postJson(route('api.v1.gestion.categorias.store'), [
        'nombre' => 'Placas de video',
        'orden'  => 3,
    ])
        ->assertCreated()
        ->assertJsonPath('data.slug', 'placas-de-video')
        ->assertJsonPath('data.orden', 3)
        ->assertJsonPath('data.activo', true)
        ->assertJsonPath('data.productos_count', 0);

    $this->assertDatabaseHas('categorias', [
        'nombre' => 'Placas de video',
        'slug'   => 'placas-de-video',
        'activo' => true,
    ]);
});

it('rechaza un nombre repetido', function () {
    Sanctum::actingAs(administrativoDeCatalogo(), ['gestion']);

    categoriaDePrueba();

    $this->postJson(route('api.v1.gestion.categorias.store'), ['nombre' => 'Monitores'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('nombre');
});

it('edita una categoria con PUT', function () {
    Sanctum::actingAs(administrativoDeCatalogo(), ['gestion']);

    $categoria = categoriaDePrueba();

    $this->putJson(route('api.v1.gestion.categorias.update', $categoria), [
        'nombre' => 'Monitores y TV',
        'orden'  => 5,
    ])
        ->assertOk()
        ->assertJsonPath('data.nombre', 'Monitores y TV')
        ->assertJsonPath('data.slug', 'monitores-y-tv')
        ->assertJsonPath('data.orden', 5);

    expect($categoria->refresh()->nombre)->toBe('Monitores y TV');
});

it('no cambia el estado desde el PUT', function () {
    // El estado tiene su endpoint y su permiso: un 'activo' de mas en el
    // cuerpo de la edicion no puede reactivar una categoria dada de baja.
    Sanctum::actingAs(administrativoDeCatalogo(), ['gestion']);

    $categoria = categoriaDePrueba();
    $categoria->darDeBaja();

    $this->putJson(route('api.v1.gestion.categorias.update', $categoria), [
        'nombre' => 'Monitores',
        'activo' => true,
    ])->assertOk();

    expect($categoria->refresh()->activo)->toBeFalse();
});

// ============================================================
// Estado
// ============================================================

it('da de baja y reactiva una categoria', function () {
    Sanctum::actingAs(administrativoDeCatalogo(), ['gestion']);

    $categoria = categoriaDePrueba();

    $this->patchJson(route('api.v1.gestion.categorias.estado', $categoria), ['activo' => false])
        ->assertOk()
        ->assertJsonPath('data.activo', false);

    expect($categoria->refresh()->activo)->toBeFalse();

    $this->patchJson(route('api.v1.gestion.categorias.estado', $categoria), ['activo' => true])
        ->assertOk()
        ->assertJsonPath('data.activo', true);

    expect($categoria->refresh()->activo)->toBeTrue();
});

it('exige el estado al que se quiere llegar', function () {
    Sanctum::actingAs(administrativoDeCatalogo(), ['gestion']);

    $this->patchJson(route('api.v1.gestion.categorias.estado', categoriaDePrueba()), [])
        ->assertStatus(422)
        ->assertJsonValidationErrors('activo');
});

it('no deja cambiar el estado sin el permiso de baja', function () {
    Sanctum::actingAs(User::factory()->empleado('vendedor')->create(), ['gestion']);

    $categoria = categoriaDePrueba();

    $this->patchJson(route('api.v1.gestion.categorias.estado', $categoria), ['activo' => false])
        ->assertForbidden();

    expect($categoria->refresh()->activo)->toBeTrue();
});

// ============================================================
// Borrado fisico
// ============================================================

it('elimina una categoria que no tiene productos', function () {
    Sanctum::actingAs(administrativoDeCatalogo(), ['gestion']);

    $categoria = categoriaDePrueba();

    $this->deleteJson(route('api.v1.gestion.categorias.destroy', $categoria))
        ->assertNoContent();

    $this->assertDatabaseMissing('categorias', ['id' => $categoria->id]);
});

it('responde 409 al eliminar una categoria con productos', function () {
    Sanctum::actingAs(administrativoDeCatalogo(), ['gestion']);

    $categoria = categoriaDePrueba();
    productoDePrueba(['categoria_id' => $categoria->id]);

    $this->deleteJson(route('api.v1.gestion.categorias.destroy', $categoria))
        ->assertStatus(409)
        ->assertJsonStructure(['message']);

    $this->assertDatabaseHas('categorias', ['id' => $categoria->id]);
});
