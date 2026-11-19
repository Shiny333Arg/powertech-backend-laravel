<?php

use App\Models\MovimientoStock;
use App\Models\User;
use Database\Seeders\Maestros\AlicuotaIvaSeeder;
use Database\Seeders\Maestros\PermisoSeeder;
use Database\Seeders\Maestros\RolSeeder;

/*
|--------------------------------------------------------------------------
| C.1 — El ajuste de inventario y la carga inicial, ejercitados de verdad
|--------------------------------------------------------------------------
|
| Las dos pantallas respondian <b>500</b> y nadie se habia enterado:
|
|   POST /gestion/stock/ajustes        ErrorException: Undefined array key
|                                      "producto_id"
|   POST /gestion/stock/carga-inicial  Error: Call to undefined method
|                                      StockService::inicial()
|
| <b>Por que no las detectaba nada.</b> `RutasDeStockTest` comprueba que las
| rutas EXISTAN —`Route::has(...)`— y que las vistas no apunten a rutas rotas.
| Eso es lo que tenia que cuidar y lo cuida bien, pero una ruta puede resolver
| perfecto y explotar al invocarse: es el mismo patron que F1 encontro en
| `items.ajustarStock`. `StockFiltrosTest`, por su parte, cubre los filtros del
| kardex, que son de solo lectura.
|
| <b>Por eso estas pruebas POSTEAN.</b> Cada una manda el formulario y despues
| mira la base: el saldo del producto y la fila del movimiento. Una asercion
| sobre el codigo de respuesta sola no alcanzaria —un 302 puede ser tanto el
| exito como el rechazo de validacion—, asi que el estado final es lo que se
| afirma en todos los casos.
|
| `withoutVite()` porque el redirect vuelve a una vista del panel, que compila
| assets: sin eso la prueba exigiria haber corrido `npm run build` y fallaria
| por algo que no tiene que ver con lo que mide.
|
*/

beforeEach(function () {
    $this->seed([RolSeeder::class, PermisoSeeder::class, AlicuotaIvaSeeder::class]);

    $this->withoutVite();
});

/** Tiene `stock.ajustar`; el vendedor y el cajero, no. */
function administrativoDeStock(): User
{
    return User::factory()->empleado('administrativo')->create();
}

// ==================================================================
// Ajuste de inventario
// ==================================================================

it('registra un ajuste positivo y sube el stock físico', function () {
    $empleado = administrativoDeStock();
    $producto = productoDePrueba(['stock_fisico' => 10]);

    comoEmpleado($empleado)
        ->from(route('gestion.stock.show', $producto))
        ->post(route('gestion.stock.ajustar'), [
            'producto_id' => $producto->id,
            'cantidad'    => 5,
            'motivo'      => 'Diferencia de conteo a favor',
        ])
        ->assertRedirect(route('gestion.stock.show', $producto))
        ->assertSessionHas('status')
        ->assertSessionHasNoErrors();

    expect($producto->refresh()->stock_fisico)->toBe(15)
        ->and($producto->stock_disponible)->toBe(15);

    // El movimiento es lo que explica el saldo: tipo, delta, saldo resultante,
    // responsable y motivo.
    $this->assertDatabaseHas('movimientos_stock', [
        'producto_id'             => $producto->id,
        'tipo'                    => 'ajuste',
        'cantidad_fisico'         => 5,
        'stock_fisico_resultante' => 15,
        'usuario_id'              => $empleado->id,
        'motivo'                  => 'Diferencia de conteo a favor',
    ]);
});

it('registra un ajuste negativo y baja el stock físico', function () {
    $producto = productoDePrueba(['stock_fisico' => 10]);

    comoEmpleado(administrativoDeStock())
        ->from(route('gestion.stock.show', $producto))
        ->post(route('gestion.stock.ajustar'), [
            'producto_id' => $producto->id,
            'cantidad'    => -4,
            'motivo'      => 'Rotura en depósito',
        ])
        ->assertSessionHasNoErrors();

    expect($producto->refresh()->stock_fisico)->toBe(6);

    $this->assertDatabaseHas('movimientos_stock', [
        'producto_id'             => $producto->id,
        'tipo'                    => 'ajuste',
        'cantidad_fisico'         => -4,
        'stock_fisico_resultante' => 6,
    ]);
});

it('rechaza un ajuste negativo que dejaría el stock bajo cero', function () {
    // Una faltante mayor al saldo indica que el error esta en un movimiento
    // anterior, no en el ajuste.
    $producto = productoDePrueba(['stock_fisico' => 3]);

    comoEmpleado(administrativoDeStock())
        ->from(route('gestion.stock.show', $producto))
        ->post(route('gestion.stock.ajustar'), [
            'producto_id' => $producto->id,
            'cantidad'    => -10,
            'motivo'      => 'Faltante imposible',
        ])
        ->assertRedirect(route('gestion.stock.show', $producto))
        ->assertSessionHasErrors('cantidad');

    expect($producto->refresh()->stock_fisico)->toBe(3)
        ->and(MovimientoStock::count())->toBe(0);
});

it('exige el motivo del ajuste', function () {
    $producto = productoDePrueba(['stock_fisico' => 10]);

    comoEmpleado(administrativoDeStock())
        ->from(route('gestion.stock.show', $producto))
        ->post(route('gestion.stock.ajustar'), [
            'producto_id' => $producto->id,
            'cantidad'    => 5,
        ])
        ->assertSessionHasErrors('motivo');

    expect($producto->refresh()->stock_fisico)->toBe(10)
        ->and(MovimientoStock::count())->toBe(0);
});

it('rechaza un ajuste de cero unidades', function () {
    $producto = productoDePrueba(['stock_fisico' => 10]);

    comoEmpleado(administrativoDeStock())
        ->from(route('gestion.stock.show', $producto))
        ->post(route('gestion.stock.ajustar'), [
            'producto_id' => $producto->id,
            'cantidad'    => 0,
            'motivo'      => 'Nada',
        ])
        ->assertSessionHasErrors('cantidad');

    expect(MovimientoStock::count())->toBe(0);
});

it('rechaza un ajuste sobre un producto que no existe', function () {
    // Es lo que evita que el id llegue sin validar hasta el findOrFail.
    comoEmpleado(administrativoDeStock())
        ->from(route('gestion.stock.index'))
        ->post(route('gestion.stock.ajustar'), [
            'producto_id' => 9999,
            'cantidad'    => 5,
            'motivo'      => 'Producto inexistente',
        ])
        ->assertSessionHasErrors('producto_id');

    expect(MovimientoStock::count())->toBe(0);
});

it('deja ajustar un producto dado de baja', function () {
    // Un producto fuera de operacion sigue teniendo existencias que pueden
    // tener que corregirse: por eso la regla no filtra por activo.
    $producto = productoDePrueba(['stock_fisico' => 8, 'activo' => false]);

    comoEmpleado(administrativoDeStock())
        ->from(route('gestion.stock.show', $producto))
        ->post(route('gestion.stock.ajustar'), [
            'producto_id' => $producto->id,
            'cantidad'    => -2,
            'motivo'      => 'Merma de producto discontinuado',
        ])
        ->assertSessionHasNoErrors();

    expect($producto->refresh()->stock_fisico)->toBe(6);
});

it('no deja ajustar al vendedor, que solo puede mirar el stock', function () {
    // El vendedor tiene 'stock.ver' y no 'stock.ajustar': es la operacion
    // permitida y la rechazada del modulo.
    $producto = productoDePrueba(['stock_fisico' => 10]);

    comoEmpleado(User::factory()->empleado('vendedor')->create())
        ->post(route('gestion.stock.ajustar'), [
            'producto_id' => $producto->id,
            'cantidad'    => 5,
            'motivo'      => 'Sin permiso',
        ])
        ->assertForbidden();

    expect($producto->refresh()->stock_fisico)->toBe(10)
        ->and(MovimientoStock::count())->toBe(0);
});

it('no deja ajustar al cajero', function () {
    $producto = productoDePrueba(['stock_fisico' => 10]);

    comoEmpleado(User::factory()->empleado('cajero')->create())
        ->post(route('gestion.stock.ajustar'), [
            'producto_id' => $producto->id,
            'cantidad'    => 5,
            'motivo'      => 'Sin permiso',
        ])
        ->assertForbidden();

    expect(MovimientoStock::count())->toBe(0);
});

// ==================================================================
// Carga inicial del conteo
// ==================================================================

it('carga el conteo inicial como movimiento', function () {
    $empleado = administrativoDeStock();
    $producto = productoDePrueba(['stock_fisico' => 0]);

    comoEmpleado($empleado)
        ->from(route('gestion.stock.show', $producto))
        ->post(route('gestion.stock.carga-inicial'), [
            'conteos' => [
                ['producto_id' => $producto->id, 'cantidad' => 9],
            ],
        ])
        ->assertRedirect(route('gestion.stock.show', $producto))
        ->assertSessionHas('status')
        ->assertSessionHasNoErrors();

    expect($producto->refresh()->stock_fisico)->toBe(9);

    $this->assertDatabaseHas('movimientos_stock', [
        'producto_id'             => $producto->id,
        'tipo'                    => 'inicial',
        'cantidad_fisico'         => 9,
        'stock_fisico_resultante' => 9,
        'usuario_id'              => $empleado->id,
    ]);
});

it('carga el conteo inicial de varios productos de una vez', function () {
    $uno = productoDePrueba(['codigo' => 'CI-1', 'stock_fisico' => 0]);
    $dos = productoDePrueba(['codigo' => 'CI-2', 'stock_fisico' => 0]);

    comoEmpleado(administrativoDeStock())
        ->from(route('gestion.stock.index'))
        ->post(route('gestion.stock.carga-inicial'), [
            'conteos' => [
                ['producto_id' => $uno->id, 'cantidad' => 4],
                ['producto_id' => $dos->id, 'cantidad' => 6],
            ],
        ])
        ->assertSessionHasNoErrors();

    expect($uno->refresh()->stock_fisico)->toBe(4)
        ->and($dos->refresh()->stock_fisico)->toBe(6)
        ->and(MovimientoStock::where('tipo', 'inicial')->count())->toBe(2);
});

it('omite la carga inicial de un producto que ya tiene histórico', function () {
    // Con movimientos previos ya no es una puesta en marcha: eso es un ajuste,
    // y el ajuste pide motivo.
    $producto = productoDePrueba(['stock_fisico' => 10]);

    comoEmpleado(administrativoDeStock())
        ->from(route('gestion.stock.show', $producto))
        ->post(route('gestion.stock.ajustar'), [
            'producto_id' => $producto->id,
            'cantidad'    => 5,
            'motivo'      => 'Conteo',
        ]);

    comoEmpleado(administrativoDeStock())
        ->from(route('gestion.stock.show', $producto))
        ->post(route('gestion.stock.carga-inicial'), [
            'conteos' => [
                ['producto_id' => $producto->id, 'cantidad' => 100],
            ],
        ])
        ->assertSessionHasNoErrors();

    // El saldo quedo donde lo dejo el ajuste, y no hay movimiento 'inicial'.
    expect($producto->refresh()->stock_fisico)->toBe(15)
        ->and(MovimientoStock::where('tipo', 'inicial')->count())->toBe(0);
});

it('ignora un conteo en cero', function () {
    $producto = productoDePrueba(['stock_fisico' => 0]);

    comoEmpleado(administrativoDeStock())
        ->from(route('gestion.stock.show', $producto))
        ->post(route('gestion.stock.carga-inicial'), [
            'conteos' => [
                ['producto_id' => $producto->id, 'cantidad' => 0],
            ],
        ])
        ->assertSessionHasNoErrors();

    expect(MovimientoStock::count())->toBe(0);
});

it('rechaza una carga inicial sin conteos', function () {
    comoEmpleado(administrativoDeStock())
        ->from(route('gestion.stock.index'))
        ->post(route('gestion.stock.carga-inicial'), ['conteos' => []])
        ->assertSessionHasErrors('conteos');
});

it('no deja cargar el conteo inicial sin el permiso de ajustar', function () {
    $producto = productoDePrueba(['stock_fisico' => 0]);

    comoEmpleado(User::factory()->empleado('vendedor')->create())
        ->post(route('gestion.stock.carga-inicial'), [
            'conteos' => [
                ['producto_id' => $producto->id, 'cantidad' => 9],
            ],
        ])
        ->assertForbidden();

    expect($producto->refresh()->stock_fisico)->toBe(0)
        ->and(MovimientoStock::count())->toBe(0);
});
