<?php

use App\Enums\EstadoOrdenCompra;
use App\Exceptions\TransicionNoPermitidaException;
use App\Mail\RemitoDePedido;
use App\Models\MovimientoStock;
use App\Models\OrdenCompra;
use App\Models\Permiso;
use App\Models\User;
use App\Services\OrdenCompraService;
use Database\Seeders\Maestros\AlicuotaIvaSeeder;
use Database\Seeders\Maestros\CondicionIvaSeeder;
use Database\Seeders\Maestros\NumeradorSeeder;
use Database\Seeders\Maestros\PermisoSeeder;
use Database\Seeders\Maestros\RolSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;

/*
|--------------------------------------------------------------------------
| Entrega E.2.a — API de gestion: transiciones del pedido a proveedor
|--------------------------------------------------------------------------
|
| Las tres operaciones que mueven el DOCUMENTO y no el inventario:
|
|   POST /api/v1/gestion/compras/{orden}/aprobacion
|   POST /api/v1/gestion/compras/{orden}/envio
|   POST /api/v1/gestion/compras/{orden}/cancelacion
|
| <b>La recepcion no esta aca.</b> Registrar mercaderia y cerrar con faltante
| son E.2.b: son las unicas dos operaciones del circuito que tocan stock, y
| tienen su propio archivo cuando lleguen. Lo que esta entrega si prueba es que
| estas tres NO mueven inventario —la ultima prueba del archivo—, porque si
| alguna lo hiciera el deposito estaria contando mercaderia que no llego.
|
| <b>Los correos van siempre con Mail::fake().</b> El envio es el unico endpoint
| del alcance con efecto fuera del sistema, y el servicio lo manda de forma
| sincronica y ANTES de cambiar el estado, a proposito: si el correo no sale, la
| orden se queda en 'aprobada', que es la verdad. Las pruebas verifican las dos
| mitades —que salga cuando tiene que salir y que no salga cuando no—.
|
| <b>Las fabricas viven en tests/Apoyo/fabricas.php</b>, no aca: las comparte
| con OrdenCompraTest, y Pest carga todos los archivos en el mismo proceso.
|
| <b>Separacion de funciones.</b> Segun la matriz real de rol_permiso, el
| administrativo tiene siete de los ocho permisos de compras y le falta
| 'compra.aprobar_propias', que es del administrador. Por eso el actor cambia
| segun lo que se prueba, y no es casual: es la regla de negocio.
|
*/

beforeEach(function () {
    $this->seed([
        RolSeeder::class,
        PermisoSeeder::class,
        CondicionIvaSeeder::class,
        AlicuotaIvaSeeder::class,
        NumeradorSeeder::class,
    ]);
});

/** Las tres rutas de la entrega, para los recorridos que las barren todas. */
function transicionesDeCompras(OrdenCompra $orden): array
{
    return [
        'aprobacion'   => route('api.v1.gestion.compras.aprobacion', $orden),
        'envio'        => route('api.v1.gestion.compras.envio', $orden),
        'cancelacion'  => route('api.v1.gestion.compras.cancelacion', $orden),
    ];
}

// ============================================================
// Las tres capas de acceso
// ============================================================

it('rechaza las transiciones sin token', function () {
    $orden = pedidoDeUnProveedor();

    foreach (transicionesDeCompras($orden) as $url) {
        $this->postJson($url, ['motivo' => 'x'])->assertUnauthorized();
    }

    $this->assertDatabaseHas('ordenes_compra', ['id' => $orden->id, 'estado' => 'borrador']);
});

it('rechaza un token de un cliente de la tienda', function () {
    // El usuario es de otro ambito: lo frena 'api.ambito:gestion', que mira
    // quien es el usuario AHORA.
    Sanctum::actingAs(User::factory()->cliente()->create(), ['tienda']);

    $this->postJson(route('api.v1.gestion.compras.aprobacion', pedidoDeUnProveedor()))
        ->assertForbidden()
        ->assertJsonPath('codigo', 'AMBITO_INCORRECTO');
});

it('rechaza un token de empleado emitido para la tienda', function () {
    // El usuario es correcto y el token no: lo frena 'abilities:gestion', que
    // mira PARA QUE se emitio.
    Sanctum::actingAs(administrativoDeCompras(), ['tienda']);

    $this->postJson(route('api.v1.gestion.compras.aprobacion', pedidoDeUnProveedor()))
        ->assertForbidden()
        ->assertJsonPath('codigo', 'ABILITY_INSUFICIENTE');
});

// ============================================================
// Permisos del rol
// ============================================================

it('no deja entrar al vendedor ni al cajero a ninguna transicion', function () {
    // Segun la matriz real de rol_permiso, ninguno de los dos tiene un solo
    // permiso de compras: el circuito es del administrativo.
    $orden = pedidoDeUnProveedor();

    foreach (['vendedor', 'cajero'] as $rol) {
        Sanctum::actingAs(User::factory()->empleado($rol)->create(), ['gestion']);

        foreach (transicionesDeCompras($orden) as $url) {
            $this->postJson($url, ['motivo' => 'Prueba'])
                ->assertForbidden()
                ->assertJsonPath('codigo', 'PERMISO_INSUFICIENTE');
        }

        $this->app['auth']->forgetGuards();
    }

    $this->assertDatabaseHas('ordenes_compra', ['id' => $orden->id, 'estado' => 'borrador']);
});

it('da un permiso por operacion y no uno solo para todo el circuito', function () {
    // Es lo que justifica que cada ruta lleve su propio `can:`: quitarle
    // 'compra.aprobar' al administrativo tiene que dejarlo sin aprobar y con
    // todo lo demas intacto.
    $empleado = administrativoDeCompras();

    $empleado->rol->permisos()->detach(
        Permiso::where('clave', 'compra.aprobar')->value('id')
    );

    Sanctum::actingAs($empleado, ['gestion']);

    $orden = pedidoDeUnProveedor();

    $this->postJson(route('api.v1.gestion.compras.aprobacion', $orden))
        ->assertForbidden()
        ->assertJsonPath('codigo', 'PERMISO_INSUFICIENTE');

    // El mismo usuario, el mismo pedido, otra operacion: esta si.
    $this->postJson(route('api.v1.gestion.compras.cancelacion', $orden), [
        'motivo' => 'Se pidió de más.',
    ])->assertOk();

    expect($orden->fresh()->estado)->toBe(EstadoOrdenCompra::Cancelada);
});

// ============================================================
// Separacion de funciones: quien crea no aprueba
// ============================================================

it('no deja que el administrativo apruebe el pedido que el mismo cargo', function () {
    // <b>La unica separacion de funciones del circuito de compras.</b> Sin
    // ella, un mismo usuario arma el pedido, lo aprueba y lo manda, y la
    // aprobacion deja de significar algo.
    $empleado = administrativoDeCompras();

    Sanctum::actingAs($empleado, ['gestion']);

    $orden = pedidoDeUnProveedor('borrador', null, ['usuario_creo_id' => $empleado->id]);

    $this->postJson(route('api.v1.gestion.compras.aprobacion', $orden))
        ->assertForbidden()
        ->assertJsonPath('codigo', 'PERMISO_INSUFICIENTE');

    expect($orden->fresh()->estado)->toBe(EstadoOrdenCompra::Borrador)
        ->and($orden->fresh()->usuario_aprobo_id)->toBeNull();
});

it('deja que OTRO administrativo apruebe ese mismo pedido', function () {
    // La regla es sobre quien lo creo, no sobre el rol: el de al lado lo mira y
    // lo firma, que es exactamente lo que la separacion de funciones persigue.
    $autor   = administrativoDeCompras();
    $revisor = administrativoDeCompras();

    Sanctum::actingAs($revisor, ['gestion']);

    $orden = pedidoDeUnProveedor('borrador', null, ['usuario_creo_id' => $autor->id]);

    $this->postJson(route('api.v1.gestion.compras.aprobacion', $orden))
        ->assertOk()
        ->assertJsonPath('data.estado', 'aprobada');

    expect($orden->fresh()->usuario_aprobo_id)->toBe($revisor->id);
});

it('exime al administrador, que si puede aprobar lo suyo', function () {
    // En una empresa chica suele ser la misma persona, y trabarlo la dejaria
    // sin poder operar. Es la excepcion deliberada que concede
    // 'compra.aprobar_propias', y solo el administrador la tiene.
    $administrador = User::factory()->empleado('administrador')->create();

    Sanctum::actingAs($administrador, ['gestion']);

    $orden = pedidoDeUnProveedor('borrador', null, ['usuario_creo_id' => $administrador->id]);

    $this->postJson(route('api.v1.gestion.compras.aprobacion', $orden))
        ->assertOk();

    expect($orden->fresh()->estado)->toBe(EstadoOrdenCompra::Aprobada);
});

// ============================================================
// Transiciones invalidas
// ============================================================

it('rechaza las transiciones que el estado no admite', function () {
    // <b>403 y no 422</b>, y es deliberado: las tres policies miran el permiso
    // Y el estado, igual que en el panel. El 422 queda para la carrera —dos
    // peticiones simultaneas pasan las dos por la policy y la segunda choca con
    // el estado ya cambiado—, no para el caso normal.
    Sanctum::actingAs(User::factory()->empleado('administrador')->create(), ['gestion']);

    $casos = [
        // [estado de partida, accion, cuerpo]
        ['aprobada',  'aprobacion',  []],
        ['cancelada', 'aprobacion',  []],
        ['recibida',  'aprobacion',  []],
        ['borrador',  'envio',       []],
        ['enviada',   'envio',       []],
        ['recibida',  'cancelacion', ['motivo' => 'Tarde']],
        ['cancelada', 'cancelacion', ['motivo' => 'Otra vez']],
    ];

    foreach ($casos as [$estado, $accion, $cuerpo]) {
        $orden = pedidoDeUnProveedor($estado);

        $this->postJson(route("api.v1.gestion.compras.{$accion}", $orden), $cuerpo)
            ->assertForbidden()
            ->assertJsonPath('codigo', 'PERMISO_INSUFICIENTE');

        expect($orden->fresh()->estado->value)->toBe($estado);
    }
});

it('nombra al pedido y no a la venta cuando el servicio rechaza la transicion', function () {
    // <b>Defecto preexistente corregido en E.2.a.</b>
    // TransicionNoPermitidaException::entre() estaba tipada contra EstadoVenta y
    // OrdenCompraService la llamaba con EstadoOrdenCompra: ese camino terminaba
    // en TypeError —un 500— en vez de en el 422 del dominio. No se notaba
    // porque la policy rechaza antes casi todas las transiciones invalidas;
    // aparece en cuanto dos peticiones compiten por el mismo pedido.
    $error = TransicionNoPermitidaException::entre(
        EstadoOrdenCompra::Recibida,
        EstadoOrdenCompra::Aprobada,
    );

    expect($error->getMessage())
        ->toContain('Un pedido')
        ->toContain('recibida')
        ->toContain('aprobada');
});

// ============================================================
// Aprobacion
// ============================================================

it('aprueba el pedido y deja registrado quien y cuando', function () {
    // Es el punto de pedir la aprobacion: que quede la firma.
    $revisor = administrativoDeCompras();

    Sanctum::actingAs($revisor, ['gestion']);

    $orden = pedidoDeUnProveedor();

    $this->postJson(route('api.v1.gestion.compras.aprobacion', $orden))
        ->assertOk()
        ->assertJsonPath('data.id', $orden->id)
        ->assertJsonPath('data.estado', 'aprobada')
        ->assertJsonPath('data.estado_etiqueta', 'Aprobada')
        ->assertJsonPath('data.usuario_aprobo.id', $revisor->id)
        ->assertJsonPath('hijas_emitidas', 0)
        ->assertJsonPath('anulados', [])
        ->assertJsonStructure(['data' => ['lineas', 'unidades', 'proveedor'], 'anulados']);

    $orden->refresh();

    expect($orden->estado)->toBe(EstadoOrdenCompra::Aprobada)
        ->and($orden->usuario_aprobo_id)->toBe($revisor->id)
        ->and($orden->fecha_aprobacion)->not->toBeNull();
});

it('reparte el pedido mezclado en una orden por proveedor', function () {
    // <b>El reparto pasa al APROBAR y no al guardar</b>, porque hasta ahi el
    // detalle se sigue tocando. Las hijas son las que se envian y se reciben;
    // la madre es el pedido que el administrativo cargo y aprobo.
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $madre = pedidoMezcladoEnBorrador(2);

    $this->postJson(route('api.v1.gestion.compras.aprobacion', $madre))
        ->assertOk()
        ->assertJsonPath('hijas_emitidas', 2)
        ->assertJsonCount(2, 'data.hijas');

    $madre->refresh()->load('hijas.lineas');

    expect($madre->estado)->toBe(EstadoOrdenCompra::Aprobada)
        // La madre sigue sin destinatario propio y conserva sus renglones: son
        // lo que se pidio, y el restock los cuenta.
        ->and($madre->proveedor_id)->toBeNull()
        ->and($madre->lineas()->count())->toBe(2)
        ->and($madre->hijas)->toHaveCount(2);

    foreach ($madre->hijas as $i => $hija) {
        expect($hija->estado)->toBe(EstadoOrdenCompra::Aprobada)
            // El numero es derivado y NO sale del numerador: son el reparto de
            // un pedido, no pedidos distintos.
            ->and($hija->numero)->toBe($madre->numero.'/'.($i + 1))
            ->and($hija->proveedor_id)->not->toBeNull()
            ->and($hija->lineas)->toHaveCount(1)
            ->and((float) $hija->total_estimado)->toBeGreaterThan(0);
    }
});

it('da de baja los borradores automaticos que el pedido aprobado deja sin sentido', function () {
    // El restock arma borradores solo. Si el administrativo arma a mano un
    // pedido que cubre esos mismos productos, los borradores quedan pidiendo
    // algo que ya se pidio, y si alguien los aprueba por error el proveedor
    // recibe dos pedidos por el mismo faltante.
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $proveedor = proveedorConCasilla();
    $cubierto  = productoPedible($proveedor);
    $propio    = productoPedible($proveedor);

    // Queda COMPLETAMENTE cubierto por el pedido manual: se anula.
    $superado = ordenDeCompraDePrueba(['proveedor_id' => $proveedor->id, 'origen' => 'automatica']);
    lineaCrudaEn($superado, $cubierto);

    // Le queda un renglon que el pedido manual no cubre: sigue en pie. La
    // unidad que se superpone es el renglon; el documento se da de baja solo
    // cuando ya no le queda nada propio.
    $parcial = ordenDeCompraDePrueba(['proveedor_id' => $proveedor->id, 'origen' => 'automatica']);
    lineaCrudaEn($parcial, $cubierto);
    lineaCrudaEn($parcial, $propio);

    // Un borrador MANUAL es alguien escribiendo: no se toca nunca.
    $manual = ordenDeCompraDePrueba(['proveedor_id' => $proveedor->id, 'origen' => 'manual']);
    lineaCrudaEn($manual, $cubierto);

    $pedido = ordenDeCompraDePrueba(['proveedor_id' => $proveedor->id, 'origen' => 'manual']);
    lineaCrudaEn($pedido, $cubierto);

    $this->postJson(route('api.v1.gestion.compras.aprobacion', $pedido))
        ->assertOk()
        ->assertJsonPath('anulados', [$superado->numero]);

    expect($superado->fresh()->estado)->toBe(EstadoOrdenCompra::Cancelada)
        ->and($parcial->fresh()->estado)->toBe(EstadoOrdenCompra::Borrador)
        ->and($manual->fresh()->estado)->toBe(EstadoOrdenCompra::Borrador);

    // La baja se le atribuye al que aprobo: nadie decidio crear ese borrador,
    // pero esta persona si decidio aprobar el pedido que lo reemplaza.
    expect($superado->fresh()->observaciones)
        ->toContain("quedó cubierto por el pedido {$pedido->numero}");
});

it('rechaza aprobar un pedido sin renglones', function () {
    // Acá si es 422: la policy deja pasar —el estado es 'borrador' y el permiso
    // esta— y lo que falla es una regla del documento, no la autorizacion.
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $orden = ordenDeCompraDePrueba();

    $this->postJson(route('api.v1.gestion.compras.aprobacion', $orden))
        ->assertStatus(422)
        ->assertJsonPath('message', 'La orden no tiene renglones: no hay nada que aprobar.');

    expect($orden->fresh()->estado)->toBe(EstadoOrdenCompra::Borrador);
});

it('rechaza aprobar un pedido mezclado con un renglon sin proveedor', function () {
    // No hay a quien emitirle esa orden. Se detecta al repartir, que es cuando
    // el documento pasa a tener destinatario.
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $madre = ordenDeCompraDePrueba(['proveedor_id' => null]);

    lineaCrudaEn($madre, productoPedible());
    lineaCrudaEn($madre, productoDePrueba(['proveedor_id' => null, 'costo' => 100.00]));

    $this->postJson(route('api.v1.gestion.compras.aprobacion', $madre))
        ->assertStatus(422)
        ->assertJsonPath('message', 'Hay renglones sin proveedor: no hay a quién emitirles la orden. Asignales un proveedor desde la ficha del producto.');

    // La transaccion volvio atras entera: ni la madre quedo aprobada ni
    // sobrevivio la hija del proveedor que si estaba.
    expect($madre->fresh()->estado)->toBe(EstadoOrdenCompra::Borrador)
        ->and($madre->hijas()->count())->toBe(0);
});

// ============================================================
// Envio al proveedor
// ============================================================

it('manda el remito por correo y marca el pedido como enviado', function () {
    Mail::fake();

    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $proveedor = proveedorConCasilla();
    $orden     = pedidoDeUnProveedor('aprobada', $proveedor);

    $this->postJson(route('api.v1.gestion.compras.envio', $orden))
        ->assertOk()
        ->assertJsonPath('data.estado', 'enviada');

    Mail::assertSent(RemitoDePedido::class, 1);
    Mail::assertSent(
        RemitoDePedido::class,
        fn (RemitoDePedido $correo) => $correo->hasTo($proveedor->email)
            && $correo->orden->is($orden)
    );

    expect($orden->fresh()->estado)->toBe(EstadoOrdenCompra::Enviada)
        ->and($orden->fresh()->fecha_envio)->not->toBeNull();
});

it('no envia nada si el proveedor no tiene casilla cargada', function () {
    // El pedido se aprueba igual, pero no se puede enviar: el sistema lo dice
    // en vez de marcarlo enviado sin que haya salido nada.
    Mail::fake();

    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    // proveedorDePrueba() deja el canal en 'email' y la casilla vacia.
    $orden = pedidoDeUnProveedor('aprobada', proveedorDePrueba());

    $this->postJson(route('api.v1.gestion.compras.envio', $orden))
        ->assertStatus(422)
        ->assertJsonPath('message', fn (string $mensaje) => str_contains($mensaje, 'no tiene correo cargado'));

    Mail::assertNothingSent();

    // <b>Sigue en 'aprobada', que es la verdad</b>, y es reintentable en cuanto
    // alguien le cargue la casilla al proveedor.
    expect($orden->fresh()->estado)->toBe(EstadoOrdenCompra::Aprobada)
        ->and($orden->fresh()->fecha_envio)->toBeNull();
});

it('no envia nada si el proveedor no recibe pedidos por correo', function () {
    Mail::fake();

    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $orden = pedidoDeUnProveedor('aprobada', proveedorConCasilla([
        'canal_pedido' => 'portal_externo',
        'portal_url'   => 'https://proveedor.test/pedidos',
    ]));

    $this->postJson(route('api.v1.gestion.compras.envio', $orden))
        ->assertStatus(422)
        ->assertJsonPath('message', fn (string $mensaje) => str_contains($mensaje, 'canal de pedido'));

    Mail::assertNothingSent();

    expect($orden->fresh()->estado)->toBe(EstadoOrdenCompra::Aprobada);
});

it('manda un remito por proveedor en un pedido repartido', function () {
    Mail::fake();

    $madre = pedidoMezcladoEnBorrador(2);

    // Se aprueba por el servicio y no por el endpoint: lo que esta prueba mira
    // es el envio, y llegar al estado de partida por el circuito la haria
    // depender de la operacion anterior.
    app(OrdenCompraService::class)->aprobar($madre, administrativoDeCompras());

    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $this->postJson(route('api.v1.gestion.compras.envio', $madre))
        ->assertOk()
        ->assertJsonCount(2, 'envios.enviadas')
        ->assertJsonPath('envios.fallidas', [])
        ->assertJsonStructure(['envios' => ['enviadas' => [['id', 'numero', 'proveedor', 'email']], 'fallidas']]);

    Mail::assertSent(RemitoDePedido::class, 2);

    foreach ($madre->fresh()->hijas as $hija) {
        expect($hija->estado)->toBe(EstadoOrdenCompra::Enviada);
    }

    // El resumen de la madre sale del renglon mas atrasado: con las dos
    // enviadas, el pedido entero esta enviado.
    expect($madre->fresh()->estado)->toBe(EstadoOrdenCompra::Enviada);
});

it('deja salir los remitos que si se pueden mandar aunque uno falle', function () {
    // <b>Es el motivo por el que el reparto existe.</b> Con un solo documento
    // para tres destinatarios, un proveedor sin casilla dejaria el pedido entero
    // sin enviar, y reintentar le mandaria el remito dos veces a los que si lo
    // recibieron.
    Mail::fake();

    $completo   = proveedorConCasilla(['razon_social' => 'Proveedor Completo']);
    $sinCasilla = proveedorDePrueba(['razon_social' => 'Proveedor Sin Casilla']);

    $madre = ordenDeCompraDePrueba(['proveedor_id' => null]);
    lineaCrudaEn($madre, productoPedible($completo));
    lineaCrudaEn($madre, productoPedible($sinCasilla));

    app(OrdenCompraService::class)->aprobar($madre->fresh('lineas'), administrativoDeCompras());

    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    // <b>200 y no 4xx.</b> Un remito salio de verdad: responder error dejaria
    // al cliente creyendo que no se envio nada, y reintentando.
    $this->postJson(route('api.v1.gestion.compras.envio', $madre))
        ->assertOk()
        ->assertJsonCount(1, 'envios.enviadas')
        ->assertJsonPath('envios.enviadas.0.proveedor', 'Proveedor Completo')
        ->assertJsonPath('envios.fallidas.Proveedor Sin Casilla', fn (string $motivo) => str_contains($motivo, 'no tiene correo cargado'));

    Mail::assertSent(RemitoDePedido::class, 1);

    // Reintentar alcanza SOLO a la que fallo: la que salio sigue 'enviada' y no
    // recibe un segundo remito.
    $this->postJson(route('api.v1.gestion.compras.envio', $madre))
        ->assertOk()
        ->assertJsonCount(0, 'envios.enviadas')
        ->assertJsonCount(1, 'envios.fallidas');

    Mail::assertSent(RemitoDePedido::class, 1);
});

it('responde 502 y deja el pedido reintentable si el correo no pudo salir', function () {
    // Una falla del transporte de correo no es un defecto del sistema ni un
    // error del cliente: es un servicio de afuera que no respondio. El 500
    // diria «esto es un bug nuestro» y el 422 diria «corregi el pedido»; las
    // dos cosas son falsas, y lo que el cliente necesita saber es que puede
    // reintentar.
    Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('El servidor de correo no responde.'));

    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $orden = pedidoDeUnProveedor('aprobada');

    $this->postJson(route('api.v1.gestion.compras.envio', $orden))
        ->assertStatus(502)
        ->assertJsonPath('codigo', 'ENVIO_FALLIDO');

    expect($orden->fresh()->estado)->toBe(EstadoOrdenCompra::Aprobada)
        ->and($orden->fresh()->fecha_envio)->toBeNull();
});

// ============================================================
// Cancelacion
// ============================================================

it('cancela el pedido y anota el motivo con su autor', function () {
    $empleado = administrativoDeCompras();

    Sanctum::actingAs($empleado, ['gestion']);

    $orden = pedidoDeUnProveedor('enviada');

    $this->postJson(route('api.v1.gestion.compras.cancelacion', $orden), [
        'motivo' => 'El proveedor avisó que no tiene stock.',
    ])
        ->assertOk()
        ->assertJsonPath('data.estado', 'cancelada')
        ->assertJsonPath('hijas_canceladas', 0);

    $orden->refresh();

    expect($orden->estado)->toBe(EstadoOrdenCompra::Cancelada)
        ->and($orden->observaciones)
        ->toContain("Cancelada por {$empleado->usuario}")
        ->toContain('El proveedor avisó que no tiene stock.');
});

it('exige el motivo de la cancelacion', function () {
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $orden = pedidoDeUnProveedor();

    $this->postJson(route('api.v1.gestion.compras.cancelacion', $orden), [])
        ->assertStatus(422)
        ->assertJsonValidationErrors('motivo')
        ->assertJsonPath('errors.motivo.0', 'Escribí por qué se cancela el pedido.');

    $this->postJson(route('api.v1.gestion.compras.cancelacion', $orden), [
        'motivo' => str_repeat('x', 256),
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('motivo');

    expect($orden->fresh()->estado)->toBe(EstadoOrdenCompra::Borrador);
});

it('cancela las ordenes del reparto junto con el pedido', function () {
    Mail::fake();

    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $madre = pedidoMezcladoEnBorrador(2);

    app(OrdenCompraService::class)->aprobar($madre, administrativoDeCompras());

    $this->postJson(route('api.v1.gestion.compras.cancelacion', $madre->fresh()), [
        'motivo' => 'Se dio de baja la compra entera.',
    ])
        ->assertOk()
        ->assertJsonPath('hijas_canceladas', 2);

    expect($madre->fresh()->estado)->toBe(EstadoOrdenCompra::Cancelada);

    foreach ($madre->fresh()->hijas as $hija) {
        expect($hija->estado)->toBe(EstadoOrdenCompra::Cancelada);
    }
});

it('deja el resto del pedido en pie al cancelar la orden de un solo proveedor', function () {
    // Es el caso legitimo: el proveedor que avisa que no tiene stock. Cancelar
    // su orden no puede arrastrar a los demas, que ya recibieron su remito.
    Mail::fake();

    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $madre = pedidoMezcladoEnBorrador(2);

    app(OrdenCompraService::class)->aprobar($madre, administrativoDeCompras());

    $hijas = $madre->fresh()->hijas;

    $this->postJson(route('api.v1.gestion.compras.cancelacion', $hijas->first()), [
        'motivo' => 'Sin stock en ese proveedor.',
    ])
        ->assertOk()
        ->assertJsonPath('data.estado', 'cancelada')
        ->assertJsonPath('data.padre.numero', $madre->numero);

    expect($hijas->first()->fresh()->estado)->toBe(EstadoOrdenCompra::Cancelada)
        ->and($hijas->last()->fresh()->estado)->toBe(EstadoOrdenCompra::Aprobada)
        // La madre vuelve al resumen de lo que queda vivo: una sola hija
        // aprobada, asi que el pedido sigue aprobado.
        ->and($madre->fresh()->estado)->toBe(EstadoOrdenCompra::Aprobada);
});

// ============================================================
// Lo que E.2.a NO hace
// ============================================================

it('no mueve inventario en ninguna de las tres transiciones', function () {
    // <b>La regla de la entrega.</b> El stock se toca en un solo punto del
    // circuito —la recepcion de mercaderia, que es E.2.b—. Si aprobar o enviar
    // emitieran un movimiento, el deposito estaria contando mercaderia que
    // todavia no llego.
    Mail::fake();

    Sanctum::actingAs(User::factory()->empleado('administrador')->create(), ['gestion']);

    $orden    = pedidoDeUnProveedor();
    $producto = $orden->lineas->first()->producto;

    $fisicoAntes       = $producto->stock_fisico;
    $comprometidoAntes = $producto->stock_comprometido;

    $this->postJson(route('api.v1.gestion.compras.aprobacion', $orden))->assertOk();
    $this->postJson(route('api.v1.gestion.compras.envio', $orden))->assertOk();
    $this->postJson(route('api.v1.gestion.compras.cancelacion', $orden), [
        'motivo' => 'Ya no hace falta.',
    ])->assertOk();

    $producto->refresh();

    expect(MovimientoStock::count())->toBe(0)
        ->and($producto->stock_fisico)->toBe($fisicoAntes)
        ->and($producto->stock_comprometido)->toBe($comprometidoAntes);
});

it('suelta el candado del pedido al terminar cada transicion', function () {
    // El candado serializa las transiciones sobre un mismo pedido. Si no se
    // soltara, la primera aprobacion dejaria el documento trabado treinta
    // segundos y el envio siguiente contestaria 422 sin motivo.
    Mail::fake();

    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $orden = pedidoDeUnProveedor();

    $this->postJson(route('api.v1.gestion.compras.aprobacion', $orden))->assertOk();

    $candado = Cache::lock("orden-compra:transicion:{$orden->id}", 5);

    expect($candado->get())->toBeTrue();

    $candado->release();
});
