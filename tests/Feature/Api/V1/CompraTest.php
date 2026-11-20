<?php

use App\Enums\EstadoPago;
use App\Enums\EstadoVenta;
use App\Models\Pago;
use App\Models\User;
use App\Models\Venta;
use Database\Seeders\Maestros\AlicuotaIvaSeeder;
use Database\Seeders\Maestros\CondicionIvaSeeder;
use Database\Seeders\Maestros\RolSeeder;
use Database\Seeders\Maestros\TipoDocumentoSeeder;

/*
|--------------------------------------------------------------------------
| "Mis compras" de la API movil — /api/v1/compras
|--------------------------------------------------------------------------
|
| Las fabricas compartidas estan en tests/Apoyo/fabricas.php. Las de este
| archivo son locales porque arman ventas CON cliente, y la unica que habia
| —ventaEnPreventa()— arma una de mostrador a consumidor final, sin cliente.
|
| <b>El bloque es solo lectura.</b> No hay POST, PATCH ni DELETE que probar:
| el cliente no cancela ni modifica su pedido desde la app. Lo que se prueba
| es que vea lo suyo, que no vea lo ajeno y que un presupuesto no sea una
| compra.
|
| <b>Las dos reglas que mas importan no son de presentacion.</b> Un pedido
| ajeno responde 404 y no 403 —un 403 confirmaria que ese numero existe— y un
| cliente sin ficha en `clientes` recibe la lista vacia, porque un
| where('cliente_id', null) traduce a "is null" y esas son las ventas de
| mostrador de otra gente. Las dos tienen su test.
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

// ------------------------------------------------------------------
// Fabricas locales
// ------------------------------------------------------------------

/** Numero de venta unico, que la columna exige. */
function numeroDeVenta(): int
{
    static $siguiente = 0;

    return 8_000_000 + ++$siguiente;
}

/**
 * Una compra del cliente, con una linea.
 *
 * Por defecto `pagada` y de canal online, que es lo que deja el checkout de
 * la app. `$atributos` permite mover estado, canal o fecha sin repetir todo.
 */
function compraDe(User $user, array $atributos = []): Venta
{
    $venta = Venta::create([
        'numero'       => numeroDeVenta(),
        'cliente_id'   => $user->cliente->id,
        'canal'        => 'online',
        'estado'       => EstadoVenta::Pagada->value,
        'modo_entrega' => 'retiro',
        'fecha'        => now(),
        'subtotal'     => 10_000,
        'total'        => 10_000,
        ...$atributos,
    ]);

    $venta->lineas()->create([
        'producto_id'     => productoDePrueba()->id,
        'descripcion'     => 'Producto de prueba',
        'cantidad'        => 2,
        'precio_unitario' => 5_000,
        'alicuota_iva'    => 21,
        'neto'            => 10_000,
        'iva'             => 0,
        'total'           => 10_000,
    ]);

    return $venta->fresh('lineas');
}

/** Un presupuesto del cliente: misma tabla, estado que no es compra. */
function presupuestoDe(User $user): Venta
{
    return compraDe($user, ['estado' => EstadoVenta::Presupuesto->value]);
}

/** Venta de mostrador a consumidor final: sin fila en `clientes`. */
function ventaSinCliente(): Venta
{
    return Venta::create([
        'numero'       => numeroDeVenta(),
        'cliente_id'   => null,
        'canal'        => 'mostrador',
        'estado'       => EstadoVenta::Pagada->value,
        'modo_entrega' => 'retiro',
        'fecha'        => now(),
        'subtotal'     => 5_000,
        'total'        => 5_000,
    ]);
}

/** Un pago acreditado, que es el que suma para el estado del cobro. */
function pagoAcreditado(Venta $venta, float $monto): Pago
{
    return $venta->pagos()->create([
        'metodo' => 'efectivo',
        'monto'  => $monto,
        'estado' => EstadoPago::Acreditado->value,
        'fecha'  => now(),
    ]);
}

// ==================================================================
// 1. Acceso
// ==================================================================

test('un visitante no puede ver el historial ni un detalle', function () {
    $this->getJson('/api/v1/compras')->assertUnauthorized();
    $this->getJson('/api/v1/compras/1')->assertUnauthorized();
});

test('un cliente sin el correo verificado si puede ver sus compras', function () {

    // Deliberado: el grupo de compras no lleva 'api.verificado'. Mirar el
    // historial no necesita correo confirmado, armar un pedido si. Un cliente
    // que compro en el mostrador y todavia no confirmo tiene que poder verlo.
    $user = clienteDeTienda(['email_verified_at' => null]);

    $compra = compraDe($user);

    comoCliente($user);

    $this->getJson('/api/v1/compras')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $compra->id);

    $this->getJson("/api/v1/compras/{$compra->id}")->assertOk();
});

// ==================================================================
// 2. Historial — GET /api/v1/compras
// ==================================================================

test('el historial trae los datos que la tarjeta de la pantalla usa', function () {

    $user = clienteDeTienda();

    $compra = compraDe($user);

    comoCliente($user);

    $this->getJson('/api/v1/compras')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $compra->id)
        ->assertJsonPath('data.0.numero', $compra->numero)
        ->assertJsonPath('data.0.numero_formateado', $compra->numeroFormateado())
        ->assertJsonPath('data.0.canal.valor', 'online')
        ->assertJsonPath('data.0.estado.valor', 'pagada')
        ->assertJsonPath('data.0.modo_entrega.valor', 'retiro')
        // Entero y no 10000.0: un decimal exacto se serializa como int.
        ->assertJsonPath('data.0.total', 10000)
        // Lineas, no unidades: la linea es una con cantidad 2.
        ->assertJsonPath('data.0.cantidad_lineas', 1)
        ->assertJsonStructure([
            'data' => [
                ['fecha', 'estado' => ['valor', 'etiqueta', 'tono'],
                    'pago' => ['situacion', 'etiqueta', 'total_acreditado', 'saldo']],
            ],
        ]);
});

test('el historial incluye las compras de mostrador, no solo las de la app', function () {

    $user = clienteDeTienda();

    compraDe($user, ['canal' => 'mostrador', 'estado' => EstadoVenta::Entregada->value]);

    comoCliente($user);

    $this->getJson('/api/v1/compras')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.canal.valor', 'mostrador');
});

test('el historial no trae las compras de otro cliente', function () {

    $propio = clienteDeTienda();
    $ajeno  = clienteDeTienda();

    $mia = compraDe($propio);

    compraDe($ajeno);
    compraDe($ajeno);

    comoCliente($propio);

    $this->getJson('/api/v1/compras')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $mia->id);

    expect(Venta::count())->toBe(3);
});

test('el historial no trae las ventas de mostrador a consumidor final', function () {

    $user = clienteDeTienda();

    ventaSinCliente();
    ventaSinCliente();

    comoCliente($user);

    // Si el filtro fuera where('cliente_id', $cliente?->id) con null, estas
    // dos aparecerian: son las unicas con cliente_id nulo.
    $this->getJson('/api/v1/compras')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

test('el historial llega de la compra mas reciente a la mas vieja', function () {

    $user = clienteDeTienda();

    $vieja    = compraDe($user, ['fecha' => now()->subDays(10)]);
    $reciente = compraDe($user, ['fecha' => now()]);
    $media    = compraDe($user, ['fecha' => now()->subDays(3)]);

    comoCliente($user);

    $this->getJson('/api/v1/compras')
        ->assertOk()
        ->assertJsonPath('data.0.id', $reciente->id)
        ->assertJsonPath('data.1.id', $media->id)
        ->assertJsonPath('data.2.id', $vieja->id);
});

test('el historial pagina de diez en diez', function () {

    $user = clienteDeTienda();

    foreach (range(1, 12) as $i) {
        compraDe($user, ['fecha' => now()->subDays($i)]);
    }

    comoCliente($user);

    $this->getJson('/api/v1/compras')
        ->assertOk()
        ->assertJsonCount(10, 'data')
        ->assertJsonPath('meta.total', 12)
        ->assertJsonPath('meta.per_page', 10)
        ->assertJsonPath('meta.current_page', 1);

    $this->getJson('/api/v1/compras?page=2')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.current_page', 2);
});

test('un cliente sin ficha recibe el historial vacio y no un error', function () {

    // Sin fila en `clientes` no hay ventas posibles. El controlador corta
    // antes de consultar justamente para no traducir el null a "is null".
    $user = User::factory()->create();

    ventaSinCliente();

    comoCliente($user);

    $this->getJson('/api/v1/compras')
        ->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('meta.total', 0);
});

test('el estado del cobro sale de los pagos acreditados', function () {

    $user = clienteDeTienda();

    $aCobrar  = compraDe($user, ['fecha' => now()->subDay()]);
    $aMedias  = compraDe($user, ['fecha' => now()->subDays(2)]);
    $cobrada  = compraDe($user, ['fecha' => now()->subDays(3)]);

    pagoAcreditado($aMedias, 4_000);
    pagoAcreditado($cobrada, 10_000);

    // Un pago pendiente no suma: el estado del cobro mira los acreditados.
    $aCobrar->pagos()->create([
        'metodo' => 'transferencia',
        'monto'  => 10_000,
        'estado' => EstadoPago::Pendiente->value,
        'fecha'  => now(),
    ]);

    comoCliente($user);

    $this->getJson('/api/v1/compras')
        ->assertOk()
        ->assertJsonPath('data.0.pago.situacion', 'pendiente')
        ->assertJsonPath('data.0.pago.total_acreditado', 0)
        ->assertJsonPath('data.0.pago.saldo', 10000)
        ->assertJsonPath('data.1.pago.situacion', 'parcial')
        ->assertJsonPath('data.1.pago.total_acreditado', 4000)
        ->assertJsonPath('data.1.pago.saldo', 6000)
        ->assertJsonPath('data.2.pago.situacion', 'acreditado')
        ->assertJsonPath('data.2.pago.saldo', 0);
});

// ==================================================================
// 3. Detalle — GET /api/v1/compras/{venta}
// ==================================================================

test('un cliente ve el detalle de su compra con lineas, importes y seguimiento', function () {

    $user = clienteDeTienda();

    $compra = compraDe($user);

    comoCliente($user);

    $this->getJson("/api/v1/compras/{$compra->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $compra->id)
        ->assertJsonPath('data.numero_formateado', $compra->numeroFormateado())
        ->assertJsonPath('data.estado.valor', 'pagada')
        ->assertJsonPath('data.importes.total', 10000)
        ->assertJsonCount(1, 'data.lineas')
        ->assertJsonPath('data.lineas.0.cantidad', 2)
        ->assertJsonStructure([
            'data' => [
                'fecha', 'canal', 'en_curso', 'seguimiento', 'desenlace',
                'importes', 'entrega', 'lineas', 'pago', 'facturacion',
            ],
        ]);
});

test('el detalle informa los pagos de la compra', function () {

    $user = clienteDeTienda();

    $compra = compraDe($user);

    pagoAcreditado($compra, 10_000);

    comoCliente($user);

    // El listado va anidado dentro de `pago`, junto al resumen del cobro:
    // no hay una clave `pagos` de primer nivel.
    $this->getJson("/api/v1/compras/{$compra->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data.pago.pagos')
        ->assertJsonPath('data.pago.situacion', 'acreditado')
        ->assertJsonPath('data.pago.saldo', 0)
        ->assertJsonPath('data.pago.pagos.0.monto', 10000)
        ->assertJsonPath('data.pago.pagos.0.estado.valor', 'acreditado');
});

test('el seguimiento viene en orden y con la situacion de cada paso', function () {

    $user = clienteDeTienda();

    $compra = compraDe($user, ['estado' => EstadoVenta::EnPreparacion->value]);

    comoCliente($user);

    $respuesta = $this->getJson("/api/v1/compras/{$compra->id}")->assertOk();

    $pasos = $respuesta->json('data.seguimiento');

    expect($pasos)->toBeArray()->not->toBeEmpty();

    // Se deriva del estado actual, no de un historial de transiciones: cada
    // paso dice si ya ocurrio, no cuando.
    foreach ($pasos as $paso) {
        expect($paso)->toHaveKeys(['etiqueta', 'detalle', 'situacion'])
            ->and($paso['situacion'])->toBeIn(['hecho', 'actual', 'pendiente']);
    }

    // Con la venta en preparacion, el primer paso ya tiene que estar hecho.
    expect($pasos[0]['situacion'])->toBe('hecho');
});

// ==================================================================
// 4. Aislamiento entre clientes
// ==================================================================

test('el detalle de una compra ajena responde 404 y no 403', function () {

    $propio = clienteDeTienda();
    $ajeno  = clienteDeTienda();

    $deOtro = compraDe($ajeno);

    comoCliente($propio);

    // 404 a proposito: un 403 confirmaria que ese numero de pedido existe.
    $respuesta = $this->getJson("/api/v1/compras/{$deOtro->id}");

    $respuesta->assertNotFound();

    expect($respuesta->status())->not->toBe(403);
});

test('el detalle de una venta sin cliente responde 404', function () {

    $user = clienteDeTienda();

    $deMostrador = ventaSinCliente();

    comoCliente($user);

    $this->getJson("/api/v1/compras/{$deMostrador->id}")->assertNotFound();
});

test('un cliente sin ficha no ve el detalle de ninguna compra', function () {

    $comprador = clienteDeTienda();

    $compra = compraDe($comprador);

    $sinFicha = User::factory()->create();

    comoCliente($sinFicha);

    $this->getJson("/api/v1/compras/{$compra->id}")->assertNotFound();
});

test('un id que no existe responde 404', function () {

    $user = clienteDeTienda();

    comoCliente($user);

    $this->getJson('/api/v1/compras/999999')->assertNotFound();
});

// ==================================================================
// 5. Presupuestos: no son compras
// ==================================================================

test('un presupuesto propio no aparece en el historial', function () {

    $user = clienteDeTienda();

    $compra = compraDe($user);

    presupuestoDe($user);

    comoCliente($user);

    $this->getJson('/api/v1/compras')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $compra->id);

    // Existe en la tabla: lo que no es, es una compra.
    expect(Venta::count())->toBe(2);
});

test('el detalle de un presupuesto propio responde 404', function () {

    $user = clienteDeTienda();

    $presupuesto = presupuestoDe($user);

    comoCliente($user);

    // Es del cliente, pero no es una compra: mismo 404 que un pedido ajeno,
    // ni escribiendo la URL a mano.
    $this->getJson("/api/v1/compras/{$presupuesto->id}")->assertNotFound();
});

test('una venta vencida tampoco es una compra', function () {

    $user = clienteDeTienda();

    $vencida = compraDe($user, ['estado' => EstadoVenta::Vencida->value]);

    comoCliente($user);

    // NO_SON_COMPRAS son dos: Presupuesto y Vencida. Un presupuesto que se
    // paso de su validez no reaparece como compra.
    $this->getJson('/api/v1/compras')
        ->assertOk()
        ->assertJsonCount(0, 'data');

    $this->getJson("/api/v1/compras/{$vencida->id}")->assertNotFound();
});

test('un presupuesto confirmado pasa a ser una compra', function () {

    $user = clienteDeTienda();

    $venta = presupuestoDe($user);

    comoCliente($user);

    $this->getJson('/api/v1/compras')->assertJsonCount(0, 'data');

    // La transicion la hace el circuito de gestion; aca solo interesa que
    // "Mis compras" lo empiece a mostrar cuando deja de ser presupuesto.
    $venta->update(['estado' => EstadoVenta::Confirmada->value]);

    $this->getJson('/api/v1/compras')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $venta->id);

    $this->getJson("/api/v1/compras/{$venta->id}")->assertOk();
});
