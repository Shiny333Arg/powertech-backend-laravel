<?php

use App\Enums\TipoMovimientoStock;
use App\Models\MovimientoStock;
use App\Models\Producto;
use App\Models\User;
use Database\Seeders\Maestros\AlicuotaIvaSeeder;
use Database\Seeders\Maestros\CondicionIvaSeeder;
use Database\Seeders\Maestros\PermisoSeeder;
use Database\Seeders\Maestros\RolSeeder;
use Database\Seeders\Maestros\TipoDocumentoSeeder;

/*
|--------------------------------------------------------------------------
| Filtros del historico de movimientos
|--------------------------------------------------------------------------
|
| <b>Por que existe este archivo.</b> El kardex global
| (`StockController::index`) filtraba por producto, tipo y rango de fechas
| desde el principio. El kardex de un producto (`::show`) mostraba el mismo
| formulario de filtros pero no leia ninguno de los parametros ni recibia
| `$filtros`, asi que los controles volvian siempre vacios y la pantalla
| recargaba sin filtrar.
|
| La correccion compartio las reglas y la cadena de filtros entre las dos
| pantallas. Estas pruebas cubren las dos, porque el riesgo de compartir
| codigo es romper el kardex global al escribir el individual.
|
| <b>Las fechas se fuerzan a mano.</b> `movimientos_stock.created_at` tiene
| DEFAULT current_timestamp y el modelo no maneja timestamps, asi que para
| probar un rango hay que escribir la fecha con `update()` despues de crear
| el movimiento.
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

    $this->withoutVite();

    $this->administrativo = User::factory()->empleado('administrativo')->create();
});

/**
 * Movimiento de stock con tipo y fecha a eleccion.
 *
 * No pasa por StockService a proposito: estas pruebas miden el filtrado de
 * la consulta, no el motor de inventario.
 */
function movimientoDePrueba(
    Producto $producto,
    TipoMovimientoStock $tipo,
    string $fecha,
): MovimientoStock {
    $movimiento = MovimientoStock::create([
        'producto_id' => $producto->id,
        'tipo' => $tipo->value,
        'cantidad_fisico' => 1,
        'cantidad_comprometido' => 0,
        'stock_fisico_resultante' => 1,
        'stock_comprometido_resultante' => 0,
    ]);

    MovimientoStock::query()->whereKey($movimiento->id)->update(['created_at' => $fecha]);

    return $movimiento->fresh();
}

// ==================================================================
// Kardex de un producto
// ==================================================================

test('el kardex de un producto filtra por tipo de movimiento', function () {
    comoEmpleado($this->administrativo);

    $producto = productoDePrueba();

    movimientoDePrueba($producto, TipoMovimientoStock::Inicial, '2026-03-01 10:00:00');
    movimientoDePrueba($producto, TipoMovimientoStock::Ajuste, '2026-03-02 10:00:00');
    movimientoDePrueba($producto, TipoMovimientoStock::Compra, '2026-03-03 10:00:00');

    $respuesta = $this->get(route('gestion.stock.show', $producto).'?tipo=ajuste');

    $respuesta->assertOk();

    expect($respuesta->viewData('movimientos')->pluck('tipo')->all())
        ->toBe([TipoMovimientoStock::Ajuste]);
});

test('el kardex de un producto filtra por rango de fechas', function () {
    comoEmpleado($this->administrativo);

    $producto = productoDePrueba();

    movimientoDePrueba($producto, TipoMovimientoStock::Inicial, '2026-03-01 10:00:00');
    $dentro = movimientoDePrueba($producto, TipoMovimientoStock::Ajuste, '2026-03-15 10:00:00');
    movimientoDePrueba($producto, TipoMovimientoStock::Compra, '2026-04-10 10:00:00');

    $respuesta = $this->get(route('gestion.stock.show', $producto).'?desde=2026-03-10&hasta=2026-03-20');

    $respuesta->assertOk();

    expect($respuesta->viewData('movimientos')->pluck('id')->all())->toBe([$dentro->id]);
});

test('el kardex de un producto combina tipo y fechas', function () {
    comoEmpleado($this->administrativo);

    $producto = productoDePrueba();

    movimientoDePrueba($producto, TipoMovimientoStock::Ajuste, '2026-03-05 10:00:00');
    $buscado = movimientoDePrueba($producto, TipoMovimientoStock::Ajuste, '2026-03-15 10:00:00');
    movimientoDePrueba($producto, TipoMovimientoStock::Compra, '2026-03-16 10:00:00');

    $respuesta = $this->get(route('gestion.stock.show', $producto).'?tipo=ajuste&desde=2026-03-10');

    $respuesta->assertOk();

    expect($respuesta->viewData('movimientos')->pluck('id')->all())->toBe([$buscado->id]);
});

test('el kardex de un producto no mezcla los movimientos de otro', function () {
    comoEmpleado($this->administrativo);

    $producto = productoDePrueba();
    $otro = productoDePrueba(['codigo' => 'OTRO-001']);

    $propio = movimientoDePrueba($producto, TipoMovimientoStock::Ajuste, '2026-03-01 10:00:00');
    movimientoDePrueba($otro, TipoMovimientoStock::Ajuste, '2026-03-01 10:00:00');

    $respuesta = $this->get(route('gestion.stock.show', $producto).'?tipo=ajuste');

    expect($respuesta->viewData('movimientos')->pluck('id')->all())->toBe([$propio->id]);
});

/**
 * `producto_id` no se acepta en esta pantalla: el producto lo fija la ruta.
 * Mandarlo por query string no debe cambiar lo que se ve.
 */
test('el kardex de un producto ignora un producto_id en la query string', function () {
    comoEmpleado($this->administrativo);

    $producto = productoDePrueba();
    $otro = productoDePrueba(['codigo' => 'OTRO-002']);

    $propio = movimientoDePrueba($producto, TipoMovimientoStock::Ajuste, '2026-03-01 10:00:00');
    movimientoDePrueba($otro, TipoMovimientoStock::Ajuste, '2026-03-01 10:00:00');

    $respuesta = $this->get(route('gestion.stock.show', $producto)."?producto_id={$otro->id}");

    $respuesta->assertOk();

    expect($respuesta->viewData('movimientos')->pluck('id')->all())->toBe([$propio->id]);
});

test('el kardex de un producto deja los filtros marcados en el formulario', function () {
    comoEmpleado($this->administrativo);

    $producto = productoDePrueba();

    movimientoDePrueba($producto, TipoMovimientoStock::Ajuste, '2026-03-15 10:00:00');

    $respuesta = $this->get(route('gestion.stock.show', $producto).'?tipo=ajuste&desde=2026-03-10&hasta=2026-03-20');

    $respuesta->assertOk();

    expect($respuesta->viewData('filtros'))->toBe([
        'tipo' => 'ajuste',
        'desde' => '2026-03-10',
        'hasta' => '2026-03-20',
    ]);

    $respuesta->assertSee('value="2026-03-10"', false)
        ->assertSee('value="2026-03-20"', false);
});

test('el kardex de un producto conserva los filtros al paginar', function () {
    comoEmpleado($this->administrativo);

    $producto = productoDePrueba();

    foreach (range(1, 25) as $i) {
        movimientoDePrueba($producto, TipoMovimientoStock::Ajuste, '2026-03-15 10:00:00');
    }

    $respuesta = $this->get(route('gestion.stock.show', $producto).'?tipo=ajuste');

    $respuesta->assertOk();

    $movimientos = $respuesta->viewData('movimientos');

    expect($movimientos->total())->toBe(25)
        ->and($movimientos->count())->toBe(20)
        ->and($movimientos->nextPageUrl())->toContain('tipo=ajuste');
});

test('el kardex de un producto rechaza un rango de fechas invertido', function () {
    comoEmpleado($this->administrativo);

    $producto = productoDePrueba();

    $this->get(route('gestion.stock.show', $producto).'?desde=2026-03-20&hasta=2026-03-10')
        ->assertSessionHasErrors('hasta');
});

test('el cajero no entra al kardex de un producto ni con filtros', function () {
    comoEmpleado(User::factory()->empleado('cajero')->create());

    $producto = productoDePrueba();

    $this->get(route('gestion.stock.show', $producto).'?tipo=ajuste')->assertForbidden();
});

// ==================================================================
// Kardex global: no tiene que haber cambiado
// ==================================================================

test('el kardex global sigue filtrando por producto', function () {
    comoEmpleado($this->administrativo);

    $producto = productoDePrueba();
    $otro = productoDePrueba(['codigo' => 'OTRO-003']);

    $propio = movimientoDePrueba($producto, TipoMovimientoStock::Inicial, '2026-03-01 10:00:00');
    movimientoDePrueba($otro, TipoMovimientoStock::Inicial, '2026-03-01 10:00:00');

    $respuesta = $this->get(route('gestion.stock.index')."?producto_id={$producto->id}");

    $respuesta->assertOk();

    expect($respuesta->viewData('movimientos')->pluck('id')->all())->toBe([$propio->id]);
});

test('el kardex global sigue filtrando por tipo y fechas', function () {
    comoEmpleado($this->administrativo);

    $producto = productoDePrueba();

    movimientoDePrueba($producto, TipoMovimientoStock::Inicial, '2026-03-01 10:00:00');
    $buscado = movimientoDePrueba($producto, TipoMovimientoStock::Ajuste, '2026-03-15 10:00:00');
    movimientoDePrueba($producto, TipoMovimientoStock::Ajuste, '2026-04-20 10:00:00');

    $respuesta = $this->get(route('gestion.stock.index').'?tipo=ajuste&desde=2026-03-10&hasta=2026-03-20');

    $respuesta->assertOk();

    // `validate()` devuelve solo las claves presentes en el request: sin
    // producto_id en la query string, la clave no aparece. La vista lo
    // resuelve con `$filtros['producto_id'] ?? ''`.
    expect($respuesta->viewData('movimientos')->pluck('id')->all())->toBe([$buscado->id])
        ->and($respuesta->viewData('filtros'))->toBe([
            'tipo' => 'ajuste',
            'desde' => '2026-03-10',
            'hasta' => '2026-03-20',
        ]);
});

test('el kardex global conserva los filtros al paginar', function () {
    comoEmpleado($this->administrativo);

    $producto = productoDePrueba();

    foreach (range(1, 25) as $i) {
        movimientoDePrueba($producto, TipoMovimientoStock::Ajuste, '2026-03-15 10:00:00');
    }

    $respuesta = $this->get(route('gestion.stock.index').'?tipo=ajuste');

    expect($respuesta->viewData('movimientos')->nextPageUrl())->toContain('tipo=ajuste');
});

test('el kardex global rechaza un rango de fechas invertido', function () {
    comoEmpleado($this->administrativo);

    $this->get(route('gestion.stock.index').'?desde=2026-03-20&hasta=2026-03-10')
        ->assertSessionHasErrors('hasta');
});
