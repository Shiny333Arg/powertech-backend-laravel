<?php

use App\Models\MovimientoStock;
use App\Models\OrdenCompra;
use App\Models\Permiso;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use Database\Seeders\Maestros\AlicuotaIvaSeeder;
use Database\Seeders\Maestros\CondicionIvaSeeder;
use Database\Seeders\Maestros\NumeradorSeeder;
use Database\Seeders\Maestros\PermisoSeeder;
use Database\Seeders\Maestros\RolSeeder;
use Laravel\Sanctum\Sanctum;

/*
|--------------------------------------------------------------------------
| Entrega E.1 — API de gestion: compras (pedidos a proveedores)
|--------------------------------------------------------------------------
|
| Cubre los tres endpoints de E.1 —listar, detalle y crear borrador— y las tres
| capas que los protegen: el token (401), el ambito y la ability (403), y el
| permiso del rol (403).
|
| <b>NumeradorSeeder es imprescindible acá y no lo era en las entregas
| anteriores.</b> El alta pasa por OrdenCompraService, que toma el numero de la
| serie 'orden_compra' con la fila del contador bloqueada y DENTRO de la
| transaccion. Sin esa fila sembrada, el alta explota con "No existe la serie
| 'orden_compra' en numeradores", que es exactamente lo que tiene que pasar: el
| numero de un documento no se inventa.
|
| <b>El actor de las pruebas es el ADMINISTRATIVO.</b> Segun la matriz real de
| rol_permiso, vendedor y cajero no tienen ninguno de los ocho permisos de
| compras. El administrativo tiene siete: todos menos 'compra.aprobar_propias',
| que es del administrador. Esa diferencia no se nota en E.1 —no hay aprobacion
| todavia— pero si en E.2: quien crea un pedido por la API no va a poder
| aprobarlo el mismo.
|
| <b>Lo que esta entrega NO tiene</b> esta probado igual, al final del archivo:
| aprobar, enviar, recibir y cancelar responden 405 porque la ruta no existe. Es
| la forma de dejar asentado el limite del alcance.
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

// administrativoDeCompras(), productoPedible(), renglonDePedido() y
// lineaCrudaEn() se movieron a tests/Apoyo/fabricas.php en E.2.a: las necesita
// tambien OrdenCompraTransicionesTest, y Pest carga todos los archivos en el
// mismo proceso, asi que declararlas dos veces no compila.

// ============================================================
// Las tres capas de acceso
// ============================================================

it('rechaza la consulta sin token', function () {
    $this->getJson(route('api.v1.gestion.compras.index'))
        ->assertUnauthorized();
});

it('rechaza un token de un cliente de la tienda', function () {
    // El usuario es de otro ambito: lo frena 'api.ambito:gestion', que mira
    // quien es el usuario AHORA.
    Sanctum::actingAs(User::factory()->cliente()->create(), ['tienda']);

    $this->getJson(route('api.v1.gestion.compras.index'))
        ->assertForbidden()
        ->assertJsonPath('codigo', 'AMBITO_INCORRECTO');
});

it('rechaza un token de empleado emitido para la tienda', function () {
    // El usuario es correcto y el token no: lo frena 'abilities:gestion', que
    // mira PARA QUE se emitio.
    Sanctum::actingAs(administrativoDeCompras(), ['tienda']);

    $this->getJson(route('api.v1.gestion.compras.index'))
        ->assertForbidden()
        ->assertJsonPath('codigo', 'ABILITY_INSUFICIENTE');
});

// ============================================================
// Listado
// ============================================================

it('lista los pedidos con su cabecera y el conteo de renglones', function () {
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $proveedor = proveedorDePrueba(['razon_social' => 'Austral SA']);
    $orden     = ordenDeCompraDePrueba(['proveedor_id' => $proveedor->id]);

    lineaCrudaEn($orden, productoPedible($proveedor), 3);

    $this->getJson(route('api.v1.gestion.compras.index'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $orden->id)
        ->assertJsonPath('data.0.numero', $orden->numero)
        ->assertJsonPath('data.0.forma', 'simple')
        ->assertJsonPath('data.0.estado', 'borrador')
        ->assertJsonPath('data.0.estado_etiqueta', 'Borrador')
        ->assertJsonPath('data.0.origen', 'manual')
        ->assertJsonPath('data.0.lineas_count', 1)
        ->assertJsonPath('data.0.total_estimado', 4500)
        ->assertJsonPath('data.0.proveedor.razon_social', 'Austral SA')
        ->assertJsonPath('data.0.proveedores.0.razon_social', 'Austral SA')
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('filtros.estado', null);
});

it('no publica renglones en el listado', function () {
    // La grilla no los muestra y traerlos multiplicaria por quince el peso de
    // cada pagina. Estan en el detalle.
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $orden = ordenDeCompraDePrueba();
    lineaCrudaEn($orden, productoPedible());

    $this->getJson(route('api.v1.gestion.compras.index'))
        ->assertOk()
        ->assertJsonMissingPath('data.0.lineas')
        ->assertJsonMissingPath('data.0.unidades');
});

it('muestra el pedido repartido una sola vez y no sus hijas', function () {
    // Es la regla del scope raiz(): una hija ya esta representada por su madre,
    // y listar las dos convertiria un pedido de tres proveedores en cuatro
    // filas sin que se sepa cual es el pedido.
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $austral = proveedorDePrueba(['razon_social' => 'Austral SA']);
    $boreal  = proveedorDePrueba(['razon_social' => 'Boreal SRL']);

    $madre = ordenDeCompraDePrueba(['proveedor_id' => null, 'estado' => 'aprobada']);

    lineaCrudaEn($madre, productoPedible($austral));
    lineaCrudaEn($madre, productoPedible($boreal));

    ordenDeCompraDePrueba([
        'proveedor_id'   => $austral->id,
        'orden_padre_id' => $madre->id,
        'estado'         => 'aprobada',
    ]);

    ordenDeCompraDePrueba([
        'proveedor_id'   => $boreal->id,
        'orden_padre_id' => $madre->id,
        'estado'         => 'aprobada',
    ]);

    $this->getJson(route('api.v1.gestion.compras.index'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $madre->id)
        ->assertJsonPath('data.0.forma', 'madre')
        // Una madre no tiene destinatario propio, pero si destinatarios.
        ->assertJsonPath('data.0.proveedor', null)
        ->assertJsonCount(2, 'data.0.proveedores');
});

it('nombra a los proveedores de un borrador mezclado que todavia no se repartio', function () {
    // Antes de aprobar no hay hijas, y el administrativo igual tiene que ver a
    // quienes se les va a emitir: proveedoresInvolucrados() lo saca de los
    // renglones.
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $austral = proveedorDePrueba(['razon_social' => 'Austral SA']);
    $boreal  = proveedorDePrueba(['razon_social' => 'Boreal SRL']);

    $madre = ordenDeCompraDePrueba(['proveedor_id' => null]);

    lineaCrudaEn($madre, productoPedible($austral));
    lineaCrudaEn($madre, productoPedible($boreal));

    $this->getJson(route('api.v1.gestion.compras.index'))
        ->assertOk()
        ->assertJsonPath('data.0.forma', 'madre')
        ->assertJsonCount(2, 'data.0.proveedores')
        ->assertJsonPath('data.0.proveedores.0.razon_social', 'Austral SA')
        ->assertJsonPath('data.0.proveedores.1.razon_social', 'Boreal SRL');
});

it('pone los borradores primero', function () {
    // Es lo que el administrativo entra a hacer. El orden se arma con CASE WHEN
    // y no con el FIELD() del panel, que es de MySQL: asi esta prueba puede
    // correr sobre SQLite.
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $vieja    = ordenDeCompraDePrueba(['estado' => 'recibida']);
    $borrador = ordenDeCompraDePrueba(['estado' => 'borrador']);
    $enviada  = ordenDeCompraDePrueba(['estado' => 'enviada']);

    $this->getJson(route('api.v1.gestion.compras.index'))
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.id', $borrador->id)
        // Dentro de cada grupo, la mas nueva primero.
        ->assertJsonPath('data.1.id', $enviada->id)
        ->assertJsonPath('data.2.id', $vieja->id);
});

it('pagina el listado y respeta por_pagina', function () {
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    foreach (range(1, 3) as $numero) {
        ordenDeCompraDePrueba();
    }

    $this->getJson(route('api.v1.gestion.compras.index', ['por_pagina' => 2]))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.total', 3)
        ->assertJsonPath('meta.last_page', 2);

    $this->getJson(route('api.v1.gestion.compras.index', ['por_pagina' => 2, 'page' => 2]))
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('rechaza un por_pagina fuera del tope', function () {
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $this->getJson(route('api.v1.gestion.compras.index', ['por_pagina' => 500]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('por_pagina');
});

// ============================================================
// Filtros
// ============================================================

it('filtra por estado', function () {
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $borrador = ordenDeCompraDePrueba(['estado' => 'borrador']);
    ordenDeCompraDePrueba(['estado' => 'enviada']);

    $this->getJson(route('api.v1.gestion.compras.index', ['estado' => 'borrador']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $borrador->id)
        ->assertJsonPath('filtros.estado', 'borrador');
});

it('filtra por origen', function () {
    // Manual contra automatica: distinguirlas es la unica forma de medir si los
    // umbrales del restock estan bien calibrados.
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    ordenDeCompraDePrueba(['origen' => 'manual']);
    $automatica = ordenDeCompraDePrueba(['origen' => 'automatica']);

    $this->getJson(route('api.v1.gestion.compras.index', ['origen' => 'automatica']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $automatica->id)
        ->assertJsonPath('data.0.origen_etiqueta', 'Automática por reposición');
});

it('busca por numero de pedido', function () {
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $buscada = ordenDeCompraDePrueba();
    ordenDeCompraDePrueba();

    $this->getJson(route('api.v1.gestion.compras.index', ['q' => $buscada->numero]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $buscada->id);
});

it('busca por la razon social del proveedor', function () {
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $austral = proveedorDePrueba(['razon_social' => 'Austral SA']);
    $orden   = ordenDeCompraDePrueba(['proveedor_id' => $austral->id]);

    ordenDeCompraDePrueba(['proveedor_id' => proveedorDePrueba(['razon_social' => 'Boreal SRL'])->id]);

    $this->getJson(route('api.v1.gestion.compras.index', ['q' => 'austr']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $orden->id);
});

it('encuentra un pedido repartido por el proveedor de una de sus ordenes', function () {
    // La madre no tiene proveedor propio: si la busqueda no mirara las hijas,
    // un pedido repartido seria imposible de encontrar por destinatario.
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $austral = proveedorDePrueba(['razon_social' => 'Austral SA']);
    $madre   = ordenDeCompraDePrueba(['proveedor_id' => null, 'estado' => 'aprobada']);

    ordenDeCompraDePrueba([
        'proveedor_id'   => $austral->id,
        'orden_padre_id' => $madre->id,
        'estado'         => 'aprobada',
    ]);

    ordenDeCompraDePrueba(['proveedor_id' => proveedorDePrueba(['razon_social' => 'Boreal SRL'])->id]);

    $this->getJson(route('api.v1.gestion.compras.index', ['q' => 'Austral']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $madre->id);
});

it('rechaza un estado y un origen que no existen', function () {
    // El panel los pasa crudos al where y un valor inventado devuelve una lista
    // vacia, indistinguible de «no hay pedidos en ese estado». Acá se validan
    // contra el enum.
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $this->getJson(route('api.v1.gestion.compras.index', ['estado' => 'archivada']))
        ->assertStatus(422)
        ->assertJsonValidationErrors('estado');

    $this->getJson(route('api.v1.gestion.compras.index', ['origen' => 'telefonico']))
        ->assertStatus(422)
        ->assertJsonValidationErrors('origen');
});

// ============================================================
// Detalle
// ============================================================

it('devuelve el detalle con renglones, cantidades e importes', function () {
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $proveedor = proveedorDePrueba(['razon_social' => 'Austral SA']);
    $producto  = productoPedible($proveedor, ['nombre' => 'Monitor 24', 'costo' => 1000.00]);

    $orden = ordenDeCompraDePrueba(['proveedor_id' => $proveedor->id, 'estado' => 'enviada']);

    lineaCrudaEn($orden, $producto, cantidad: 10, recibida: 4);

    $this->getJson(route('api.v1.gestion.compras.show', $orden))
        ->assertOk()
        ->assertJsonPath('data.id', $orden->id)
        ->assertJsonPath('data.forma', 'simple')
        ->assertJsonPath('data.estado', 'enviada')
        ->assertJsonPath('data.estado_etiqueta', 'Enviada al proveedor')
        ->assertJsonPath('data.proveedor.razon_social', 'Austral SA')
        ->assertJsonPath('data.lineas_count', 1)
        ->assertJsonCount(1, 'data.lineas')
        ->assertJsonPath('data.lineas.0.producto.nombre', 'Monitor 24')
        ->assertJsonPath('data.lineas.0.proveedor.razon_social', 'Austral SA')
        ->assertJsonPath('data.lineas.0.cantidad_pedida', 10)
        ->assertJsonPath('data.lineas.0.cantidad_recibida', 4)
        ->assertJsonPath('data.lineas.0.pendiente', 6)
        ->assertJsonPath('data.lineas.0.excedente', 0)
        ->assertJsonPath('data.lineas.0.costo_unitario', 1000)
        ->assertJsonPath('data.lineas.0.subtotal_estimado', 10000)
        ->assertJsonPath('data.total_estimado', 10000)
        ->assertJsonPath('data.unidades.pedidas', 10)
        ->assertJsonPath('data.unidades.recibidas', 4)
        ->assertJsonPath('data.unidades.pendientes', 6);
});

it('informa el excedente cuando el proveedor mando de mas', function () {
    // Recibir de mas se acepta: la mercaderia esta en el deposito. Lo que hace
    // falta es que se VEA, porque el total se estimo sobre lo pedido y la
    // factura va a venir por mas. El pendiente no se va a negativo.
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $orden = ordenDeCompraDePrueba(['estado' => 'recibida']);

    lineaCrudaEn($orden, productoPedible(), cantidad: 10, recibida: 12);

    $this->getJson(route('api.v1.gestion.compras.show', $orden))
        ->assertOk()
        ->assertJsonPath('data.lineas.0.excedente', 2)
        ->assertJsonPath('data.lineas.0.pendiente', 0)
        ->assertJsonPath('data.unidades.pendientes', 0);
});

it('devuelve el detalle de un pedido repartido con sus ordenes hijas', function () {
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $austral = proveedorDePrueba(['razon_social' => 'Austral SA']);
    $boreal  = proveedorDePrueba(['razon_social' => 'Boreal SRL']);

    $madre = ordenDeCompraDePrueba(['proveedor_id' => null, 'estado' => 'aprobada']);

    lineaCrudaEn($madre, productoPedible($austral));
    lineaCrudaEn($madre, productoPedible($boreal));

    ordenDeCompraDePrueba([
        'proveedor_id'   => $austral->id,
        'orden_padre_id' => $madre->id,
        'estado'         => 'aprobada',
        'numero'         => $madre->numero.'/1',
    ]);

    ordenDeCompraDePrueba([
        'proveedor_id'   => $boreal->id,
        'orden_padre_id' => $madre->id,
        'estado'         => 'enviada',
        'numero'         => $madre->numero.'/2',
    ]);

    $this->getJson(route('api.v1.gestion.compras.show', $madre))
        ->assertOk()
        ->assertJsonPath('data.forma', 'madre')
        ->assertJsonPath('data.proveedor', null)
        ->assertJsonCount(2, 'data.hijas')
        ->assertJsonPath('data.hijas.0.numero', $madre->numero.'/1')
        ->assertJsonPath('data.hijas.0.proveedor.razon_social', 'Austral SA')
        ->assertJsonPath('data.hijas.1.estado_etiqueta', 'Enviada al proveedor')
        ->assertJsonPath('data.padre', null);
});

it('sirve el detalle de una hija y la deja volver a su pedido', function () {
    // El listado muestra solo las raiz, pero una hija es una orden real —tiene
    // su numero, su remito y su recepcion— y su detalle es donde se la mira.
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $madre = ordenDeCompraDePrueba(['proveedor_id' => null, 'estado' => 'aprobada']);

    $hija = ordenDeCompraDePrueba([
        'orden_padre_id' => $madre->id,
        'estado'         => 'aprobada',
        'numero'         => $madre->numero.'/1',
    ]);

    $this->getJson(route('api.v1.gestion.compras.show', $hija))
        ->assertOk()
        ->assertJsonPath('data.id', $hija->id)
        ->assertJsonPath('data.forma', 'hija')
        ->assertJsonPath('data.padre.id', $madre->id)
        ->assertJsonPath('data.padre.numero', $madre->numero)
        ->assertJsonCount(0, 'data.hijas');
});

it('no publica el historial de recepciones todavia', function () {
    // Las entradas de mercaderia son movimientos de stock con origen en la
    // orden, y viajan con el endpoint de recepcion, en E.2. Lo que si viaja es
    // cuanto se recibio de cada renglon.
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $orden = ordenDeCompraDePrueba(['estado' => 'recibida_parcial']);
    lineaCrudaEn($orden, productoPedible(), cantidad: 10, recibida: 4);

    $this->getJson(route('api.v1.gestion.compras.show', $orden))
        ->assertOk()
        ->assertJsonMissingPath('data.recepciones')
        ->assertJsonMissingPath('data.movimientos')
        ->assertJsonPath('data.lineas.0.cantidad_recibida', 4);
});

it('responde 404 por un pedido que no existe', function () {
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $this->getJson(route('api.v1.gestion.compras.show', 9999))
        ->assertNotFound();
});

// ============================================================
// Alta de borradores
// ============================================================

it('crea el pedido en borrador y devuelve su detalle', function () {
    $empleado = administrativoDeCompras();

    Sanctum::actingAs($empleado, ['gestion']);

    $proveedor = proveedorDePrueba(['razon_social' => 'Austral SA']);
    $producto  = productoPedible($proveedor, ['costo' => 1200.00]);

    $respuesta = $this->postJson(route('api.v1.gestion.compras.store'), [
        'lineas'        => [renglonDePedido($producto, 5)],
        'observaciones' => 'Reposición de monitores.',
    ]);

    $respuesta->assertCreated()
        ->assertJsonPath('data.estado', 'borrador')
        ->assertJsonPath('data.forma', 'simple')
        ->assertJsonPath('data.origen', 'manual')
        ->assertJsonPath('data.observaciones', 'Reposición de monitores.')
        ->assertJsonPath('data.proveedor.razon_social', 'Austral SA')
        ->assertJsonPath('data.lineas_count', 1)
        ->assertJsonPath('data.lineas.0.cantidad_pedida', 5)
        ->assertJsonPath('data.lineas.0.cantidad_recibida', 0)
        // El costo sale del catalogo, no del cuerpo.
        ->assertJsonPath('data.lineas.0.costo_unitario', 1200)
        ->assertJsonPath('data.total_estimado', 6000)
        ->assertJsonPath('data.unidades.pendientes', 5)
        // Un borrador recien cargado no tiene nada de esto todavia.
        ->assertJsonPath('data.usuario_aprobo', null)
        ->assertJsonPath('data.fecha_aprobacion', null)
        ->assertJsonPath('data.fecha_envio', null);

    $this->assertDatabaseHas('ordenes_compra', [
        'id'                => $respuesta->json('data.id'),
        'estado'            => 'borrador',
        'origen'            => 'manual',
        'proveedor_id'      => $proveedor->id,
        'orden_padre_id'    => null,
        'usuario_creo_id'   => $empleado->id,
        'usuario_aprobo_id' => null,
        'total_estimado'    => 6000.00,
    ]);

    // El renglon guarda el snapshot del proveedor: a quien se le pidio ESTE
    // renglon, que puede no ser el que el producto tenga manana.
    $this->assertDatabaseHas('orden_compra_lineas', [
        'orden_compra_id'   => $respuesta->json('data.id'),
        'producto_id'       => $producto->id,
        'proveedor_id'      => $proveedor->id,
        'cantidad_pedida'   => 5,
        'cantidad_recibida' => 0,
        'costo_unitario'    => 1200.00,
    ]);
});

it('le pone al pedido el numero que sigue en la serie', function () {
    // El numero lo asigna el Numerador dentro de la transaccion, no el cuerpo:
    // la serie tiene que quedar correlativa y sin huecos.
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $producto = productoPedible();

    $primero = $this->postJson(route('api.v1.gestion.compras.store'), [
        'lineas' => [renglonDePedido($producto)],
    ])->assertCreated();

    $segundo = $this->postJson(route('api.v1.gestion.compras.store'), [
        'lineas' => [renglonDePedido($producto)],
    ])->assertCreated();

    expect($primero->json('data.numero'))->toBe('OC-0000001');
    expect($segundo->json('data.numero'))->toBe('OC-0000002');
});

it('deja el pedido sin destinatario cuando mezcla proveedores', function () {
    // La forma del documento sale del detalle y no de una eleccion previa: con
    // varios proveedores queda madre y se reparte al aprobarse, que es E.2.
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $austral = proveedorDePrueba(['razon_social' => 'Austral SA']);
    $boreal  = proveedorDePrueba(['razon_social' => 'Boreal SRL']);

    $respuesta = $this->postJson(route('api.v1.gestion.compras.store'), [
        'lineas' => [
            renglonDePedido(productoPedible($austral), 2),
            renglonDePedido(productoPedible($boreal), 3),
        ],
    ]);

    $respuesta->assertCreated()
        ->assertJsonPath('data.forma', 'madre')
        ->assertJsonPath('data.proveedor', null)
        ->assertJsonCount(2, 'data.proveedores')
        ->assertJsonPath('data.lineas_count', 2)
        // Todavia no hay reparto: las hijas se emiten al aprobar.
        ->assertJsonCount(0, 'data.hijas');

    $this->assertDatabaseHas('ordenes_compra', [
        'id'           => $respuesta->json('data.id'),
        'proveedor_id' => null,
        'estado'       => 'borrador',
    ]);
});

it('crear un borrador no mueve el inventario', function () {
    // <b>Es el requisito explicito de la entrega.</b> Pedir no es recibir: la
    // compra afecta el stock en un solo punto del circuito, que es la recepcion
    // de mercaderia, y en E.1 ese punto no tiene endpoint.
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $producto = productoPedible(null, ['stock_fisico' => 10]);

    // El refresh no es decorativo: stock_disponible es una columna GENERADA,
    // asi que el modelo recien creado todavia no la tiene y la base de
    // comparacion seria null contra null.
    $producto->refresh();

    $fisicoAntes     = $producto->stock_fisico;
    $disponibleAntes = $producto->stock_disponible;

    $this->postJson(route('api.v1.gestion.compras.store'), [
        'lineas' => [renglonDePedido($producto, 50)],
    ])->assertCreated();

    $producto->refresh();

    expect($producto->stock_fisico)->toBe($fisicoAntes);
    expect($producto->stock_disponible)->toBe($disponibleAntes);
    expect(MovimientoStock::count())->toBe(0);

    $this->assertDatabaseCount('movimientos_stock', 0);
});

it('ignora el estado, el origen, el numero y el proveedor que mande el cuerpo', function () {
    // Ninguno de los cuatro se acepta, y la ausencia es la decision: el estado
    // se mueve con transiciones, el origen lo fija el servicio —si no, una
    // manual podria disfrazarse de automatica y arruinaria la medicion de los
    // umbrales—, el numero lo da el numerador y el destinatario sale del
    // detalle.
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $austral = proveedorDePrueba(['razon_social' => 'Austral SA']);
    $boreal  = proveedorDePrueba(['razon_social' => 'Boreal SRL']);

    $respuesta = $this->postJson(route('api.v1.gestion.compras.store'), [
        'lineas'       => [renglonDePedido(productoPedible($austral))],
        'estado'       => 'aprobada',
        'origen'       => 'automatica',
        'numero'       => 'OC-9999999',
        'proveedor_id' => $boreal->id,
    ]);

    $respuesta->assertCreated()
        ->assertJsonPath('data.estado', 'borrador')
        ->assertJsonPath('data.origen', 'manual')
        ->assertJsonPath('data.numero', 'OC-0000001')
        // El destinatario es el del renglon, no el que mandaron.
        ->assertJsonPath('data.proveedor.id', $austral->id);
});

it('ignora el costo que mande el cuerpo y usa el del catalogo', function () {
    // El dato tiene un solo dueño, que es la ficha del producto. Aceptarlo del
    // cuerpo dejaria estimar un pedido contra un costo que no existe.
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $producto = productoPedible(null, ['costo' => 800.00]);

    $this->postJson(route('api.v1.gestion.compras.store'), [
        'lineas' => [[
            'producto_id'     => $producto->id,
            'cantidad_pedida' => 2,
            'costo_unitario'  => 1.00,
        ]],
    ])
        ->assertCreated()
        ->assertJsonPath('data.lineas.0.costo_unitario', 800)
        ->assertJsonPath('data.total_estimado', 1600);
});

// ============================================================
// Validaciones del alta
// ============================================================

it('exige al menos un renglon', function () {
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $this->postJson(route('api.v1.gestion.compras.store'), [])
        ->assertStatus(422)
        ->assertJsonValidationErrors('lineas');

    $this->postJson(route('api.v1.gestion.compras.store'), ['lineas' => []])
        ->assertStatus(422)
        ->assertJsonValidationErrors('lineas');

    expect(OrdenCompra::count())->toBe(0);
});

it('rechaza un producto que no existe', function () {
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $this->postJson(route('api.v1.gestion.compras.store'), [
        'lineas' => [['producto_id' => 9999, 'cantidad_pedida' => 1]],
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('lineas.0.producto_id');

    expect(OrdenCompra::count())->toBe(0);
});

it('rechaza un producto dado de baja', function () {
    // La regla de ProveedorRequest es Rule::exists(...)->where('activo', true):
    // un producto inactivo no se repone.
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $producto = productoPedible(null, ['activo' => false]);

    $this->postJson(route('api.v1.gestion.compras.store'), [
        'lineas' => [renglonDePedido($producto)],
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('lineas.0.producto_id');
});

it('rechaza el mismo producto en dos renglones', function () {
    // El proveedor recibiria un remito con el mismo item dos veces: se suman
    // las cantidades en un solo renglon.
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $producto = productoPedible();

    $this->postJson(route('api.v1.gestion.compras.store'), [
        'lineas' => [renglonDePedido($producto, 2), renglonDePedido($producto, 3)],
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('lineas.1.producto_id');
});

it('rechaza cantidades que no son una cantidad', function () {
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $producto = productoPedible();

    foreach ([0, -5] as $cantidad) {
        $this->postJson(route('api.v1.gestion.compras.store'), [
            'lineas' => [renglonDePedido($producto, $cantidad)],
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('lineas.0.cantidad_pedida');
    }

    $this->postJson(route('api.v1.gestion.compras.store'), [
        'lineas' => [['producto_id' => $producto->id]],
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('lineas.0.cantidad_pedida');

    expect(OrdenCompra::count())->toBe(0);
});

it('rechaza un producto sin proveedor asignado', function () {
    // No hay a quien pedirselo, y si llegara a aprobarse reventaria en el
    // reparto. Es una de las dos reglas que OrdenCompraRequest resuelve contra
    // la base, en withValidator().
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $producto = productoDePrueba(['proveedor_id' => null, 'costo' => 100.00]);

    $this->postJson(route('api.v1.gestion.compras.store'), [
        'lineas' => [renglonDePedido($producto)],
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('lineas');

    expect(OrdenCompra::count())->toBe(0);
});

it('rechaza un producto cuyo proveedor esta dado de baja', function () {
    // El otro arreglo: este se resuelve en la ficha del proveedor, no en la del
    // producto, y por eso el mensaje es distinto.
    Sanctum::actingAs(administrativoDeCompras(), ['gestion']);

    $producto = productoPedible(proveedorDePrueba(['activo' => false]));

    $this->postJson(route('api.v1.gestion.compras.store'), [
        'lineas' => [renglonDePedido($producto)],
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('lineas');

    expect(OrdenCompra::count())->toBe(0);
});

// ============================================================
// Permisos del rol
// ============================================================

it('no deja entrar al vendedor ni al cajero', function () {
    // Segun la matriz real de rol_permiso, ninguno de los dos tiene
    // 'compra.ver': las compras son del administrativo.
    foreach (['vendedor', 'cajero'] as $rol) {
        Sanctum::actingAs(User::factory()->empleado($rol)->create(), ['gestion']);

        $this->getJson(route('api.v1.gestion.compras.index'))
            ->assertForbidden()
            ->assertJsonPath('codigo', 'PERMISO_INSUFICIENTE');

        $this->app['auth']->forgetGuards();
    }
});

it('deja mirar pero no crear al que no tiene el permiso de alta', function () {
    // La matriz real no trae ningun rol con 'compra.ver' y sin 'compra.crear',
    // asi que el actor se arma quitandole el permiso al administrativo en lugar
    // de inventar un rol para la prueba.
    $empleado = administrativoDeCompras();

    $empleado->rol->permisos()->detach(
        Permiso::where('clave', 'compra.crear')->value('id')
    );

    Sanctum::actingAs($empleado, ['gestion']);

    $this->getJson(route('api.v1.gestion.compras.index'))->assertOk();

    $this->postJson(route('api.v1.gestion.compras.store'), [
        'lineas' => [renglonDePedido(productoPedible())],
    ])
        ->assertForbidden()
        ->assertJsonPath('codigo', 'PERMISO_INSUFICIENTE');

    expect(OrdenCompra::count())->toBe(0);
});

it('no deja ver el detalle sin el permiso de consulta', function () {
    $empleado = administrativoDeCompras();

    $empleado->rol->permisos()->detach(
        Permiso::where('clave', 'compra.ver')->value('id')
    );

    Sanctum::actingAs($empleado, ['gestion']);

    $this->getJson(route('api.v1.gestion.compras.show', ordenDeCompraDePrueba()))
        ->assertForbidden()
        ->assertJsonPath('codigo', 'PERMISO_INSUFICIENTE');
});

it('refleja el permiso quitado al rol con el mismo token', function () {
    // <b>Token real y withToken(), no Sanctum::actingAs.</b> El doble deja
    // clavada la MISMA instancia de User entre las dos llamadas, con su
    // relacion de permisos ya cargada: el detach no se veria y la prueba
    // pasaria sin probar nada.
    $empleado = administrativoDeCompras();

    $token = tokenDeGestion($empleado)->plainTextToken;

    $this->withToken($token)
        ->getJson(route('api.v1.gestion.compras.index'))
        ->assertOk();

    $empleado->rol->permisos()->detach(
        Permiso::where('clave', 'compra.ver')->value('id')
    );

    $this->app['auth']->forgetGuards();

    $this->withToken($token)
        ->getJson(route('api.v1.gestion.compras.index'))
        ->assertForbidden()
        ->assertJsonPath('codigo', 'PERMISO_INSUFICIENTE');
});

// ============================================================
// El limite del alcance: que sigue sin existir
// ============================================================

it('no expone todavia la recepcion de mercaderia', function () {
    // <b>Actualizada en E.2.a.</b> Aprobar, enviar y cancelar ya tienen
    // endpoint —son /aprobacion, /envio y /cancelacion, y las prueba
    // OrdenCompraTransicionesTest—. Lo que sigue sin existir es la recepcion:
    // POST /{orden}/recepciones y POST /{orden}/cierre son E.2.b.
    //
    // <b>Ni siquiera con el administrador</b>, que tiene los ocho permisos de
    // compras: lo que falta no es permiso, es la ruta. OrdenCompraPolicy ya
    // tiene recibir() y cerrar(), y PermisoSeeder ya sembro 'compra.recibir'.
    Sanctum::actingAs(User::factory()->empleado('administrador')->create(), ['gestion']);

    $orden = ordenDeCompraDePrueba();

    // 404 y no 403: estas URL no existen. Un 403 significaria «existe pero no
    // podes», que es lo que contesta la policy cuando la ruta si esta.
    foreach (['recepciones', 'cierre'] as $accion) {
        $this->postJson("/api/v1/gestion/compras/{$orden->id}/{$accion}")
            ->assertNotFound();
    }

    // Las URL con el verbo tampoco existen, y no van a existir: el contrato son
    // los sub-recursos sustantivados.
    foreach (['aprobar', 'enviar', 'recibir', 'cancelar'] as $verbo) {
        $this->postJson("/api/v1/gestion/compras/{$orden->id}/{$verbo}")
            ->assertNotFound();
    }

    // El detalle tampoco se edita por ahora: no hay PUT. Acá si es 405 y no
    // 404, porque la URL existe —la sirve el GET del detalle— y lo que no
    // existe es el metodo.
    $this->putJson("/api/v1/gestion/compras/{$orden->id}", [
        'lineas' => [renglonDePedido(productoPedible())],
    ])->assertStatus(405);

    $this->assertDatabaseHas('ordenes_compra', ['id' => $orden->id, 'estado' => 'borrador']);
});
