<?php

use App\Enums\EstadoCarrito;
use App\Enums\ModoEntrega;
use App\Models\Carrito;
use App\Models\CondicionIva;
use App\Models\TipoDocumento;
use App\Models\User;
use Database\Seeders\Maestros\AlicuotaIvaSeeder;
use Database\Seeders\Maestros\CondicionIvaSeeder;
use Database\Seeders\Maestros\RolSeeder;
use Database\Seeders\Maestros\TipoDocumentoSeeder;

/*
|--------------------------------------------------------------------------
| Checkout de la API movil — /api/v1/checkout
|--------------------------------------------------------------------------
|
| Las fabricas estan en tests/Apoyo/fabricas.php, compartidas con CarritoTest.
|
| <b>Nada de esto crea una venta y no es un hueco de las pruebas.</b> La
| venta online nace pagada: sin acreditacion de la pasarela no hay pedido.
| Crear la venta es trabajo de CheckoutService::confirmar(), que ya existe y
| lo dispara el webhook de Mercado Pago. Lo que se prueba aca es la antesala.
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

/** Cliente con un producto ya cargado en el carrito. */
function clienteConCarrito(array $atributos = []): User
{
    $user = comoCliente(clienteDeTienda($atributos));

    test()->postJson('/api/v1/carrito', [
        'producto_id' => productoDePrueba()->id,
        'cantidad'    => 1,
    ])->assertCreated();

    return $user;
}

// ==================================================================
// Acceso
// ==================================================================

test('un visitante no puede ver el checkout', function () {
    $this->getJson('/api/v1/checkout')->assertUnauthorized();
    $this->patchJson('/api/v1/checkout', ['modo_entrega' => 'retiro'])
        ->assertUnauthorized();
});

test('un cliente con el correo sin verificar no puede ver el checkout', function () {
    comoCliente(clienteDeTienda(['email_verified_at' => null]));

    $this->getJson('/api/v1/checkout')
        ->assertForbidden()
        ->assertJsonPath('codigo', 'email_no_verificado');
});

test('cada cliente ve su propio checkout', function () {
    $ajeno = clienteConCarrito();

    comoCliente(clienteDeTienda());

    $this->getJson('/api/v1/checkout')
        ->assertOk()
        ->assertJsonPath('data.carrito.vacio', true);

    // El del otro quedo intacto.
    comoCliente($ajeno);
    $this->getJson('/api/v1/checkout')->assertJsonPath('data.carrito.vacio', false);
});

// ==================================================================
// Resumen
// ==================================================================

test('el resumen trae el carrito valuado con los mismos importes', function () {
    comoCliente(clienteDeTienda());

    $producto = productoDePrueba(['precio' => 100000]);

    $this->postJson('/api/v1/carrito', [
        'producto_id' => $producto->id,
        'cantidad'    => 2,
    ]);

    $carrito = $this->getJson('/api/v1/carrito')->json('data');

    $this->getJson('/api/v1/checkout')
        ->assertOk()
        ->assertJsonPath('data.carrito.total', $carrito['total'])
        ->assertJsonPath('data.carrito.subtotal_lista', $carrito['subtotal_lista'])
        ->assertJsonPath('data.carrito.lineas.0.subtotal', 200000);
});

test('los precios del resumen son los vigentes, no los de cuando se agrego', function () {
    comoCliente(clienteDeTienda());

    $producto = productoDePrueba(['precio' => 100000]);

    $this->postJson('/api/v1/carrito', [
        'producto_id' => $producto->id,
        'cantidad'    => 1,
    ]);

    $producto->update(['precio' => 180000]);

    $this->getJson('/api/v1/checkout')
        ->assertOk()
        ->assertJsonPath('data.carrito.total', 180000);
});

test('el resumen trae los datos de facturacion del cliente', function () {
    comoCliente(clienteDeTienda());

    $this->getJson('/api/v1/checkout')
        ->assertOk()
        ->assertJsonPath('data.facturacion.razon_social', 'Cliente de prueba')
        ->assertJsonStructure([
            'data' => [
                'facturacion' => [
                    'razon_social',
                    'tipo_documento',
                    'nro_doc',
                    'condicion_iva',
                ],
            ],
        ]);
});

test('el resumen no expone datos internos del cliente ni del carrito', function () {
    clienteConCarrito();

    $datos = $this->getJson('/api/v1/checkout')->assertOk()->json('data');

    expect($datos['facturacion'])
        ->not->toHaveKey('id')
        ->not->toHaveKey('user_id')
        ->not->toHaveKey('telefono');

    expect($datos['carrito'])
        ->not->toHaveKey('user_id')
        ->not->toHaveKey('session_token')
        ->not->toHaveKey('mp_preference_id');
});

test('solo se ofrece retiro como modo de entrega', function () {
    clienteConCarrito();

    $this->getJson('/api/v1/checkout')
        ->assertOk()
        ->assertJsonCount(1, 'data.entrega.modos_disponibles')
        ->assertJsonPath('data.entrega.modos_disponibles.0.valor', 'retiro');
});

// ==================================================================
// Validacion final
// ==================================================================

test('un carrito completo y un cliente con datos fiscales pueden pagar', function () {
    clienteConCarrito();

    $this->getJson('/api/v1/checkout')
        ->assertOk()
        ->assertJsonPath('data.puede_pagar', true)
        ->assertJsonCount(0, 'data.bloqueos');
});

test('un carrito vacio bloquea el pago', function () {
    comoCliente(clienteDeTienda());

    $respuesta = $this->getJson('/api/v1/checkout')->assertOk();

    $respuesta->assertJsonPath('data.puede_pagar', false);

    expect(collect($respuesta->json('data.bloqueos'))->pluck('codigo'))
        ->toContain('carrito_vacio');
});

test('un producto despublicado bloquea el pago y lo nombra', function () {
    comoCliente(clienteDeTienda());

    $producto = productoDePrueba(['nombre' => 'Monitor fantasma']);

    $this->postJson('/api/v1/carrito', [
        'producto_id' => $producto->id,
        'cantidad'    => 1,
    ]);

    $producto->update(['publicado_app' => false]);

    $respuesta = $this->getJson('/api/v1/checkout')->assertOk();

    $respuesta->assertJsonPath('data.puede_pagar', false);

    $bloqueo = collect($respuesta->json('data.bloqueos'))
        ->firstWhere('codigo', 'producto_no_disponible');

    expect($bloqueo)->not->toBeNull()
        ->and($bloqueo['mensaje'])->toContain('Monitor fantasma');
});

test('un cliente sin fila en clientes no puede pagar', function () {
    // Usuario de tienda sin Cliente asociado: la tabla lo admite.
    comoCliente(User::factory()->create());

    $respuesta = $this->getJson('/api/v1/checkout')->assertOk();

    $respuesta
        ->assertJsonPath('data.puede_pagar', false)
        ->assertJsonPath('data.facturacion', null);

    expect(collect($respuesta->json('data.bloqueos'))->pluck('codigo'))
        ->toContain('sin_datos_fiscales');
});

test('un responsable inscripto sin CUIT no puede pagar', function () {
    $user = clienteConCarrito();

    $codigos = config('facturacion.codigos');

    // Se lo pasa a responsable inscripto dejandole un DNI: es la regla que
    // FacturacionService rechazaria DESPUES de cobrar.
    $user->cliente->update([
        'condicion_iva_id' => CondicionIva::query()
            ->where('codigo_afip', $codigos['condicion_iva']['responsable_inscripto'])
            ->value('id'),
        'tipo_documento_id' => TipoDocumento::query()
            ->where('codigo_afip', '!=', $codigos['documento']['cuit'])
            ->value('id'),
    ]);

    $respuesta = $this->getJson('/api/v1/checkout')->assertOk();

    $respuesta->assertJsonPath('data.puede_pagar', false);

    $bloqueo = collect($respuesta->json('data.bloqueos'))
        ->firstWhere('codigo', 'facturacion_incompleta');

    expect($bloqueo)->not->toBeNull()
        ->and($bloqueo['mensaje'])->toContain('CUIT');
});

test('se informan todos los bloqueos juntos, no el primero', function () {
    // Sin cliente Y con el carrito vacio: dos problemas a la vez.
    comoCliente(User::factory()->create());

    $codigos = collect($this->getJson('/api/v1/checkout')->json('data.bloqueos'))
        ->pluck('codigo');

    expect($codigos)
        ->toContain('carrito_vacio')
        ->toContain('sin_datos_fiscales')
        ->toHaveCount(2);
});

test('puede_pagar es mas estricto que puede_comprar del carrito', function () {
    // Carrito impecable, cliente que no puede facturar.
    comoCliente(User::factory()->create());

    $this->postJson('/api/v1/carrito', [
        'producto_id' => productoDePrueba()->id,
        'cantidad'    => 1,
    ])->assertCreated();

    $this->getJson('/api/v1/checkout')
        ->assertOk()
        ->assertJsonPath('data.carrito.puede_comprar', true)
        ->assertJsonPath('data.puede_pagar', false);
});

// ==================================================================
// Preferencias de entrega
// ==================================================================

test('se guarda el modo de entrega', function () {
    $user = clienteConCarrito();

    $this->patchJson('/api/v1/checkout', ['modo_entrega' => 'retiro'])
        ->assertOk()
        ->assertJsonPath('data.entrega.modo', 'retiro');

    expect(Carrito::query()->where('user_id', $user->id)->value('modo_entrega'))
        ->toBe(ModoEntrega::Retiro);
});

test('se guardan las observaciones', function () {
    clienteConCarrito();

    $this->patchJson('/api/v1/checkout', ['observaciones' => 'Paso el viernes.'])
        ->assertOk()
        ->assertJsonPath('data.entrega.observaciones', 'Paso el viernes.');
});

test('un PATCH parcial no pisa lo que ya estaba guardado', function () {
    clienteConCarrito();

    $this->patchJson('/api/v1/checkout', ['modo_entrega' => 'retiro'])->assertOk();

    $this->patchJson('/api/v1/checkout', ['observaciones' => 'Timbre B.'])
        ->assertOk()
        ->assertJsonPath('data.entrega.modo', 'retiro')
        ->assertJsonPath('data.entrega.observaciones', 'Timbre B.');
});

test('las observaciones se pueden vaciar explicitamente', function () {
    clienteConCarrito();

    $this->patchJson('/api/v1/checkout', ['observaciones' => 'Algo.'])->assertOk();

    $this->patchJson('/api/v1/checkout', ['observaciones' => null])
        ->assertOk()
        ->assertJsonPath('data.entrega.observaciones', null);
});

test('no se acepta el envio a domicilio', function () {
    clienteConCarrito();

    $this->patchJson('/api/v1/checkout', ['modo_entrega' => 'envio'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('modo_entrega');
});

test('no se aceptan observaciones mas largas que la columna', function () {
    clienteConCarrito();

    $this->patchJson('/api/v1/checkout', ['observaciones' => str_repeat('x', 501)])
        ->assertStatus(422)
        ->assertJsonValidationErrors('observaciones');
});

test('el PATCH devuelve el resumen completo recalculado', function () {
    clienteConCarrito();

    $this->patchJson('/api/v1/checkout', ['modo_entrega' => 'retiro'])
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'carrito'     => ['total', 'lineas'],
                'entrega'     => ['modo', 'observaciones', 'modos_disponibles'],
                'facturacion' => ['razon_social'],
                'puede_pagar',
                'bloqueos',
            ],
        ]);
});

// ==================================================================
// El checkout no toca el carrito ni el stock
// ==================================================================

test('consultar el checkout no cambia el estado del carrito', function () {
    $user = clienteConCarrito();

    $this->getJson('/api/v1/checkout')->assertOk();

    expect(Carrito::query()->where('user_id', $user->id)->value('estado'))
        ->toBe(EstadoCarrito::Activo);
});

test('guardar las preferencias no manda el carrito a en_checkout', function () {
    $user = clienteConCarrito();

    $this->patchJson('/api/v1/checkout', ['modo_entrega' => 'retiro'])->assertOk();

    expect(Carrito::query()->where('user_id', $user->id)->value('estado'))
        ->toBe(EstadoCarrito::Activo);
});

test('el checkout no reserva ni descuenta stock', function () {
    comoCliente(clienteDeTienda());

    $producto = productoDePrueba(['stock_fisico' => 8]);

    $this->postJson('/api/v1/carrito', [
        'producto_id' => $producto->id,
        'cantidad'    => 5,
    ]);

    $this->getJson('/api/v1/checkout')->assertOk();
    $this->patchJson('/api/v1/checkout', ['modo_entrega' => 'retiro'])->assertOk();

    $producto->refresh();

    expect($producto->stock_fisico)->toBe(8)
        ->and($producto->stock_comprometido)->toBe(0)
        ->and($producto->stock_disponible)->toBe(8);
});

test('el checkout no crea ninguna venta', function () {
    clienteConCarrito();

    $this->getJson('/api/v1/checkout')->assertOk();
    $this->patchJson('/api/v1/checkout', ['modo_entrega' => 'retiro'])->assertOk();

    // La venta nace del pago acreditado, no de mirar el resumen.
    expect(App\Models\Venta::count())->toBe(0);
});

test('un carrito convertido no se reabre: el checkout pasa a uno nuevo', function () {
    $user = clienteConCarrito();

    $viejo = Carrito::query()->where('user_id', $user->id)->sole();

    // Es lo que deja el webhook despues de crear la venta.
    $viejo->update(['estado' => EstadoCarrito::Convertido->value]);

    // `abiertoDe()` solo devuelve activo o en_checkout, asi que crea otro.
    // El resumen sale vacio y bloqueado, no sobre el pedido ya cerrado.
    $respuesta = $this->getJson('/api/v1/checkout')->assertOk();

    $respuesta
        ->assertJsonPath('data.carrito.vacio', true)
        ->assertJsonPath('data.puede_pagar', false);

    expect($respuesta->json('data.carrito.id'))->not->toBe($viejo->id);

    // Y el carrito convertido queda intacto, con su linea.
    expect($viejo->refresh()->estado)->toBe(EstadoCarrito::Convertido)
        ->and($viejo->lineas()->count())->toBe(1);
});
