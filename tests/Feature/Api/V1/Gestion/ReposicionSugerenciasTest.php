<?php

use App\Models\OrdenCompra;
use App\Models\Permiso;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use Database\Seeders\Maestros\AlicuotaIvaSeeder;
use Database\Seeders\Maestros\CondicionIvaSeeder;
use Database\Seeders\Maestros\PermisoSeeder;
use Database\Seeders\Maestros\RolSeeder;
use Laravel\Sanctum\Sanctum;

/*
|--------------------------------------------------------------------------
| GET /api/v1/gestion/compras/sugerencias — lo que falta pedir
|--------------------------------------------------------------------------
|
| El endpoint que alimenta el boton «traer lo que falta pedir» del alta de
| pedidos. Es una CONSULTA: no abre borradores, no cancela ninguno y no toca el
| inventario.
|
| <b>Reemplaza a gestion.compras.sugerencias.</b> La logica no se duplico: se
| mudo del controlador web al de la API cuando la ruta web se retiro, con la
| misma consulta y la misma cuenta de ReposicionService::cantidadSugerida().
|
| Las dos reglas que esta prueba fija, y que son las que no son obvias:
|
|   1. «Todavia no pedido» significa que ningun proveedor lo sabe. Lo que figura
|      en una orden aprobada, enviada o recibida a medias queda afuera; lo que
|      solo figura en un BORRADOR entra igual, porque un borrador no es un
|      compromiso y este pedido lo reemplaza.
|   2. Lo que no se puede pedir se informa, no se agrega: un producto sin
|      proveedor —o con el proveedor dado de baja— viaja en 'omitidos' con su
|      motivo. Meterlo en 'data' dejaria el formulario sin poder guardarse.
|
| No hace falta NumeradorSeeder: ninguna prueba de este archivo crea pedidos por
| el servicio.
|
*/

beforeEach(function () {
    $this->seed([
        RolSeeder::class,
        PermisoSeeder::class,
        CondicionIvaSeeder::class,
        AlicuotaIvaSeeder::class,
    ]);
});

/**
 * Producto bajo su punto de reposicion.
 *
 * La cuenta de ReposicionService::cantidadSugerida() es «lo que falta para
 * volver al umbral mas el lote de compra»: con 1 de stock, umbral 5 y lote 10,
 * son 14 unidades. Es la misma que usa el restock automatico, y que las dos
 * vias pidan lo mismo es lo que hace comparables un pedido armado a mano y uno
 * generado solo.
 */
function productoEnFalta(?Proveedor $proveedor = null, array $cambios = []): Producto
{
    return productoPedible($proveedor, array_merge([
        'stock_fisico'        => 1,
        'punto_reposicion'    => 5,
        'cantidad_reposicion' => 10,
    ], $cambios));
}

function administrativoQuePideSugerencias(): User
{
    $empleado = administrativoDeCompras();

    Sanctum::actingAs($empleado, ['gestion']);

    return $empleado;
}

// ============================================================
// Acceso
// ============================================================

it('rechaza la consulta sin token', function () {
    $this->getJson(route('api.v1.gestion.compras.sugerencias'))
        ->assertUnauthorized();
});

it('rechaza al que no tiene el permiso de armar pedidos', function () {
    // <b>Se protege con 'compra.crear' y no con 'compra.ver'.</b> Esta lista
    // existe para armar un pedido: quien solo consulta compras no tiene nada
    // que hacer con ella. Es el mismo permiso que exigia la ruta web.
    $empleado = administrativoQuePideSugerencias();

    $empleado->rol->permisos()->detach(
        Permiso::where('clave', 'compra.crear')->value('id')
    );

    $this->getJson(route('api.v1.gestion.compras.sugerencias'))
        ->assertForbidden();
});

it('no deja entrar al vendedor', function () {
    // Segun la matriz real, vendedor y cajero no tienen ninguno de los ocho
    // permisos de compras.
    Sanctum::actingAs(User::factory()->empleado('vendedor')->create(), ['gestion']);

    $this->getJson(route('api.v1.gestion.compras.sugerencias'))
        ->assertForbidden();
});

// ============================================================
// Que devuelve
// ============================================================

it('devuelve el producto en falta con su cantidad sugerida', function () {
    $proveedor = proveedorDePrueba(['razon_social' => 'Distribuidora Austral SA']);

    $producto = productoEnFalta($proveedor, ['nombre' => 'Monitor en falta']);

    administrativoQuePideSugerencias();

    $this->getJson(route('api.v1.gestion.compras.sugerencias'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.producto_id', $producto->id)
        ->assertJsonPath('data.0.nombre', 'Monitor en falta')
        ->assertJsonPath('data.0.codigo', $producto->codigo)
        // (5 - 1) + 10
        ->assertJsonPath('data.0.cantidad_sugerida', 14)
        ->assertJsonPath('data.0.proveedor.razon_social', 'Distribuidora Austral SA')
        ->assertJsonPath('data.0.proveedor.activo', true)
        ->assertJsonCount(0, 'omitidos');
});

it('no devuelve un producto con stock por encima de su punto', function () {
    productoPedible(null, ['stock_fisico' => 20, 'punto_reposicion' => 5]);

    administrativoQuePideSugerencias();

    $this->getJson(route('api.v1.gestion.compras.sugerencias'))
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('no devuelve un producto dado de baja', function () {
    productoEnFalta(null, ['activo' => false]);

    administrativoQuePideSugerencias();

    $this->getJson(route('api.v1.gestion.compras.sugerencias'))
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('incluye el producto con reposicion manual', function () {
    // El filtro no es por 'reposicion_automatica': un producto con reposicion
    // manual y el stock en el piso es justamente lo que nadie va a pedir solo,
    // y es el motivo por el que alguien abre esta pantalla.
    productoEnFalta(null, ['reposicion_automatica' => false]);

    administrativoQuePideSugerencias();

    $this->getJson(route('api.v1.gestion.compras.sugerencias'))
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('ordena por nombre', function () {
    $proveedor = proveedorDePrueba();

    productoEnFalta($proveedor, ['nombre' => 'Zapatilla de red']);
    productoEnFalta($proveedor, ['nombre' => 'Auricular inalámbrico']);

    administrativoQuePideSugerencias();

    $this->getJson(route('api.v1.gestion.compras.sugerencias'))
        ->assertOk()
        ->assertJsonPath('data.0.nombre', 'Auricular inalámbrico')
        ->assertJsonPath('data.1.nombre', 'Zapatilla de red');
});

// ============================================================
// «Todavia no pedido» = ningun proveedor lo sabe
// ============================================================

it('deja afuera lo que ya esta en una orden que el proveedor conoce', function (string $estado) {
    $proveedor = proveedorDePrueba();
    $producto  = productoEnFalta($proveedor);

    $orden = ordenDeCompraDePrueba(['proveedor_id' => $proveedor->id, 'estado' => $estado]);

    lineaCrudaEn($orden, $producto);

    administrativoQuePideSugerencias();

    $this->getJson(route('api.v1.gestion.compras.sugerencias'))
        ->assertOk()
        ->assertJsonCount(0, 'data');
})->with(['aprobada', 'enviada', 'recibida_parcial']);

it('incluye lo que solo figura en un borrador', function () {
    // Un borrador no es un compromiso: no salio ningun remito y el pedido que
    // se esta armando lo reemplaza. Ese borrador se da de baja recien cuando el
    // pedido nuevo se apruebe, en anularBorradoresSuperados().
    $proveedor = proveedorDePrueba();
    $producto  = productoEnFalta($proveedor);

    $borrador = ordenDeCompraDePrueba(['proveedor_id' => $proveedor->id, 'estado' => 'borrador']);

    lineaCrudaEn($borrador, $producto);

    administrativoQuePideSugerencias();

    $this->getJson(route('api.v1.gestion.compras.sugerencias'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.producto_id', $producto->id);
});

it('incluye lo que figura en una orden ya cerrada', function (string $estado) {
    // Una recibida o una cancelada no tienen nada en camino: el producto vuelve
    // a estar disponible para pedir. Cancelar es, de hecho, lo que vuelve a
    // habilitar la reposicion de sus productos.
    $proveedor = proveedorDePrueba();
    $producto  = productoEnFalta($proveedor);

    $orden = ordenDeCompraDePrueba(['proveedor_id' => $proveedor->id, 'estado' => $estado]);

    lineaCrudaEn($orden, $producto);

    administrativoQuePideSugerencias();

    $this->getJson(route('api.v1.gestion.compras.sugerencias'))
        ->assertOk()
        ->assertJsonCount(1, 'data');
})->with(['recibida', 'cancelada']);

it('no da por pedido lo que esta en una madre todavia sin repartir', function () {
    // Una madre aprobada no tiene destinatario propio: quien puede cumplir el
    // pedido es la hija, y hasta que exista nadie se compromete a nada. Es la
    // misma regla de OrdenCompra::scopeConProducto().
    $proveedor = proveedorDePrueba();
    $producto  = productoEnFalta($proveedor);

    $madre = ordenDeCompraDePrueba(['proveedor_id' => null, 'estado' => 'aprobada']);

    lineaCrudaEn($madre, $producto);

    administrativoQuePideSugerencias();

    $this->getJson(route('api.v1.gestion.compras.sugerencias'))
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

// ============================================================
// Lo que no se puede pedir se informa, no se agrega
// ============================================================

it('informa aparte el producto sin proveedor', function () {
    $producto = productoDePrueba([
        'nombre'              => 'Huérfano sin proveedor',
        'costo'               => 500.00,
        'stock_fisico'        => 0,
        'punto_reposicion'    => 3,
        'cantidad_reposicion' => 6,
    ]);

    administrativoQuePideSugerencias();

    $this->getJson(route('api.v1.gestion.compras.sugerencias'))
        ->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonCount(1, 'omitidos')
        ->assertJsonPath('omitidos.0.producto_id', $producto->id)
        ->assertJsonPath('omitidos.0.nombre', 'Huérfano sin proveedor')
        ->assertJsonPath('omitidos.0.motivo', 'no tiene proveedor asignado');
});

it('informa aparte el producto cuyo proveedor esta dado de baja', function () {
    $deBaja = proveedorDePrueba([
        'razon_social' => 'Mayorista del Sur SRL',
        'activo'       => false,
    ]);

    productoEnFalta($deBaja, ['nombre' => 'Teclado sin quien lo reponga']);

    administrativoQuePideSugerencias();

    $this->getJson(route('api.v1.gestion.compras.sugerencias'))
        ->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonCount(1, 'omitidos')
        ->assertJsonPath('omitidos.0.motivo', '«Mayorista del Sur SRL» está dado de baja');
});

it('devuelve las dos listas a la vez', function () {
    $proveedor = proveedorDePrueba();

    productoEnFalta($proveedor, ['nombre' => 'Pedible']);
    productoDePrueba([
        'nombre'           => 'Sin proveedor',
        'stock_fisico'     => 0,
        'punto_reposicion' => 2,
    ]);

    administrativoQuePideSugerencias();

    $this->getJson(route('api.v1.gestion.compras.sugerencias'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.nombre', 'Pedible')
        ->assertJsonCount(1, 'omitidos')
        ->assertJsonPath('omitidos.0.nombre', 'Sin proveedor');
});

// ============================================================
// Es una consulta
// ============================================================

it('no crea ningun pedido ni toca ningun borrador', function () {
    $proveedor = proveedorDePrueba();
    $producto  = productoEnFalta($proveedor);

    $borrador = ordenDeCompraDePrueba(['proveedor_id' => $proveedor->id, 'estado' => 'borrador']);

    lineaCrudaEn($borrador, $producto);

    administrativoQuePideSugerencias();

    $this->getJson(route('api.v1.gestion.compras.sugerencias'))->assertOk();

    expect(OrdenCompra::count())->toBe(1)
        ->and($borrador->fresh()->estado->value)->toBe('borrador');
});

it('no acepta POST sobre la lista', function () {
    // Pedir la lista no es abrir pedidos. Si alguna vez hiciera falta un
    // «generar los borradores de todo lo que falta», seria otro endpoint con su
    // propio nombre, y no este verbo sobre esta URL.
    administrativoQuePideSugerencias();

    $this->postJson(route('api.v1.gestion.compras.sugerencias'))
        ->assertStatus(405);
});

it('no la captura el comodin del detalle', function () {
    // /{orden} lleva whereNumber('orden'), asi que la palabra 'sugerencias' no
    // entra por ahi. Si alguna vez se le saca esa restriccion, esta prueba
    // empieza a fallar con un 404 en lugar de devolver la lista.
    administrativoQuePideSugerencias();

    $this->getJson('/api/v1/gestion/compras/sugerencias')
        ->assertOk()
        ->assertJsonStructure(['data', 'omitidos']);
});
