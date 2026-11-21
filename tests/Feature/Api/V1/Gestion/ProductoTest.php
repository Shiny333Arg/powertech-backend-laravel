<?php

use App\Models\AlicuotaIva;
use App\Models\Categoria;
use App\Models\CondicionIva;
use App\Models\Permiso;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use Database\Seeders\Maestros\AlicuotaIvaSeeder;
use Database\Seeders\Maestros\CondicionIvaSeeder;
use Database\Seeders\Maestros\PermisoSeeder;
use Database\Seeders\Maestros\RolSeeder;
use Database\Seeders\Maestros\TipoDocumentoSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

/*
|--------------------------------------------------------------------------
| F6 — API de gestion: productos
|--------------------------------------------------------------------------
|
| Los seis endpoints, los permisos de cada uno y las reglas de negocio que no
| se reimplementaron sino que vienen de ProductoRequest y del modelo.
|
| La prueba que cierra la etapa es la ultima: <b>se publica un producto con
| token de gestion y se lo consulta desde la tienda</b>. Es lo que pide la
| demostracion de la consigna y lo unico que demuestra de verdad que las dos
| interfaces comen de la misma API.
|
*/

beforeEach(function () {
    $this->seed([
        RolSeeder::class,
        PermisoSeeder::class,
        AlicuotaIvaSeeder::class,
        TipoDocumentoSeeder::class,
        CondicionIvaSeeder::class,
    ]);
});

/** Rol con los cinco permisos del catalogo. */
function administrativoDeProductos(): User
{
    return User::factory()->empleado('administrativo')->create();
}

/** El cuerpo minimo que ProductoRequest acepta para un alta. */
function datosDeAlta(array $cambios = []): array
{
    return array_merge([
        'codigo'              => 'TEC-001',
        'nombre'              => 'Teclado mecánico',
        'marca'               => 'Genérica',
        'categoria_id'        => categoriaDePrueba()->id,
        'alicuota_iva_id'     => AlicuotaIva::query()->value('id'),
        'precio'              => 85000.50,
        'costo'               => 50000,
        'punto_reposicion'    => 3,
        'cantidad_reposicion' => 10,
    ], $cambios);
}

// ============================================================
// Acceso
// ============================================================

it('rechaza el listado de productos sin token', function () {
    $this->getJson(route('api.v1.gestion.productos.index'))
        ->assertUnauthorized();
});

it('rechaza el listado con un token de la tienda', function () {
    Sanctum::actingAs(User::factory()->cliente()->create(), ['tienda']);

    $this->getJson(route('api.v1.gestion.productos.index'))
        ->assertForbidden()
        ->assertJsonPath('codigo', 'AMBITO_INCORRECTO');
});

// ============================================================
// Listado y detalle
// ============================================================

it('lista los productos con los datos que la tienda no ve', function () {
    Sanctum::actingAs(administrativoDeProductos(), ['gestion']);

    productoDePrueba(['nombre' => 'Monitor 24', 'costo' => 70000, 'stock_fisico' => 4]);

    $respuesta = $this->getJson(route('api.v1.gestion.productos.index'));

    $respuesta->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.nombre', 'Monitor 24')
        // Lo que el ProductoResource de la tienda NO expone:
        ->assertJsonPath('data.0.costo', 70000)
        ->assertJsonPath('data.0.stock_fisico', 4)
        ->assertJsonPath('data.0.stock_comprometido', 0)
        ->assertJsonPath('data.0.stock_disponible', 4)
        ->assertJsonPath('data.0.categoria.nombre', 'Monitores')
        ->assertJsonPath('meta.total', 1);
});

it('filtra por búsqueda libre, categoría y estado', function () {
    Sanctum::actingAs(administrativoDeProductos(), ['gestion']);

    $otra = Categoria::create(['nombre' => 'Teclados', 'slug' => 'teclados', 'orden' => 2, 'activo' => true]);

    productoDePrueba(['codigo' => 'MON-1', 'nombre' => 'Monitor 24', 'marca' => 'Acme']);
    productoDePrueba(['codigo' => 'TEC-9', 'nombre' => 'Teclado', 'categoria_id' => $otra->id, 'activo' => false]);

    // Por defecto vienen los dos: es la pantalla desde donde se reactiva.
    $this->getJson(route('api.v1.gestion.productos.index'))->assertJsonCount(2, 'data');

    $this->getJson(route('api.v1.gestion.productos.index', ['q' => 'MON']))
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.codigo', 'MON-1');

    // La busqueda tambien pega contra la marca.
    $this->getJson(route('api.v1.gestion.productos.index', ['q' => 'Acme']))
        ->assertJsonCount(1, 'data');

    $this->getJson(route('api.v1.gestion.productos.index', ['categoria_id' => $otra->id]))
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.codigo', 'TEC-9');

    $this->getJson(route('api.v1.gestion.productos.index', ['estado' => 'inactivos']))
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.codigo', 'TEC-9');
});

it('filtra los dos cortes de stock', function () {
    Sanctum::actingAs(administrativoDeProductos(), ['gestion']);

    productoDePrueba(['codigo' => 'OK-1', 'stock_fisico' => 50, 'punto_reposicion' => 5]);
    productoDePrueba(['codigo' => 'REP-1', 'stock_fisico' => 5, 'punto_reposicion' => 5]);
    productoDePrueba(['codigo' => 'PRE-1', 'stock_fisico' => 0, 'stock_comprometido' => 2]);

    // bajoPuntoReposicion() compara con <=, asi que el que esta justo en el
    // umbral entra. El de preventa tambien: 0 - 2 = -2 disponible.
    $this->getJson(route('api.v1.gestion.productos.index', ['stock' => 'reponer']))
        ->assertJsonCount(2, 'data');

    $respuesta = $this->getJson(route('api.v1.gestion.productos.index', ['stock' => 'preventa']));

    $respuesta->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.codigo', 'PRE-1')
        ->assertJsonPath('data.0.stock_disponible', -2)
        ->assertJsonPath('data.0.en_preventa', true);
});

it('rechaza un filtro de estado que no existe', function () {
    Sanctum::actingAs(administrativoDeProductos(), ['gestion']);

    $this->getJson(route('api.v1.gestion.productos.index', ['estado' => 'cualquiera']))
        ->assertStatus(422)
        ->assertJsonValidationErrors('estado');
});

it('devuelve el detalle de un producto', function () {
    Sanctum::actingAs(administrativoDeProductos(), ['gestion']);

    $producto = productoDePrueba();

    $this->getJson(route('api.v1.gestion.productos.show', $producto))
        ->assertOk()
        ->assertJsonPath('data.id', $producto->id)
        ->assertJsonPath('data.publicado_app', true)
        ->assertJsonPath('data.alicuota_iva.id', $producto->alicuota_iva_id);
});

// ============================================================
// Permisos
// ============================================================

it('deja mirar al vendedor pero no crear ni editar', function () {
    Sanctum::actingAs(User::factory()->empleado('vendedor')->create(), ['gestion']);

    $producto = productoDePrueba();

    $this->getJson(route('api.v1.gestion.productos.index'))->assertOk();

    $this->post(route('api.v1.gestion.productos.store'), datosDeAlta())
        ->assertForbidden()
        ->assertJsonPath('codigo', 'PERMISO_INSUFICIENTE');

    $this->putJson(route('api.v1.gestion.productos.update', $producto), datosDeAlta())
        ->assertForbidden();

    expect(Producto::count())->toBe(1);
});

it('no deja publicar al vendedor', function () {
    // 'producto.publicar' lo tienen el administrador y el administrativo, no
    // el vendedor: es la operacion rechazada por permisos que pide la
    // demostracion de la consigna.
    Sanctum::actingAs(User::factory()->empleado('vendedor')->create(), ['gestion']);

    $producto = productoDePrueba(['publicado_app' => false]);

    $this->patchJson(route('api.v1.gestion.productos.publicacion', $producto), ['publicado_app' => true])
        ->assertForbidden()
        ->assertJsonPath('codigo', 'PERMISO_INSUFICIENTE');

    expect($producto->refresh()->publicado_app)->toBeFalse();
});

it('no deja dar de baja al cajero', function () {
    Sanctum::actingAs(User::factory()->empleado('cajero')->create(), ['gestion']);

    $producto = productoDePrueba();

    $this->patchJson(route('api.v1.gestion.productos.estado', $producto), ['activo' => false])
        ->assertForbidden();

    expect($producto->refresh()->activo)->toBeTrue();
});

// ============================================================
// Alta
// ============================================================

it('crea un producto y lo deja activo', function () {
    Sanctum::actingAs(administrativoDeProductos(), ['gestion']);

    $this->post(route('api.v1.gestion.productos.store'), datosDeAlta())
        ->assertCreated()
        ->assertJsonPath('data.codigo', 'TEC-001')
        ->assertJsonPath('data.precio', 85000.5)
        ->assertJsonPath('data.costo', 50000)
        ->assertJsonPath('data.activo', true)
        // Sin 'publicado_app' en el cuerpo nace sin publicar: publicar es una
        // decision aparte, con su propio permiso.
        ->assertJsonPath('data.publicado_app', false)
        ->assertJsonPath('data.stock_disponible', 0);

    $this->assertDatabaseHas('productos', ['codigo' => 'TEC-001', 'activo' => true]);
});

it('carga el stock inicial como movimiento y no como columna', function () {
    $empleado = administrativoDeProductos();

    Sanctum::actingAs($empleado, ['gestion']);

    $this->post(route('api.v1.gestion.productos.store'), datosDeAlta(['stock_inicial' => 25]))
        ->assertCreated()
        ->assertJsonPath('data.stock_fisico', 25)
        ->assertJsonPath('data.stock_disponible', 25);

    $producto = Producto::where('codigo', 'TEC-001')->sole();

    // El saldo queda respaldado por el historico desde el primer dia.
    $this->assertDatabaseHas('movimientos_stock', [
        'producto_id'             => $producto->id,
        'tipo'                    => 'inicial',
        'cantidad_fisico'         => 25,
        'stock_fisico_resultante' => 25,
        'usuario_id'              => $empleado->id,
    ]);
});

it('rechaza un código repetido', function () {
    Sanctum::actingAs(administrativoDeProductos(), ['gestion']);

    productoDePrueba(['codigo' => 'TEC-001']);

    $this->post(route('api.v1.gestion.productos.store'), datosDeAlta())
        ->assertStatus(422)
        ->assertJsonValidationErrors('codigo');
});

it('aplica las reglas cruzadas de reposición automática', function () {
    // Regla existente de ProductoRequest, que la API hereda sin reescribirla:
    // sin proveedor no hay a quien emitirle la orden, y con cantidad 0 cada
    // venta generaria un pedido nuevo.
    Sanctum::actingAs(administrativoDeProductos(), ['gestion']);

    $this->post(route('api.v1.gestion.productos.store'), datosDeAlta([
        'reposicion_automatica' => true,
        'cantidad_reposicion'   => 0,
    ]))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['reposicion_automatica', 'cantidad_reposicion']);

    $proveedor = Proveedor::create([
        'razon_social'     => 'Mayorista SA',
        'cuit'             => '30711111118',
        'condicion_iva_id' => CondicionIva::query()->value('id'),
        'activo'           => true,
    ]);

    $this->post(route('api.v1.gestion.productos.store'), datosDeAlta([
        'reposicion_automatica' => true,
        'proveedor_id'          => $proveedor->id,
        'cantidad_reposicion'   => 10,
    ]))
        ->assertCreated()
        ->assertJsonPath('data.proveedor.razon_social', 'Mayorista SA');
});

it('no acepta que el alta decida el estado', function () {
    // 'activo' esta prohibited: la baja y el alta van por su endpoint.
    Sanctum::actingAs(administrativoDeProductos(), ['gestion']);

    $this->post(route('api.v1.gestion.productos.store'), datosDeAlta(['activo' => false]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('activo');
});

it('guarda la imagen del alta en el disco público', function () {
    Storage::fake('public');

    Sanctum::actingAs(administrativoDeProductos(), ['gestion']);

    $respuesta = $this->post(route('api.v1.gestion.productos.store'), datosDeAlta([
        'imagen' => UploadedFile::fake()->image('teclado.jpg'),
    ]));

    $respuesta->assertCreated();

    $ruta = Producto::where('codigo', 'TEC-001')->value('imagen');

    expect($ruta)->toStartWith('productos/');
    Storage::disk('public')->assertExists($ruta);

    // La URL que consume la pantalla sale de la ruta relativa.
    $respuesta->assertJsonPath('data.imagen_url', config('app.url').'/storage/'.$ruta);
});

it('rechaza un archivo que no es imagen', function () {
    Storage::fake('public');

    Sanctum::actingAs(administrativoDeProductos(), ['gestion']);

    $this->post(route('api.v1.gestion.productos.store'), datosDeAlta([
        'imagen' => UploadedFile::fake()->create('lista.pdf', 100, 'application/pdf'),
    ]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('imagen');
});

// ============================================================
// Edicion
// ============================================================

it('edita un producto con PUT', function () {
    Sanctum::actingAs(administrativoDeProductos(), ['gestion']);

    $producto = productoDePrueba(['codigo' => 'MON-1', 'precio' => 100000]);

    $this->putJson(route('api.v1.gestion.productos.update', $producto), datosDeAlta([
        'codigo' => 'MON-1',
        'nombre' => 'Monitor 27 curvo',
        'precio' => 123456.78,
    ]))
        ->assertOk()
        ->assertJsonPath('data.nombre', 'Monitor 27 curvo')
        ->assertJsonPath('data.precio', 123456.78);

    expect($producto->refresh()->nombre)->toBe('Monitor 27 curvo');
});

it('no deja corregir el stock desde la edición', function () {
    // El saldo se mueve con movimientos: escribirlo desde el producto
    // dejaria el historico sin explicar.
    Sanctum::actingAs(administrativoDeProductos(), ['gestion']);

    $producto = productoDePrueba(['codigo' => 'MON-1', 'stock_fisico' => 10]);

    $this->putJson(route('api.v1.gestion.productos.update', $producto), datosDeAlta([
        'codigo'        => 'MON-1',
        'stock_inicial' => 99,
    ]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('stock_inicial');

    expect($producto->refresh()->stock_fisico)->toBe(10);
});

it('reemplaza la imagen y borra la anterior', function () {
    Storage::fake('public');

    Sanctum::actingAs(administrativoDeProductos(), ['gestion']);

    $producto = productoDePrueba(['codigo' => 'MON-1']);

    $anterior = UploadedFile::fake()->image('vieja.jpg')->store('productos', 'public');
    $producto->update(['imagen' => $anterior]);

    // multipart con _method=PUT: PHP no parsea el cuerpo multipart de un PUT,
    // asi que el envio con archivo viaja como POST. Es lo que hace el
    // formulario Blade con @method('PUT').
    $this->post(route('api.v1.gestion.productos.update', $producto), datosDeAlta([
        'codigo'  => 'MON-1',
        '_method' => 'PUT',
        'imagen'  => UploadedFile::fake()->image('nueva.jpg'),
    ]))->assertOk();

    $nueva = $producto->refresh()->imagen;

    expect($nueva)->not->toBe($anterior);
    Storage::disk('public')->assertExists($nueva);
    Storage::disk('public')->assertMissing($anterior);
});

// ============================================================
// Estado y publicacion
// ============================================================

it('da de baja y reactiva un producto sin tocar la publicación', function () {
    Sanctum::actingAs(administrativoDeProductos(), ['gestion']);

    $producto = productoDePrueba(['publicado_app' => true]);

    $this->patchJson(route('api.v1.gestion.productos.estado', $producto), ['activo' => false])
        ->assertOk()
        ->assertJsonPath('data.activo', false)
        // La decision de publicacion se conserva por si se reactiva; la
        // vitrina igual lo oculta, porque filtra por activo Y publicado.
        ->assertJsonPath('data.publicado_app', true);

    $this->patchJson(route('api.v1.gestion.productos.estado', $producto), ['activo' => true])
        ->assertOk()
        ->assertJsonPath('data.activo', true);

    expect($producto->refresh()->activo)->toBeTrue();
});

it('publica y despublica un producto', function () {
    Sanctum::actingAs(administrativoDeProductos(), ['gestion']);

    $producto = productoDePrueba(['publicado_app' => false]);

    $this->patchJson(route('api.v1.gestion.productos.publicacion', $producto), ['publicado_app' => true])
        ->assertOk()
        ->assertJsonPath('data.publicado_app', true);

    $this->patchJson(route('api.v1.gestion.productos.publicacion', $producto), ['publicado_app' => false])
        ->assertOk()
        ->assertJsonPath('data.publicado_app', false);

    expect($producto->refresh()->publicado_app)->toBeFalse();
});

/*
 * ------------------------------------------------------------------
 * La publicacion NO se escribe desde la edicion
 * ------------------------------------------------------------------
 *
 * Las dos pruebas que siguen cubren las dos mitades del mismo agujero, que el
 * PUT tenia abierto hasta esta correccion:
 *
 *   - 'producto.editar' alcanzaba para publicar, salteando 'producto.publicar';
 *   - y un PUT que no mandaba el campo despublicaba el producto en silencio,
 *     porque prepareForValidation() lo mete en validated() con valor false.
 *
 * update() ahora descarta 'publicado_app' y el unico camino es
 * PATCH .../publicacion.
 */

it('no deja publicar con un PUT al que solo puede editar', function () {
    // Un administrativo sin 'producto.publicar': conserva 'producto.editar',
    // asi que el PUT se autoriza y responde 200. Lo que no puede es publicar.
    $empleado = administrativoDeProductos();

    $empleado->rol->permisos()->detach(
        Permiso::where('clave', 'producto.publicar')->value('id')
    );

    Sanctum::actingAs($empleado, ['gestion']);

    $producto = productoDePrueba(['codigo' => 'EDI-1', 'publicado_app' => false]);

    $this->putJson(route('api.v1.gestion.productos.update', $producto), datosDeAlta([
        'codigo' => 'EDI-1',
        'nombre' => 'Monitor editado',
        'publicado_app' => true,
    ]))
        ->assertOk()
        ->assertJsonPath('data.nombre', 'Monitor editado')
        ->assertJsonPath('data.publicado_app', false);

    expect($producto->refresh()->publicado_app)->toBeFalse();

    // Y por el camino correcto tampoco, que para eso esta el permiso.
    $this->patchJson(route('api.v1.gestion.productos.publicacion', $producto), ['publicado_app' => true])
        ->assertForbidden();

    expect($producto->refresh()->publicado_app)->toBeFalse();
});

it('el PUT no despublica un producto que no trae la publicación en el cuerpo', function () {
    Sanctum::actingAs(administrativoDeProductos(), ['gestion']);

    $producto = productoDePrueba(['codigo' => 'EDI-2']);
    $producto->update(['publicado_app' => true]);

    // datosDeAlta() no incluye 'publicado_app': es el cuerpo de una edicion
    // que solo quiere cambiar el precio.
    $this->putJson(route('api.v1.gestion.productos.update', $producto), datosDeAlta([
        'codigo' => 'EDI-2',
        'precio' => 111111.11,
    ]))
        ->assertOk()
        ->assertJsonPath('data.precio', 111111.11)
        ->assertJsonPath('data.publicado_app', true);

    expect($producto->refresh()->publicado_app)->toBeTrue();
});

it('no deja publicar un producto dado de baja', function () {
    Sanctum::actingAs(administrativoDeProductos(), ['gestion']);

    $producto = productoDePrueba(['activo' => false, 'publicado_app' => false]);

    $this->patchJson(route('api.v1.gestion.productos.publicacion', $producto), ['publicado_app' => true])
        ->assertStatus(422)
        ->assertJsonPath('message', 'Un producto dado de baja no se puede publicar. Reactivalo primero.');

    expect($producto->refresh()->publicado_app)->toBeFalse();
});

it('deja despublicar un producto dado de baja', function () {
    // Sacar algo de la vitrina nunca puede quedar bloqueado.
    Sanctum::actingAs(administrativoDeProductos(), ['gestion']);

    $producto = productoDePrueba(['activo' => false, 'publicado_app' => true]);

    $this->patchJson(route('api.v1.gestion.productos.publicacion', $producto), ['publicado_app' => false])
        ->assertOk();

    expect($producto->refresh()->publicado_app)->toBeFalse();
});

it('exige el valor de la publicación', function () {
    Sanctum::actingAs(administrativoDeProductos(), ['gestion']);

    $this->patchJson(route('api.v1.gestion.productos.publicacion', productoDePrueba()), [])
        ->assertStatus(422)
        ->assertJsonValidationErrors('publicado_app');
});

// ============================================================
// La demostracion de la consigna
// ============================================================

it('publica desde gestión y el cambio se ve desde la tienda', function () {
    $producto = productoDePrueba(['nombre' => 'Monitor 27', 'publicado_app' => false]);

    $cliente = clienteDeTienda();

    // 1. Con la tienda no se ve: esta sin publicar.
    comoCliente($cliente);
    $this->getJson(route('api.v1.productos.index'))->assertJsonCount(0, 'data');

    // 2. El administrativo lo publica con su token de gestion.
    Sanctum::actingAs(administrativoDeProductos(), ['gestion']);
    $this->patchJson(route('api.v1.gestion.productos.publicacion', $producto), ['publicado_app' => true])
        ->assertOk();

    // 3. El mismo cliente lo ve, con precio, sin que nadie toque la base
    //    por fuera de la API.
    comoCliente($cliente);
    $this->getJson(route('api.v1.productos.index'))
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.nombre', 'Monitor 27')
        ->assertJsonPath('data.0.precio', 100000);

    // 4. Y al despublicarlo desaparece.
    Sanctum::actingAs(administrativoDeProductos(), ['gestion']);
    $this->patchJson(route('api.v1.gestion.productos.publicacion', $producto), ['publicado_app' => false])
        ->assertOk();

    comoCliente($cliente);
    $this->getJson(route('api.v1.productos.index'))->assertJsonCount(0, 'data');
});

it('el cambio de precio hecho desde gestión es el que ve la tienda', function () {
    $producto = productoDePrueba(['codigo' => 'MON-1', 'precio' => 100000]);

    $cliente = clienteDeTienda();

    Sanctum::actingAs(administrativoDeProductos(), ['gestion']);
    $this->putJson(route('api.v1.gestion.productos.update', $producto), datosDeAlta([
        'codigo'        => 'MON-1',
        'nombre'        => $producto->nombre,
        'precio'        => 88000,
        'publicado_app' => true,
    ]))->assertOk();

    comoCliente($cliente);
    $this->getJson(route('api.v1.productos.show', $producto))
        ->assertOk()
        ->assertJsonPath('data.precio', 88000);
});
