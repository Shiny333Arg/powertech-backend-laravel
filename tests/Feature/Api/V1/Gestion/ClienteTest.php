<?php

use App\Models\Cliente;
use App\Models\CondicionIva;
use App\Models\Direccion;
use App\Models\Permiso;
use App\Models\TipoDocumento;
use App\Models\User;
use App\Models\Venta;
use App\Notifications\InvitacionCliente;
use Database\Seeders\Maestros\AlicuotaIvaSeeder;
use Database\Seeders\Maestros\CondicionIvaSeeder;
use Database\Seeders\Maestros\PermisoSeeder;
use Database\Seeders\Maestros\RolSeeder;
use Database\Seeders\Maestros\TipoDocumentoSeeder;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

/*
|--------------------------------------------------------------------------
| Etapa 3 — API de gestion: clientes
|--------------------------------------------------------------------------
|
| Cubre los diez endpoints y las tres capas que los protegen: el token (401), el
| ambito y la ability (403), y el permiso del rol (403). Las dos ultimas se
| prueban por separado a proposito: son preguntas distintas y fallan por motivos
| distintos.
|
| <b>El actor sin permiso es el VENDEDOR, y aca eso significa otra cosa.</b> En
| proveedores el vendedor no tiene ningun permiso del modulo, asi que servia como
| "no entra". Segun la matriz real de rol_permiso que siembra PermisoSeeder, en
| clientes el vendedor y el cajero tienen 'cliente.ver', 'cliente.crear' y
| 'cliente.editar' y NO tienen 'cliente.baja': entran, cargan y corrigen, pero no
| sacan a nadie de la operacion. Es la diferencia mas importante de este modulo y
| por eso tiene sus propias pruebas en lugar de copiar las del anterior.
|
| <b>Las dos filas.</b> Un cliente es `users` —con que correo entra a la tienda—
| mas `clientes` —a quien se le factura—. El alta las escribe juntas y manda la
| invitacion; la edicion, si le cambian el correo, desverifica la cuenta. Las dos
| reglas vienen del panel y son las que mas pruebas tienen.
|
| <b>Se usa Sanctum::actingAs y no createToken(), salvo donde hace falta que cada
| request resuelva el usuario desde la base.</b> Aca no se afirma nada sobre
| revocacion ni vigencia —de eso se ocupa AutenticacionCompartidaTest—, asi que el
| doble alcanza y las pruebas corren mas rapido. Ver tests/Apoyo/fabricas.php.
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

/** Rol con los cuatro permisos de clientes: ver, crear, editar y baja. */
function administrativoDeClientes(): User
{
    return User::factory()->empleado('administrativo')->create();
}

/** Id del tipo de documento por codigo de ARCA (80 = CUIT, 96 = DNI). */
function tipoDocumentoPorCodigo(int $codigoAfip): int
{
    return (int) TipoDocumento::where('codigo_afip', $codigoAfip)->value('id');
}

/** Id de la condicion frente al IVA por codigo de ARCA (5 = consumidor final). */
function condicionIvaPorCodigo(int $codigoAfip): int
{
    return (int) CondicionIva::where('codigo_afip', $codigoAfip)->value('id');
}

/** Un DNI distinto en cada llamada, para no chocar contra el indice unico. */
function dniDePrueba(): string
{
    static $siguiente = 0;

    return (string) (30_000_000 + $siguiente++);
}

/**
 * Cliente con su cuenta en la tienda, que es lo que crea la pantalla.
 *
 * Se arma a mano porque el proyecto solo tiene UserFactory y Cliente no usa
 * HasFactory: fabricarlo exigiria tocar codigo de produccion. Mismo criterio que
 * las fabricas de tests/Apoyo/fabricas.php.
 *
 * Con `$conCuenta: false` sale el cliente de mostrador viejo, sin fila en
 * `users`: es un caso real de la base —user_id es nullable— y la edicion lo
 * tiene que soportar.
 */
function clienteDePrueba(array $atributos = [], bool $conCuenta = true, array $cuenta = []): Cliente
{
    $usuario = $conCuenta ? User::factory()->cliente()->create($cuenta) : null;

    return Cliente::create(array_merge([
        'user_id'           => $usuario?->id,
        'razon_social'      => 'Cliente de prueba',
        'tipo_documento_id' => tipoDocumentoPorCodigo(96),
        'nro_doc'           => dniDePrueba(),
        'condicion_iva_id'  => condicionIvaPorCodigo(5),
        'telefono'          => '2974000000',
        'activo'            => true,
    ], $atributos));
}

/**
 * Cuerpo valido para el alta: los datos de la cuenta y los de facturacion.
 *
 * Consumidor final con DNI, que es el caso comun del mostrador. La contraseña no
 * esta y no es un olvido: el cliente la elige desde el enlace que recibe.
 *
 * @return array<string, mixed>
 */
function datosDeAltaDeCliente(array $cambios = []): array
{
    return array_merge([
        'nombres'           => 'Juan Carlos',
        'apellido'          => 'Pérez',
        'email'             => 'jperez'.fake()->unique()->numberBetween(1000, 9999).'@correo.test',
        'telefono'          => '2974000000',
        'tipo_documento_id' => tipoDocumentoPorCodigo(96),
        'nro_doc'           => dniDePrueba(),
        'condicion_iva_id'  => condicionIvaPorCodigo(5),
        'razon_social'      => 'Distribuidora del Sur SRL',
    ], $cambios);
}

/**
 * Venta de mostrador ya entregada, a nombre del cliente.
 *
 * Se arma a mano y no por VentaService porque lo que la ficha muestra es el
 * renglon del historial, no el camino que llevo hasta el. Mismo criterio que
 * ventaEnPreventa() en tests/Apoyo/fabricas.php.
 */
function ventaDelCliente(Cliente $cliente, DateTimeInterface $fecha): Venta
{
    static $siguiente = 0;

    return Venta::create([
        'numero'       => 8_000_000 + ++$siguiente,
        'canal'        => 'mostrador',
        'estado'       => 'entregada',
        'modo_entrega' => 'retiro',
        'cliente_id'   => $cliente->id,
        'fecha'        => $fecha,
        'subtotal'     => 150000,
        'total'        => 150000,
    ]);
}

/** @return array<string, mixed> */
function datosDeDireccion(array $cambios = []): array
{
    return array_merge([
        'tipo'              => 'fiscal',
        'calle'             => 'Av. Siempreviva',
        'numero'            => '742',
        'piso_depto'        => null,
        'codigo_postal'     => '9011',
        'localidad'         => 'Caleta Olivia',
        'provincia'         => 'Santa Cruz',
        'es_predeterminada' => true,
    ], $cambios);
}

// ============================================================
// Las tres capas de acceso
// ============================================================

it('rechaza la consulta sin token', function () {
    $this->getJson(route('api.v1.gestion.clientes.index'))
        ->assertUnauthorized();
});

it('rechaza un token de un cliente de la tienda', function () {
    // El usuario es de otro ambito: lo frena 'api.ambito:gestion', que mira
    // quien es el usuario AHORA.
    Sanctum::actingAs(User::factory()->cliente()->create(), ['tienda']);

    $this->getJson(route('api.v1.gestion.clientes.index'))
        ->assertForbidden()
        ->assertJsonPath('codigo', 'AMBITO_INCORRECTO');
});

it('rechaza un token de empleado emitido para la tienda', function () {
    // El usuario es correcto y el token no: lo frena 'abilities:gestion', que
    // mira PARA QUE se emitio. Es el caso que justifica las dos capas.
    Sanctum::actingAs(administrativoDeClientes(), ['tienda']);

    $this->getJson(route('api.v1.gestion.clientes.index'))
        ->assertForbidden();
});

// ============================================================
// Listado
// ============================================================

it('lista los clientes ordenados por razon social', function () {
    clienteDePrueba(['razon_social' => 'Zapata Hermanos']);
    clienteDePrueba(['razon_social' => 'Almacén Central']);

    Sanctum::actingAs(administrativoDeClientes(), ['gestion']);

    $this->getJson(route('api.v1.gestion.clientes.index'))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.razon_social', 'Almacén Central')
        ->assertJsonPath('data.1.razon_social', 'Zapata Hermanos');
});

it('muestra activos e inactivos por defecto, porque es la pantalla donde se reactiva', function () {
    clienteDePrueba(['razon_social' => 'En operación', 'activo' => true]);
    clienteDePrueba(['razon_social' => 'Dado de baja', 'activo' => false]);

    Sanctum::actingAs(administrativoDeClientes(), ['gestion']);

    $this->getJson(route('api.v1.gestion.clientes.index'))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('filtros.activo', null);
});

it('filtra por situacion', function () {
    clienteDePrueba(['razon_social' => 'En operación', 'activo' => true]);
    clienteDePrueba(['razon_social' => 'Dado de baja', 'activo' => false]);

    Sanctum::actingAs(administrativoDeClientes(), ['gestion']);

    $this->getJson(route('api.v1.gestion.clientes.index', ['activo' => 0]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.razon_social', 'Dado de baja')
        ->assertJsonPath('filtros.activo', false);
});

it('busca por razon social y por numero de documento', function () {
    clienteDePrueba(['razon_social' => 'Ferretería Austral', 'nro_doc' => '27123456']);
    clienteDePrueba(['razon_social' => 'Kiosco del Puerto', 'nro_doc' => '20987654']);

    $empleado = administrativoDeClientes();

    Sanctum::actingAs($empleado, ['gestion']);

    $this->getJson(route('api.v1.gestion.clientes.index', ['q' => 'Austral']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.razon_social', 'Ferretería Austral')
        ->assertJsonPath('filtros.q', 'Austral');

    $this->getJson(route('api.v1.gestion.clientes.index', ['q' => '20987']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.razon_social', 'Kiosco del Puerto');
});

it('no manda la cuenta ni las direcciones en el listado', function () {
    $cliente = clienteDePrueba();
    $cliente->direcciones()->create(datosDeDireccion());

    Sanctum::actingAs(administrativoDeClientes(), ['gestion']);

    // Las claves FALTAN, no vienen en null: el listado no carga esas
    // relaciones y una clave en null diria "no tiene", que no es lo mismo.
    $this->getJson(route('api.v1.gestion.clientes.index'))
        ->assertOk()
        ->assertJsonMissingPath('data.0.cuenta')
        ->assertJsonMissingPath('data.0.direcciones');
});

it('respeta el tope de por_pagina', function () {
    Sanctum::actingAs(administrativoDeClientes(), ['gestion']);

    $this->getJson(route('api.v1.gestion.clientes.index', ['por_pagina' => 500]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('por_pagina');
});

// ============================================================
// Detalle
// ============================================================

it('entrega el detalle con la cuenta y las direcciones', function () {
    $cliente = clienteDePrueba([], true, ['email' => 'titular@correo.test']);
    $cliente->direcciones()->create(datosDeDireccion(['localidad' => 'Pico Truncado']));

    Sanctum::actingAs(administrativoDeClientes(), ['gestion']);

    $this->getJson(route('api.v1.gestion.clientes.show', $cliente))
        ->assertOk()
        ->assertJsonPath('data.id', $cliente->id)
        ->assertJsonPath('data.cuenta.email', 'titular@correo.test')
        ->assertJsonPath('data.cuenta.email_verificado', true)
        ->assertJsonPath('data.cuenta.invitacion_pendiente', false)
        ->assertJsonPath('data.tipo_documento.codigo_afip', 96)
        ->assertJsonPath('data.condicion_iva.codigo_afip', 5)
        ->assertJsonCount(1, 'data.direcciones')
        ->assertJsonPath('data.direcciones.0.localidad', 'Pico Truncado')
        ->assertJsonPath('data.direcciones.0.tipo_etiqueta', 'Fiscal');
});

it('marca la invitacion pendiente del cliente que todavia no eligio contraseña', function () {
    $cliente = clienteDePrueba([], true, ['password' => null, 'email_verified_at' => null]);

    Sanctum::actingAs(administrativoDeClientes(), ['gestion']);

    $this->getJson(route('api.v1.gestion.clientes.show', $cliente))
        ->assertOk()
        ->assertJsonPath('data.cuenta.invitacion_pendiente', true)
        ->assertJsonPath('data.cuenta.email_verificado', false);
});

it('entrega en null la cuenta del cliente de mostrador sin usuario', function () {
    $cliente = clienteDePrueba([], conCuenta: false);

    Sanctum::actingAs(administrativoDeClientes(), ['gestion']);

    // Acá la clave SÍ viene, y en null: la relación se cargó y no hay fila.
    $this->getJson(route('api.v1.gestion.clientes.show', $cliente))
        ->assertOk()
        ->assertJsonPath('data.cuenta', null);
});

it('nunca publica el hash de la contraseña', function () {
    $cliente = clienteDePrueba();

    Sanctum::actingAs(administrativoDeClientes(), ['gestion']);

    $respuesta = $this->getJson(route('api.v1.gestion.clientes.show', $cliente))->assertOk();

    expect($respuesta->json('data.cuenta'))->not->toHaveKey('password');
});

it('responde 404 por un cliente que no existe', function () {
    Sanctum::actingAs(administrativoDeClientes(), ['gestion']);

    $this->getJson(url('/api/v1/gestion/clientes/99999'))
        ->assertNotFound();
});

// ============================================================
// Alta
// ============================================================

it('da de alta el cliente con su cuenta y le manda la invitacion', function () {
    Notification::fake();

    Sanctum::actingAs(administrativoDeClientes(), ['gestion']);

    $datos = datosDeAltaDeCliente();

    $respuesta = $this->postJson(route('api.v1.gestion.clientes.store'), $datos)
        ->assertCreated()
        ->assertJsonPath('data.razon_social', 'Distribuidora del Sur SRL')
        ->assertJsonPath('data.activo', true)
        ->assertJsonPath('data.cuenta.email', $datos['email'])
        ->assertJsonPath('data.cuenta.invitacion_pendiente', true)
        ->assertJsonPath('invitacion.enviada', true);

    $cliente = Cliente::findOrFail($respuesta->json('data.id'));

    // Las DOS filas, que es lo propio de este modulo.
    $this->assertDatabaseHas('users', ['email' => $datos['email'], 'password' => null, 'activo' => true]);
    $this->assertDatabaseHas('clientes', ['id' => $cliente->id, 'user_id' => $cliente->user_id]);

    // El rol es el único de ámbito tienda: un cliente cargado por un empleado
    // tiene los mismos permisos que uno que se registró solo.
    expect($cliente->user->rol->nombre)->toBe('cliente')
        ->and($cliente->user->rol->ambito->value)->toBe('tienda');

    Notification::assertSentTo($cliente->user, InvitacionCliente::class);
});

it('usa apellido y nombres cuando no le dan razon social', function () {
    Notification::fake();

    Sanctum::actingAs(administrativoDeClientes(), ['gestion']);

    $this->postJson(route('api.v1.gestion.clientes.store'), datosDeAltaDeCliente([
        'razon_social' => null,
    ]))
        ->assertCreated()
        ->assertJsonPath('data.razon_social', 'Pérez Juan Carlos');
});

it('rechaza un correo que ya tiene cuenta', function () {
    $existente = clienteDePrueba([], true, ['email' => 'repetido@correo.test']);

    Sanctum::actingAs(administrativoDeClientes(), ['gestion']);

    $this->postJson(route('api.v1.gestion.clientes.store'), datosDeAltaDeCliente([
        'email' => 'repetido@correo.test',
    ]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('email');

    expect($existente->user->refresh()->email)->toBe('repetido@correo.test');
});

it('rechaza un documento ya registrado para el mismo tipo', function () {
    clienteDePrueba(['nro_doc' => '28111222', 'tipo_documento_id' => tipoDocumentoPorCodigo(96)]);

    Sanctum::actingAs(administrativoDeClientes(), ['gestion']);

    $this->postJson(route('api.v1.gestion.clientes.store'), datosDeAltaDeCliente([
        'nro_doc'           => '28111222',
        'tipo_documento_id' => tipoDocumentoPorCodigo(96),
    ]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('nro_doc');
});

it('exige CUIT cuando la condicion frente al IVA no es consumidor final', function () {
    Sanctum::actingAs(administrativoDeClientes(), ['gestion']);

    // Monotributista con DNI: ARCA no lo admite y lo decide
    // ReglasDatosCliente::validarCoherenciaFiscal(), compartido con el registro
    // de la tienda.
    $this->postJson(route('api.v1.gestion.clientes.store'), datosDeAltaDeCliente([
        'tipo_documento_id' => tipoDocumentoPorCodigo(96),
        'condicion_iva_id'  => condicionIvaPorCodigo(6),
    ]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('tipo_documento_id');
});

it('normaliza el documento escrito con guiones', function () {
    Notification::fake();

    Sanctum::actingAs(administrativoDeClientes(), ['gestion']);

    // El CUIT sale de la fabrica compartida porque tiene el digito verificador
    // correcto: App\Rules\Cuit lo valida, igual que en el registro de la tienda.
    $cuit = cuitValido();

    $conGuiones = substr($cuit, 0, 2).'-'.substr($cuit, 2, 8).'-'.substr($cuit, 10);

    $this->postJson(route('api.v1.gestion.clientes.store'), datosDeAltaDeCliente([
        'tipo_documento_id' => tipoDocumentoPorCodigo(80),
        'condicion_iva_id'  => condicionIvaPorCodigo(1),
        'nro_doc'           => $conGuiones,
    ]))
        ->assertCreated()
        // "30-71000000-5" y "30710000005" son el mismo documento: lo normaliza
        // ReglasDatosCliente para que el indice unico sirva.
        ->assertJsonPath('data.nro_doc', $cuit);
});

it('no deja crear al que solo puede mirar', function () {
    // El vendedor TIENE 'cliente.crear' segun la matriz real, asi que el actor
    // se arma quitandoselo: es la misma tecnica que ProveedorTest, con la matriz
    // como punto de partida y sin inventar un rol para la prueba.
    //
    // Se emite un token real porque la policy consulta la base en cada request:
    // con Sanctum::actingAs el usuario queda cacheado y el detach no se veria.
    $empleado = User::factory()->empleado('vendedor')->create();

    $empleado->rol->permisos()->detach(Permiso::where('clave', 'cliente.crear')->value('id'));

    $this->withToken(tokenDeGestion($empleado)->plainTextToken)
        ->postJson(route('api.v1.gestion.clientes.store'), datosDeAltaDeCliente())
        ->assertForbidden();

    $this->assertDatabaseCount('clientes', 0);
});

// ============================================================
// Edicion
// ============================================================

it('edita los datos del cliente y de su cuenta', function () {
    $cliente = clienteDePrueba([], true, ['email' => 'antes@correo.test']);

    Sanctum::actingAs(administrativoDeClientes(), ['gestion']);

    $this->putJson(route('api.v1.gestion.clientes.update', $cliente), datosDeAltaDeCliente([
        'nombres'           => 'Marina',
        'apellido'          => 'Ruiz',
        'email'             => 'antes@correo.test',
        'razon_social'      => 'Ruiz y Asociados',
        'telefono'          => '2975550000',
        'tipo_documento_id' => $cliente->tipo_documento_id,
        'nro_doc'           => $cliente->nro_doc,
        'condicion_iva_id'  => $cliente->condicion_iva_id,
    ]))
        ->assertOk()
        ->assertJsonPath('data.razon_social', 'Ruiz y Asociados')
        ->assertJsonPath('data.telefono', '2975550000')
        ->assertJsonPath('data.cuenta.nombres', 'Marina')
        // No le cambiaron el correo, así que la cuenta sigue verificada.
        ->assertJsonPath('data.cuenta.email_verificado', true)
        ->assertJsonPath('correo.cambio', false);
});

it('desverifica la cuenta y pide confirmar la casilla nueva al cambiar el correo', function () {
    Notification::fake();

    $cliente = clienteDePrueba([], true, ['email' => 'antes@correo.test']);

    Sanctum::actingAs(administrativoDeClientes(), ['gestion']);

    $this->putJson(route('api.v1.gestion.clientes.update', $cliente), datosDeAltaDeCliente([
        'email'             => 'despues@correo.test',
        'tipo_documento_id' => $cliente->tipo_documento_id,
        'nro_doc'           => $cliente->nro_doc,
        'condicion_iva_id'  => $cliente->condicion_iva_id,
    ]))
        ->assertOk()
        ->assertJsonPath('data.cuenta.email', 'despues@correo.test')
        ->assertJsonPath('data.cuenta.email_verificado', false)
        ->assertJsonPath('correo.cambio', true);

    // Ya tenía contraseña: lo que corresponde es verificar la casilla nueva.
    Notification::assertSentTo($cliente->user->refresh(), VerifyEmail::class);
});

it('reinvita en lugar de verificar si todavia no eligio su contraseña', function () {
    Notification::fake();

    $cliente = clienteDePrueba([], true, ['password' => null, 'email_verified_at' => null]);

    Sanctum::actingAs(administrativoDeClientes(), ['gestion']);

    $this->putJson(route('api.v1.gestion.clientes.update', $cliente), datosDeAltaDeCliente([
        'email'             => 'casilla-corregida@correo.test',
        'tipo_documento_id' => $cliente->tipo_documento_id,
        'nro_doc'           => $cliente->nro_doc,
        'condicion_iva_id'  => $cliente->condicion_iva_id,
    ]))->assertOk();

    // El enlace viejo apunta a la casilla anterior: mandar una verificación no
    // lo dejaría entrar a ningún lado.
    $usuario = $cliente->user->refresh();

    Notification::assertSentTo($usuario, InvitacionCliente::class);
    Notification::assertNotSentTo($usuario, VerifyEmail::class);
});

it('no cambia el estado desde la edicion aunque venga en el cuerpo', function () {
    $cliente = clienteDePrueba(['activo' => true]);

    Sanctum::actingAs(administrativoDeClientes(), ['gestion']);

    $this->putJson(route('api.v1.gestion.clientes.update', $cliente), datosDeAltaDeCliente([
        'email'             => $cliente->user->email,
        'tipo_documento_id' => $cliente->tipo_documento_id,
        'nro_doc'           => $cliente->nro_doc,
        'condicion_iva_id'  => $cliente->condicion_iva_id,
        // El estado tiene su endpoint y su permiso: mezclarlos dejaría que
        // alguien con 'cliente.editar' diera de baja.
        'activo'            => false,
    ]))
        ->assertOk()
        ->assertJsonPath('data.activo', true);

    expect($cliente->refresh()->activo)->toBeTrue();
});

it('edita solo los datos fiscales del cliente de mostrador sin cuenta', function () {
    $cliente = clienteDePrueba(['razon_social' => 'Mostrador viejo'], conCuenta: false);

    Sanctum::actingAs(administrativoDeClientes(), ['gestion']);

    // Sin cuenta no hay nombre ni apellido de los que derivar la razón social,
    // así que deja de ser opcional. Y no se mandan datos de `users`.
    $this->putJson(route('api.v1.gestion.clientes.update', $cliente), [
        'razon_social'      => 'Mostrador corregido',
        'telefono'          => '2974111111',
        'tipo_documento_id' => $cliente->tipo_documento_id,
        'nro_doc'           => $cliente->nro_doc,
        'condicion_iva_id'  => $cliente->condicion_iva_id,
    ])
        ->assertOk()
        ->assertJsonPath('data.razon_social', 'Mostrador corregido')
        ->assertJsonPath('data.cuenta', null);
});

it('deja corregir el telefono sin revalidar lo fiscal', function () {
    // Cliente cargado como monotributista con DNI, de cuando eso se aceptaba:
    // endurecer la regla no tiene que congelar la ficha. Lo decide
    // ClienteRequest::tocaronLosDatosFiscales(), que ya existia.
    $cliente = clienteDePrueba([
        'tipo_documento_id' => tipoDocumentoPorCodigo(96),
        'condicion_iva_id'  => condicionIvaPorCodigo(6),
    ]);

    Sanctum::actingAs(administrativoDeClientes(), ['gestion']);

    $this->putJson(route('api.v1.gestion.clientes.update', $cliente), [
        'nombres'           => $cliente->user->nombres,
        'apellido'          => $cliente->user->apellido,
        'email'             => $cliente->user->email,
        'razon_social'      => $cliente->razon_social,
        'telefono'          => '2974222222',
        'tipo_documento_id' => $cliente->tipo_documento_id,
        'nro_doc'           => $cliente->nro_doc,
        'condicion_iva_id'  => $cliente->condicion_iva_id,
    ])
        ->assertOk()
        ->assertJsonPath('data.telefono', '2974222222');
});

it('no deja editar al que no tiene el permiso', function () {
    $cliente = clienteDePrueba(['razon_social' => 'Intacto']);

    $empleado = User::factory()->empleado('vendedor')->create();

    $empleado->rol->permisos()->detach(Permiso::where('clave', 'cliente.editar')->value('id'));

    $this->withToken(tokenDeGestion($empleado)->plainTextToken)
        ->putJson(route('api.v1.gestion.clientes.update', $cliente), datosDeAltaDeCliente())
        ->assertForbidden();

    expect($cliente->refresh()->razon_social)->toBe('Intacto');
});

// ============================================================
// Estado
// ============================================================

it('da de baja y reactiva con el mismo endpoint', function () {
    $cliente = clienteDePrueba(['activo' => true]);

    Sanctum::actingAs(administrativoDeClientes(), ['gestion']);

    $this->patchJson(route('api.v1.gestion.clientes.estado', $cliente), ['activo' => false])
        ->assertOk()
        ->assertJsonPath('data.activo', false);

    expect($cliente->refresh()->activo)->toBeFalse();

    $this->patchJson(route('api.v1.gestion.clientes.estado', $cliente), ['activo' => true])
        ->assertOk()
        ->assertJsonPath('data.activo', true);

    expect($cliente->refresh()->activo)->toBeTrue();
});

it('la baja del cliente le inhabilita la cuenta', function () {
    // <b>Esta prueba estaba invertida y fijaba el bug.</b> Afirmaba que la baja
    // NO tocaba users.activo, leyendo las dos columnas como dos decisiones
    // independientes. Lo eran en el codigo —era lo que hacia el controlador web
    // del que salio— pero el resultado era que un cliente dado de baja seguia
    // pudiendo ingresar por la web, por Flutter y por el panel, y comprar
    // online: ninguno de los cinco puntos de acceso lee clientes.activo.
    //
    // Son dos columnas y UNA decision, igual que en empleados: clientes.activo
    // lo saca del mostrador y users.activo le corta el acceso.
    $cliente = clienteDePrueba(['activo' => true]);

    Sanctum::actingAs(administrativoDeClientes(), ['gestion']);

    $this->patchJson(route('api.v1.gestion.clientes.estado', $cliente), ['activo' => false])
        ->assertOk()
        ->assertJsonPath('data.activo', false)
        ->assertJsonPath('data.cuenta.activo', false);

    expect($cliente->refresh()->activo)->toBeFalse()
        ->and($cliente->user->refresh()->activo)->toBeFalse();
});

it('la reactivacion le devuelve la cuenta', function () {
    $cliente = clienteDePrueba(['activo' => true]);

    Sanctum::actingAs(administrativoDeClientes(), ['gestion']);

    $this->patchJson(route('api.v1.gestion.clientes.estado', $cliente), ['activo' => false])
        ->assertOk();

    $this->patchJson(route('api.v1.gestion.clientes.estado', $cliente), ['activo' => true])
        ->assertOk()
        ->assertJsonPath('data.activo', true)
        ->assertJsonPath('data.cuenta.activo', true);

    expect($cliente->refresh()->activo)->toBeTrue()
        ->and($cliente->user->refresh()->activo)->toBeTrue();
});

it('revoca todos los tokens del cliente al darlo de baja', function () {
    $cliente = clienteDePrueba(['activo' => true]);

    // Dos dispositivos: el borrado es de TODOS, no solo del que esta en uso.
    $cliente->user->createToken('Telefono', ['tienda']);
    $cliente->user->createToken('Tablet', ['tienda']);

    expect($cliente->user->tokens()->count())->toBe(2);

    Sanctum::actingAs(administrativoDeClientes(), ['gestion']);

    $this->patchJson(route('api.v1.gestion.clientes.estado', $cliente), ['activo' => false])
        ->assertOk();

    expect($cliente->user->tokens()->count())->toBe(0);
});

it('no le devuelve los tokens al reactivarlo', function () {
    // Revocar es definitivo: el cliente vuelve a tener acceso, pero ingresando
    // de nuevo. Reactivar no puede resucitar una credencial borrada.
    $cliente = clienteDePrueba(['activo' => true]);

    $cliente->user->createToken('Telefono', ['tienda']);

    Sanctum::actingAs(administrativoDeClientes(), ['gestion']);

    $this->patchJson(route('api.v1.gestion.clientes.estado', $cliente), ['activo' => false])->assertOk();
    $this->patchJson(route('api.v1.gestion.clientes.estado', $cliente), ['activo' => true])->assertOk();

    expect($cliente->user->tokens()->count())->toBe(0)
        ->and($cliente->user->refresh()->activo)->toBeTrue();
});

it('da de baja al cliente de mostrador sin cuenta sin romperse', function () {
    // user_id es nullable y el cliente de mostrador viejo existe sin fila en
    // `users`: no tiene credencial que cortar ni tokens que revocar.
    $cliente = clienteDePrueba(['activo' => true], conCuenta: false);

    Sanctum::actingAs(administrativoDeClientes(), ['gestion']);

    $this->patchJson(route('api.v1.gestion.clientes.estado', $cliente), ['activo' => false])
        ->assertOk()
        ->assertJsonPath('data.activo', false)
        ->assertJsonPath('data.cuenta', null);

    expect($cliente->refresh()->activo)->toBeFalse();

    $this->patchJson(route('api.v1.gestion.clientes.estado', $cliente), ['activo' => true])
        ->assertOk()
        ->assertJsonPath('data.activo', true)
        ->assertJsonPath('data.cuenta', null);

    expect($cliente->refresh()->activo)->toBeTrue();
});

it('no le toca la cuenta a ningun otro cliente', function () {
    $victima = clienteDePrueba(['activo' => true]);
    $otro    = clienteDePrueba(['activo' => true]);

    $otro->user->createToken('Telefono', ['tienda']);

    Sanctum::actingAs(administrativoDeClientes(), ['gestion']);

    $this->patchJson(route('api.v1.gestion.clientes.estado', $victima), ['activo' => false])
        ->assertOk();

    expect($otro->refresh()->activo)->toBeTrue()
        ->and($otro->user->refresh()->activo)->toBeTrue()
        ->and($otro->user->tokens()->count())->toBe(1);
});

it('exige el estado al que se quiere llegar', function () {
    $cliente = clienteDePrueba();

    Sanctum::actingAs(administrativoDeClientes(), ['gestion']);

    $this->patchJson(route('api.v1.gestion.clientes.estado', $cliente), [])
        ->assertStatus(422)
        ->assertJsonValidationErrors('activo');
});

it('le niega la baja al vendedor, que puede ver y editar', function () {
    // Es la diferencia mas importante de este modulo contra proveedores: el
    // vendedor y el cajero tienen ver, crear y editar, y NO tienen
    // 'cliente.baja'. No hace falta quitarle nada: la matriz real ya lo dice.
    $cliente = clienteDePrueba(['activo' => true]);

    Sanctum::actingAs(User::factory()->empleado('vendedor')->create(), ['gestion']);

    $this->getJson(route('api.v1.gestion.clientes.show', $cliente))->assertOk();

    $this->patchJson(route('api.v1.gestion.clientes.estado', $cliente), ['activo' => false])
        ->assertForbidden();

    expect($cliente->refresh()->activo)->toBeTrue();
});

it('le niega la baja al cajero por el mismo motivo', function () {
    $cliente = clienteDePrueba(['activo' => true]);

    Sanctum::actingAs(User::factory()->empleado('cajero')->create(), ['gestion']);

    $this->patchJson(route('api.v1.gestion.clientes.estado', $cliente), ['activo' => false])
        ->assertForbidden();
});

// ============================================================
// No hay borrado
// ============================================================

it('no publica ningun DELETE sobre el cliente', function () {
    $cliente = clienteDePrueba();

    Sanctum::actingAs(administrativoDeClientes(), ['gestion']);

    // El administrador tiene los cuatro permisos: si contestara 2xx seria
    // porque la ruta existe, no porque le falte autorizacion. 405 y no 404
    // porque la URL existe para GET y PUT: lo que no existe es el verbo.
    $this->deleteJson(route('api.v1.gestion.clientes.show', $cliente))
        ->assertStatus(405);

    $this->assertDatabaseHas('clientes', ['id' => $cliente->id]);
});

// ============================================================
// Reenvio de la invitacion
// ============================================================

it('reenvia la invitacion al cliente que todavia no activo su cuenta', function () {
    Notification::fake();

    $cliente = clienteDePrueba([], true, ['password' => null, 'email_verified_at' => null]);

    Sanctum::actingAs(administrativoDeClientes(), ['gestion']);

    $this->postJson(route('api.v1.gestion.clientes.invitacion', $cliente))
        ->assertStatus(202)
        ->assertJsonPath('data.email', $cliente->user->email)
        ->assertJsonPath('data.horas_de_vigencia', InvitacionCliente::HORAS_DE_VIGENCIA);

    Notification::assertSentTo($cliente->user, InvitacionCliente::class);
});

it('no reenvia la invitacion si ya eligio su contraseña', function () {
    Notification::fake();

    $cliente = clienteDePrueba();

    Sanctum::actingAs(administrativoDeClientes(), ['gestion']);

    // 422 y no 403: el permiso está, lo que no corresponde es la operación
    // sobre el estado en que está la cuenta. El camino es "¿Olvidaste tu
    // contraseña?", como cualquier usuario.
    $this->postJson(route('api.v1.gestion.clientes.invitacion', $cliente))
        ->assertStatus(422)
        ->assertJsonValidationErrors('estado');

    Notification::assertNothingSent();
});

it('no reenvia la invitacion al cliente de mostrador sin cuenta', function () {
    Notification::fake();

    $cliente = clienteDePrueba([], conCuenta: false);

    Sanctum::actingAs(administrativoDeClientes(), ['gestion']);

    $this->postJson(route('api.v1.gestion.clientes.invitacion', $cliente))
        ->assertStatus(422);

    Notification::assertNothingSent();
});

it('no deja reenviar la invitacion al que no puede editar', function () {
    $cliente = clienteDePrueba([], true, ['password' => null]);

    $empleado = User::factory()->empleado('vendedor')->create();

    $empleado->rol->permisos()->detach(Permiso::where('clave', 'cliente.editar')->value('id'));

    $this->withToken(tokenDeGestion($empleado)->plainTextToken)
        ->postJson(route('api.v1.gestion.clientes.invitacion', $cliente))
        ->assertForbidden();
});

// ============================================================
// Historial de compras
// ============================================================

it('lista las compras del cliente, de la mas nueva a la mas vieja', function () {
    $cliente = clienteDePrueba();

    $vieja = ventaDelCliente($cliente, now()->subMonth());
    $nueva = ventaDelCliente($cliente, now());

    // De otro cliente: no tiene que aparecer.
    ventaDelCliente(clienteDePrueba(), now());

    Sanctum::actingAs(administrativoDeClientes(), ['gestion']);

    $this->getJson(route('api.v1.gestion.clientes.ventas', $cliente))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $nueva->id)
        ->assertJsonPath('data.1.id', $vieja->id)
        ->assertJsonPath('data.0.numero_formateado', $nueva->numeroFormateado())
        ->assertJsonPath('data.0.estado_etiqueta', $nueva->estado->etiqueta());
});

it('deja ver el historial al vendedor, que tiene cliente.ver', function () {
    $cliente = clienteDePrueba();

    ventaDelCliente($cliente, now());

    Sanctum::actingAs(User::factory()->empleado('vendedor')->create(), ['gestion']);

    $this->getJson(route('api.v1.gestion.clientes.ventas', $cliente))
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

// ============================================================
// Direcciones
// ============================================================

it('agrega una direccion al cliente', function () {
    $cliente = clienteDePrueba();

    Sanctum::actingAs(administrativoDeClientes(), ['gestion']);

    $this->postJson(route('api.v1.gestion.clientes.direcciones.store', $cliente), datosDeDireccion())
        ->assertCreated()
        ->assertJsonPath('data.calle', 'Av. Siempreviva')
        ->assertJsonPath('data.tipo', 'fiscal')
        ->assertJsonPath('data.es_predeterminada', true);

    $this->assertDatabaseHas('direcciones', [
        'direccionable_id'   => $cliente->id,
        'direccionable_type' => $cliente->getMorphClass(),
        'calle'              => 'Av. Siempreviva',
    ]);
});

it('no publica el dueño de la direccion', function () {
    $cliente = clienteDePrueba();

    Sanctum::actingAs(administrativoDeClientes(), ['gestion']);

    $respuesta = $this->postJson(
        route('api.v1.gestion.clientes.direcciones.store', $cliente),
        datosDeDireccion()
    )->assertCreated();

    expect($respuesta->json('data'))
        ->not->toHaveKey('direccionable_id')
        ->not->toHaveKey('direccionable_type');
});

it('deja una sola predeterminada por tipo', function () {
    $cliente = clienteDePrueba();

    $primera = $cliente->direcciones()->create(datosDeDireccion(['es_predeterminada' => true]));

    Sanctum::actingAs(administrativoDeClientes(), ['gestion']);

    $this->postJson(route('api.v1.gestion.clientes.direcciones.store', $cliente), datosDeDireccion([
        'calle'             => 'Calle Nueva',
        'es_predeterminada' => true,
    ]))->assertCreated();

    expect($primera->refresh()->es_predeterminada)->toBeFalse();

    // Otro tipo no se pisa: la regla es por dueño Y por tipo.
    $envio = $cliente->direcciones()->create(datosDeDireccion([
        'tipo'              => 'envio',
        'es_predeterminada' => true,
    ]));

    $this->postJson(route('api.v1.gestion.clientes.direcciones.store', $cliente), datosDeDireccion([
        'calle'             => 'Otra fiscal',
        'es_predeterminada' => true,
    ]))->assertCreated();

    expect($envio->refresh()->es_predeterminada)->toBeTrue();
});

it('edita una direccion del cliente', function () {
    $cliente = clienteDePrueba();

    $direccion = $cliente->direcciones()->create(datosDeDireccion());

    Sanctum::actingAs(administrativoDeClientes(), ['gestion']);

    $this->putJson(
        route('api.v1.gestion.clientes.direcciones.update', [$cliente, $direccion]),
        datosDeDireccion(['calle' => 'Avenida Corregida', 'localidad' => 'Comodoro'])
    )
        ->assertOk()
        ->assertJsonPath('data.calle', 'Avenida Corregida')
        ->assertJsonPath('data.localidad', 'Comodoro');
});

it('borra una direccion, que no es un documento', function () {
    $cliente = clienteDePrueba();

    $direccion = $cliente->direcciones()->create(datosDeDireccion());

    Sanctum::actingAs(administrativoDeClientes(), ['gestion']);

    $this->deleteJson(route('api.v1.gestion.clientes.direcciones.destroy', [$cliente, $direccion]))
        ->assertNoContent();

    $this->assertDatabaseMissing('direcciones', ['id' => $direccion->id]);
});

it('exige la direccion requerida completa', function () {
    $cliente = clienteDePrueba();

    Sanctum::actingAs(administrativoDeClientes(), ['gestion']);

    // Mismas reglas que el formulario Blade: el DireccionRequest no se tocó.
    $this->postJson(route('api.v1.gestion.clientes.direcciones.store', $cliente), [
        'tipo' => 'fiscal',
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['calle', 'codigo_postal', 'localidad', 'provincia']);
});

it('no deja tocar la direccion de otro cliente', function () {
    $mio = clienteDePrueba();
    $ajeno = clienteDePrueba();

    $direccionAjena = $ajeno->direcciones()->create(datosDeDireccion());

    Sanctum::actingAs(administrativoDeClientes(), ['gestion']);

    // 404 y no 403: desde la URL pedida ese recurso no existe. Sin esta
    // comprobación un id de la tabla polimórfica alcanzaría para borrar la
    // dirección de cualquiera.
    $this->deleteJson(route('api.v1.gestion.clientes.direcciones.destroy', [$mio, $direccionAjena]))
        ->assertNotFound();

    $this->putJson(
        route('api.v1.gestion.clientes.direcciones.update', [$mio, $direccionAjena]),
        datosDeDireccion()
    )->assertNotFound();

    $this->assertDatabaseHas('direcciones', ['id' => $direccionAjena->id]);
});

it('no deja tocar direcciones al que no puede editar el cliente', function () {
    $cliente = clienteDePrueba();

    $direccion = $cliente->direcciones()->create(datosDeDireccion());

    $empleado = User::factory()->empleado('vendedor')->create();

    $empleado->rol->permisos()->detach(Permiso::where('clave', 'cliente.editar')->value('id'));

    $token = tokenDeGestion($empleado)->plainTextToken;

    $this->withToken($token)
        ->postJson(route('api.v1.gestion.clientes.direcciones.store', $cliente), datosDeDireccion())
        ->assertForbidden();

    $this->withToken($token)
        ->deleteJson(route('api.v1.gestion.clientes.direcciones.destroy', [$cliente, $direccion]))
        ->assertForbidden();

    expect(Direccion::whereKey($direccion->id)->exists())->toBeTrue();
});
