<?php

use App\Enums\EstadoCarrito;
use App\Models\Carrito;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Services\CarritoService;
use App\Services\CheckoutService;
use App\Services\Pagos\PasarelaPago;
use Database\Seeders\Maestros\AlicuotaIvaSeeder;
use Database\Seeders\Maestros\CondicionIvaSeeder;
use Database\Seeders\Maestros\NumeradorSeeder;
use Database\Seeders\Maestros\PuntoVentaSeeder;
use Database\Seeders\Maestros\RolSeeder;
use Database\Seeders\Maestros\TipoComprobanteSeeder;
use Database\Seeders\Maestros\TipoDocumentoSeeder;
use Tests\Apoyo\PasarelaFalsa;

/*
|--------------------------------------------------------------------------
| Pago desde la app — /api/v1/checkout/pagar y /estado
|--------------------------------------------------------------------------
|
| <b>La pasarela es falsa y hay que decir qué implica.</b> Estas pruebas
| verifican el circuito de PowerTech: que se revalide antes de cobrar, que no
| se creen ventas duplicadas, que el stock no se mueva antes de tiempo, que el
| carrito sobreviva a un pago fallido. Para eso la pasarela tiene que ser
| predecible.
|
| Lo que NO prueban, y sólo se comprueba a mano contra el entorno de prueba de
| Mercado Pago con credenciales TEST-: que el cuerpo de la preferencia sea el
| que Mercado Pago acepta, que la firma HMAC del webhook se verifique bien
| contra una firma real, y que el mapeo de estados de pago sea el correcto.
|
| Ninguna de estas pruebas se corrió contra Mercado Pago.
|
*/

beforeEach(function () {
    $this->seed([
        RolSeeder::class,
        TipoDocumentoSeeder::class,
        CondicionIvaSeeder::class,
        AlicuotaIvaSeeder::class,

        // Los tres de abajo sólo hacen falta en las pruebas que acreditan un
        // pago: la venta nace facturada, y FacturacionService necesita punto
        // de venta, tipos de comprobante y el numerador de la serie. Se
        // siembran siempre porque son baratos y porque olvidarse en una
        // prueba nueva da un error que no se parece en nada a su causa.
        NumeradorSeeder::class,
        PuntoVentaSeeder::class,
        TipoComprobanteSeeder::class,
    ]);

    $this->pasarela = new PasarelaFalsa();
    $this->swap(PasarelaPago::class, $this->pasarela);
});

/** Cliente verificado, con un producto en el carrito y modo de entrega puesto. */
function clienteListoParaPagar(array $atributos = []): User
{
    $user = comoCliente(clienteDeTienda($atributos));

    test()->postJson('/api/v1/carrito', [
        'producto_id' => productoDePrueba(['precio' => 100000])->id,
        'cantidad'    => 2,
    ])->assertCreated();

    test()->patchJson('/api/v1/checkout', ['modo_entrega' => 'retiro'])->assertOk();

    return $user;
}

function carritoDe(User $user): Carrito
{
    return Carrito::query()->where('user_id', $user->id)->latest('id')->sole();
}

// ==================================================================
// Acceso
// ==================================================================

test('un visitante no puede iniciar un pago ni consultar el estado', function () {
    $this->postJson('/api/v1/checkout/pagar', ['confirma' => true])
        ->assertUnauthorized();

    $this->getJson('/api/v1/checkout/estado')->assertUnauthorized();
});

test('un cliente sin verificar no puede pagar', function () {
    comoCliente(clienteDeTienda(['email_verified_at' => null]));

    $this->postJson('/api/v1/checkout/pagar', ['confirma' => true])
        ->assertForbidden()
        ->assertJsonPath('codigo', 'email_no_verificado');
});

// ==================================================================
// 1. Inicio correcto de una operación de pago
// ==================================================================

test('se inicia el pago y se devuelve el link', function () {
    $user = clienteListoParaPagar();

    $this->postJson('/api/v1/checkout/pagar', ['confirma' => true])
        ->assertOk()
        ->assertJsonPath('data.preferencia_id', 'PREF-'.carritoDe($user)->id)
        ->assertJsonPath('data.total', 200000)
        ->assertJsonStructure(['data' => ['preferencia_id', 'url_pago', 'total']]);
});

test('el importe que se le pide cobrar a la pasarela lo calcula el backend', function () {
    clienteListoParaPagar();

    // El cuerpo intenta imponer un total. No existe como regla, así que
    // `validated()` lo descarta y no llega a ningún cálculo.
    $this->postJson('/api/v1/checkout/pagar', [
        'confirma' => true,
        'total'    => 1,
        'precio'   => 1,
    ])->assertOk();

    expect($this->pasarela->preferenciasCreadas)->toHaveCount(1)
        ->and($this->pasarela->preferenciasCreadas[0]['total_centavos'])
        ->toBe(20_000_000);
});

test('la preferencia apunta al rebote movil y no al retorno web', function () {
    clienteListoParaPagar();

    $this->postJson('/api/v1/checkout/pagar', ['confirma' => true])->assertOk();

    expect($this->pasarela->preferenciasCreadas[0]['url_retorno'])
        ->toBe(route('cliente.checkout.retorno-movil'));
});

test('el carrito pasa a en_checkout y guarda la preferencia', function () {
    $user = clienteListoParaPagar();

    $this->postJson('/api/v1/checkout/pagar', ['confirma' => true])->assertOk();

    $carrito = carritoDe($user);

    expect($carrito->estado)->toBe(EstadoCarrito::EnCheckout)
        ->and($carrito->mp_preference_id)->toBe('PREF-'.$carrito->id);
});

test('sin confirmar no se puede pagar', function () {
    clienteListoParaPagar();

    $this->postJson('/api/v1/checkout/pagar', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors('confirma');

    expect($this->pasarela->preferenciasCreadas)->toBeEmpty();
});

test('sin modo de entrega no se puede pagar', function () {
    comoCliente(clienteDeTienda());

    $this->postJson('/api/v1/carrito', [
        'producto_id' => productoDePrueba()->id,
        'cantidad'    => 1,
    ]);

    // No se hizo el PATCH: el webhook no tendría de dónde leerlo.
    $this->postJson('/api/v1/checkout/pagar', ['confirma' => true])
        ->assertStatus(422);

    expect($this->pasarela->preferenciasCreadas)->toBeEmpty();
});

// ==================================================================
// 2. Carrito inválido y producto que dejó de estar disponible
// ==================================================================

test('un carrito vacio no inicia el pago', function () {
    comoCliente(clienteDeTienda());

    $this->patchJson('/api/v1/checkout', ['modo_entrega' => 'retiro'])->assertOk();

    $respuesta = $this->postJson('/api/v1/checkout/pagar', ['confirma' => true])
        ->assertStatus(409);

    expect(collect($respuesta->json('bloqueos'))->pluck('codigo'))
        ->toContain('carrito_vacio')
        ->and($this->pasarela->preferenciasCreadas)->toBeEmpty();
});

test('un producto despublicado entre el resumen y el boton frena el pago', function () {
    comoCliente(clienteDeTienda());

    $producto = productoDePrueba(['nombre' => 'Monitor fantasma']);

    $this->postJson('/api/v1/carrito', [
        'producto_id' => $producto->id,
        'cantidad'    => 1,
    ]);
    $this->patchJson('/api/v1/checkout', ['modo_entrega' => 'retiro'])->assertOk();

    // El cliente ya vio la pantalla con todo en orden.
    $this->getJson('/api/v1/checkout')->assertJsonPath('data.puede_pagar', true);

    $producto->update(['publicado_app' => false]);

    $respuesta = $this->postJson('/api/v1/checkout/pagar', ['confirma' => true])
        ->assertStatus(409);

    expect(collect($respuesta->json('bloqueos'))->pluck('codigo'))
        ->toContain('producto_no_disponible')
        ->and($respuesta->json('message'))->toContain('Monitor fantasma')
        ->and($this->pasarela->preferenciasCreadas)->toBeEmpty();
});

test('un cliente que no puede facturar no inicia el pago', function () {
    comoCliente(User::factory()->create());

    $this->postJson('/api/v1/carrito', [
        'producto_id' => productoDePrueba()->id,
        'cantidad'    => 1,
    ])->assertCreated();
    $this->patchJson('/api/v1/checkout', ['modo_entrega' => 'retiro'])->assertOk();

    $this->postJson('/api/v1/checkout/pagar', ['confirma' => true])
        ->assertStatus(409);

    expect($this->pasarela->preferenciasCreadas)->toBeEmpty();
});

// ==================================================================
// 3. Error al comunicarse con Mercado Pago
// ==================================================================

test('si la pasarela falla se devuelve 502 y el carrito no se toca', function () {
    $user = clienteListoParaPagar();

    $this->pasarela->fallaAlCrear = true;

    $this->postJson('/api/v1/checkout/pagar', ['confirma' => true])
        ->assertStatus(502);

    $carrito = carritoDe($user);

    // Sigue activo y con su línea: el cliente puede volver a intentar.
    expect($carrito->estado)->toBe(EstadoCarrito::Activo)
        ->and($carrito->mp_preference_id)->toBeNull()
        ->and($carrito->lineas()->count())->toBe(1);
});

// ==================================================================
// 4. Stock: iniciar el pago no mueve nada
// ==================================================================

test('iniciar el pago no reserva ni descuenta stock', function () {
    comoCliente(clienteDeTienda());

    $producto = productoDePrueba(['stock_fisico' => 10]);

    $this->postJson('/api/v1/carrito', [
        'producto_id' => $producto->id,
        'cantidad'    => 3,
    ]);
    $this->patchJson('/api/v1/checkout', ['modo_entrega' => 'retiro'])->assertOk();

    $this->postJson('/api/v1/checkout/pagar', ['confirma' => true])->assertOk();

    $producto->refresh();

    expect($producto->stock_fisico)->toBe(10)
        ->and($producto->stock_comprometido)->toBe(0)
        ->and($producto->stock_disponible)->toBe(10);
});

test('iniciar el pago no crea ninguna venta', function () {
    clienteListoParaPagar();

    $this->postJson('/api/v1/checkout/pagar', ['confirma' => true])->assertOk();

    expect(Venta::count())->toBe(0);
});

// ==================================================================
// 5. Estado del pago
// ==================================================================

test('sin ningun intento de pago el estado es sin_pago', function () {
    comoCliente(clienteDeTienda());

    $this->getJson('/api/v1/checkout/estado')
        ->assertOk()
        ->assertJsonPath('data.estado', 'sin_pago')
        ->assertJsonPath('data.venta', null);
});

test('un pago acreditado sin webhook todavia se informa pendiente', function () {
    clienteListoParaPagar();

    $this->postJson('/api/v1/checkout/pagar', ['confirma' => true])->assertOk();

    // Mercado Pago ya aprobó, pero el webhook —que es el que crea la venta—
    // todavía no llegó. Se informa pendiente a propósito: la consulta de
    // estado no crea ventas, y un segundo camino creándolas sin firma es
    // justo lo que no queremos.
    $this->pasarela->estadoDeReferencia = 'approved';

    $this->getJson('/api/v1/checkout/estado')
        ->assertOk()
        ->assertJsonPath('data.estado', 'pendiente')
        ->assertJsonPath('data.venta', null);

    expect(Venta::count())->toBe(0);
});

test('un pago en revision se informa pendiente', function () {
    clienteListoParaPagar();

    $this->postJson('/api/v1/checkout/pagar', ['confirma' => true])->assertOk();

    $this->pasarela->estadoDeReferencia = 'in_process';

    $this->getJson('/api/v1/checkout/estado')
        ->assertJsonPath('data.estado', 'pendiente');
});

test('un pago rechazado se informa rechazado', function () {
    clienteListoParaPagar();

    $this->postJson('/api/v1/checkout/pagar', ['confirma' => true])->assertOk();

    $this->pasarela->estadoDeReferencia = 'rejected';

    $this->getJson('/api/v1/checkout/estado')
        ->assertOk()
        ->assertJsonPath('data.estado', 'rechazado')
        ->assertJsonPath('data.venta', null);

    expect(Venta::count())->toBe(0);
});

test('un checkout abandonado sin intento de pago se informa sin_pago', function () {
    clienteListoParaPagar();

    $this->postJson('/api/v1/checkout/pagar', ['confirma' => true])->assertOk();

    // El cliente abrió Mercado Pago y cerró el navegador: no hay ni un
    // intento registrado contra la referencia.
    $this->pasarela->estadoDeReferencia = null;

    $this->getJson('/api/v1/checkout/estado')
        ->assertOk()
        ->assertJsonPath('data.estado', 'sin_pago');
});

test('si no se puede consultar la pasarela se informa pendiente', function () {
    clienteListoParaPagar();

    $this->postJson('/api/v1/checkout/pagar', ['confirma' => true])->assertOk();

    $this->pasarela->fallaAlConsultarReferencia = true;

    // No poder preguntar no es lo mismo que saber que no hay pago: decir
    // sin_pago acá invitaría a pagar dos veces.
    $this->getJson('/api/v1/checkout/estado')
        ->assertOk()
        ->assertJsonPath('data.estado', 'pendiente');
});

test('el retorno del navegador por si solo no aprueba nada', function () {
    clienteListoParaPagar();

    $this->postJson('/api/v1/checkout/pagar', ['confirma' => true])->assertOk();

    // El cliente vuelve por la página de rebote, que es pública y no decide
    // nada. Ni siquiera escribiendo ?status=approved a mano.
    $this->get('/checkout/retorno-movil?status=approved&payment_id=999')
        ->assertOk();

    // La pasarela no registra ningún pago: lo que diga la query string no
    // cambia nada.
    $this->getJson('/api/v1/checkout/estado')
        ->assertJsonPath('data.estado', 'sin_pago');

    expect(Venta::count())->toBe(0);
});

// ==================================================================
// 6. Confirmación: el webhook es el que crea la venta
// ==================================================================

/** Acredita el pago como lo haría el webhook, con el total real del carrito. */
function acreditarPagoDe(User $user, string $idPago = 'MP-1'): Venta
{
    $carrito = carritoDe($user);

    $resumen = app(CarritoService::class)->resumen($carrito);

    /** @var PasarelaFalsa $pasarela */
    $pasarela = app(PasarelaPago::class);

    $pago = $pasarela->acreditar($carrito, $resumen['total_centavos'], $idPago);

    return app(CheckoutService::class)->confirmar($carrito, $pago);
}

test('acreditado el pago nace la venta y el estado pasa a aprobado', function () {

    $user = clienteListoParaPagar();

    $this->postJson('/api/v1/checkout/pagar', ['confirma' => true])->assertOk();

    $venta = acreditarPagoDe($user);

    comoCliente($user);

    $this->getJson('/api/v1/checkout/estado')
        ->assertOk()
        ->assertJsonPath('data.estado', 'aprobado')
        ->assertJsonPath('data.venta.id', $venta->id)
        ->assertJsonPath('data.venta.total', 200000)
        ->assertJsonStructure([
            'data' => ['venta' => ['id', 'numero', 'estado', 'total', 'mensaje']],
        ]);
});

test('el carrito queda convertido y no se borra', function () {

    $user = clienteListoParaPagar();
    $this->postJson('/api/v1/checkout/pagar', ['confirma' => true])->assertOk();

    $carritoId = carritoDe($user)->id;

    acreditarPagoDe($user);

    $carrito = Carrito::find($carritoId);

    expect($carrito)->not->toBeNull()
        ->and($carrito->estado)->toBe(EstadoCarrito::Convertido)
        // Las líneas sobreviven: son el detalle de lo que se compró.
        ->and($carrito->lineas()->count())->toBe(1);
});

test('despues de comprar el cliente arranca un carrito nuevo y vacio', function () {

    $user = clienteListoParaPagar();
    $this->postJson('/api/v1/checkout/pagar', ['confirma' => true])->assertOk();

    acreditarPagoDe($user);

    comoCliente($user);

    $this->getJson('/api/v1/carrito')
        ->assertOk()
        ->assertJsonPath('data.vacio', true);
});

test('la venta acreditada compromete stock, sin tocar el fisico', function () {

    comoCliente($user = clienteDeTienda());

    $producto = productoDePrueba(['stock_fisico' => 10, 'precio' => 100000]);

    $this->postJson('/api/v1/carrito', [
        'producto_id' => $producto->id,
        'cantidad'    => 3,
    ]);
    $this->patchJson('/api/v1/checkout', ['modo_entrega' => 'retiro'])->assertOk();
    $this->postJson('/api/v1/checkout/pagar', ['confirma' => true])->assertOk();

    acreditarPagoDe($user);

    $producto->refresh();

    // Pagar no mueve mercadería: el físico baja cuando el cliente retira.
    expect($producto->stock_fisico)->toBe(10)
        ->and($producto->stock_comprometido)->toBe(3)
        ->and($producto->stock_disponible)->toBe(7);
});

// ==================================================================
// 7. Notificaciones duplicadas y ventas duplicadas
// ==================================================================

test('acreditar el mismo pago dos veces no crea dos ventas', function () {

    $user = clienteListoParaPagar();
    $this->postJson('/api/v1/checkout/pagar', ['confirma' => true])->assertOk();

    $primera = acreditarPagoDe($user, 'MP-REPETIDO');

    // Es el caso normal: el webhook que Mercado Pago reintenta.
    $segunda = acreditarPagoDe($user, 'MP-REPETIDO');

    expect($segunda->id)->toBe($primera->id)
        ->and(Venta::count())->toBe(1);
});

test('un reintento del webhook no comprometa stock dos veces', function () {

    comoCliente($user = clienteDeTienda());

    $producto = productoDePrueba(['stock_fisico' => 10]);

    $this->postJson('/api/v1/carrito', [
        'producto_id' => $producto->id,
        'cantidad'    => 2,
    ]);
    $this->patchJson('/api/v1/checkout', ['modo_entrega' => 'retiro'])->assertOk();
    $this->postJson('/api/v1/checkout/pagar', ['confirma' => true])->assertOk();

    acreditarPagoDe($user, 'MP-REPETIDO');
    acreditarPagoDe($user, 'MP-REPETIDO');

    expect($producto->refresh()->stock_comprometido)->toBe(2);
});

// ==================================================================
// 8. El carrito sobrevive a un pago que no prospera
// ==================================================================

test('cancelar el pago deja el carrito intacto y permite reintentar', function () {
    $user = clienteListoParaPagar();

    $this->postJson('/api/v1/checkout/pagar', ['confirma' => true])->assertOk();

    // El cliente cierra el navegador sin pagar. No llega ningún webhook.
    expect(Venta::count())->toBe(0);

    comoCliente($user);

    // El carrito sigue completo: `abiertoDe()` acepta en_checkout.
    $this->getJson('/api/v1/carrito')
        ->assertOk()
        ->assertJsonPath('data.vacio', false)
        ->assertJsonPath('data.cantidad_items', 2);

    // Y se puede pagar de nuevo.
    $this->postJson('/api/v1/checkout/pagar', ['confirma' => true])->assertOk();

    expect($this->pasarela->preferenciasCreadas)->toHaveCount(2);
});

test('tocar el carrito despues de un pago abandonado lo devuelve a activo', function () {
    $user = clienteListoParaPagar();

    $this->postJson('/api/v1/checkout/pagar', ['confirma' => true])->assertOk();

    expect(carritoDe($user)->estado)->toBe(EstadoCarrito::EnCheckout);

    // Seguir comprando reabre el carrito, que es lo que hace volverAComprar().
    $this->postJson('/api/v1/carrito', [
        'producto_id' => productoDePrueba()->id,
        'cantidad'    => 1,
    ])->assertCreated();

    expect(carritoDe($user)->estado)->toBe(EstadoCarrito::Activo);

    // Y el estado del pago deja de estar en curso: no hay nada que esperar.
    $this->getJson('/api/v1/checkout/estado')
        ->assertJsonPath('data.estado', 'sin_pago');
});

// ==================================================================
// 9. Aislamiento entre clientes
// ==================================================================

test('el estado del pago es el propio, no el de otro cliente', function () {

    $comprador = clienteListoParaPagar();
    $this->postJson('/api/v1/checkout/pagar', ['confirma' => true])->assertOk();
    acreditarPagoDe($comprador);

    comoCliente(clienteDeTienda());

    $this->getJson('/api/v1/checkout/estado')
        ->assertOk()
        ->assertJsonPath('data.estado', 'sin_pago')
        ->assertJsonPath('data.venta', null);
});

// ==================================================================
// 10. La página de rebote
// ==================================================================

test('la pagina de rebote es publica y no exige sesion', function () {
    $this->get('/checkout/retorno-movil')
        ->assertOk()
        ->assertSee('PowerTech', false);
});

test('la pagina de rebote adapta el texto sin decidir nada', function () {
    $this->get('/checkout/retorno-movil?status=rejected')
        ->assertOk()
        ->assertSee('no se completó', false);

    expect(Venta::count())->toBe(0);
});

// ==================================================================
// 11. El estado del pago sobrevive al carrito nuevo (regresión F8.3)
// ==================================================================

/*
| El bug: `estado()` resolvía el intento con el ÚLTIMO carrito del usuario. Al
| confirmarse una compra el carrito queda `convertido`, y la primera llamada
| que resuelve el carrito abierto —`GET /carrito` o `GET /checkout`— crea uno
| nuevo y vacío con id mayor y sin preferencia. Desde ese momento la consulta
| contestaba `sin_pago` para una compra pagada y facturada.
|
| El disparador no es un caso raro: la app invalida el carrito justo al
| aprobarse el pago, así que el carrito nuevo aparece solo. Y como la pantalla
| de espera vuelve a consultar en cada `AppLifecycleState.resumed`, el cartel
| de éxito se daba vuelta con sólo cambiar de aplicación y volver.
|
| `carritoDe()` y `acreditarPagoDe()` de más arriba usan `sole()` y
| `latest('id')`, que no sirven con dos carritos en juego: los helpers de este
| bloque son los equivalentes que sí nombran cuál.
*/

/** El carrito del último intento: el que tiene preferencia, no el más nuevo. */
function carritoConPreferenciaDe(User $user): Carrito
{
    return Carrito::query()
        ->where('user_id', $user->id)
        ->whereNotNull('mp_preference_id')
        ->latest('id')
        ->firstOrFail();
}

/** Acredita el pago del carrito indicado, como lo haría el webhook. */
function acreditarCarrito(Carrito $carrito, string $idPago = 'MP-1'): Venta
{
    $resumen = app(CarritoService::class)->resumen($carrito);

    /** @var PasarelaFalsa $pasarela */
    $pasarela = app(PasarelaPago::class);

    $pago = $pasarela->acreditar($carrito, $resumen['total_centavos'], $idPago);

    return app(CheckoutService::class)->confirmar($carrito, $pago);
}

/**
 * Carga un producto y elige el retiro sobre el carrito abierto de ahora.
 *
 * Hace falta volver a elegir el modo de entrega: el carrito nuevo nace con
 * `modo_entrega` en null y `pagar()` lo exige.
 */
function prepararOtroPedido(int $cantidad = 2): void
{
    test()->postJson('/api/v1/carrito', [
        'producto_id' => productoDePrueba(['precio' => 100000])->id,
        'cantidad'    => $cantidad,
    ])->assertCreated();

    test()->patchJson('/api/v1/checkout', ['modo_entrega' => 'retiro'])->assertOk();
}

test('el estado sigue aprobado despues de que la app pide el carrito nuevo', function () {

    $user = clienteListoParaPagar();

    $this->postJson('/api/v1/checkout/pagar', ['confirma' => true])->assertOk();

    $venta = acreditarPagoDe($user);

    comoCliente($user);

    // Esto es exactamente lo que dispara el bug.
    $this->getJson('/api/v1/carrito')
        ->assertOk()
        ->assertJsonPath('data.vacio', true);

    expect(Carrito::query()->where('user_id', $user->id)->count())->toBe(2);

    $this->getJson('/api/v1/checkout/estado')
        ->assertOk()
        ->assertJsonPath('data.estado', 'aprobado')
        ->assertJsonPath('data.venta.id', $venta->id)
        ->assertJsonPath('data.venta.total', 200000);
});

test('el estado sigue aprobado despues de que la app pide el checkout', function () {

    $user = clienteListoParaPagar();

    $this->postJson('/api/v1/checkout/pagar', ['confirma' => true])->assertOk();

    $venta = acreditarPagoDe($user);

    comoCliente($user);

    // El otro camino que crea el carrito nuevo: `index()` también llama a
    // `abiertoDe()`.
    $this->getJson('/api/v1/checkout')->assertOk();

    $this->getJson('/api/v1/checkout/estado')
        ->assertOk()
        ->assertJsonPath('data.estado', 'aprobado')
        ->assertJsonPath('data.venta.id', $venta->id);
});

test('consultar el estado dos veces seguidas no cambia la respuesta', function () {

    $user = clienteListoParaPagar();

    $this->postJson('/api/v1/checkout/pagar', ['confirma' => true])->assertOk();

    $venta = acreditarPagoDe($user);

    comoCliente($user);

    // La pantalla de espera consulta en cada vuelta al frente. Dos consultas
    // seguidas tienen que decir lo mismo, y ninguna puede crear nada.
    foreach (range(1, 3) as $vuelta) {
        $this->getJson('/api/v1/checkout/estado')
            ->assertOk()
            ->assertJsonPath('data.estado', 'aprobado')
            ->assertJsonPath('data.venta.id', $venta->id);
    }

    expect(Venta::count())->toBe(1);
});

test('un segundo pago en curso no se informa con la venta del primero', function () {

    $user = clienteListoParaPagar();

    $this->postJson('/api/v1/checkout/pagar', ['confirma' => true])->assertOk();

    acreditarPagoDe($user);

    comoCliente($user);

    // Segunda compra: carrito nuevo, preferencia nueva y webhook que todavía
    // no llegó. Es el caso que descarta "buscar el último pago del cliente":
    // ese atajo contestaría `aprobado` con la venta vieja.
    prepararOtroPedido();

    $this->postJson('/api/v1/checkout/pagar', ['confirma' => true])->assertOk();

    $this->pasarela->estadoDeReferencia = 'in_process';

    $this->getJson('/api/v1/checkout/estado')
        ->assertOk()
        ->assertJsonPath('data.estado', 'pendiente')
        ->assertJsonPath('data.venta', null);

    expect(Venta::count())->toBe(1);
});

test('un segundo pago rechazado no se informa con la venta del primero', function () {

    $user = clienteListoParaPagar();

    $this->postJson('/api/v1/checkout/pagar', ['confirma' => true])->assertOk();

    acreditarPagoDe($user);

    comoCliente($user);

    prepararOtroPedido();

    $this->postJson('/api/v1/checkout/pagar', ['confirma' => true])->assertOk();

    $this->pasarela->estadoDeReferencia = 'rejected';

    $this->getJson('/api/v1/checkout/estado')
        ->assertOk()
        ->assertJsonPath('data.estado', 'rechazado')
        ->assertJsonPath('data.venta', null);
});

test('con dos compras se informa la ultima y no la anterior', function () {

    $user = clienteListoParaPagar();

    $this->postJson('/api/v1/checkout/pagar', ['confirma' => true])->assertOk();

    $primera = acreditarPagoDe($user, 'MP-PRIMERA');

    comoCliente($user);

    prepararOtroPedido(1);

    $this->postJson('/api/v1/checkout/pagar', ['confirma' => true])->assertOk();

    $segunda = acreditarCarrito(carritoConPreferenciaDe($user), 'MP-SEGUNDA');

    comoCliente($user);

    $this->getJson('/api/v1/checkout/estado')
        ->assertOk()
        ->assertJsonPath('data.estado', 'aprobado')
        ->assertJsonPath('data.venta.id', $segunda->id);

    expect($segunda->id)->not->toBe($primera->id)
        ->and(Venta::count())->toBe(2);
});

test('reintentar el pago sobre el mismo carrito no pierde la venta', function () {

    $user = clienteListoParaPagar();

    $this->postJson('/api/v1/checkout/pagar', ['confirma' => true])->assertOk();

    $carrito = carritoConPreferenciaDe($user);

    // Contra Mercado Pago real, un segundo `pagar()` sobre el mismo carrito
    // devuelve OTRA preferencia y pisa la columna. La pasarela falsa deriva el
    // id del carrito, así que el reemplazo se fuerza acá.
    //
    // Que el emparejamiento aguante no es casualidad: `buscarPago()` devuelve
    // `mpPreferenceId: null` siempre, así que `confirmar()` asienta en el pago
    // la preferencia que el carrito tiene en ese momento —la misma que lee la
    // consulta de estado—.
    $carrito->update(['mp_preference_id' => 'PREF-REEMPLAZADA']);

    $venta = acreditarCarrito($carrito->refresh(), 'MP-REINTENTO');

    comoCliente($user);

    $this->getJson('/api/v1/carrito')->assertOk();

    $this->getJson('/api/v1/checkout/estado')
        ->assertOk()
        ->assertJsonPath('data.estado', 'aprobado')
        ->assertJsonPath('data.venta.id', $venta->id);
});

test('un carrito con la preferencia en blanco no tapa al intento anterior', function () {

    $user = clienteListoParaPagar();

    $this->postJson('/api/v1/checkout/pagar', ['confirma' => true])->assertOk();

    $venta = acreditarPagoDe($user);

    comoCliente($user);

    // `whereNotNull` por sí solo no descarta la cadena vacía, que es lo que
    // `blank()` sí descartaba antes.
    Carrito::create([
        'user_id'          => $user->id,
        'estado'           => EstadoCarrito::Activo,
        'mp_preference_id' => '',
    ]);

    $this->getJson('/api/v1/checkout/estado')
        ->assertOk()
        ->assertJsonPath('data.estado', 'aprobado')
        ->assertJsonPath('data.venta.id', $venta->id);
});

// ==================================================================
// 12. Aislamiento: la venta del intento es del cliente del token
// ==================================================================

test('otro cliente con la misma preferencia no ve la venta ajena', function () {

    $comprador = clienteListoParaPagar();

    $this->postJson('/api/v1/checkout/pagar', ['confirma' => true])->assertOk();

    $venta = acreditarPagoDe($comprador);

    $preferencia = carritoConPreferenciaDe($comprador)->mp_preference_id;

    // Caso defensivo: `carritos.mp_preference_id` no tiene índice único, así
    // que se fuerza la colisión que Mercado Pago no debería producir. Sin
    // acotar el pago al cliente del token, el `latest('id')` traía la venta
    // del otro.
    $otro = clienteDeTienda();

    Carrito::create([
        'user_id'          => $otro->id,
        'estado'           => EstadoCarrito::EnCheckout,
        'mp_preference_id' => $preferencia,
    ]);

    comoCliente($otro);

    $this->pasarela->estadoDeReferencia = null;

    $this->getJson('/api/v1/checkout/estado')
        ->assertOk()
        ->assertJsonPath('data.venta', null)
        ->assertJsonPath('data.estado', 'sin_pago');

    // Y la venta del comprador sigue intacta y visible para él.
    expect(Venta::find($venta->id))->not->toBeNull();

    comoCliente($comprador);

    $this->getJson('/api/v1/checkout/estado')
        ->assertJsonPath('data.venta.id', $venta->id);
});

test('un usuario sin ficha de cliente no recibe la venta de otro', function () {

    $comprador = clienteListoParaPagar();

    $this->postJson('/api/v1/checkout/pagar', ['confirma' => true])->assertOk();

    acreditarPagoDe($comprador);

    $preferencia = carritoConPreferenciaDe($comprador)->mp_preference_id;

    // Sin fila en `clientes` no puede haber venta online: `confirmar()` la
    // exige antes de tocar el pago.
    $sinFicha = User::factory()->create();

    Carrito::create([
        'user_id'          => $sinFicha->id,
        'estado'           => EstadoCarrito::EnCheckout,
        'mp_preference_id' => $preferencia,
    ]);

    comoCliente($sinFicha);

    $this->pasarela->estadoDeReferencia = null;

    $this->getJson('/api/v1/checkout/estado')
        ->assertOk()
        ->assertJsonPath('data.venta', null)
        ->assertJsonPath('data.estado', 'sin_pago');
});
