<?php

use App\Models\MovimientoStock;
use App\Models\User;
use Database\Seeders\Maestros\AlicuotaIvaSeeder;
use Database\Seeders\Maestros\PermisoSeeder;
use Database\Seeders\Maestros\RolSeeder;
use Laravel\Sanctum\Sanctum;

/*
|--------------------------------------------------------------------------
| C.2 — API de stock: kardex, situación por producto y ajustes
|--------------------------------------------------------------------------
|
| Tres endpoints y las tres capas que los protegen: el token (401), el ámbito
| y la ability (403), y el permiso (403).
|
| <b>El reparto de permisos es lo que hace demostrable este módulo:</b> el
| vendedor tiene `stock.ver` y no `stock.ajustar`, así que mira el kardex y no
| puede tocar un saldo; el cajero no tiene ninguno de los dos. Es la operación
| permitida y la rechazada que pide la consigna, sobre el mismo token.
|
| Las pruebas del ajuste no se conforman con el código de respuesta: después de
| cada una se mira el saldo del producto y la fila del movimiento, que es donde
| se vería un ajuste que dice que se registró y no se registró.
|
*/

beforeEach(function () {
    $this->seed([RolSeeder::class, PermisoSeeder::class, AlicuotaIvaSeeder::class]);
});

/** Tiene `stock.ver` y `stock.ajustar`. */
function administrativoDeInventario(): User
{
    return User::factory()->empleado('administrativo')->create();
}

// ==================================================================
// Acceso
// ==================================================================

it('rechaza el kardex sin token', function () {
    $this->getJson(route('api.v1.gestion.stock.movimientos'))->assertUnauthorized();
});

it('rechaza el kardex con un token de la tienda', function () {
    Sanctum::actingAs(User::factory()->cliente()->create(), ['tienda']);

    $this->getJson(route('api.v1.gestion.stock.movimientos'))
        ->assertForbidden()
        ->assertJsonPath('codigo', 'AMBITO_INCORRECTO');
});

it('rechaza el kardex con un token de empleado emitido para la tienda', function () {
    Sanctum::actingAs(administrativoDeInventario(), ['tienda']);

    $this->getJson(route('api.v1.gestion.stock.movimientos'))
        ->assertForbidden()
        ->assertJsonPath('codigo', 'ABILITY_INSUFICIENTE');
});

it('no deja ver el stock al cajero, que no tiene ningún permiso del módulo', function () {
    Sanctum::actingAs(User::factory()->empleado('cajero')->create(), ['gestion']);

    $producto = productoDePrueba();

    $this->getJson(route('api.v1.gestion.stock.movimientos'))->assertForbidden();
    $this->getJson(route('api.v1.gestion.productos.stock', $producto))
        ->assertForbidden()
        ->assertJsonPath('codigo', 'PERMISO_INSUFICIENTE');
});

// ==================================================================
// Kardex global
// ==================================================================

it('lista el kardex con el producto, el usuario y los saldos resultantes', function () {
    $empleado = administrativoDeInventario();

    Sanctum::actingAs($empleado, ['gestion']);

    $producto = productoDePrueba(['codigo' => 'MON-1', 'stock_fisico' => 10]);
    app(App\Services\StockService::class)->ajustar($producto, 5, 'Conteo a favor', $empleado);

    $this->getJson(route('api.v1.gestion.stock.movimientos'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.tipo', 'ajuste')
        ->assertJsonPath('data.0.tipo_etiqueta', 'Ajuste de inventario')
        ->assertJsonPath('data.0.cantidad_fisico', 5)
        ->assertJsonPath('data.0.stock_fisico_resultante', 15)
        ->assertJsonPath('data.0.motivo', 'Conteo a favor')
        ->assertJsonPath('data.0.producto.codigo', 'MON-1')
        ->assertJsonPath('data.0.usuario.id', $empleado->id)
        // El ajuste manual es el único movimiento sin documento de origen:
        // ahí la explicación es el motivo.
        ->assertJsonPath('data.0.origen', null)
        ->assertJsonPath('meta.total', 1);
});

it('ordena el kardex del movimiento más reciente al más viejo', function () {
    $empleado = administrativoDeInventario();

    Sanctum::actingAs($empleado, ['gestion']);

    $producto = productoDePrueba(['stock_fisico' => 10]);
    $stock = app(App\Services\StockService::class);
    $stock->ajustar($producto, 5, 'Primero', $empleado);
    $stock->ajustar($producto->refresh(), -2, 'Segundo', $empleado);

    $this->getJson(route('api.v1.gestion.stock.movimientos'))
        ->assertJsonPath('data.0.motivo', 'Segundo')
        ->assertJsonPath('data.1.motivo', 'Primero');
});

it('filtra el kardex por producto, tipo y fechas', function () {
    $empleado = administrativoDeInventario();

    Sanctum::actingAs($empleado, ['gestion']);

    $uno = productoDePrueba(['codigo' => 'P-1', 'stock_fisico' => 0]);
    $dos = productoDePrueba(['codigo' => 'P-2', 'stock_fisico' => 10]);

    $stock = app(App\Services\StockService::class);
    $stock->cargaInicial($uno, 7, $empleado);          // tipo inicial
    $stock->ajustar($dos, 3, 'Ajuste de P-2', $empleado); // tipo ajuste

    $this->getJson(route('api.v1.gestion.stock.movimientos', ['producto_id' => $uno->id]))
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.producto.codigo', 'P-1')
        ->assertJsonPath('filtros.producto_id', $uno->id);

    $this->getJson(route('api.v1.gestion.stock.movimientos', ['tipo' => 'ajuste']))
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.producto.codigo', 'P-2');

    // Los dos movimientos son de hoy: el rango los toma a los dos.
    $this->getJson(route('api.v1.gestion.stock.movimientos', [
        'desde' => now()->toDateString(),
        'hasta' => now()->toDateString(),
    ]))->assertJsonCount(2, 'data');

    // Y un rango anterior, ninguno.
    $this->getJson(route('api.v1.gestion.stock.movimientos', [
        'hasta' => now()->subDay()->toDateString(),
    ]))->assertJsonCount(0, 'data');
});

it('rechaza un tipo de movimiento que no existe', function () {
    // En el panel el tipo sale de un <select>; acá lo manda el cliente, y un
    // filtro mal escrito tiene que avisar en vez de devolver una lista vacía
    // que parezca «no hay movimientos».
    Sanctum::actingAs(administrativoDeInventario(), ['gestion']);

    $this->getJson(route('api.v1.gestion.stock.movimientos', ['tipo' => 'inventado']))
        ->assertStatus(422)
        ->assertJsonValidationErrors('tipo');
});

it('rechaza un rango de fechas invertido', function () {
    Sanctum::actingAs(administrativoDeInventario(), ['gestion']);

    $this->getJson(route('api.v1.gestion.stock.movimientos', [
        'desde' => '2026-11-10',
        'hasta' => '2026-11-01',
    ]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('hasta');
});

// ==================================================================
// Situacion de un producto
// ==================================================================

it('devuelve la situación de un producto con su histórico', function () {
    $empleado = administrativoDeInventario();

    Sanctum::actingAs($empleado, ['gestion']);

    $producto = productoDePrueba(['codigo' => 'MON-1', 'stock_fisico' => 10, 'punto_reposicion' => 4]);
    app(App\Services\StockService::class)->ajustar($producto, -2, 'Rotura', $empleado);

    $this->getJson(route('api.v1.gestion.productos.stock', $producto))
        ->assertOk()
        // La situación va al lado del histórico, con el mismo resource del
        // catálogo: los saldos se leen igual en las dos pantallas.
        ->assertJsonPath('producto.codigo', 'MON-1')
        ->assertJsonPath('producto.stock_fisico', 8)
        ->assertJsonPath('producto.stock_disponible', 8)
        ->assertJsonPath('producto.punto_reposicion', 4)
        ->assertJsonPath('producto.en_preventa', false)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.cantidad_fisico', -2)
        ->assertJsonPath('data.0.stock_fisico_resultante', 8);
});

it('no mezcla en el histórico los movimientos de otro producto', function () {
    $empleado = administrativoDeInventario();

    Sanctum::actingAs($empleado, ['gestion']);

    $uno = productoDePrueba(['codigo' => 'P-1', 'stock_fisico' => 10]);
    $dos = productoDePrueba(['codigo' => 'P-2', 'stock_fisico' => 10]);

    $stock = app(App\Services\StockService::class);
    $stock->ajustar($uno, 5, 'Del uno', $empleado);
    $stock->ajustar($dos, 5, 'Del dos', $empleado);

    $this->getJson(route('api.v1.gestion.productos.stock', $uno))
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.motivo', 'Del uno');
});

it('ignora un producto_id metido por query string en el histórico de otro', function () {
    // El filtro lo impone la ruta. Aceptarlo por query string permitiría
    // pedir los movimientos de un producto ajeno desde la URL de este.
    $empleado = administrativoDeInventario();

    Sanctum::actingAs($empleado, ['gestion']);

    $uno = productoDePrueba(['codigo' => 'P-1', 'stock_fisico' => 10]);
    $dos = productoDePrueba(['codigo' => 'P-2', 'stock_fisico' => 10]);

    $stock = app(App\Services\StockService::class);
    $stock->ajustar($uno, 5, 'Del uno', $empleado);
    $stock->ajustar($dos, 5, 'Del dos', $empleado);

    $this->getJson(route('api.v1.gestion.productos.stock', ['producto' => $uno, 'producto_id' => $dos->id]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.motivo', 'Del uno');
});

it('deja mirar el stock al vendedor', function () {
    Sanctum::actingAs(User::factory()->empleado('vendedor')->create(), ['gestion']);

    $producto = productoDePrueba();

    $this->getJson(route('api.v1.gestion.stock.movimientos'))->assertOk();
    $this->getJson(route('api.v1.gestion.productos.stock', $producto))->assertOk();
});

// ==================================================================
// Ajustes
// ==================================================================

it('registra un ajuste positivo y devuelve el saldo nuevo', function () {
    $empleado = administrativoDeInventario();

    Sanctum::actingAs($empleado, ['gestion']);

    $producto = productoDePrueba(['stock_fisico' => 10]);

    $this->postJson(route('api.v1.gestion.productos.stock.ajustes', $producto), [
        'cantidad' => 5,
        'motivo'   => 'Diferencia de conteo a favor',
    ])
        ->assertCreated()
        ->assertJsonPath('data.tipo', 'ajuste')
        ->assertJsonPath('data.cantidad_fisico', 5)
        ->assertJsonPath('data.stock_fisico_resultante', 15)
        ->assertJsonPath('data.motivo', 'Diferencia de conteo a favor')
        ->assertJsonPath('data.usuario.id', $empleado->id)
        ->assertJsonPath('producto.stock_fisico', 15)
        ->assertJsonPath('ventas_liberadas', 0);

    expect($producto->refresh()->stock_fisico)->toBe(15);

    $this->assertDatabaseHas('movimientos_stock', [
        'producto_id'             => $producto->id,
        'tipo'                    => 'ajuste',
        'cantidad_fisico'         => 5,
        'stock_fisico_resultante' => 15,
        'usuario_id'              => $empleado->id,
    ]);
});

it('registra un ajuste negativo', function () {
    Sanctum::actingAs(administrativoDeInventario(), ['gestion']);

    $producto = productoDePrueba(['stock_fisico' => 10]);

    $this->postJson(route('api.v1.gestion.productos.stock.ajustes', $producto), [
        'cantidad' => -4,
        'motivo'   => 'Rotura en depósito',
    ])
        ->assertCreated()
        ->assertJsonPath('data.cantidad_fisico', -4)
        ->assertJsonPath('producto.stock_fisico', 6);

    expect($producto->refresh()->stock_fisico)->toBe(6);
});

it('responde 409 cuando el ajuste dejaría el stock bajo cero', function () {
    // Una faltante mayor al saldo indica que el error está en un movimiento
    // anterior, no en el ajuste. 409 y no 422: es un conflicto con el estado
    // actual, no un cuerpo mal formado.
    Sanctum::actingAs(administrativoDeInventario(), ['gestion']);

    $producto = productoDePrueba(['stock_fisico' => 3]);

    $this->postJson(route('api.v1.gestion.productos.stock.ajustes', $producto), [
        'cantidad' => -10,
        'motivo'   => 'Faltante imposible',
    ])
        ->assertStatus(409)
        ->assertJsonStructure(['message']);

    expect($producto->refresh()->stock_fisico)->toBe(3)
        ->and(MovimientoStock::count())->toBe(0);
});

it('exige motivo y cantidad distinta de cero', function () {
    Sanctum::actingAs(administrativoDeInventario(), ['gestion']);

    $producto = productoDePrueba(['stock_fisico' => 10]);

    $this->postJson(route('api.v1.gestion.productos.stock.ajustes', $producto), ['cantidad' => 5])
        ->assertStatus(422)
        ->assertJsonValidationErrors('motivo');

    $this->postJson(route('api.v1.gestion.productos.stock.ajustes', $producto), [
        'cantidad' => 0,
        'motivo'   => 'Nada',
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('cantidad');

    expect(MovimientoStock::count())->toBe(0);
});

it('toma el producto de la ruta y pisa el que venga en el cuerpo', function () {
    // Es la razón por la que el endpoint cuelga del producto: no hay forma de
    // ajustar el stock de uno distinto del que dice la URL.
    Sanctum::actingAs(administrativoDeInventario(), ['gestion']);

    $objetivo = productoDePrueba(['codigo' => 'P-1', 'stock_fisico' => 10]);
    $ajeno    = productoDePrueba(['codigo' => 'P-2', 'stock_fisico' => 10]);

    $this->postJson(route('api.v1.gestion.productos.stock.ajustes', $objetivo), [
        'producto_id' => $ajeno->id,
        'cantidad'    => 5,
        'motivo'      => 'Intento de ajustar otro producto',
    ])->assertCreated();

    expect($objetivo->refresh()->stock_fisico)->toBe(15)
        ->and($ajeno->refresh()->stock_fisico)->toBe(10);
});

it('responde 404 si el producto de la ruta no existe', function () {
    Sanctum::actingAs(administrativoDeInventario(), ['gestion']);

    $this->postJson(route('api.v1.gestion.productos.stock.ajustes', 9999), [
        'cantidad' => 5,
        'motivo'   => 'Producto inexistente',
    ])->assertNotFound();
});

it('deja ajustar un producto dado de baja', function () {
    // Mismo criterio que el panel: un producto fuera de operación sigue
    // teniendo existencias que pueden tener que corregirse.
    Sanctum::actingAs(administrativoDeInventario(), ['gestion']);

    $producto = productoDePrueba(['stock_fisico' => 8, 'activo' => false]);

    $this->postJson(route('api.v1.gestion.productos.stock.ajustes', $producto), [
        'cantidad' => -2,
        'motivo'   => 'Merma de producto discontinuado',
    ])->assertCreated();

    expect($producto->refresh()->stock_fisico)->toBe(6);
});

it('no deja ajustar al vendedor, que solo puede mirar', function () {
    Sanctum::actingAs(User::factory()->empleado('vendedor')->create(), ['gestion']);

    $producto = productoDePrueba(['stock_fisico' => 10]);

    $this->postJson(route('api.v1.gestion.productos.stock.ajustes', $producto), [
        'cantidad' => 5,
        'motivo'   => 'Sin permiso',
    ])
        ->assertForbidden()
        ->assertJsonPath('codigo', 'PERMISO_INSUFICIENTE');

    expect($producto->refresh()->stock_fisico)->toBe(10)
        ->and(MovimientoStock::count())->toBe(0);
});

it('el ajuste queda en el kardex y en el histórico del producto', function () {
    // Cierra el circuito de los tres endpoints: lo que escribe el ajuste es lo
    // que leen las dos consultas.
    Sanctum::actingAs(administrativoDeInventario(), ['gestion']);

    $producto = productoDePrueba(['stock_fisico' => 10]);

    $this->postJson(route('api.v1.gestion.productos.stock.ajustes', $producto), [
        'cantidad' => 7,
        'motivo'   => 'Reconteo anual',
    ])->assertCreated();

    $this->getJson(route('api.v1.gestion.stock.movimientos', ['tipo' => 'ajuste']))
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.motivo', 'Reconteo anual');

    $this->getJson(route('api.v1.gestion.productos.stock', $producto))
        ->assertJsonPath('producto.stock_fisico', 17)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.stock_fisico_resultante', 17);
});
