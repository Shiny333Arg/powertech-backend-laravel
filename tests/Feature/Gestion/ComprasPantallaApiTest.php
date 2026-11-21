<?php

use App\Models\Permiso;
use App\Models\User;
use Database\Seeders\Maestros\AlicuotaIvaSeeder;
use Database\Seeders\Maestros\CondicionIvaSeeder;
use Database\Seeders\Maestros\NumeradorSeeder;
use Database\Seeders\Maestros\PermisoSeeder;
use Database\Seeders\Maestros\RolSeeder;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;

/*
|--------------------------------------------------------------------------
| Las pantallas de compras despues de la primera entrega de la migracion
|--------------------------------------------------------------------------
|
| Mismo contrato que fijaron CategoriaPantallaApiTest, ProductoPantallaApiTest,
| ProveedorPantallaApiTest y ClientePantallaApiTest: las paginas migradas
| entregan la estructura visual, los desplegables del filtro y los permisos del
| rol, y ningun dato de negocio. Los datos los pide el JavaScript a
| /api/v1/gestion/compras, que tiene su cobertura completa en
| tests/Feature/Api/V1/Gestion/OrdenCompra*Test.php.
|
| <b>Son TRES pantallas y no cuatro.</b> El listado, el formulario que comparten
| el alta y la edicion, y la recepcion de mercaderia. <b>El detalle del pedido
| NO se migro</b>, y no es un olvido: es donde viven aprobar, enviar, cancelar y
| cerrar con faltante, que son la segunda entrega. Por eso las cuatro rutas web
| de esas transiciones siguen publicadas, y esta prueba lo deja asentado: si
| alguien las retira antes de migrar el detalle, falla.
|
| `withoutVite()` porque las vistas extienden el layout de gestion.
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

    $this->withoutVite();
});

function administrativoDePantallaDeCompras(): User
{
    return User::factory()->empleado('administrativo')->create();
}

// ============================================================
// Las paginas no traen datos de negocio
// ============================================================

it('entrega el listado sin los pedidos adentro', function () {
    $proveedor = proveedorConCasilla(['razon_social' => 'Distribuidora Austral SA']);

    $orden = pedidoDeUnProveedor('enviada', $proveedor);

    comoEmpleado(administrativoDePantallaDeCompras())
        ->get(route('gestion.compras.index'))
        ->assertOk()
        // La tabla esta, vacia y con el hueco que llena el JS.
        ->assertSee('contenidoTablaCompras')
        ->assertSee('paginadoCompras')
        // Los datos no.
        ->assertDontSee($orden->numero)
        ->assertDontSee('Distribuidora Austral SA');
});

it('entrega los dos desplegables del filtro desde el servidor', function () {
    // Son los enums EstadoOrdenCompra y OrigenOrdenCompra: el catalogo de
    // opciones del filtro, no datos de ningun pedido. El valor seleccionado lo
    // pone el JS desde la URL.
    comoEmpleado(administrativoDePantallaDeCompras())
        ->get(route('gestion.compras.index'))
        ->assertOk()
        ->assertSee('Recibida parcialmente')
        ->assertSee('Automática por reposición');
});

it('baja el color de cada estado desde el enum', function () {
    // Para que el badge del listado migrado y el del detalle —que sigue siendo
    // Blade leyendo EstadoOrdenCompra::color()— no tengan dos paletas.
    comoEmpleado(administrativoDePantallaDeCompras())
        ->get(route('gestion.compras.index'))
        ->assertOk()
        ->assertSee('"borrador":"secondary"', false)
        ->assertSee('"recibida":"success"', false);
});

it('entrega el alta como un formulario vacio', function () {
    comoEmpleado(administrativoDePantallaDeCompras())
        ->get(route('gestion.compras.create'))
        ->assertOk()
        ->assertSee('cuerpoRenglones')
        ->assertSee('buscadorProducto')
        ->assertSee('btnSugerencias')
        // El renglon inicial ya no viene del servidor: no hay JSON embebido.
        ->assertDontSee('datosRenglones');
});

it('entrega la edicion sin el detalle del pedido', function () {
    $proveedor = proveedorConCasilla(['razon_social' => 'Mayorista del Sur SRL']);

    $orden = pedidoDeUnProveedor('borrador', $proveedor, ['observaciones' => 'Nota interna del pedido.']);

    $producto = $orden->lineas->first()->producto;

    comoEmpleado(administrativoDePantallaDeCompras())
        ->get(route('gestion.compras.edit', $orden))
        ->assertOk()
        // Solo el id, para que el JS sepa a que recurso pedirle los datos.
        ->assertSee('id: '.$orden->id, false)
        ->assertDontSee($orden->numero)
        ->assertDontSee('Mayorista del Sur SRL')
        ->assertDontSee('Nota interna del pedido.')
        ->assertDontSee($producto->codigo);
});

it('entrega la recepcion sin el pedido ni su historial', function () {
    $proveedor = proveedorConCasilla(['razon_social' => 'Importadora del Plata SA']);

    $orden = pedidoListoParaRecibir(1, $proveedor);

    $producto = $orden->lineas->first()->producto;

    comoEmpleado(administrativoDePantallaDeCompras())
        ->get(route('gestion.compras.recibir', $orden))
        ->assertOk()
        ->assertSee('id: '.$orden->id, false)
        ->assertSee('cuerpoRecepcion')
        ->assertSee('cuerpoHistorial')
        // Ni el documento ni sus renglones: los trae GET /compras/{orden}, que
        // desde E.2.b devuelve tambien las entregas en 'data.recepciones'.
        ->assertDontSee($orden->numero)
        ->assertDontSee('Importadora del Plata SA')
        ->assertDontSee($producto->codigo)
        ->assertDontSee($producto->nombre);
});

// ============================================================
// Las rutas web de negocio ya no existen
// ============================================================

it('retiro las cuatro rutas web que ejecutaban las operaciones migradas', function () {
    foreach (['store', 'update', 'sugerencias', 'recibir.store'] as $retirada) {
        expect(Route::has("gestion.compras.{$retirada}"))
            ->toBeFalse("La ruta web gestion.compras.{$retirada} sigue publicada");
    }
});

it('conserva las cinco rutas web que entregan las paginas Blade', function () {
    foreach (['index', 'create', 'show', 'edit', 'recibir'] as $pagina) {
        expect(Route::has("gestion.compras.{$pagina}"))->toBeTrue();
    }
});

it('conserva las transiciones que viven en el detalle, que es la segunda entrega', function () {
    // <b>Esta prueba protege el alcance de esta entrega.</b> Retirar estas
    // cuatro antes de migrar show.blade.php dejaria el detalle con botones que
    // no hacen nada.
    foreach (['aprobar', 'enviar', 'cancelar', 'cerrar'] as $transicion) {
        expect(Route::has("gestion.compras.{$transicion}"))
            ->toBeTrue("Falta la ruta web gestion.compras.{$transicion}, que usa el detalle");
    }
});

it('no responde a las direcciones de negocio que ya no existen', function () {
    $orden = pedidoListoParaRecibir(1);

    $empleado = administrativoDePantallaDeCompras();

    // El administrativo tiene siete de los ocho permisos de compras: si algo
    // contestara 2xx o 302, seria porque la ruta sigue viva, no porque le falte
    // autorizacion.
    //
    // <b>Son 405 y no 404</b> en los tres casos en que la URI sigue existiendo
    // para otro verbo: el alta comparte URI con el GET del listado, la edicion
    // con el GET del detalle, y la carga de la entrega con el GET de su propio
    // formulario. Lo que ya no existe es el metodo.
    comoEmpleado($empleado)->post('/gestion/purchase-order', [])->assertMethodNotAllowed();
    comoEmpleado($empleado)->put("/gestion/purchase-order/{$orden->id}", [])->assertMethodNotAllowed();
    comoEmpleado($empleado)->post("/gestion/purchase-order/{$orden->id}/recibir", [])->assertMethodNotAllowed();

    // Las sugerencias si dan 404: la URI no existe mas, y el comodin del
    // detalle no la resuelve porque 'suggestions' no es un pedido.
    comoEmpleado($empleado)->get('/gestion/purchase-order/suggestions')->assertNotFound();

    // Y nada de eso dejo rastro: ni se creo un pedido ni entro mercaderia.
    expect($orden->fresh()->lineas->first()->cantidad_recibida)->toBe(0);
});

it('no deja ningun formulario de las pantallas migradas apuntando al panel', function () {
    $borrador = pedidoDeUnProveedor();
    $recibible = pedidoListoParaRecibir(1);

    $empleado = administrativoDePantallaDeCompras();

    // Se mira el `action="..."` y no la URL suelta: el formulario de filtros
    // del listado sigue siendo un GET legitimo contra la propia pagina, y el
    // `@csrf` del encabezado —el del cierre de sesion— esta en todas las
    // paginas del panel.
    comoEmpleado($empleado)
        ->get(route('gestion.compras.index'))
        ->assertOk()
        ->assertDontSee('name="_method"', false);

    comoEmpleado($empleado)
        ->get(route('gestion.compras.create'))
        ->assertOk()
        ->assertDontSee('action="'.url('/gestion/purchase-order').'"', false)
        ->assertDontSee('name="_method"', false);

    comoEmpleado($empleado)
        ->get(route('gestion.compras.edit', $borrador))
        ->assertOk()
        ->assertDontSee('action="'.url("/gestion/purchase-order/{$borrador->id}").'"', false)
        ->assertDontSee('name="_method"', false);

    comoEmpleado($empleado)
        ->get(route('gestion.compras.recibir', $recibible))
        ->assertOk()
        ->assertDontSee('action="'.url("/gestion/purchase-order/{$recibible->id}/recibir").'"', false)
        ->assertDontSee('name="_method"', false);
});

// ============================================================
// Los botones y las paginas salen de la policy
// ============================================================

it('le dice a la pantalla si puede armar pedidos', function () {
    comoEmpleado(administrativoDePantallaDeCompras())
        ->get(route('gestion.compras.index'))
        ->assertOk()
        ->assertSee(route('gestion.compras.create'), false);
});

it('esconde el alta al que no tiene el permiso de crear', function () {
    $empleado = administrativoDePantallaDeCompras();

    $empleado->rol->permisos()->detach(
        Permiso::where('clave', 'compra.crear')->value('id')
    );

    comoEmpleado($empleado)
        ->get(route('gestion.compras.index'))
        ->assertOk()
        ->assertDontSee(route('gestion.compras.create'), false);
});

it('no le muestra la pantalla al vendedor', function () {
    // Segun la matriz real, vendedor y cajero no tienen ninguno de los ocho
    // permisos de compras.
    comoEmpleado(User::factory()->empleado('vendedor')->create())
        ->get(route('gestion.compras.index'))
        ->assertForbidden();
});

it('no entrega la pagina de edicion de una orden enviada', function () {
    // El estado manda sobre el permiso: lo decide OrdenCompraPolicy::update(),
    // la misma que autoriza el PUT de la API. Se resuelve antes de servir la
    // pagina para que el enlace directo tampoco entre.
    $orden = pedidoDeUnProveedor('enviada');

    comoEmpleado(administrativoDePantallaDeCompras())
        ->get(route('gestion.compras.edit', $orden))
        ->assertForbidden();
});

it('no entrega la pagina de recepcion de un pedido que no admite entregas', function (string $estado) {
    // La policy exige esRecibible(): un borrador todavia no lo aprobo nadie y
    // una aprobada sin enviar no llego al proveedor, asi que no hay nada que
    // pueda estar entrando contra ellas.
    $orden = pedidoDeUnProveedor($estado);

    comoEmpleado(administrativoDePantallaDeCompras())
        ->get(route('gestion.compras.recibir', $orden))
        ->assertForbidden();
})->with(['borrador', 'aprobada', 'recibida', 'cancelada']);

it('no entrega la pagina de recepcion de un pedido repartido', function () {
    // La mercaderia se recibe en la orden de cada proveedor, no en la madre.
    $madre = pedidoMezcladoEnBorrador(2, ['estado' => 'enviada']);

    comoEmpleado(administrativoDePantallaDeCompras())
        ->get(route('gestion.compras.recibir', $madre))
        ->assertForbidden();
});

// ============================================================
// El detalle sigue siendo Blade, con sus datos adentro
// ============================================================

it('sirve el detalle con el pedido adentro, que es lo que falta migrar', function () {
    // <b>Es el limite de esta entrega, y esta probado a proposito.</b> El
    // detalle muestra el documento desde Blade porque es donde viven las cuatro
    // transiciones; migrarlo a medias dejaria la mitad de los botones sin poder
    // operar.
    $proveedor = proveedorConCasilla(['razon_social' => 'Casa Central SRL']);

    $orden = pedidoDeUnProveedor('borrador', $proveedor);

    comoEmpleado(administrativoDePantallaDeCompras())
        ->get(route('gestion.compras.show', $orden))
        ->assertOk()
        ->assertSee($orden->numero)
        ->assertSee('Casa Central SRL');
});

// ============================================================
// El contrato que usa el buscador del formulario
// ============================================================
//
// El formulario de pedidos busca productos contra GET /gestion/productos, con
// 'estado=activos'. Antes iba contra gestion.buscar.productos, que sigue
// publicada porque la usan ventas y promociones. Lo que se fija aca es que el
// endpoint del catalogo alcance para lo que la pantalla necesita mostrar.

it('el buscador recibe el estado del proveedor y los dos cortes de stock', function () {
    // <b>'proveedor.activo' es el campo que se agrego a ProductoResource en
    // esta entrega</b>, y es el que decide si un renglon se puede pedir: la
    // pantalla marca y bloquea los de un proveedor dado de baja antes de que el
    // administrativo mande el pedido y se lo rechace OrdenCompraRequest.
    //
    // El disponible y el punto de reposicion son lo que pinta la insignia roja
    // del desplegable: se pide lo que falta.
    $proveedor = proveedorDePrueba(['razon_social' => 'Distribuidora Austral SA']);

    $producto = productoPedible($proveedor, [
        'nombre'           => 'Monitor curvo 27',
        'stock_fisico'     => 2,
        'punto_reposicion' => 5,
    ]);

    Sanctum::actingAs(administrativoDePantallaDeCompras(), ['gestion']);

    $this->getJson(route('api.v1.gestion.productos.index', ['q' => 'Monitor curvo', 'estado' => 'activos']))
        ->assertOk()
        ->assertJsonPath('data.0.id', $producto->id)
        ->assertJsonPath('data.0.proveedor.razon_social', 'Distribuidora Austral SA')
        ->assertJsonPath('data.0.proveedor.activo', true)
        ->assertJsonPath('data.0.stock_disponible', 2)
        ->assertJsonPath('data.0.punto_reposicion', 5);
});

it('el buscador ve a los proveedores dados de baja como tales', function () {
    $deBaja = proveedorDePrueba(['razon_social' => 'Mayorista del Sur SRL', 'activo' => false]);

    productoPedible($deBaja, ['nombre' => 'Teclado sin quien lo reponga']);

    Sanctum::actingAs(administrativoDePantallaDeCompras(), ['gestion']);

    $this->getJson(route('api.v1.gestion.productos.index', ['q' => 'Teclado sin', 'estado' => 'activos']))
        ->assertOk()
        ->assertJsonPath('data.0.proveedor.activo', false);
});

it('el buscador no devuelve productos dados de baja', function () {
    // Es lo que hacia el `where('activo', true)` de la ruta vieja: un producto
    // dado de baja no se pide.
    productoPedible(null, ['nombre' => 'Fuente discontinuada', 'activo' => false]);

    Sanctum::actingAs(administrativoDePantallaDeCompras(), ['gestion']);

    $this->getJson(route('api.v1.gestion.productos.index', ['q' => 'Fuente discontinuada', 'estado' => 'activos']))
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

// ============================================================
// El JavaScript de las pantallas esta declarado
// ============================================================

it('declara en vite los modulos que piden las vistas de compras', function () {
    $configuracion = file_get_contents(base_path('vite.config.js'));

    $modulos = [
        'resources/js/gestion/compras/index.js',
        'resources/js/gestion/compras/formulario.js',
        'resources/js/gestion/compras/recepcion.js',
    ];

    foreach ($modulos as $modulo) {
        expect(file_exists(base_path($modulo)))->toBeTrue("Falta el modulo {$modulo}")
            ->and($configuracion)->toContain($modulo);
    }

    $vistas = [
        'views/gestion/compras/index.blade.php'   => $modulos[0],
        'views/gestion/compras/create.blade.php'  => $modulos[1],
        'views/gestion/compras/edit.blade.php'    => $modulos[1],
        'views/gestion/compras/recibir.blade.php' => $modulos[2],
    ];

    foreach ($vistas as $vista => $modulo) {
        expect(file_get_contents(resource_path($vista)))->toContain($modulo);
    }
});

it('se llevo el script viejo del formulario', function () {
    // El modulo que manejaba los renglones dinamicos con selects y
    // costo_unitario por fila. Quedo obsoleto cuando el formulario paso a
    // dibujar la tabla desde JavaScript, y ya no lo importaba ninguna vista.
    expect(file_exists(resource_path('js/gestion/compras/partials/formulario.js')))
        ->toBeFalse('Hay que borrar a mano resources/js/gestion/compras/partials/formulario.js');
});
