<?php

use App\Enums\EstadoOrdenCompra;
use App\Enums\EstadoVenta;
use App\Enums\TipoMovimientoStock;
use App\Events\OrdenCompraCerrada;
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
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;

/*
|--------------------------------------------------------------------------
| Entrega E.2.b — API de gestion: recepcion de mercaderia
|--------------------------------------------------------------------------
|
| Las dos operaciones que cierran el circuito compras → stock → preventas:
|
|   POST /api/v1/gestion/compras/{orden}/recepciones
|   POST /api/v1/gestion/compras/{orden}/cierre
|
| <b>La recepcion es el unico endpoint de toda la API de compras que toca el
| inventario</b>, y por eso la mitad de este archivo mira el movimiento de
| stock: que exista uno y solo uno por renglon, que su origen apunte a la orden,
| que el remito viaje en el motivo, que el fisico suba exactamente lo que entro
| y que el comprometido no se mueva.
|
| <b>El remito es la clave de idempotencia.</b> Un cliente HTTP que reintenta
| por timeout de red sumaria las unidades dos veces: la guarda vive en el
| controlador y corre DENTRO del candado, que es el unico lugar donde el dato es
| firme. Las cuatro pruebas de esa seccion fijan tambien sus limites, incluido
| el caso sin remito, que deliberadamente no tiene idempotencia.
|
| <b>Las fabricas viven en tests/Apoyo/fabricas.php</b>: Pest carga todos los
| archivos en el mismo proceso y declararlas dos veces no compila.
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

/** Las dos rutas de la entrega, para los recorridos que las barren juntas. */
function recepcionesDeCompras(OrdenCompra $orden): array
{
    return [
        'recepciones' => route('api.v1.gestion.compras.recepciones', $orden),
        'cierre'      => route('api.v1.gestion.compras.cierre', $orden),
    ];
}

/** El cuerpo de una entrega: cuanto entra de cada renglon del pedido. */
function entregaDe(OrdenCompra $orden, array $cantidadesPorPosicion, ?string $remito = 'R-0001-00000001'): array
{
    $lineas = $orden->lineas->values();

    $cantidades = [];

    foreach ($cantidadesPorPosicion as $posicion => $cantidad) {
        $cantidades[(string) $lineas[$posicion]->id] = $cantidad;
    }

    return array_filter([
        'cantidades' => $cantidades,
        'remito'     => $remito,
    ], fn ($valor) => $valor !== null);
}

// ============================================================
// Acceso y permisos
// ============================================================

it('rechaza la recepcion y el cierre sin token', function () {
    $orden = pedidoListoParaRecibir();

    foreach (recepcionesDeCompras($orden) as $url) {
        $this->postJson($url, ['motivo' => 'x'])->assertUnauthorized();
    }

    expect(MovimientoStock::count())->toBe(0);
});

it('rechaza credenciales del ambito equivocado', function () {
    $orden = pedidoListoParaRecibir();

    // Usuario de otro ambito: lo frena 'api.ambito:gestion'.
    Sanctum::actingAs(User::factory()->cliente()->create(), ['tienda']);

    $this->postJson(route('api.v1.gestion.compras.recepciones', $orden))
        ->assertForbidden()
        ->assertJsonPath('codigo', 'AMBITO_INCORRECTO');

    $this->app['auth']->forgetGuards();

    // Usuario correcto y token emitido para la tienda: lo frena 'abilities'.
    Sanctum::actingAs(administrativoDeCompras(), ['tienda']);

    $this->postJson(route('api.v1.gestion.compras.recepciones', $orden))
        ->assertForbidden()
        ->assertJsonPath('codigo', 'ABILITY_INSUFICIENTE');
});

it('no deja entrar al vendedor ni al cajero', function () {
    // Segun la matriz real de rol_permiso, ninguno de los dos tiene
    // 'compra.recibir': el deposito es del administrativo.
    $orden = pedidoListoParaRecibir();

    foreach (['vendedor', 'cajero'] as $rol) {
        Sanctum::actingAs(User::factory()->empleado($rol)->create(), ['gestion']);

        foreach (recepcionesDeCompras($orden) as $url) {
            $this->postJson($url, ['motivo' => 'Prueba'])
                ->assertForbidden()
                ->assertJsonPath('codigo', 'PERMISO_INSUFICIENTE');
        }

        $this->app['auth']->forgetGuards();
    }

    expect(MovimientoStock::count())->toBe(0);
});

it('exige el mismo permiso para recibir y para cerrar', function () {
    // 'compra.recibir' cubre las dos: son la misma tarea mirada desde los dos
    // lados, y la descripcion del permiso en PermisoSeeder lo dice textualmente.
    $empleado = administrativoDeCompras();

    $empleado->rol->permisos()->detach(
        Permiso::where('clave', 'compra.recibir')->value('id')
    );

    Sanctum::actingAs($empleado, ['gestion']);

    $orden = pedidoListoParaRecibir();

    $this->postJson(route('api.v1.gestion.compras.recepciones', $orden), entregaDe($orden, [0 => 5]))
        ->assertForbidden()
        ->assertJsonPath('codigo', 'PERMISO_INSUFICIENTE');

    $parcial = pedidoListoParaRecibir(1, null, 10, ['estado' => 'recibida_parcial']);

    $this->postJson(route('api.v1.gestion.compras.cierre', $parcial), ['motivo' => 'No viene'])
        ->assertForbidden()
        ->assertJsonPath('codigo', 'PERMISO_INSUFICIENTE');

    expect(MovimientoStock::count())->toBe(0);
});

it('deja recibir al que no puede aprobar', function () {
    // Granularidad: recibir no es autorizar un gasto. El que esta parado en el
    // deposito constata que algo llego, y para eso no hace falta firmar nada.
    $empleado = administrativoDeCompras();

    $empleado->rol->permisos()->detach(
        Permiso::where('clave', 'compra.aprobar')->value('id')
    );

    Sanctum::actingAs($empleado, ['gestion']);

    $orden = pedidoListoParaRecibir(1);

    $this->postJson(route('api.v1.gestion.compras.recepciones', $orden), entregaDe($orden, [0 => 10]))
        ->assertCreated();
});

// ============================================================
// Transiciones invalidas
// ============================================================

it('rechaza recibir contra un pedido que no admite recepciones', function () {
    // <b>403 y no 422</b>: OrdenCompraPolicy::recibir() mira el permiso Y
    // esRecibible(). Un borrador no lo aprobo nadie y una aprobada sin enviar
    // no llego al proveedor, asi que no hay nada que pueda estar entrando; si
    // la mercaderia aparece igual, lo que falta es enviar el pedido.
    Sanctum::actingAs(User::factory()->empleado('administrador')->create(), ['gestion']);

    foreach (['borrador', 'aprobada', 'recibida', 'cancelada'] as $estado) {
        $orden = pedidoListoParaRecibir(1, null, 10, ['estado' => $estado]);

        $this->postJson(route('api.v1.gestion.compras.recepciones', $orden), entregaDe($orden, [0 => 5]))
            ->assertForbidden()
            ->assertJsonPath('codigo', 'PERMISO_INSUFICIENTE');

        expect($orden->fresh()->estado->value)->toBe($estado);
    }

    expect(MovimientoStock::count())->toBe(0);
});

it('rechaza recibir contra un pedido repartido', function () {
    // La mercaderia la manda cada proveedor contra su propia orden, y cargarla
    // sobre el pedido entero no diria cual de los tres cumplio.
    Mail::fake();

    Sanctum::actingAs(User::factory()->empleado('administrador')->create(), ['gestion']);

    $madre = pedidoMezcladoEnBorrador(2);

    app(OrdenCompraService::class)->aprobar($madre, administrativoDeCompras());

    $madre->refresh()->load('lineas');

    $this->postJson(route('api.v1.gestion.compras.recepciones', $madre), entregaDe($madre, [0 => 1]))
        ->assertForbidden()
        ->assertJsonPath('codigo', 'PERMISO_INSUFICIENTE');

    expect(MovimientoStock::count())->toBe(0);
});

it('rechaza cerrar un pedido que no quedo a medio recibir', function () {
    Sanctum::actingAs(User::factory()->empleado('administrador')->create(), ['gestion']);

    foreach (['enviada', 'recibida', 'cancelada', 'borrador'] as $estado) {
        $orden = pedidoListoParaRecibir(1, null, 10, ['estado' => $estado]);

        $this->postJson(route('api.v1.gestion.compras.cierre', $orden), ['motivo' => 'El resto no viene'])
            ->assertForbidden()
            ->assertJsonPath('codigo', 'PERMISO_INSUFICIENTE');

        expect($orden->fresh()->estado->value)->toBe($estado);
    }
});

// ============================================================
// Recepcion total y parcial
// ============================================================

it('recibe el pedido completo y lo deja cerrado', function () {
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $orden = pedidoListoParaRecibir(2, null, 10);

    $this->postJson(
        route('api.v1.gestion.compras.recepciones', $orden),
        entregaDe($orden, [0 => 10, 1 => 10], 'R-0001-00004521')
    )
        ->assertCreated()
        ->assertJsonPath('data.estado', 'recibida')
        ->assertJsonPath('recepcion.unidades', 20)
        ->assertJsonPath('recepcion.renglones', 2)
        ->assertJsonPath('recepcion.cerrada', true)
        ->assertJsonPath('recepcion.remito', 'R-0001-00004521')
        ->assertJsonPath('recepcion.ventas_liberadas', 0)
        ->assertJsonPath('data.unidades.pendientes', 0)
        ->assertJsonPath('data.unidades.recibidas', 20);

    $orden->refresh()->load('lineas');

    expect($orden->estado)->toBe(EstadoOrdenCompra::Recibida);

    foreach ($orden->lineas as $linea) {
        expect($linea->cantidad_recibida)->toBe(10)
            ->and($linea->pendiente)->toBe(0);
    }
});

it('recibe una entrega parcial y deja el pedido abierto', function () {
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $orden = pedidoListoParaRecibir(2, null, 10);

    $this->postJson(route('api.v1.gestion.compras.recepciones', $orden), entregaDe($orden, [0 => 4]))
        ->assertCreated()
        ->assertJsonPath('data.estado', 'recibida_parcial')
        ->assertJsonPath('recepcion.unidades', 4)
        ->assertJsonPath('recepcion.renglones', 1)
        ->assertJsonPath('recepcion.cerrada', false)
        ->assertJsonPath('data.unidades.pendientes', 16);

    $orden->refresh()->load('lineas');

    expect($orden->estado)->toBe(EstadoOrdenCompra::RecibidaParcial)
        ->and($orden->lineas[0]->cantidad_recibida)->toBe(4)
        ->and($orden->lineas[0]->pendiente)->toBe(6)
        // El renglon que no vino no se toca y no genera movimiento.
        ->and($orden->lineas[1]->cantidad_recibida)->toBe(0);

    expect(MovimientoStock::count())->toBe(1);
});

it('cierra el pedido con la entrega que completa el acumulado', function () {
    // <b>El estado sale del acumulado de TODOS los renglones</b>, no del tamaño
    // de esta entrega: 'recibida_parcial → recibida_parcial' esta declarada en
    // el enum justamente para que la segunda parcial no explote.
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $orden = pedidoListoParaRecibir(1, null, 10);

    $this->postJson(route('api.v1.gestion.compras.recepciones', $orden), entregaDe($orden, [0 => 6], 'R-1'))
        ->assertCreated()
        ->assertJsonPath('recepcion.cerrada', false);

    $this->postJson(route('api.v1.gestion.compras.recepciones', $orden), entregaDe($orden, [0 => 3], 'R-2'))
        ->assertCreated()
        ->assertJsonPath('data.estado', 'recibida_parcial');

    $this->postJson(route('api.v1.gestion.compras.recepciones', $orden), entregaDe($orden, [0 => 1], 'R-3'))
        ->assertCreated()
        ->assertJsonPath('data.estado', 'recibida')
        ->assertJsonPath('recepcion.cerrada', true);

    expect($orden->fresh()->load('lineas')->lineas[0]->cantidad_recibida)->toBe(10)
        ->and(MovimientoStock::count())->toBe(3);
});

it('ignora los renglones que no vinieron', function () {
    // Un cero no es un error: es el producto que el proveedor no mando.
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $orden = pedidoListoParaRecibir(2, null, 10);

    $this->postJson(route('api.v1.gestion.compras.recepciones', $orden), entregaDe($orden, [0 => 5, 1 => 0]))
        ->assertCreated()
        ->assertJsonPath('recepcion.renglones', 1)
        ->assertJsonPath('recepcion.unidades', 5);

    expect(MovimientoStock::count())->toBe(1);
});

// ============================================================
// El movimiento de stock
// ============================================================

it('registra el movimiento de compra con su origen, su remito y su autor', function () {
    // <b>El movimiento ES el registro de la entrada.</b> El modelo de datos
    // decidio no tener tabla de recepciones, asi que el remito viaja en el
    // 'motivo' del movimiento y no en la orden: una orden puede recibir tres
    // remitos distintos.
    $empleado = administrativoDeCompras();

    Sanctum::actingAs($empleado, ['gestion']);

    $orden    = pedidoListoParaRecibir(1, null, 10);
    $producto = $orden->lineas[0]->producto;

    $this->postJson(
        route('api.v1.gestion.compras.recepciones', $orden),
        entregaDe($orden, [0 => 7], 'R-0001-00009876')
    )->assertCreated();

    $movimiento = MovimientoStock::sole();

    expect($movimiento->tipo)->toBe(TipoMovimientoStock::Compra)
        ->and($movimiento->producto_id)->toBe($producto->id)
        ->and($movimiento->cantidad_fisico)->toBe(7)
        ->and($movimiento->cantidad_comprometido)->toBe(0)
        ->and($movimiento->origen_id)->toBe($orden->id)
        ->and($movimiento->origen_type)->toBe(OrdenCompra::class)
        ->and($movimiento->motivo)->toBe('R-0001-00009876')
        ->and($movimiento->usuario_id)->toBe($empleado->id);
});

it('sube el fisico exactamente lo que entro y no toca el comprometido', function () {
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $orden    = pedidoListoParaRecibir(1, null, 10);
    $producto = $orden->lineas[0]->producto;

    $fisicoAntes       = $producto->stock_fisico;
    $comprometidoAntes = $producto->stock_comprometido;

    $this->postJson(route('api.v1.gestion.compras.recepciones', $orden), entregaDe($orden, [0 => 6]))
        ->assertCreated();

    $producto->refresh();

    expect($producto->stock_fisico)->toBe($fisicoAntes + 6)
        ->and($producto->stock_comprometido)->toBe($comprometidoAntes)
        // stock_disponible es columna generada: lo recalcula el motor.
        ->and($producto->stock_disponible)->toBe($fisicoAntes + 6 - $comprometidoAntes);
});

it('registra un movimiento por renglon y no uno por unidad', function () {
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $orden = pedidoListoParaRecibir(2, null, 10);

    $this->postJson(route('api.v1.gestion.compras.recepciones', $orden), entregaDe($orden, [0 => 5, 1 => 3]))
        ->assertCreated();

    expect(MovimientoStock::count())->toBe(2)
        ->and(MovimientoStock::sum('cantidad_fisico'))->toBe(8);
});

// ============================================================
// Excedente
// ============================================================

it('frena el excedente sin confirmar y no escribe nada', function () {
    // Recibir de mas esta permitido: la mercaderia esta en el deposito y
    // negarse a registrarla obliga al operador a cargar lo pedido y despues un
    // ajuste por la diferencia —un ajuste que no tiene origen apuntando a la
    // orden—. Pero sin ninguna friccion, un 120 donde iba 12 mete 120 unidades
    // al fisico. Por eso el excedente no se rechaza: se confirma.
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $orden    = pedidoListoParaRecibir(1, null, 10);
    $producto = $orden->lineas[0]->producto;

    $fisicoAntes = $producto->stock_fisico;

    $this->postJson(route('api.v1.gestion.compras.recepciones', $orden), entregaDe($orden, [0 => 12]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('confirmar_excedente')
        ->assertJsonPath(
            'errors.confirmar_excedente.0',
            fn (string $mensaje) => str_contains($mensaje, 'más de lo pedido')
        );

    expect(MovimientoStock::count())->toBe(0)
        ->and($producto->fresh()->stock_fisico)->toBe($fisicoAntes)
        ->and($orden->fresh()->load('lineas')->lineas[0]->cantidad_recibida)->toBe(0)
        ->and($orden->fresh()->estado)->toBe(EstadoOrdenCompra::Enviada);
});

it('registra el excedente cuando el operador lo confirma', function () {
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $orden = pedidoListoParaRecibir(1, null, 10);

    $this->postJson(route('api.v1.gestion.compras.recepciones', $orden), [
        ...entregaDe($orden, [0 => 12]),
        'confirmar_excedente' => true,
    ])
        ->assertCreated()
        ->assertJsonPath('data.estado', 'recibida')
        ->assertJsonPath('data.lineas.0.cantidad_recibida', 12)
        ->assertJsonPath('data.lineas.0.excedente', 2)
        // 'pendiente' nunca es negativo, aunque el proveedor haya mandado de mas.
        ->assertJsonPath('data.lineas.0.pendiente', 0)
        ->assertJsonPath('data.unidades.pendientes', 0);

    expect(MovimientoStock::sole()->cantidad_fisico)->toBe(12);
});

it('vuelve a mirar el excedente con el candado tomado', function () {
    // <b>La validacion del FormRequest corre FUERA del candado.</b> El pipeline
    // resuelve RecepcionRequest antes de entrar al controlador, asi que lee el
    // acumulado en un momento en que otra entrega del mismo pedido todavia
    // puede estar por aplicarse. El controlador vuelve a preguntarlo ya con el
    // candado puesto, reusando el mismo metodo del Request.
    //
    // Esta prueba ejercita esa segunda lectura de la unica forma que un proceso
    // puede: dejando que la primera entrega agote lo pedido, de modo que la
    // segunda sea excedente por el ACUMULADO y no por su propio tamaño.
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $orden = pedidoListoParaRecibir(1, null, 10);

    $this->postJson(route('api.v1.gestion.compras.recepciones', $orden), entregaDe($orden, [0 => 10], 'R-1'))
        ->assertCreated()
        ->assertJsonPath('data.estado', 'recibida');

    // El pedido ya esta 'recibida', que es terminal: la policy lo frena antes.
    // Lo que importa del caso del excedente acumulado se prueba sobre un pedido
    // que sigue abierto, abajo.
    $otro = pedidoListoParaRecibir(1, null, 10);

    $this->postJson(route('api.v1.gestion.compras.recepciones', $otro), entregaDe($otro, [0 => 8], 'R-A'))
        ->assertCreated();

    // 8 + 4 = 12 sobre 10 pedidas: ninguna de las dos entregas es excedente por
    // si sola, el acumulado si.
    $this->postJson(route('api.v1.gestion.compras.recepciones', $otro), entregaDe($otro, [0 => 4], 'R-B'))
        ->assertStatus(422)
        ->assertJsonValidationErrors('confirmar_excedente');

    expect($otro->fresh()->load('lineas')->lineas[0]->cantidad_recibida)->toBe(8);
});

// ============================================================
// Validacion de las cantidades
// ============================================================

it('rechaza un cuerpo sin nada que recibir', function () {
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $orden = pedidoListoParaRecibir(1, null, 10);
    $url   = route('api.v1.gestion.compras.recepciones', $orden);

    // Sin la clave.
    $this->postJson($url, ['remito' => 'R-1'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('cantidades');

    // Con todo en cero.
    $this->postJson($url, entregaDe($orden, [0 => 0]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('cantidades');

    expect(MovimientoStock::count())->toBe(0);
});

it('rechaza cantidades negativas', function () {
    // Una recepcion no devuelve mercaderia: para sacar unidades del deposito
    // esta el ajuste de inventario.
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $orden = pedidoListoParaRecibir(1, null, 10);

    $this->postJson(route('api.v1.gestion.compras.recepciones', $orden), entregaDe($orden, [0 => -3]))
        ->assertStatus(422);

    expect(MovimientoStock::count())->toBe(0);
});

it('rechaza un renglon que no pertenece al pedido', function () {
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $orden = pedidoListoParaRecibir(1, null, 10);
    $ajeno = pedidoListoParaRecibir(1, null, 10);

    $this->postJson(route('api.v1.gestion.compras.recepciones', $orden), [
        'cantidades' => [(string) $ajeno->lineas[0]->id => 3],
        'remito'     => 'R-1',
    ])
        ->assertStatus(422)
        ->assertJsonPath('message', fn (string $m) => str_contains($m, 'no pertenece al pedido'));

    expect(MovimientoStock::count())->toBe(0);
});

// ============================================================
// Idempotencia por remito
// ============================================================

it('rechaza el mismo remito dos veces contra el mismo pedido', function () {
    // <b>Es la guarda de idempotencia.</b> Nada mas en la cadena distingue «dos
    // entregas legitimas» de «la misma entrega mandada dos veces»: los
    // incrementos son relativos y 'recibida_parcial → recibida_parcial' es una
    // transicion valida. Un cliente que reintenta por timeout sumaria las
    // unidades dos veces al fisico.
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $orden    = pedidoListoParaRecibir(1, null, 10);
    $producto = $orden->lineas[0]->producto;

    $fisicoAntes = $producto->stock_fisico;

    $cuerpo = entregaDe($orden, [0 => 4], 'R-0001-00004521');

    $this->postJson(route('api.v1.gestion.compras.recepciones', $orden), $cuerpo)
        ->assertCreated();

    $this->postJson(route('api.v1.gestion.compras.recepciones', $orden), $cuerpo)
        ->assertStatus(409)
        ->assertJsonPath('codigo', 'REMITO_DUPLICADO')
        ->assertJsonPath('message', fn (string $m) => str_contains($m, 'R-0001-00004521'));

    // Ni un segundo movimiento, ni unidades duplicadas, ni acumulado tocado.
    expect(MovimientoStock::count())->toBe(1)
        ->and($producto->fresh()->stock_fisico)->toBe($fisicoAntes + 4)
        ->and($orden->fresh()->load('lineas')->lineas[0]->cantidad_recibida)->toBe(4);
});

it('acepta el mismo remito contra otro pedido', function () {
    // El alcance de la guarda es por documento, y es lo correcto: lo que se
    // impide es cargar dos veces la misma entrega contra el mismo pedido, no
    // que dos proveedores usen numeraciones que se pisan.
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $uno = pedidoListoParaRecibir(1, null, 10);
    $dos = pedidoListoParaRecibir(1, null, 10);

    $this->postJson(route('api.v1.gestion.compras.recepciones', $uno), entregaDe($uno, [0 => 4], 'R-777'))
        ->assertCreated();

    $this->postJson(route('api.v1.gestion.compras.recepciones', $dos), entregaDe($dos, [0 => 4], 'R-777'))
        ->assertCreated();

    expect(MovimientoStock::count())->toBe(2);
});

it('acepta remitos distintos contra el mismo pedido', function () {
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $orden = pedidoListoParaRecibir(1, null, 10);

    $this->postJson(route('api.v1.gestion.compras.recepciones', $orden), entregaDe($orden, [0 => 4], 'R-1'))
        ->assertCreated();

    $this->postJson(route('api.v1.gestion.compras.recepciones', $orden), entregaDe($orden, [0 => 3], 'R-2'))
        ->assertCreated();

    expect(MovimientoStock::count())->toBe(2)
        ->and($orden->fresh()->load('lineas')->lineas[0]->cantidad_recibida)->toBe(7);
});

it('no tiene idempotencia cuando la entrega no trae remito', function () {
    // <b>Limitacion conocida y deliberada.</b> Sin numero no hay nada que
    // comparar, y dos entregas del mismo proveedor en el mismo dia sin
    // documento pueden ser las dos legitimas. Esta prueba lo fija para que no
    // se «arregle» sin darse cuenta: la forma de tener idempotencia es cargar
    // el remito.
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $orden = pedidoListoParaRecibir(1, null, 10);

    $cuerpo = entregaDe($orden, [0 => 3], remito: null);

    $this->postJson(route('api.v1.gestion.compras.recepciones', $orden), $cuerpo)->assertCreated();
    $this->postJson(route('api.v1.gestion.compras.recepciones', $orden), $cuerpo)->assertCreated();

    expect(MovimientoStock::count())->toBe(2)
        ->and($orden->fresh()->load('lineas')->lineas[0]->cantidad_recibida)->toBe(6);
});

// ============================================================
// Preventas
// ============================================================

it('destraba las ventas que esperaban esta mercaderia', function () {
    // <b>La recepcion no es solo un alta de inventario.</b> La mercaderia que
    // entra puede estar ya vendida y esperada por alguien que pago hace tres
    // semanas. La cola se recorre DESPUES del commit, a proposito: lo que se
    // promete tiene que apoyarse en un hecho firme.
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $proveedor = proveedorConCasilla();
    $producto  = productoPedible($proveedor, ['stock_fisico' => 0]);

    $orden = ordenDeCompraDePrueba(['proveedor_id' => $proveedor->id, 'estado' => 'enviada']);
    lineaCrudaEn($orden, $producto, 10);
    $orden = $orden->fresh('lineas');

    $venta = ventaEnPreventa($producto, 5);

    $this->postJson(route('api.v1.gestion.compras.recepciones', $orden), entregaDe($orden, [0 => 5]))
        ->assertCreated()
        ->assertJsonPath('recepcion.ventas_liberadas', 1);

    expect($venta->fresh()->estado)->toBe(EstadoVenta::EnPreparacion);
});

it('deja esperando la venta que lo recibido no alcanza a cubrir', function () {
    // El orden por antiguedad decide a quien se le promete primero, no cuanto
    // hay para prometer: lo que se compara es el fisico LIBRE.
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $proveedor = proveedorConCasilla();
    $producto  = productoPedible($proveedor, ['stock_fisico' => 0]);

    $orden = ordenDeCompraDePrueba(['proveedor_id' => $proveedor->id, 'estado' => 'enviada']);
    lineaCrudaEn($orden, $producto, 10);
    $orden = $orden->fresh('lineas');

    $venta = ventaEnPreventa($producto, 8);

    $this->postJson(route('api.v1.gestion.compras.recepciones', $orden), entregaDe($orden, [0 => 3]))
        ->assertCreated()
        ->assertJsonPath('recepcion.ventas_liberadas', 0);

    expect($venta->fresh()->estado)->toBe(EstadoVenta::PendienteStock);
});

// ============================================================
// Pedido repartido: madre e hija
// ============================================================

it('sincroniza el pedido madre cuando una de sus ordenes recibe', function () {
    Mail::fake();

    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $madre = pedidoMezcladoEnBorrador(2);

    $ordenes = app(OrdenCompraService::class);
    $ordenes->aprobar($madre, administrativoDeCompras());
    $ordenes->enviarTodas($madre->fresh(), administrativoDeCompras());

    $hijas   = $madre->fresh()->hijas;
    $primera = $hijas->first()->fresh('lineas');

    $this->postJson(route('api.v1.gestion.compras.recepciones', $primera), entregaDe($primera, [0 => 2]))
        ->assertCreated()
        ->assertJsonPath('data.estado', 'recibida')
        ->assertJsonPath('data.padre.numero', $madre->numero);

    // El resumen de la madre es el del renglon mas atrasado: una hija entregada
    // y otra sin entregar deja el pedido a medio camino, no terminado.
    expect($madre->fresh()->estado)->toBe(EstadoOrdenCompra::RecibidaParcial)
        ->and($hijas->last()->fresh()->estado)->toBe(EstadoOrdenCompra::Enviada);
});

it('deja la madre recibida cuando entregaron todos los proveedores', function () {
    Mail::fake();

    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $madre = pedidoMezcladoEnBorrador(2);

    $ordenes = app(OrdenCompraService::class);
    $ordenes->aprobar($madre, administrativoDeCompras());
    $ordenes->enviarTodas($madre->fresh(), administrativoDeCompras());

    foreach ($madre->fresh()->hijas as $i => $hija) {
        $hija = $hija->fresh('lineas');

        $this->postJson(
            route('api.v1.gestion.compras.recepciones', $hija),
            entregaDe($hija, [0 => 2], "R-{$i}")
        )->assertCreated();
    }

    expect($madre->fresh()->estado)->toBe(EstadoOrdenCompra::Recibida);
});

// ============================================================
// Cierre con faltante
// ============================================================

it('cierra el pedido con lo recibido y anota el faltante', function () {
    // Es la salida para el proveedor que manda 8 de 10 y avisa que el resto no
    // viene. Sin esto la orden se queda en 'recibida_parcial' para siempre, y
    // ese es un estado ABIERTO: el restock da el faltante por pedido y no vuelve
    // a pedir esos productos nunca.
    $empleado = administrativoDeCompras();

    Sanctum::actingAs($empleado, ['gestion']);

    $orden = pedidoListoParaRecibir(1, null, 10);

    $this->postJson(route('api.v1.gestion.compras.recepciones', $orden), entregaDe($orden, [0 => 8]))
        ->assertCreated()
        ->assertJsonPath('data.estado', 'recibida_parcial');

    $this->postJson(route('api.v1.gestion.compras.cierre', $orden), [
        'motivo' => 'El proveedor avisó que el resto no viene.',
    ])
        ->assertOk()
        ->assertJsonPath('data.estado', 'recibida');

    $orden->refresh()->load('lineas.producto');

    $codigo = $orden->lineas[0]->producto->codigo;

    expect($orden->estado)->toBe(EstadoOrdenCompra::Recibida)
        ->and($orden->observaciones)
        ->toContain("Cerrado con faltante por {$empleado->usuario}")
        ->toContain('El proveedor avisó que el resto no viene.')
        ->toContain("Quedó sin recibir: {$codigo} x2");
});

it('exige el motivo del cierre, con su propio mensaje', function () {
    // MotivoOrdenCompraRequest es el mismo de la cancelacion y distingue por la
    // ruta: cerrar no cancela, da por terminado lo que el proveedor ya no va a
    // mandar, y decirle lo otro al operador seria mentirle.
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $orden = pedidoListoParaRecibir(1, null, 10, ['estado' => 'recibida_parcial']);

    $this->postJson(route('api.v1.gestion.compras.cierre', $orden), [])
        ->assertStatus(422)
        ->assertJsonValidationErrors('motivo')
        ->assertJsonPath('errors.motivo.0', 'Escribí por qué se da por cerrado el pedido con faltante.');

    $this->postJson(route('api.v1.gestion.compras.cierre', $orden), ['motivo' => str_repeat('x', 256)])
        ->assertStatus(422)
        ->assertJsonValidationErrors('motivo');

    expect($orden->fresh()->estado)->toBe(EstadoOrdenCompra::RecibidaParcial);
});

it('no mueve stock al cerrar con faltante', function () {
    // Cerrar a mano no es recibir: no entra mercaderia. Lo que cambia es que el
    // pedido deja de estar abierto.
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $orden    = pedidoListoParaRecibir(1, null, 10);
    $producto = $orden->lineas[0]->producto;

    $this->postJson(route('api.v1.gestion.compras.recepciones', $orden), entregaDe($orden, [0 => 8]))
        ->assertCreated();

    $fisicoTrasRecibir = $producto->fresh()->stock_fisico;

    $this->postJson(route('api.v1.gestion.compras.cierre', $orden), ['motivo' => 'No viene el resto.'])
        ->assertOk();

    expect(MovimientoStock::count())->toBe(1)
        ->and($producto->fresh()->stock_fisico)->toBe($fisicoTrasRecibir);
});

it('avisa a la reposicion por los dos caminos que cierran el pedido', function () {
    // OrdenCompraCerrada es el unico aviso que recibe el restock: el listener
    // reactivo del stock descarta las compras —suben el disponible— y el cierre
    // a mano ni siquiera mueve stock.
    Event::fake([OrdenCompraCerrada::class]);

    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    // Camino 1: se recibio todo.
    $completo = pedidoListoParaRecibir(1, null, 10);

    $this->postJson(route('api.v1.gestion.compras.recepciones', $completo), entregaDe($completo, [0 => 10]))
        ->assertCreated();

    // Camino 2: se cerro a mano con faltante.
    $parcial = pedidoListoParaRecibir(1, null, 10, ['estado' => 'recibida_parcial']);

    $this->postJson(route('api.v1.gestion.compras.cierre', $parcial), ['motivo' => 'No viene.'])
        ->assertOk();

    Event::assertDispatched(OrdenCompraCerrada::class, 2);
});

// ============================================================
// Historial de recepciones en el detalle
// ============================================================

it('publica el historial de entregas en el detalle del pedido', function () {
    // <b>Sin tabla nueva.</b> Cada entrada ES un movimiento de stock con origen
    // en la orden y el remito en su motivo, y lo sirve el MovimientoStockResource
    // que ya existia para el kardex.
    $empleado = administrativoDeCompras();

    Sanctum::actingAs($empleado, ['gestion']);

    $orden    = pedidoListoParaRecibir(1, null, 10);
    $producto = $orden->lineas[0]->producto;

    $this->postJson(route('api.v1.gestion.compras.recepciones', $orden), entregaDe($orden, [0 => 4], 'R-VIEJO'))
        ->assertCreated();

    // <b>Un segundo de por medio, y no es cosmético.</b> recepciones() ordena
    // por created_at, que es un timestamp de segundos: dos entregas cargadas
    // dentro del mismo segundo quedan en un orden que la base no garantiza. En
    // producción no pasa —dos remitos no entran al depósito en el mismo
    // instante— pero en una prueba sí, y una prueba que depende de un desempate
    // indefinido falla cuando menos conviene. Es una limitación conocida de la
    // relación; está anotada en el diagnóstico de la entrega.
    $this->travel(1)->second();

    $this->postJson(route('api.v1.gestion.compras.recepciones', $orden), entregaDe($orden, [0 => 3], 'R-NUEVO'))
        ->assertCreated()
        ->assertJsonCount(2, 'data.recepciones');

    $this->getJson(route('api.v1.gestion.compras.show', $orden))
        ->assertOk()
        ->assertJsonCount(2, 'data.recepciones')
        ->assertJsonPath('data.recepciones.0.tipo', 'compra')
        ->assertJsonPath('data.recepciones.0.motivo', 'R-NUEVO')
        ->assertJsonPath('data.recepciones.0.cantidad_fisico', 3)
        ->assertJsonPath('data.recepciones.0.producto.codigo', $producto->codigo)
        ->assertJsonPath('data.recepciones.0.usuario.nombre', $empleado->nombre_completo)
        ->assertJsonPath('data.recepciones.0.origen.id', $orden->id)
        ->assertJsonPath('data.recepciones.1.motivo', 'R-VIEJO')
        ->assertJsonStructure([
            'data' => ['recepciones' => [['id', 'tipo', 'motivo', 'cantidad_fisico', 'registrado_el']]],
        ]);
});

it('trae el historial vacio en un pedido que todavia no recibio nada', function () {
    // La respuesta tiene la misma forma sea cual sea el estado del documento:
    // la clave viaja siempre, igual que 'hijas' y 'padre'.
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $orden = pedidoDeUnProveedor();

    $this->getJson(route('api.v1.gestion.compras.show', $orden))
        ->assertOk()
        ->assertJsonPath('data.recepciones', []);
});

it('no mezcla el historial de un pedido con el de otro', function () {
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $uno = pedidoListoParaRecibir(1, null, 10);
    $dos = pedidoListoParaRecibir(1, null, 10);

    $this->postJson(route('api.v1.gestion.compras.recepciones', $uno), entregaDe($uno, [0 => 4], 'R-UNO'))
        ->assertCreated();

    $this->getJson(route('api.v1.gestion.compras.show', $dos))
        ->assertOk()
        ->assertJsonPath('data.recepciones', []);

    $this->getJson(route('api.v1.gestion.compras.show', $uno))
        ->assertOk()
        ->assertJsonCount(1, 'data.recepciones')
        ->assertJsonPath('data.recepciones.0.motivo', 'R-UNO');
});

// ============================================================
// El candado
// ============================================================

it('suelta el candado del pedido al terminar la recepcion', function () {
    // El candado de la recepcion vive 60 segundos y no 30: la retiene durante
    // la liberacion de preventas. Si no se soltara, el cierre siguiente
    // contestaria 422 sin motivo.
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $orden = pedidoListoParaRecibir(1, null, 10);

    $this->postJson(route('api.v1.gestion.compras.recepciones', $orden), entregaDe($orden, [0 => 4]))
        ->assertCreated();

    $candado = Cache::lock("orden-compra:transicion:{$orden->id}", 5);

    expect($candado->get())->toBeTrue();

    $candado->release();

    // Y el pedido sigue operable en el acto.
    $this->postJson(route('api.v1.gestion.compras.cierre', $orden), ['motivo' => 'El resto no viene.'])
        ->assertOk();
});
