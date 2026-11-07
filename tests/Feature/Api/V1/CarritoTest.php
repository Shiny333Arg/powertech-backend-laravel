<?php

use App\Models\AlicuotaIva;
use App\Models\Carrito;
use App\Models\CarritoLinea;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\CondicionIva;
use App\Models\Producto;
use App\Models\Promocion;
use App\Models\TipoDocumento;
use App\Models\User;
use Database\Seeders\Maestros\AlicuotaIvaSeeder;
use Database\Seeders\Maestros\CondicionIvaSeeder;
use Database\Seeders\Maestros\RolSeeder;
use Database\Seeders\Maestros\TipoDocumentoSeeder;
use Laravel\Sanctum\Sanctum;

/*
|--------------------------------------------------------------------------
| Carrito de la API movil — /api/v1/carrito
|--------------------------------------------------------------------------
|
| <b>Las entidades se arman a mano y no con factories.</b> El proyecto solo
| tiene UserFactory, y de los modelos del catalogo ninguno usa el trait
| HasFactory: fabricarlos exigiria tocar Producto, Categoria y Promocion, que
| estan fuera del alcance de esta etapa. Los helpers de abajo cumplen la misma
| funcion sin modificar codigo de produccion.
|
| Los maestros si se siembran: UserFactory busca el rol por nombre y falla sin
| RolSeeder, y productos y clientes tienen FK contra las tablas de referencia.
|
*/

beforeEach(function () {
    $this->seed([
        RolSeeder::class,
        TipoDocumentoSeeder::class,
        CondicionIvaSeeder::class,
        AlicuotaIvaSeeder::class,
    ]);
});

// ==================================================================
// Helpers
// ==================================================================

/** Cliente de tienda, activo y con el correo confirmado. */
function clienteDeTienda(array $atributos = []): User
{
    $user = User::factory()->create($atributos);

    Cliente::create([
        'user_id' => $user->id,
        'razon_social' => 'Cliente de prueba',
        'tipo_documento_id' => TipoDocumento::query()->value('id'),
        'nro_doc' => (string) fake()->unique()->numberBetween(10_000_000, 99_999_999),
        'condicion_iva_id' => CondicionIva::query()->value('id'),
        'activo' => true,
    ]);

    return $user->refresh();
}

/** Autentica con la ability 'tienda', que es la que emite la app. */
function comoCliente(User $user): User
{
    Sanctum::actingAs($user, [config('movil.ability')]);

    return $user;
}

function categoriaDePrueba(): Categoria
{
    return Categoria::firstOrCreate(
        ['slug' => 'monitor'],
        ['nombre' => 'Monitores', 'orden' => 1, 'activo' => true],
    );
}

/**
 * Producto publicado y con stock, salvo que se indique otra cosa.
 *
 * `stock_disponible` es una columna GENERADA: no se puede escribir. Se setea
 * `stock_fisico` y el motor calcula el disponible.
 */
function productoDePrueba(array $atributos = []): Producto
{
    return Producto::create(array_merge([
        'categoria_id' => categoriaDePrueba()->id,
        'alicuota_iva_id' => AlicuotaIva::query()->value('id'),
        'codigo' => 'P-'.fake()->unique()->numberBetween(1000, 9999),
        'nombre' => 'Monitor de prueba',
        'marca' => 'Genérica',
        'precio' => 100000.00,
        'stock_fisico' => 10,
        'publicado_app' => true,
        'activo' => true,
    ], $atributos));
}

/** Promocion vigente del canal online aplicada a un producto. */
function promocionSobre(Producto $producto, array $atributos = []): Promocion
{
    $promocion = Promocion::create(array_merge([
        'nombre' => 'Promo de prueba',
        'tipo' => 'porcentaje',
        'valor' => 10,
        'canal' => 'online',
        'prioridad' => 10,
        'acumulable' => false,
        'vigencia_desde' => now()->subDay(),
        'vigencia_hasta' => now()->addDay(),
        'activo' => true,
        'creado_por' => User::factory()->empleado('administrativo')->create()->id,
    ], $atributos));

    $promocion->productos()->attach($producto->id);

    return $promocion;
}

// ==================================================================
// 1. Un visitante no puede consultar ni modificar un carrito persistente
// ==================================================================

test('un visitante no puede consultar el carrito', function () {
    $this->getJson('/api/v1/carrito')->assertUnauthorized();
});

test('un visitante no puede modificar el carrito', function () {
    $producto = productoDePrueba();

    $this->postJson('/api/v1/carrito', ['producto_id' => $producto->id, 'cantidad' => 1])
        ->assertUnauthorized();

    $this->patchJson('/api/v1/carrito/1', ['cantidad' => 2])->assertUnauthorized();
    $this->deleteJson('/api/v1/carrito/1')->assertUnauthorized();
    $this->deleteJson('/api/v1/carrito')->assertUnauthorized();
});

test('un visitante no deja carritos creados en la base', function () {
    $this->getJson('/api/v1/carrito');

    expect(Carrito::count())->toBe(0);
});

// ==================================================================
// 2. Un cliente autenticado puede consultar su carrito
// ==================================================================

test('un cliente autenticado consulta su carrito vacio', function () {
    comoCliente(clienteDeTienda());

    $this->getJson('/api/v1/carrito')
        ->assertOk()
        ->assertJsonPath('data.vacio', true)
        ->assertJsonPath('data.cantidad_items', 0)
        ->assertJsonPath('data.total', 0)
        ->assertJsonPath('data.puede_comprar', false)
        ->assertJsonCount(0, 'data.lineas');
});

test('el carrito no expone datos internos del cliente', function () {
    comoCliente(clienteDeTienda());

    $respuesta = $this->getJson('/api/v1/carrito')->assertOk()->json('data');

    expect($respuesta)
        ->not->toHaveKey('user_id')
        ->not->toHaveKey('session_token')
        ->not->toHaveKey('mp_preference_id');
});

test('un cliente con el correo sin verificar no puede usar el carrito', function () {
    comoCliente(clienteDeTienda(['email_verified_at' => null]));

    $this->getJson('/api/v1/carrito')
        ->assertForbidden()
        ->assertJsonPath('codigo', 'email_no_verificado');
});

// ==================================================================
// 3. Un cliente puede agregar un producto
// ==================================================================

test('un cliente agrega un producto al carrito', function () {
    comoCliente(clienteDeTienda());

    $producto = productoDePrueba(['precio' => 100000]);

    $this->postJson('/api/v1/carrito', ['producto_id' => $producto->id, 'cantidad' => 2])
        ->assertCreated()
        ->assertJsonPath('data.cantidad_items', 2)
        ->assertJsonPath('data.lineas.0.producto_id', $producto->id)
        ->assertJsonPath('data.lineas.0.cantidad', 2)
        ->assertJsonPath('data.lineas.0.subtotal', 200000)
        ->assertJsonPath('data.total', 200000)
        ->assertJsonPath('data.puede_comprar', true);

    expect(CarritoLinea::count())->toBe(1);
});

test('agregar dos veces el mismo producto suma en una sola linea', function () {
    comoCliente(clienteDeTienda());

    $producto = productoDePrueba();

    $this->postJson('/api/v1/carrito', ['producto_id' => $producto->id, 'cantidad' => 2]);

    $this->postJson('/api/v1/carrito', ['producto_id' => $producto->id, 'cantidad' => 3])
        ->assertCreated()
        ->assertJsonCount(1, 'data.lineas')
        ->assertJsonPath('data.lineas.0.cantidad', 5);
});

// ==================================================================
// 4. Un cliente puede modificar su cantidad
// ==================================================================

test('un cliente cambia la cantidad de una linea', function () {
    comoCliente(clienteDeTienda());

    $producto = productoDePrueba(['precio' => 50000]);

    $linea = CarritoLinea::find(
        $this->postJson('/api/v1/carrito', ['producto_id' => $producto->id, 'cantidad' => 1])
            ->json('data.lineas.0.id')
    );

    $this->patchJson("/api/v1/carrito/{$linea->id}", ['cantidad' => 4])
        ->assertOk()
        ->assertJsonPath('data.lineas.0.cantidad', 4)
        ->assertJsonPath('data.total', 200000);

    expect($linea->refresh()->cantidad)->toBe(4);
});

// ==================================================================
// 5. Un cliente puede eliminar un producto
// ==================================================================

test('un cliente quita una linea del carrito', function () {
    comoCliente(clienteDeTienda());

    $producto = productoDePrueba();

    $lineaId = $this->postJson('/api/v1/carrito', ['producto_id' => $producto->id, 'cantidad' => 1])
        ->json('data.lineas.0.id');

    $this->deleteJson("/api/v1/carrito/{$lineaId}")
        ->assertOk()
        ->assertJsonPath('data.vacio', true)
        ->assertJsonCount(0, 'data.lineas');

    expect(CarritoLinea::find($lineaId))->toBeNull();
});

// ==================================================================
// 6. Un cliente puede vaciar su carrito
// ==================================================================

test('un cliente vacia su carrito y el carrito sobrevive', function () {
    comoCliente(clienteDeTienda());

    $this->postJson('/api/v1/carrito', ['producto_id' => productoDePrueba()->id, 'cantidad' => 1]);
    $this->postJson('/api/v1/carrito', ['producto_id' => productoDePrueba()->id, 'cantidad' => 2]);

    $carritoId = $this->getJson('/api/v1/carrito')->json('data.id');

    $this->deleteJson('/api/v1/carrito')
        ->assertOk()
        ->assertJsonPath('data.id', $carritoId)
        ->assertJsonPath('data.vacio', true)
        ->assertJsonPath('data.total', 0);

    expect(CarritoLinea::count())->toBe(0)
        ->and(Carrito::find($carritoId))->not->toBeNull();
});

test('vaciar un carrito ya vacio no es un error', function () {
    comoCliente(clienteDeTienda());

    $this->deleteJson('/api/v1/carrito')->assertOk();
    $this->deleteJson('/api/v1/carrito')->assertOk()->assertJsonPath('data.vacio', true);
});

// ==================================================================
// 7. Un cliente no puede modificar el carrito de otro cliente
// ==================================================================

test('un cliente no puede tocar la linea de otro cliente', function () {
    $ajeno = clienteDeTienda();

    comoCliente($ajeno);
    $lineaAjena = $this->postJson('/api/v1/carrito', [
        'producto_id' => productoDePrueba()->id,
        'cantidad' => 1,
    ])->json('data.lineas.0.id');

    comoCliente(clienteDeTienda());

    $this->patchJson("/api/v1/carrito/{$lineaAjena}", ['cantidad' => 99])->assertNotFound();
    $this->deleteJson("/api/v1/carrito/{$lineaAjena}")->assertNotFound();

    expect(CarritoLinea::find($lineaAjena)->cantidad)->toBe(1);
});

test('vaciar el carrito propio no toca el de otro cliente', function () {
    $ajeno = clienteDeTienda();

    comoCliente($ajeno);
    $this->postJson('/api/v1/carrito', ['producto_id' => productoDePrueba()->id, 'cantidad' => 1]);

    comoCliente(clienteDeTienda());
    $this->deleteJson('/api/v1/carrito')->assertOk();

    expect(CarritoLinea::count())->toBe(1);
});

// ==================================================================
// 8. No se aceptan cantidades invalidas
// ==================================================================

test('no se aceptan cantidades invalidas al agregar', function (mixed $cantidad) {
    comoCliente(clienteDeTienda());

    $this->postJson('/api/v1/carrito', [
        'producto_id' => productoDePrueba()->id,
        'cantidad' => $cantidad,
    ])->assertStatus(422)->assertJsonValidationErrors('cantidad');

    expect(CarritoLinea::count())->toBe(0);
})->with([
    'cero' => 0,
    'negativa' => -3,
    'arriba' => 100,
    'decimal' => 1.5,
    'texto' => 'dos',
]);

test('no se aceptan cantidades invalidas al actualizar', function () {
    comoCliente(clienteDeTienda());

    $lineaId = $this->postJson('/api/v1/carrito', [
        'producto_id' => productoDePrueba()->id,
        'cantidad' => 2,
    ])->json('data.lineas.0.id');

    $this->patchJson("/api/v1/carrito/{$lineaId}", ['cantidad' => 0])
        ->assertStatus(422)
        ->assertJsonValidationErrors('cantidad');

    expect(CarritoLinea::find($lineaId)->cantidad)->toBe(2);
});

test('agregar sin producto_id es un error de validacion', function () {
    comoCliente(clienteDeTienda());

    $this->postJson('/api/v1/carrito', ['cantidad' => 1])
        ->assertStatus(422)
        ->assertJsonValidationErrors('producto_id');
});

// ==================================================================
// 10. Los cambios de precio y de promociones se reflejan al consultar
// ==================================================================

test('un cambio de precio posterior se refleja al consultar el carrito', function () {
    comoCliente(clienteDeTienda());

    $producto = productoDePrueba(['precio' => 100000]);

    $this->postJson('/api/v1/carrito', ['producto_id' => $producto->id, 'cantidad' => 2])
        ->assertJsonPath('data.total', 200000);

    $producto->update(['precio' => 150000]);

    $this->getJson('/api/v1/carrito')
        ->assertOk()
        ->assertJsonPath('data.lineas.0.precio_unitario', 150000)
        ->assertJsonPath('data.total', 300000);
});

test('una promocion creada despues se refleja al consultar el carrito', function () {
    comoCliente(clienteDeTienda());

    $producto = productoDePrueba(['precio' => 100000]);

    $this->postJson('/api/v1/carrito', ['producto_id' => $producto->id, 'cantidad' => 2])
        ->assertJsonPath('data.total', 200000)
        ->assertJsonPath('data.descuento_total', 0);

    promocionSobre($producto, ['tipo' => 'porcentaje', 'valor' => 10]);

    $this->getJson('/api/v1/carrito')
        ->assertOk()
        ->assertJsonPath('data.lineas.0.precio_unitario', 100000)
        ->assertJsonPath('data.lineas.0.precio_unitario_final', 90000)
        ->assertJsonPath('data.lineas.0.tiene_descuento', true)
        ->assertJsonPath('data.lineas.0.porcentaje_descuento', 10)
        ->assertJsonPath('data.subtotal_lista', 200000)
        ->assertJsonPath('data.descuento_total', 20000)
        ->assertJsonPath('data.total', 180000);
});

test('una promocion vencida deja de descontar', function () {
    comoCliente(clienteDeTienda());

    $producto = productoDePrueba(['precio' => 100000]);
    $promocion = promocionSobre($producto, ['valor' => 25]);

    $this->postJson('/api/v1/carrito', ['producto_id' => $producto->id, 'cantidad' => 1])
        ->assertJsonPath('data.total', 75000);

    $promocion->update(['vigencia_hasta' => now()->subMinute()]);

    $this->getJson('/api/v1/carrito')
        ->assertOk()
        ->assertJsonPath('data.descuento_total', 0)
        ->assertJsonPath('data.total', 100000);
});

test('una promocion del canal mostrador no alcanza al carrito online', function () {
    comoCliente(clienteDeTienda());

    $producto = productoDePrueba(['precio' => 100000]);
    promocionSobre($producto, ['canal' => 'mostrador', 'valor' => 50]);

    $this->postJson('/api/v1/carrito', ['producto_id' => $producto->id, 'cantidad' => 1])
        ->assertJsonPath('data.total', 100000)
        ->assertJsonPath('data.descuento_total', 0);
});

// ==================================================================
// 11. Agregar productos no descuenta ni reserva stock
// ==================================================================

test('agregar al carrito no toca ningun saldo de stock', function () {
    comoCliente(clienteDeTienda());

    $producto = productoDePrueba(['stock_fisico' => 10]);

    $this->postJson('/api/v1/carrito', ['producto_id' => $producto->id, 'cantidad' => 7])
        ->assertCreated();

    $producto->refresh();

    expect($producto->stock_fisico)->toBe(10)
        ->and($producto->stock_comprometido)->toBe(0)
        ->and($producto->stock_disponible)->toBe(10);
});

test('cambiar la cantidad tampoco mueve el stock', function () {
    comoCliente(clienteDeTienda());

    $producto = productoDePrueba(['stock_fisico' => 5]);

    $lineaId = $this->postJson('/api/v1/carrito', [
        'producto_id' => $producto->id,
        'cantidad' => 1,
    ])->json('data.lineas.0.id');

    $this->patchJson("/api/v1/carrito/{$lineaId}", ['cantidad' => 99])->assertOk();

    expect($producto->refresh()->stock_disponible)->toBe(5);
});

// ==================================================================
// 12. Los productos sin stock se manejan segun la regla de preventa
// ==================================================================

test('un producto sin stock se puede agregar y viaja marcado como preventa', function () {
    comoCliente(clienteDeTienda());

    $producto = productoDePrueba(['stock_fisico' => 0, 'precio' => 100000]);

    $this->postJson('/api/v1/carrito', ['producto_id' => $producto->id, 'cantidad' => 3])
        ->assertCreated()
        ->assertJsonPath('data.lineas.0.en_preventa', true)
        ->assertJsonPath('data.lineas.0.unidades_en_preventa', 3)
        ->assertJsonPath('data.lineas.0.stock_disponible', 0)
        ->assertJsonPath('data.tiene_preventa', true)
        // La preventa NO bloquea la compra ni saca la linea del total.
        ->assertJsonPath('data.total', 300000)
        ->assertJsonPath('data.puede_comprar', true);
});

test('una linea parcialmente cubierta informa cuantas unidades van en preventa', function () {
    comoCliente(clienteDeTienda());

    $producto = productoDePrueba(['stock_fisico' => 2]);

    $this->postJson('/api/v1/carrito', ['producto_id' => $producto->id, 'cantidad' => 5])
        ->assertCreated()
        // El PRODUCTO tiene stock: en_preventa es false.
        ->assertJsonPath('data.lineas.0.en_preventa', false)
        // La LINEA pide 3 unidades que no estan en el deposito.
        ->assertJsonPath('data.lineas.0.unidades_en_preventa', 3)
        ->assertJsonPath('data.tiene_preventa', true);
});

test('un producto con stock suficiente no reporta preventa', function () {
    comoCliente(clienteDeTienda());

    $this->postJson('/api/v1/carrito', [
        'producto_id' => productoDePrueba(['stock_fisico' => 10])->id,
        'cantidad' => 2,
    ])
        ->assertCreated()
        ->assertJsonPath('data.lineas.0.en_preventa', false)
        ->assertJsonPath('data.lineas.0.unidades_en_preventa', 0)
        ->assertJsonPath('data.tiene_preventa', false);
});

// ==================================================================
// 13. Los productos inactivos o no publicados no pueden incorporarse
// ==================================================================

test('no se puede agregar un producto que no es de la vitrina', function (array $atributos) {
    comoCliente(clienteDeTienda());

    $this->postJson('/api/v1/carrito', [
        'producto_id' => productoDePrueba($atributos)->id,
        'cantidad' => 1,
    ])->assertStatus(422)->assertJsonValidationErrors('producto_id');

    expect(CarritoLinea::count())->toBe(0);
})->with([
    'dado de baja' => [['activo' => false]],
    'despublicado' => [['publicado_app' => false]],
    'ambas cosas' => [['activo' => false, 'publicado_app' => false]],
]);

test('no se puede agregar un producto inexistente', function () {
    comoCliente(clienteDeTienda());

    $this->postJson('/api/v1/carrito', ['producto_id' => 999999, 'cantidad' => 1])
        ->assertStatus(422)
        ->assertJsonValidationErrors('producto_id');
});

// ==================================================================
// 14. Productos que dejan de estar disponibles despues de agregarse
// ==================================================================

test('un producto despublicado despues queda marcado y fuera del total', function () {
    comoCliente(clienteDeTienda());

    $queda = productoDePrueba(['precio' => 100000]);
    $cae = productoDePrueba(['precio' => 70000]);

    $this->postJson('/api/v1/carrito', ['producto_id' => $queda->id, 'cantidad' => 1]);
    $this->postJson('/api/v1/carrito', ['producto_id' => $cae->id, 'cantidad' => 2]);

    $this->getJson('/api/v1/carrito')->assertJsonPath('data.total', 240000);

    $cae->update(['publicado_app' => false]);

    $respuesta = $this->getJson('/api/v1/carrito')->assertOk();

    $respuesta
        ->assertJsonCount(2, 'data.lineas')
        ->assertJsonPath('data.tiene_no_disponibles', true)
        ->assertJsonPath('data.puede_comprar', false)
        // Solo suma lo que el checkout podria cobrar.
        ->assertJsonPath('data.total', 100000)
        // Las unidades siguen contandose: el cliente las metio y las ve.
        ->assertJsonPath('data.cantidad_items', 3);

    $linea = collect($respuesta->json('data.lineas'))
        ->firstWhere('producto_id', $cae->id);

    expect($linea['disponible'])->toBeFalse()
        ->and($linea['subtotal'])->toBe(0)
        ->and($linea['motivo_no_disponible'])->not->toBeNull();
});

test('una linea no disponible se puede quitar y el carrito vuelve a ser comprable', function () {
    comoCliente(clienteDeTienda());

    $producto = productoDePrueba();

    $lineaId = $this->postJson('/api/v1/carrito', [
        'producto_id' => $producto->id,
        'cantidad' => 1,
    ])->json('data.lineas.0.id');

    $producto->update(['activo' => false]);

    $this->getJson('/api/v1/carrito')->assertJsonPath('data.puede_comprar', false);

    $this->deleteJson("/api/v1/carrito/{$lineaId}")
        ->assertOk()
        ->assertJsonPath('data.tiene_no_disponibles', false)
        ->assertJsonPath('data.vacio', true);
});
