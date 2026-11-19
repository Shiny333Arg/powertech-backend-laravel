<?php

use App\Exceptions\EntidadNoEliminableException;
use App\Models\CondicionIva;
use App\Models\Direccion;
use App\Models\OrdenCompra;
use App\Models\Permiso;
use App\Models\Proveedor;
use App\Models\User;
use Database\Seeders\Maestros\AlicuotaIvaSeeder;
use Database\Seeders\Maestros\CondicionIvaSeeder;
use Database\Seeders\Maestros\PermisoSeeder;
use Database\Seeders\Maestros\RolSeeder;
use Laravel\Sanctum\Sanctum;

/*
|--------------------------------------------------------------------------
| Entrega D — API de gestion: proveedores
|--------------------------------------------------------------------------
|
| Cubre los cinco endpoints y las tres capas que los protegen: el token (401),
| el ambito y la ability (403), y el permiso del rol (403). Las dos ultimas se
| prueban por separado a proposito: son preguntas distintas y fallan por
| motivos distintos.
|
| <b>Se usa Sanctum::actingAs y no createToken(), salvo en una prueba.</b> Aca
| no se afirma nada sobre revocacion ni vigencia —de eso se ocupa
| AutenticacionCompartidaTest—, asi que el doble alcanza y las pruebas corren
| mas rapido. La excepcion es la del permiso quitado al rol, que necesita que
| cada request resuelva el usuario desde la base; esta explicado ahi y en
| tests/Apoyo/fabricas.php.
|
| <b>El actor sin permiso es el VENDEDOR.</b> Segun la matriz real de
| rol_permiso que siembra PermisoSeeder, vendedor y cajero no tienen ninguno de
| los cuatro permisos de proveedores: no son como categorias, donde el vendedor
| tiene 'categoria.ver'. Por eso la prueba de "mira pero no escribe" se arma
| quitandole un permiso al administrativo, con la matriz real como punto de
| partida y sin inventar un rol para la prueba.
|
*/

beforeEach(function () {
    $this->seed([
        RolSeeder::class,
        PermisoSeeder::class,
        CondicionIvaSeeder::class,
        AlicuotaIvaSeeder::class,
    ]);
});

/** Rol con los cuatro permisos de proveedores: ver, crear, editar y baja. */
function administrativoDeProveedores(): User
{
    return User::factory()->empleado('administrativo')->create();
}

/**
 * Cuerpo valido para el alta, con el domicilio fiscal opcional incluido.
 *
 * @return array<string, mixed>
 */
function datosDeAltaDeProveedor(array $cambios = []): array
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
        'direccion'          => [
            'calle'         => 'Av. Siempreviva',
            'numero'        => '742',
            'codigo_postal' => '9011',
            'localidad'     => 'Caleta Olivia',
            'provincia'     => 'Santa Cruz',
        ],
    ], $cambios);
}

/** Orden de compra en un estado abierto, que es lo que cuenta el listado. */
function ordenAbiertaDe(Proveedor $proveedor): OrdenCompra
{
    return OrdenCompra::create([
        'numero'       => 'OC-'.str_pad((string) (OrdenCompra::count() + 1), 7, '0', STR_PAD_LEFT),
        'proveedor_id' => $proveedor->id,
        'estado'       => 'borrador',
        'origen'       => 'manual',
    ]);
}

// ============================================================
// Las tres capas de acceso
// ============================================================

it('rechaza la consulta sin token', function () {
    $this->getJson(route('api.v1.gestion.proveedores.index'))
        ->assertUnauthorized();
});

it('rechaza un token de un cliente de la tienda', function () {
    // El usuario es de otro ambito: lo frena 'api.ambito:gestion', que mira
    // quien es el usuario AHORA.
    Sanctum::actingAs(User::factory()->cliente()->create(), ['tienda']);

    $this->getJson(route('api.v1.gestion.proveedores.index'))
        ->assertForbidden()
        ->assertJsonPath('codigo', 'AMBITO_INCORRECTO');
});

it('rechaza un token de empleado emitido para la tienda', function () {
    // El usuario es correcto y el token no: lo frena 'abilities:gestion', que
    // mira PARA QUE se emitio. Es el caso que justifica las dos capas.
    Sanctum::actingAs(administrativoDeProveedores(), ['tienda']);

    $this->getJson(route('api.v1.gestion.proveedores.index'))
        ->assertForbidden()
        ->assertJsonPath('codigo', 'ABILITY_INSUFICIENTE');
});

// ============================================================
// Listado
// ============================================================

it('lista los proveedores con los dos conteos que se miran antes de la baja', function () {
    Sanctum::actingAs(administrativoDeProveedores(), ['gestion']);

    $conHistoria = proveedorDePrueba(['razon_social' => 'Austral SA']);
    productoDePrueba(['proveedor_id' => $conHistoria->id]);
    ordenAbiertaDe($conHistoria);

    proveedorDePrueba(['razon_social' => 'Boreal SRL']);

    $respuesta = $this->getJson(route('api.v1.gestion.proveedores.index'));

    $respuesta->assertOk()
        ->assertJsonCount(2, 'data')
        // Ordenado por razon social, igual que el panel.
        ->assertJsonPath('data.0.razon_social', 'Austral SA')
        ->assertJsonPath('data.0.productos_count', 1)
        ->assertJsonPath('data.0.ordenes_abiertas_count', 1)
        ->assertJsonPath('data.1.productos_count', 0)
        ->assertJsonPath('data.1.ordenes_abiertas_count', 0)
        ->assertJsonPath('meta.total', 2)
        ->assertJsonPath('filtros.estado', 'todos');
});

it('no cuenta como abierta una orden cerrada', function () {
    // El conteo usa el scope abiertas(): una orden recibida o cancelada ya no
    // va a sumar stock y no es un motivo para dudar de la baja.
    Sanctum::actingAs(administrativoDeProveedores(), ['gestion']);

    $proveedor = proveedorDePrueba();

    ordenAbiertaDe($proveedor)->update(['estado' => 'recibida']);

    $this->getJson(route('api.v1.gestion.proveedores.index'))
        ->assertOk()
        ->assertJsonPath('data.0.ordenes_abiertas_count', 0);
});

it('no expone el domicilio en el listado', function () {
    // Son 15 filas mas de una tabla polimorfica por pagina, y la grilla no lo
    // muestra. Viaja en el detalle.
    Sanctum::actingAs(administrativoDeProveedores(), ['gestion']);

    proveedorDePrueba();

    $this->getJson(route('api.v1.gestion.proveedores.index'))
        ->assertOk()
        ->assertJsonMissingPath('data.0.domicilio_fiscal');
});

it('pagina el listado y respeta por_pagina', function () {
    Sanctum::actingAs(administrativoDeProveedores(), ['gestion']);

    foreach (range(1, 3) as $numero) {
        proveedorDePrueba(['razon_social' => "Proveedor {$numero}"]);
    }

    $this->getJson(route('api.v1.gestion.proveedores.index', ['por_pagina' => 2]))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.total', 3)
        ->assertJsonPath('meta.last_page', 2);

    $this->getJson(route('api.v1.gestion.proveedores.index', ['por_pagina' => 2, 'page' => 2]))
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('rechaza un por_pagina fuera del tope', function () {
    Sanctum::actingAs(administrativoDeProveedores(), ['gestion']);

    $this->getJson(route('api.v1.gestion.proveedores.index', ['por_pagina' => 500]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('por_pagina');
});

// ============================================================
// Filtros
// ============================================================

it('muestra los inactivos y filtra por estado', function () {
    Sanctum::actingAs(administrativoDeProveedores(), ['gestion']);

    proveedorDePrueba(['razon_social' => 'Austral SA']);
    proveedorDePrueba(['razon_social' => 'Boreal SRL', 'activo' => false]);

    // Por defecto vienen los dos: es la pantalla desde donde se reactiva.
    $this->getJson(route('api.v1.gestion.proveedores.index'))
        ->assertJsonCount(2, 'data');

    $this->getJson(route('api.v1.gestion.proveedores.index', ['estado' => 'inactivos']))
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.razon_social', 'Boreal SRL');

    $this->getJson(route('api.v1.gestion.proveedores.index', ['estado' => 'activos']))
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.razon_social', 'Austral SA');
});

it('busca por razon social y por contacto', function () {
    Sanctum::actingAs(administrativoDeProveedores(), ['gestion']);

    proveedorDePrueba(['razon_social' => 'Austral SA', 'contacto' => 'Marina Ruiz']);
    proveedorDePrueba(['razon_social' => 'Boreal SRL', 'contacto' => 'Diego Paz']);

    $this->getJson(route('api.v1.gestion.proveedores.index', ['q' => 'austr']))
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.razon_social', 'Austral SA');

    $this->getJson(route('api.v1.gestion.proveedores.index', ['q' => 'Paz']))
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.razon_social', 'Boreal SRL');
});

it('encuentra el CUIT escrito con guiones', function () {
    // La columna guarda 11 digitos sin separadores, y la gente lo escribe con
    // guiones. Misma normalizacion que el buscador del panel.
    Sanctum::actingAs(administrativoDeProveedores(), ['gestion']);

    $proveedor = proveedorDePrueba(['razon_social' => 'Austral SA']);
    proveedorDePrueba(['razon_social' => 'Boreal SRL']);

    $cuit = $proveedor->cuit;
    $conGuiones = substr($cuit, 0, 2).'-'.substr($cuit, 2, 8).'-'.substr($cuit, 10, 1);

    $this->getJson(route('api.v1.gestion.proveedores.index', ['q' => $conGuiones]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $proveedor->id);
});

it('filtra por canal de pedido', function () {
    Sanctum::actingAs(administrativoDeProveedores(), ['gestion']);

    proveedorDePrueba(['razon_social' => 'Austral SA', 'canal_pedido' => 'email']);
    proveedorDePrueba([
        'razon_social' => 'Boreal SRL',
        'canal_pedido' => 'portal_externo',
        'portal_url'   => 'https://pedidos.boreal.test',
    ]);

    $this->getJson(route('api.v1.gestion.proveedores.index', ['canal_pedido' => 'portal_externo']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.razon_social', 'Boreal SRL')
        ->assertJsonPath('data.0.canal_pedido_etiqueta', 'Portal del proveedor')
        ->assertJsonPath('filtros.canal_pedido', 'portal_externo');
});

it('rechaza un canal y un estado que no existen', function () {
    Sanctum::actingAs(administrativoDeProveedores(), ['gestion']);

    $this->getJson(route('api.v1.gestion.proveedores.index', ['canal_pedido' => 'paloma']))
        ->assertStatus(422)
        ->assertJsonValidationErrors('canal_pedido');

    $this->getJson(route('api.v1.gestion.proveedores.index', ['estado' => 'cualquiera']))
        ->assertStatus(422)
        ->assertJsonValidationErrors('estado');
});

// ============================================================
// Detalle
// ============================================================

it('devuelve el detalle con la condicion de IVA y el domicilio fiscal', function () {
    Sanctum::actingAs(administrativoDeProveedores(), ['gestion']);

    $proveedor = proveedorDePrueba(['razon_social' => 'Austral SA']);

    $proveedor->direcciones()->create([
        'tipo'              => 'fiscal',
        'calle'             => 'Av. Siempreviva',
        'numero'            => '742',
        'codigo_postal'     => '9011',
        'localidad'         => 'Caleta Olivia',
        'provincia'         => 'Santa Cruz',
        'es_predeterminada' => true,
    ]);

    $this->getJson(route('api.v1.gestion.proveedores.show', $proveedor))
        ->assertOk()
        ->assertJsonPath('data.id', $proveedor->id)
        ->assertJsonPath('data.razon_social', 'Austral SA')
        ->assertJsonPath('data.activo', true)
        ->assertJsonPath('data.canal_pedido', 'email')
        ->assertJsonPath('data.canal_pedido_etiqueta', 'Correo electrónico')
        ->assertJsonPath('data.domicilio_fiscal.localidad', 'Caleta Olivia')
        ->assertJsonPath('data.domicilio_fiscal.calle', 'Av. Siempreviva')
        ->assertJsonStructure(['data' => ['condicion_iva' => ['id', 'nombre']]]);
});

it('devuelve null en el domicilio del proveedor que no tiene', function () {
    // null y no clave ausente: "no cargaron domicilio" es una respuesta, y la
    // pantalla de edicion necesita distinguirla.
    Sanctum::actingAs(administrativoDeProveedores(), ['gestion']);

    $this->getJson(route('api.v1.gestion.proveedores.show', proveedorDePrueba()))
        ->assertOk()
        ->assertJsonPath('data.domicilio_fiscal', null);
});

it('responde 404 por un proveedor que no existe', function () {
    Sanctum::actingAs(administrativoDeProveedores(), ['gestion']);

    $this->getJson(route('api.v1.gestion.proveedores.show', 9999))
        ->assertNotFound();
});

// ============================================================
// Alta
// ============================================================

it('crea un proveedor con su domicilio en una sola llamada', function () {
    Sanctum::actingAs(administrativoDeProveedores(), ['gestion']);

    $datos = datosDeAltaDeProveedor();

    $this->postJson(route('api.v1.gestion.proveedores.store'), $datos)
        ->assertCreated()
        ->assertJsonPath('data.razon_social', 'Distribuidora Austral SA')
        ->assertJsonPath('data.cuit', $datos['cuit'])
        ->assertJsonPath('data.plazo_entrega_dias', 7)
        ->assertJsonPath('data.activo', true)
        ->assertJsonPath('data.productos_count', 0)
        ->assertJsonPath('data.domicilio_fiscal.numero', '742');

    $this->assertDatabaseHas('proveedores', [
        'razon_social' => 'Distribuidora Austral SA',
        'cuit'         => $datos['cuit'],
        'activo'       => true,
    ]);

    // El domicilio quedo en la tabla polimorfica, con el tipo fiscal y marcado
    // como predeterminado.
    $this->assertDatabaseHas('direcciones', [
        'direccionable_type' => Proveedor::class,
        'direccionable_id'   => Proveedor::query()->value('id'),
        'tipo'               => 'fiscal',
        'localidad'          => 'Caleta Olivia',
        'es_predeterminada'  => true,
    ]);
});

it('normaliza el CUIT a 11 digitos al guardarlo', function () {
    // Es lo que hace util el indice unico: sin esto, el mismo proveedor escrito
    // con y sin guiones son dos filas. La regla es de ProveedorRequest y la API
    // la hereda sin reescribirla.
    Sanctum::actingAs(administrativoDeProveedores(), ['gestion']);

    $cuit = cuitValido();
    $conGuiones = substr($cuit, 0, 2).'-'.substr($cuit, 2, 8).'-'.substr($cuit, 10, 1);

    $this->postJson(route('api.v1.gestion.proveedores.store'), datosDeAltaDeProveedor([
        'cuit' => $conGuiones,
    ]))
        ->assertCreated()
        ->assertJsonPath('data.cuit', $cuit);

    $this->assertDatabaseHas('proveedores', ['cuit' => $cuit]);
});

it('crea el proveedor sin domicilio si no lo mandan', function () {
    Sanctum::actingAs(administrativoDeProveedores(), ['gestion']);

    $datos = datosDeAltaDeProveedor();
    unset($datos['direccion']);

    $this->postJson(route('api.v1.gestion.proveedores.store'), $datos)
        ->assertCreated()
        ->assertJsonPath('data.domicilio_fiscal', null);

    expect(Direccion::count())->toBe(0);
});

it('nace activo aunque el cuerpo diga lo contrario', function () {
    // El estado tiene su endpoint y su permiso. Ademas ProveedorRequest castea
    // 'activo' con boolean(), asi que un alta que no mandara el campo crearia
    // un proveedor inactivo en silencio.
    Sanctum::actingAs(administrativoDeProveedores(), ['gestion']);

    $this->postJson(route('api.v1.gestion.proveedores.store'), datosDeAltaDeProveedor([
        'activo' => false,
    ]))
        ->assertCreated()
        ->assertJsonPath('data.activo', true);

    $this->assertDatabaseHas('proveedores', ['activo' => true]);
});

// ============================================================
// Validaciones
// ============================================================

it('exige los campos obligatorios', function () {
    Sanctum::actingAs(administrativoDeProveedores(), ['gestion']);

    $this->postJson(route('api.v1.gestion.proveedores.store'), [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['razon_social', 'cuit', 'condicion_iva_id', 'canal_pedido']);

    expect(Proveedor::count())->toBe(0);
});

it('rechaza un CUIT con el digito verificador equivocado', function () {
    // Regla existente (App\Rules\Cuit, modulo 11) que la API hereda: un digito
    // cambiado crea un duplicado que despues hay que fusionar a mano.
    Sanctum::actingAs(administrativoDeProveedores(), ['gestion']);

    // Para los diez primeros digitos el verificador correcto es 1: el 2 del
    // final es el digito cambiado que la regla tiene que detectar.
    $this->postJson(route('api.v1.gestion.proveedores.store'), datosDeAltaDeProveedor([
        'cuit' => '30711111112',
    ]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('cuit');

    expect(Proveedor::count())->toBe(0);
});

it('rechaza un CUIT repetido', function () {
    Sanctum::actingAs(administrativoDeProveedores(), ['gestion']);

    $existente = proveedorDePrueba();

    $this->postJson(route('api.v1.gestion.proveedores.store'), datosDeAltaDeProveedor([
        'cuit' => $existente->cuit,
    ]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('cuit');
});

it('exige la URL del portal cuando el canal es portal externo', function () {
    Sanctum::actingAs(administrativoDeProveedores(), ['gestion']);

    $this->postJson(route('api.v1.gestion.proveedores.store'), datosDeAltaDeProveedor([
        'canal_pedido' => 'portal_externo',
    ]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('portal_url');
});

it('exige el resto del domicilio cuando viene la calle', function () {
    // La calle es la que decide si hay direccion: si viene, el resto pasa a ser
    // obligatorio. Un domicilio a medias no sirve para facturar.
    Sanctum::actingAs(administrativoDeProveedores(), ['gestion']);

    $this->postJson(route('api.v1.gestion.proveedores.store'), datosDeAltaDeProveedor([
        'direccion' => ['calle' => 'Av. Siempreviva'],
    ]))
        ->assertStatus(422)
        ->assertJsonValidationErrors([
            'direccion.codigo_postal',
            'direccion.localidad',
            'direccion.provincia',
        ]);
});

it('rechaza una condicion de IVA que no existe', function () {
    Sanctum::actingAs(administrativoDeProveedores(), ['gestion']);

    $this->postJson(route('api.v1.gestion.proveedores.store'), datosDeAltaDeProveedor([
        'condicion_iva_id' => 9999,
    ]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('condicion_iva_id');
});

// ============================================================
// Actualizacion
// ============================================================

it('edita un proveedor con PUT', function () {
    Sanctum::actingAs(administrativoDeProveedores(), ['gestion']);

    $proveedor = proveedorDePrueba();

    $this->putJson(route('api.v1.gestion.proveedores.update', $proveedor), datosDeAltaDeProveedor([
        'razon_social' => 'Austral Mayorista SA',
        'cuit'         => $proveedor->cuit, // el propio no choca con el unique
        'contacto'     => 'Marina Ruiz',
    ]))
        ->assertOk()
        ->assertJsonPath('data.razon_social', 'Austral Mayorista SA')
        ->assertJsonPath('data.contacto', 'Marina Ruiz');

    expect($proveedor->refresh()->razon_social)->toBe('Austral Mayorista SA');
});

it('reemplaza el domicilio en lugar de acumular otro', function () {
    Sanctum::actingAs(administrativoDeProveedores(), ['gestion']);

    $proveedor = proveedorDePrueba();

    $datos = datosDeAltaDeProveedor(['cuit' => $proveedor->cuit]);

    $this->putJson(route('api.v1.gestion.proveedores.update', $proveedor), $datos)
        ->assertOk();

    $datos['direccion']['localidad'] = 'Comodoro Rivadavia';

    $this->putJson(route('api.v1.gestion.proveedores.update', $proveedor), $datos)
        ->assertOk()
        ->assertJsonPath('data.domicilio_fiscal.localidad', 'Comodoro Rivadavia');

    // Un solo domicilio fiscal por proveedor: la tabla admite varios, pero para
    // un proveedor alcanza con uno.
    expect($proveedor->direcciones()->count())->toBe(1);
});

it('no borra el domicilio cargado si la edicion no lo trae', function () {
    Sanctum::actingAs(administrativoDeProveedores(), ['gestion']);

    $proveedor = proveedorDePrueba();

    $datos = datosDeAltaDeProveedor(['cuit' => $proveedor->cuit]);

    $this->putJson(route('api.v1.gestion.proveedores.update', $proveedor), $datos)->assertOk();

    unset($datos['direccion']);

    $this->putJson(route('api.v1.gestion.proveedores.update', $proveedor), $datos)
        ->assertOk()
        ->assertJsonPath('data.domicilio_fiscal.localidad', 'Caleta Olivia');

    expect($proveedor->direcciones()->count())->toBe(1);
});

it('limpia la URL del portal al dejar de usar ese canal', function () {
    // Un canal que no es portal no tiene URL que guardar: si quedo cargada de
    // un canal anterior, se limpia en vez de arrastrar un dato muerto.
    Sanctum::actingAs(administrativoDeProveedores(), ['gestion']);

    $proveedor = proveedorDePrueba([
        'canal_pedido' => 'portal_externo',
        'portal_url'   => 'https://pedidos.austral.test',
    ]);

    $this->putJson(route('api.v1.gestion.proveedores.update', $proveedor), datosDeAltaDeProveedor([
        'cuit'         => $proveedor->cuit,
        'canal_pedido' => 'email',
    ]))
        ->assertOk()
        ->assertJsonPath('data.portal_url', null);

    expect($proveedor->refresh()->portal_url)->toBeNull();
});

it('no cambia el estado desde el PUT', function () {
    // El estado tiene su endpoint y su permiso: un 'activo' de mas en el cuerpo
    // de la edicion no puede reactivar un proveedor dado de baja.
    Sanctum::actingAs(administrativoDeProveedores(), ['gestion']);

    $proveedor = proveedorDePrueba(['activo' => false]);

    $this->putJson(route('api.v1.gestion.proveedores.update', $proveedor), datosDeAltaDeProveedor([
        'cuit'   => $proveedor->cuit,
        'activo' => true,
    ]))->assertOk();

    expect($proveedor->refresh()->activo)->toBeFalse();
});

it('responde 404 al editar un proveedor que no existe', function () {
    Sanctum::actingAs(administrativoDeProveedores(), ['gestion']);

    $this->putJson(route('api.v1.gestion.proveedores.update', 9999), datosDeAltaDeProveedor())
        ->assertNotFound();
});

// ============================================================
// Estado: baja y reactivacion
// ============================================================

it('da de baja y reactiva un proveedor', function () {
    Sanctum::actingAs(administrativoDeProveedores(), ['gestion']);

    $proveedor = proveedorDePrueba();

    $this->patchJson(route('api.v1.gestion.proveedores.estado', $proveedor), ['activo' => false])
        ->assertOk()
        ->assertJsonPath('data.activo', false);

    expect($proveedor->refresh()->activo)->toBeFalse();
    $this->assertDatabaseHas('proveedores', ['id' => $proveedor->id, 'activo' => false]);

    $this->patchJson(route('api.v1.gestion.proveedores.estado', $proveedor), ['activo' => true])
        ->assertOk()
        ->assertJsonPath('data.activo', true);

    expect($proveedor->refresh()->activo)->toBeTrue();
});

it('avisa que queda historia sin atender al dar de baja', function () {
    // La baja NO se bloquea —es reversible y el proveedor sigue nombrando las
    // ordenes que ya emitio—, pero la consecuencia no es obvia: esos productos
    // dejan de generar orden automatica. El panel lo dice en un flash message;
    // la API devuelve los dos numeros.
    Sanctum::actingAs(administrativoDeProveedores(), ['gestion']);

    $proveedor = proveedorDePrueba();

    productoDePrueba([
        'proveedor_id'          => $proveedor->id,
        'reposicion_automatica' => true,
        'cantidad_reposicion'   => 5,
    ]);

    ordenAbiertaDe($proveedor);

    $this->patchJson(route('api.v1.gestion.proveedores.estado', $proveedor), ['activo' => false])
        ->assertOk()
        ->assertJsonPath('data.activo', false)
        ->assertJsonPath('advertencias.productos_en_reposicion_automatica', 1)
        ->assertJsonPath('advertencias.ordenes_abiertas', 1);

    expect($proveedor->refresh()->activo)->toBeFalse();
});

it('no manda advertencias al reactivar', function () {
    Sanctum::actingAs(administrativoDeProveedores(), ['gestion']);

    $proveedor = proveedorDePrueba(['activo' => false]);

    $this->patchJson(route('api.v1.gestion.proveedores.estado', $proveedor), ['activo' => true])
        ->assertOk()
        ->assertJsonMissingPath('advertencias');
});

it('exige el estado al que se quiere llegar', function () {
    Sanctum::actingAs(administrativoDeProveedores(), ['gestion']);

    $this->patchJson(route('api.v1.gestion.proveedores.estado', proveedorDePrueba()), [])
        ->assertStatus(422)
        ->assertJsonValidationErrors('activo');
});

// ============================================================
// Permisos del rol
// ============================================================

it('no deja entrar al vendedor ni al cajero', function () {
    // Segun la matriz real de rol_permiso, ninguno de los dos tiene
    // 'proveedor.ver': los proveedores son del administrativo.
    foreach (['vendedor', 'cajero'] as $rol) {
        Sanctum::actingAs(User::factory()->empleado($rol)->create(), ['gestion']);

        $this->getJson(route('api.v1.gestion.proveedores.index'))
            ->assertForbidden()
            ->assertJsonPath('codigo', 'PERMISO_INSUFICIENTE');

        $this->app['auth']->forgetGuards();
    }
});

it('deja mirar pero no crear al que no tiene el permiso de alta', function () {
    // La matriz real no trae ningun rol con 'proveedor.ver' y sin
    // 'proveedor.crear', asi que el actor se arma quitandole el permiso al
    // administrativo en lugar de inventar un rol para la prueba. Es la
    // operacion permitida y la rechazada que pide la consigna.
    $empleado = administrativoDeProveedores();

    $empleado->rol->permisos()->detach(
        Permiso::where('clave', 'proveedor.crear')->value('id')
    );

    Sanctum::actingAs($empleado, ['gestion']);

    $this->getJson(route('api.v1.gestion.proveedores.index'))->assertOk();

    $this->postJson(route('api.v1.gestion.proveedores.store'), datosDeAltaDeProveedor())
        ->assertForbidden()
        // En castellano y con codigo propio: el panel muestra este mensaje tal
        // cual. Sin la rama del render seria "This action is unauthorized.".
        ->assertJsonPath('codigo', 'PERMISO_INSUFICIENTE');

    expect(Proveedor::count())->toBe(0);
});

it('no deja editar sin el permiso de edicion', function () {
    $empleado = administrativoDeProveedores();

    $empleado->rol->permisos()->detach(
        Permiso::where('clave', 'proveedor.editar')->value('id')
    );

    Sanctum::actingAs($empleado, ['gestion']);

    $proveedor = proveedorDePrueba(['razon_social' => 'Austral SA']);

    $this->putJson(route('api.v1.gestion.proveedores.update', $proveedor), datosDeAltaDeProveedor([
        'cuit' => $proveedor->cuit,
    ]))->assertForbidden();

    expect($proveedor->refresh()->razon_social)->toBe('Austral SA');
});

it('no deja cambiar el estado sin el permiso de baja', function () {
    // 'proveedor.baja' es un permiso distinto de 'proveedor.editar': sacar de
    // operacion no es lo mismo que corregir un telefono.
    $empleado = administrativoDeProveedores();

    $empleado->rol->permisos()->detach(
        Permiso::where('clave', 'proveedor.baja')->value('id')
    );

    Sanctum::actingAs($empleado, ['gestion']);

    $proveedor = proveedorDePrueba();

    $this->patchJson(route('api.v1.gestion.proveedores.estado', $proveedor), ['activo' => false])
        ->assertForbidden();

    expect($proveedor->refresh()->activo)->toBeTrue();
});

it('refleja el permiso quitado al rol con el mismo token', function () {
    // <b>Token real y withToken(), no Sanctum::actingAs.</b> El doble deja
    // clavada la MISMA instancia de User entre las dos llamadas, con su
    // relacion de permisos ya cargada: el detach no se veria y la prueba
    // pasaria sin probar nada. Ver tests/Apoyo/fabricas.php.
    $empleado = administrativoDeProveedores();

    $token = tokenDeGestion($empleado)->plainTextToken;

    $this->withToken($token)
        ->getJson(route('api.v1.gestion.proveedores.index'))
        ->assertOk();

    // Sin reemitir el token: 'tienePermiso()' consulta la base en cada request.
    $empleado->rol->permisos()->detach(
        Permiso::where('clave', 'proveedor.ver')->value('id')
    );

    $this->app['auth']->forgetGuards();

    $this->withToken($token)
        ->getJson(route('api.v1.gestion.proveedores.index'))
        ->assertForbidden()
        ->assertJsonPath('codigo', 'PERMISO_INSUFICIENTE');
});

// ============================================================
// Proteccion de las referencias historicas
// ============================================================

it('no expone ningun endpoint de borrado', function () {
    // La ausencia es la regla: el proveedor esta referenciado por productos y
    // por ordenes de compra, y la orden dice a quien se le compro. 405 y no
    // 403: la ruta no existe para nadie, ni para el administrador.
    Sanctum::actingAs(User::factory()->empleado('administrador')->create(), ['gestion']);

    $proveedor = proveedorDePrueba();

    $this->deleteJson('/api/v1/gestion/proveedores/'.$proveedor->id)
        ->assertStatus(405);

    $this->assertDatabaseHas('proveedores', ['id' => $proveedor->id]);
});

it('el guard del modelo rechaza el borrado de un proveedor con ordenes', function () {
    // La segunda red, por si alguien agregara un delete() en codigo: el trait
    // ProtegeContraBorrado lanza la excepcion de dominio antes de llegar a la
    // FK, y la orden de compra conserva a quien se le compro.
    $proveedor = proveedorDePrueba();

    $orden = ordenAbiertaDe($proveedor);

    expect(fn () => $proveedor->delete())
        ->toThrow(EntidadNoEliminableException::class);

    $this->assertDatabaseHas('proveedores', ['id' => $proveedor->id]);
    $this->assertDatabaseHas('ordenes_compra', ['id' => $orden->id, 'proveedor_id' => $proveedor->id]);
});

it('deja dar de baja a un proveedor con historia, sin tocar sus ordenes', function () {
    // La baja no se bloquea: es la unica forma de sacar de operacion a alguien
    // que no se puede eliminar, y las ordenes abiertas siguen su curso.
    Sanctum::actingAs(administrativoDeProveedores(), ['gestion']);

    $proveedor = proveedorDePrueba();
    $orden = ordenAbiertaDe($proveedor);

    $this->patchJson(route('api.v1.gestion.proveedores.estado', $proveedor), ['activo' => false])
        ->assertOk();

    $this->assertDatabaseHas('ordenes_compra', [
        'id'           => $orden->id,
        'proveedor_id' => $proveedor->id,
        'estado'       => 'borrador',
    ]);
});
