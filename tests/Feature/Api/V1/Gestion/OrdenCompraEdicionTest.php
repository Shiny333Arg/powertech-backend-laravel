<?php

use App\Models\MovimientoStock;
use App\Models\OrdenCompra;
use App\Models\Permiso;
use App\Models\User;
use Database\Seeders\Maestros\AlicuotaIvaSeeder;
use Database\Seeders\Maestros\CondicionIvaSeeder;
use Database\Seeders\Maestros\NumeradorSeeder;
use Database\Seeders\Maestros\PermisoSeeder;
use Database\Seeders\Maestros\RolSeeder;
use Laravel\Sanctum\Sanctum;

/*
|--------------------------------------------------------------------------
| PUT /api/v1/gestion/compras/{orden} — edicion del detalle del borrador
|--------------------------------------------------------------------------
|
| El endpoint que entro con la migracion de la pantalla de edicion del panel.
| Hasta entonces la edicion existia solo como formulario Blade contra
| gestion.compras.update, que se retiro junto con esta entrega.
|
| <b>No agrega ninguna regla.</b> Delega en OrdenCompraService::actualizar(),
| que es el mismo metodo que invocaba el controlador web, y valida con el mismo
| OrdenCompraRequest que el alta. Lo que se fija aca son cuatro cosas: que el
| detalle se reemplace de verdad, que el destinatario se recalcule desde los
| renglones, que el estado mande sobre el permiso, y que editar un pedido NO
| toque el inventario.
|
| El actor es el ADMINISTRATIVO, igual que en OrdenCompraTest: segun la matriz
| real de rol_permiso, vendedor y cajero no tienen ninguno de los ocho permisos
| de compras.
|
| NumeradorSeeder hace falta porque varias pruebas parten de un pedido creado
| por el endpoint de alta, que toma su numero de la serie 'orden_compra'.
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

/** El administrativo, autenticado con un token de gestion. */
function administrativoQueEdita(): User
{
    $empleado = administrativoDeCompras();

    Sanctum::actingAs($empleado, ['gestion']);

    return $empleado;
}

// ============================================================
// Las tres capas de acceso
// ============================================================

it('rechaza la edicion sin token', function () {
    $orden = pedidoDeUnProveedor();

    $this->putJson(route('api.v1.gestion.compras.update', $orden), [
        'lineas' => [renglonDePedido(productoPedible())],
    ])->assertUnauthorized();
});

it('rechaza un token de empleado emitido para la tienda', function () {
    $orden = pedidoDeUnProveedor();

    // El usuario es correcto y el token no: lo frena 'abilities:gestion'.
    Sanctum::actingAs(administrativoDeCompras(), ['tienda']);

    $this->putJson(route('api.v1.gestion.compras.update', $orden), [
        'lineas' => [renglonDePedido(productoPedible())],
    ])->assertForbidden();
});

it('rechaza al que no tiene el permiso de editar', function () {
    $orden = pedidoDeUnProveedor();

    $empleado = administrativoQueEdita();

    $empleado->rol->permisos()->detach(
        Permiso::where('clave', 'compra.editar')->value('id')
    );

    $this->putJson(route('api.v1.gestion.compras.update', $orden), [
        'lineas' => [renglonDePedido(productoPedible())],
    ])->assertForbidden();
});

// ============================================================
// La edicion
// ============================================================

it('reemplaza los renglones del borrador y recalcula el total', function () {
    $proveedor = proveedorConCasilla();
    $orden     = pedidoDeUnProveedor('borrador', $proveedor, cantidad: 2);

    $otro = productoPedible($proveedor, ['costo' => 1000.00]);

    administrativoQueEdita();

    $this->putJson(route('api.v1.gestion.compras.update', $orden), [
        'lineas' => [renglonDePedido($otro, 3)],
        'observaciones' => 'Se cambió el detalle.',
    ])
        ->assertOk()
        ->assertJsonPath('data.id', $orden->id)
        // El numero NO cambia: se edita el detalle, no el documento.
        ->assertJsonPath('data.numero', $orden->numero)
        ->assertJsonPath('data.estado', 'borrador')
        ->assertJsonCount(1, 'data.lineas')
        ->assertJsonPath('data.lineas.0.producto.id', $otro->id)
        ->assertJsonPath('data.lineas.0.cantidad_pedida', 3)
        // El costo lo toma del catalogo, no del cuerpo: el cuerpo no lo manda.
        ->assertJsonPath('data.lineas.0.costo_unitario', 1000)
        ->assertJsonPath('data.total_estimado', 3000)
        ->assertJsonPath('data.observaciones', 'Se cambió el detalle.')
        ->assertJsonPath('data.unidades.pedidas', 3);

    // El renglon viejo se fue de verdad.
    expect($orden->fresh()->lineas()->count())->toBe(1);
});

it('deja el pedido dirigido a un solo proveedor cuando queda uno solo', function () {
    // Arranca mezclado —dos proveedores, sin destinatario propio— y se le saca
    // el renglon de uno: vuelve a ser una orden comun dirigida al que queda.
    $orden = pedidoMezcladoEnBorrador(2);

    $primero = $orden->lineas->first();

    administrativoQueEdita();

    $this->putJson(route('api.v1.gestion.compras.update', $orden), [
        'lineas' => [[
            'producto_id'     => $primero->producto_id,
            'cantidad_pedida' => 5,
        ]],
    ])
        ->assertOk()
        ->assertJsonPath('data.forma', 'simple')
        ->assertJsonPath('data.proveedor.id', $primero->proveedor_id)
        ->assertJsonCount(1, 'data.proveedores');
});

it('convierte el pedido en uno repartido al agregarle productos de otro proveedor', function () {
    $orden = pedidoDeUnProveedor();

    $delPedido = $orden->lineas->first();
    $deOtro    = productoPedible(proveedorConCasilla());

    administrativoQueEdita();

    $this->putJson(route('api.v1.gestion.compras.update', $orden), [
        'lineas' => [
            ['producto_id' => $delPedido->producto_id, 'cantidad_pedida' => 2],
            renglonDePedido($deOtro, 4),
        ],
    ])
        ->assertOk()
        // Sin destinatario propio: se reparte al aprobarse, una orden por
        // proveedor. Las hijas todavia no existen, y es correcto: el reparto lo
        // hace aprobar().
        ->assertJsonPath('data.forma', 'madre')
        ->assertJsonPath('data.proveedor', null)
        ->assertJsonCount(2, 'data.proveedores')
        ->assertJsonCount(0, 'data.hijas');
});

it('no toca el inventario', function () {
    // Editar un pedido es cambiar una intencion de compra. El stock se mueve en
    // un solo punto del circuito, que es la recepcion de mercaderia.
    $orden = pedidoDeUnProveedor();

    administrativoQueEdita();

    $this->putJson(route('api.v1.gestion.compras.update', $orden), [
        'lineas' => [renglonDePedido(productoPedible(), 7)],
    ])->assertOk();

    expect(MovimientoStock::count())->toBe(0);
});

it('conserva las observaciones cuando el cuerpo no las manda', function () {
    $orden = pedidoDeUnProveedor('borrador', null, ['observaciones' => 'Nota original.']);

    administrativoQueEdita();

    $this->putJson(route('api.v1.gestion.compras.update', $orden), [
        'lineas' => [renglonDePedido(productoPedible(), 1)],
    ])
        ->assertOk()
        ->assertJsonPath('data.observaciones', 'Nota original.');
});

// ============================================================
// El estado manda sobre el permiso
// ============================================================

it('rechaza con 403 la edicion de una orden enviada', function () {
    // El administrativo tiene 'compra.editar', y no alcanza: el documento ya
    // esta en manos del proveedor. Es OrdenCompraPolicy::update(), que consulta
    // esEditable(). 403 y no 422 a proposito: no es un cuerpo invalido, es que
    // sobre este documento esa operacion no esta permitida.
    $orden = pedidoDeUnProveedor('enviada');

    administrativoQueEdita();

    $this->putJson(route('api.v1.gestion.compras.update', $orden), [
        'lineas' => [renglonDePedido(productoPedible())],
    ])->assertForbidden();

    // Y el detalle quedo intacto.
    expect($orden->fresh()->lineas()->count())->toBe(1);
});

it('rechaza con 403 la edicion de una orden aprobada, recibida o cancelada', function (string $estado) {
    $orden = pedidoDeUnProveedor($estado);

    administrativoQueEdita();

    $this->putJson(route('api.v1.gestion.compras.update', $orden), [
        'lineas' => [renglonDePedido(productoPedible())],
    ])->assertForbidden();
})->with(['aprobada', 'recibida', 'cancelada']);

it('responde 404 sobre un pedido que no existe', function () {
    administrativoQueEdita();

    $this->putJson('/api/v1/gestion/compras/999999', [
        'lineas' => [renglonDePedido(productoPedible())],
    ])->assertNotFound();
});

// ============================================================
// Validacion: el mismo OrdenCompraRequest del alta
// ============================================================

it('rechaza un pedido sin renglones', function () {
    $orden = pedidoDeUnProveedor();

    administrativoQueEdita();

    $this->putJson(route('api.v1.gestion.compras.update', $orden), ['lineas' => []])
        ->assertStatus(422)
        ->assertJsonValidationErrors('lineas');
});

it('rechaza un producto repetido en dos renglones', function () {
    $orden    = pedidoDeUnProveedor();
    $producto = productoPedible();

    administrativoQueEdita();

    $this->putJson(route('api.v1.gestion.compras.update', $orden), [
        'lineas' => [renglonDePedido($producto, 1), renglonDePedido($producto, 2)],
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('lineas.1.producto_id');
});

it('rechaza un renglon cuyo proveedor esta dado de baja', function () {
    // Es la regla cruzada de OrdenCompraRequest: lo que no tiene arreglo es un
    // renglon al que no hay a quien pedirselo. Si pasara, reventaria al
    // repartir por proveedor.
    $orden = pedidoDeUnProveedor();

    $deBaja   = proveedorDePrueba(['activo' => false]);
    $producto = productoPedible($deBaja);

    administrativoQueEdita();

    $this->putJson(route('api.v1.gestion.compras.update', $orden), [
        'lineas' => [renglonDePedido($producto)],
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('lineas');
});

it('rechaza una cantidad en cero', function () {
    $orden = pedidoDeUnProveedor();

    administrativoQueEdita();

    $this->putJson(route('api.v1.gestion.compras.update', $orden), [
        'lineas' => [renglonDePedido(productoPedible(), 0)],
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('lineas.0.cantidad_pedida');
});

// ============================================================
// Lo que el cuerpo NO decide
// ============================================================

it('ignora el estado, el origen y el proveedor que vengan en el cuerpo', function () {
    // La misma ausencia que en el alta: el estado se mueve con transiciones, el
    // origen lo fija el servicio y el destinatario sale del detalle. Aceptarlos
    // permitiria disfrazar una orden manual de automatica, o emitirle a una
    // empresa un pedido de productos que no vende.
    $proveedor = proveedorConCasilla();
    $orden     = pedidoDeUnProveedor('borrador', $proveedor);

    $otroProveedor = proveedorConCasilla();

    administrativoQueEdita();

    $this->putJson(route('api.v1.gestion.compras.update', $orden), [
        'lineas'       => [renglonDePedido(productoPedible($proveedor), 1)],
        'estado'       => 'aprobada',
        'origen'       => 'automatica',
        'proveedor_id' => $otroProveedor->id,
        'numero'       => 'OC-FALSIFICADA',
    ])
        ->assertOk()
        ->assertJsonPath('data.estado', 'borrador')
        ->assertJsonPath('data.origen', 'manual')
        ->assertJsonPath('data.numero', $orden->numero)
        ->assertJsonPath('data.proveedor.id', $proveedor->id);

    $this->assertDatabaseHas('ordenes_compra', [
        'id'           => $orden->id,
        'estado'       => 'borrador',
        'origen'       => 'manual',
        'proveedor_id' => $proveedor->id,
    ]);
});

it('no deja aprobar al mismo que edito, si el pedido lo habia creado el', function () {
    // La separacion de funciones no se puede esquivar editando: 'usuario_creo_id'
    // no se reescribe al actualizar el detalle.
    $empleado = administrativoQueEdita();

    $orden = pedidoDeUnProveedor('borrador', null, ['usuario_creo_id' => $empleado->id]);

    $this->putJson(route('api.v1.gestion.compras.update', $orden), [
        'lineas' => [renglonDePedido(productoPedible(), 1)],
    ])->assertOk();

    expect($orden->fresh()->usuario_creo_id)->toBe($empleado->id)
        ->and($empleado->can('aprobar', $orden->fresh()))->toBeFalse();
});

// ============================================================
// La forma de la respuesta
// ============================================================

it('devuelve el detalle completo, con la misma forma que el GET', function () {
    $orden = pedidoDeUnProveedor();

    administrativoQueEdita();

    $respuesta = $this->putJson(route('api.v1.gestion.compras.update', $orden), [
        'lineas' => [renglonDePedido(productoPedible(), 2)],
    ])->assertOk();

    // Las mismas claves que publica OrdenCompraDetalleResource: la pantalla
    // repinta con esta respuesta y no hace un segundo GET.
    $respuesta->assertJsonStructure([
        'data' => [
            'id', 'numero', 'forma', 'estado', 'estado_etiqueta', 'origen',
            'proveedor', 'proveedores', 'total_estimado', 'observaciones',
            'lineas_count', 'lineas', 'unidades', 'recepciones', 'hijas', 'padre',
        ],
    ]);
});

it('publica el estado del proveedor en cada renglon', function () {
    // Lo necesita la pantalla de edicion: un borrador cuyo renglon quedo
    // apuntando a un proveedor dado de baja no se puede guardar, y la pantalla
    // lo marca para que se vea antes de intentarlo.
    $orden = pedidoDeUnProveedor();

    administrativoQueEdita();

    $this->getJson(route('api.v1.gestion.compras.show', $orden))
        ->assertOk()
        ->assertJsonPath('data.lineas.0.proveedor.activo', true);
});

it('sirve tambien una orden hija del reparto si esta en borrador', function () {
    // Una hija nace aprobada, asi que en la practica no se edita; lo que fija
    // esta prueba es que el limite lo pone el ESTADO y no la forma del
    // documento. Un borrador hijo —que solo existiria por una correccion
    // manual— se edita como cualquier otro.
    $madre = ordenDeCompraDePrueba(['proveedor_id' => null, 'estado' => 'aprobada']);

    $proveedor = proveedorConCasilla();

    $hija = ordenDeCompraDePrueba([
        'proveedor_id'   => $proveedor->id,
        'orden_padre_id' => $madre->id,
        'estado'         => 'borrador',
    ]);

    lineaCrudaEn($hija, productoPedible($proveedor));

    administrativoQueEdita();

    $this->putJson(route('api.v1.gestion.compras.update', $hija), [
        'lineas' => [renglonDePedido(productoPedible($proveedor), 3)],
    ])
        ->assertOk()
        ->assertJsonPath('data.forma', 'hija')
        ->assertJsonPath('data.padre.numero', $madre->numero);
});

// ============================================================
// El limite: la edicion no es una transicion
// ============================================================

it('no acepta PATCH sobre el detalle', function () {
    // El cuerpo reemplaza el detalle completo, que es lo que valida
    // OrdenCompraRequest. PATCH queda para las acciones de estado, y en compras
    // esas son POST sobre sub-recursos.
    $orden = pedidoDeUnProveedor();

    administrativoQueEdita();

    $this->patchJson("/api/v1/gestion/compras/{$orden->id}", [
        'lineas' => [renglonDePedido(productoPedible())],
    ])->assertStatus(405);

    expect(OrdenCompra::find($orden->id)->lineas()->count())->toBe(1);
});
