<?php

use App\Http\Controllers\HelpController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\gestion\BusquedaController;
use App\Http\Controllers\gestion\CategoriaController;
use App\Http\Controllers\gestion\ClienteController;
use App\Http\Controllers\gestion\ComprobanteController;
use App\Http\Controllers\gestion\DashboardController;
use App\Http\Controllers\gestion\DevolucionController;
use App\Http\Controllers\gestion\DireccionController;
use App\Http\Controllers\gestion\EmpleadoController;
use App\Http\Controllers\gestion\EntregaController;
use App\Http\Controllers\gestion\OrdenCompraController;
use App\Http\Controllers\gestion\PagoController;
use App\Http\Controllers\gestion\ProductoController;
use App\Http\Controllers\gestion\PromocionController;
use App\Http\Controllers\gestion\ProveedorController;
use App\Http\Controllers\gestion\StockController;
use App\Http\Controllers\gestion\VentaController;
use Illuminate\Support\Facades\Route;


// ============================================================
// AUTENTICACIÓN
// ============================================================
//
// Login, registro, "olvidé mi contraseña", invitación de empleados,
// verificación de correo, cambio de contraseña y logout.

require __DIR__ . '/auth.php';


// ============================================================
// TIENDA - PÚBLICO
// ============================================================

// ========================================================
// HOME
// ========================================================

// Route::get('/', function () {
//     return view('home.index');
// })->name('cliente.home');
Route::get('/', [HomeController::class, 'index'])
    ->name('cliente.home');


// ========================================================
// AYUDA
// ========================================================

Route::prefix('help')
    ->name('cliente.help.')
    ->group(function () {

        Route::get('/', [HelpController::class, 'index'])
            ->name('index');
    });


// ============================================================
// TIENDA - CLIENTE LOGUEADO
// ============================================================
//
// Cuando exista el checkout, sus rutas llevan ademas 'verified':
// no se compra con un correo sin confirmar.

Route::middleware(['auth', 'ambito:tienda'])
    ->group(function () {

        // ========================================================
        // PERFIL (datos personales y fiscales)
        // ========================================================
        // Los nombres profile.* son los de Breeze. La contraseña se
        // cambia con password.update (routes/auth.php).

        Route::get('/perfil', [ProfileController::class, 'edit'])
            ->name('profile.edit');

        Route::patch('/perfil', [ProfileController::class, 'update'])
            ->name('profile.update');
    });


// ============================================================
// GESTIÓN
// ============================================================
//
// Solo empleados logueados. Un cliente que abre /gestion vuelve a la tienda.
//
// Criterio de verbos, el mismo que ya usaba este archivo:
//   PATCH  modifica el recurso        -> baja, reactivar, publicar
//   POST   dispara una accion         -> validar, cobrar, entregar, devolver
// Las transiciones del circuito de venta son acciones: generan movimientos de
// stock y comprobantes, no editan la fila.
//
// Ninguna venta, comprobante, pago, movimiento de stock ni orden de compra
// tiene ruta DELETE. No es un olvido: no se eliminan.

Route::prefix('gestion')
    ->name('gestion.')
    ->middleware(['auth', 'ambito:gestion'])
    ->group(function () {


        // ========================================================
        // HOME
        // ========================================================
        // Un solo tablero: arma los bloques de administrativo, vendedor
        // y cajero segun los permisos de quien entra.

        Route::get('/', [DashboardController::class, 'index'])
            ->name('home');


        // ========================================================
        // MI CUENTA
        // ========================================================
        // El empleado solo puede cambiar su contraseña: el formulario
        // de esta vista envia a password.update (routes/auth.php).

        Route::view('/mi-cuenta', 'gestion.user.my-account')
            ->name('mi-cuenta');


        // ========================================================
        // CATEGORÍAS
        // ========================================================

        Route::prefix('category')
            ->name('categorias.')
            ->group(function () {

                Route::get('/', [CategoriaController::class, 'index'])
                    ->name('index');

                Route::get('/create', [CategoriaController::class, 'create'])
                    ->name('create');

                Route::post('/', [CategoriaController::class, 'store'])
                    ->name('store');

                Route::get('/{categoria}/edit', [CategoriaController::class, 'edit'])
                    ->name('edit');

                Route::put('/{categoria}', [CategoriaController::class, 'update'])
                    ->name('update');

                Route::patch('/{categoria}/baja', [CategoriaController::class, 'darDeBaja'])
                    ->name('baja');

                Route::patch('/{categoria}/reactivar', [CategoriaController::class, 'reactivar'])
                    ->name('reactivar');

                Route::delete('/{categoria}', [CategoriaController::class, 'destroy'])
                    ->name('destroy');
            });


        // ========================================================
        // PRODUCTOS
        // ========================================================

        Route::prefix('item')
            ->name('items.')
            ->group(function () {

                Route::get('/', [ProductoController::class, 'index'])
                    ->name('index');

                Route::get('/create', [ProductoController::class, 'create'])
                    ->name('create');

                Route::post('/', [ProductoController::class, 'store'])
                    ->name('store');

                Route::get('/{producto}/edit', [ProductoController::class, 'edit'])
                    ->name('edit');

                Route::put('/{producto}', [ProductoController::class, 'update'])
                    ->name('update');

                Route::patch('/{producto}/baja', [ProductoController::class, 'darDeBaja'])
                    ->name('darDeBaja');

                Route::patch('/{producto}/reactivar', [ProductoController::class, 'reactivar'])
                    ->name('reactivar');

                Route::patch('/{producto}/publicar', [ProductoController::class, 'publicar'])
                    ->name('publicar');

                Route::patch('/{producto}/despublicar', [ProductoController::class, 'despublicar'])
                    ->name('despublicar');

                // OJO: estas dos se superponen con stock.ajustar y stock.show.
                // Ver la nota al pie del bloque STOCK: hay que quedarse con una.
                Route::patch('/{producto}/stock', [ProductoController::class, 'ajustarStock'])
                    ->name('ajustarStock');

                Route::get('/{producto}/movimientos', [ProductoController::class, 'movimientos'])
                    ->name('movimientos');
            });


        // ========================================================
        // VENTAS
        // ========================================================
        // Sin destroy: la venta no se elimina ni se da de baja. Lo que en
        // otro CRUD seria la D es 'cancelar', que libera el stock
        // comprometido y conserva el numero y el historial.
        //
        // /create va antes de /{venta} para que no lo capture el wildcard.

        Route::prefix('sale')
            ->name('ventas.')
            ->group(function () {

                Route::get('/', [VentaController::class, 'index'])
                    ->name('index');

                Route::get('/create', [VentaController::class, 'create'])
                    ->name('create');

                Route::post('/', [VentaController::class, 'store'])
                    ->name('store');

                Route::get('/{venta}', [VentaController::class, 'show'])
                    ->name('show');

                // Solo un presupuesto se edita: una venta confirmada ya
                // comprometio stock y una pagada ya tiene CAE.
                Route::get('/{venta}/edit', [VentaController::class, 'edit'])
                    ->name('edit');

                Route::put('/{venta}', [VentaController::class, 'update'])
                    ->name('update');

                // Validar confirma el presupuesto, habilita el cobro y
                // compromete las unidades.
                Route::post('/{venta}/validar', [VentaController::class, 'validar'])
                    ->name('validar');

                Route::post('/{venta}/cancelar', [VentaController::class, 'cancelar'])
                    ->name('cancelar');
            });


        // ========================================================
        // DEVOLUCIONES
        // ========================================================
        // Cuelgan de la venta porque no existen sin ella. Emiten nota de
        // credito y reingresan el stock fisico.

        Route::prefix('sale/{venta}/return')
            ->name('devoluciones.')
            ->group(function () {

                Route::get('/', [DevolucionController::class, 'create'])
                    ->name('create');

                Route::post('/', [DevolucionController::class, 'store'])
                    ->name('store');
            });


        // ========================================================
        // COBROS
        // ========================================================
        // La caja. Un pago no se edita ni se borra: si se cobro de menos se
        // agrega otro pago, si se cobro de mas se devuelve con nota de
        // credito. Por eso solo hay lectura y alta.

        Route::prefix('payment')
            ->name('cobros.')
            ->group(function () {

                Route::get('/', [PagoController::class, 'index'])
                    ->name('index');

                Route::get('/pending', [PagoController::class, 'pendientes'])
                    ->name('pendientes');

                Route::get('/{venta}/create', [PagoController::class, 'create'])
                    ->name('create');

                Route::post('/{venta}', [PagoController::class, 'store'])
                    ->name('store');
            });


        // ========================================================
        // ENTREGAS
        // ========================================================
        // El deposito. Es el unico lugar del sistema donde baja el stock
        // fisico, y baja cuando la mercaderia sale, no cuando se cobra.

        Route::prefix('delivery')
            ->name('entregas.')
            ->group(function () {

                Route::get('/', [EntregaController::class, 'index'])
                    ->name('index');

                Route::post('/{venta}/lista-retiro', [EntregaController::class, 'listaRetiro'])
                    ->name('lista-retiro');

                Route::post('/{venta}/despachar', [EntregaController::class, 'despachar'])
                    ->name('despachar');

                Route::post('/{venta}/entregar', [EntregaController::class, 'entregar'])
                    ->name('entregar');
            });


        // ========================================================
        // COMPROBANTES
        // ========================================================
        // Solo lectura y reintento. No hay alta manual —el comprobante nace
        // de acreditar un pago o de una devolucion— ni edicion ni borrado:
        // es inmutable una vez que tiene CAE.

        Route::prefix('invoice')
            ->name('comprobantes.')
            ->group(function () {

                Route::get('/', [ComprobanteController::class, 'index'])
                    ->name('index');

                Route::get('/{comprobante}', [ComprobanteController::class, 'show'])
                    ->name('show');

                Route::post('/{comprobante}/reintentar', [ComprobanteController::class, 'reintentar'])
                    ->name('reintentar');
            });


        // ========================================================
        // CLIENTES
        // ========================================================
        // Sin destroy: solo baja logica. Esta referenciado por ventas y
        // comprobantes emitidos.

        Route::prefix('customer')
            ->name('clientes.')
            ->group(function () {

                Route::get('/', [ClienteController::class, 'index'])
                    ->name('index');

                Route::get('/create', [ClienteController::class, 'create'])
                    ->name('create');

                Route::post('/', [ClienteController::class, 'store'])
                    ->name('store');

                Route::get('/{cliente}', [ClienteController::class, 'show'])
                    ->name('show');

                Route::get('/{cliente}/edit', [ClienteController::class, 'edit'])
                    ->name('edit');

                Route::put('/{cliente}', [ClienteController::class, 'update'])
                    ->name('update');

                Route::patch('/{cliente}/baja', [ClienteController::class, 'baja'])
                    ->name('baja');

                Route::patch('/{cliente}/reactivar', [ClienteController::class, 'reactivar'])
                    ->name('reactivar');
            });


        // ========================================================
        // DIRECCIONES
        // ========================================================
        // Una sola tabla polimorfica para clientes, proveedores y empleados.
        // El dueño sale de la URL y nunca del body: si el tipo llegara del
        // formulario, cualquiera podria colgarle una direccion fiscal a otro.
        //
        // {tipo} es el discriminador, no un prefijo de recurso: sus valores
        // son las claves de DireccionController::DUENOS.
        //
        // La direccion SI se borra: no es un documento. Los envios copian el
        // domicilio en lugar de apuntar aca, justamente para que un despacho
        // no cambie de destino si alguien edita el perfil.

        Route::prefix('{tipo}/{id}/address')
            ->name('direcciones.')
            ->where(['tipo' => 'clientes|proveedores|empleados', 'id' => '[0-9]+'])
            ->group(function () {

                Route::post('/', [DireccionController::class, 'store'])
                    ->name('store');

                Route::put('/{direccion}', [DireccionController::class, 'update'])
                    ->name('update');

                Route::delete('/{direccion}', [DireccionController::class, 'destroy'])
                    ->name('destroy');
            });


        // ========================================================
        // STOCK
        // ========================================================
        // Kardex, ajustes con motivo y carga del conteo inicial. Ninguna
        // pantalla escribe productos.stock_fisico directamente: todo cambio
        // deja un movimiento.
        //
        // SUPERPOSICION A RESOLVER: items.ajustarStock e items.movimientos
        // hacen lo mismo que stock.ajustar y stock.show. Conviene quedarse
        // con un solo camino y borrar el otro, o el ajuste va a existir en
        // dos pantallas con dos validaciones distintas.

        Route::prefix('stock')
            ->name('stock.')
            ->group(function () {

                Route::get('/', [StockController::class, 'index'])
                    ->name('index');

                Route::post('/ajustes', [StockController::class, 'ajustar'])
                    ->name('ajustar');

                Route::post('/carga-inicial', [StockController::class, 'cargaInicial'])
                    ->name('carga-inicial');

                Route::get('/{producto}', [StockController::class, 'show'])
                    ->name('show');
            });


        // ========================================================
        // PEDIDOS A PROVEEDORES (ÓRDENES DE COMPRA)
        // ========================================================
        // Sin destroy, por el mismo criterio que ventas: la orden no se
        // elimina ni se da de baja. Lo que en otro CRUD seria la D es
        // 'cancelar', que conserva el numero y el historial y —esto es lo que
        // importa para el circuito— vuelve a habilitar la reposicion
        // automatica de sus productos.
        //
        // Criterio de verbos, el mismo del resto del archivo:
        //   PUT   modifica el recurso  -> el detalle del borrador
        //   POST  dispara una accion   -> aprobar, enviar, cancelar
        // Aprobar y enviar son acciones y no ediciones: la primera deja
        // registro de quien autorizo, la segunda le manda un correo al
        // proveedor. Ninguna de las dos es "guardar un campo".
        //
        // /create va antes de /{orden} para que no lo capture el wildcard.

        Route::prefix('purchase-order')
            ->name('compras.')
            ->group(function () {

                Route::get('/', [OrdenCompraController::class, 'index'])
                    ->name('index');

                Route::get('/create', [OrdenCompraController::class, 'create'])
                    ->name('create');

                Route::post('/', [OrdenCompraController::class, 'store'])
                    ->name('store');

                Route::get('/{orden}', [OrdenCompraController::class, 'show'])
                    ->name('show');

                // Solo un borrador se edita: una orden aprobada ya paso por
                // la decision de un administrativo y una enviada ya esta en
                // manos del proveedor.
                Route::get('/{orden}/edit', [OrdenCompraController::class, 'edit'])
                    ->name('edit');

                Route::put('/{orden}', [OrdenCompraController::class, 'update'])
                    ->name('update');

                // Requerimiento explicito: ninguna orden sale sin que un
                // administrativo la apruebe.
                Route::post('/{orden}/aprobar', [OrdenCompraController::class, 'aprobar'])
                    ->name('aprobar');

                // Manda el remito por correo. Es el unico canal de pedido.
                Route::post('/{orden}/enviar', [OrdenCompraController::class, 'enviar'])
                    ->name('enviar');

                Route::post('/{orden}/cancelar', [OrdenCompraController::class, 'cancelar'])
                    ->name('cancelar');
            });


        // ========================================================
        // BÚSQUEDA (JSON)
        // ========================================================
        // Autocompletado de la pantalla de venta. Devuelve JSON, no vistas.

        Route::prefix('search')
            ->name('buscar.')
            ->group(function () {

                Route::get('/item', [BusquedaController::class, 'productos'])
                    ->name('productos');

                Route::get('/customer', [BusquedaController::class, 'clientes'])
                    ->name('clientes');
            });


        // ========================================================
        // EMPLEADOS
        // ========================================================

        Route::prefix('empleado')
            ->name('empleados.')
            ->group(function () {

                Route::get('/', [EmpleadoController::class, 'index'])
                    ->name('index');

                Route::get('/create', [EmpleadoController::class, 'create'])
                    ->name('create');

                Route::post('/', [EmpleadoController::class, 'store'])
                    ->name('store');

                Route::get('/{empleado}/edit', [EmpleadoController::class, 'edit'])
                    ->name('edit');

                Route::put('/{empleado}', [EmpleadoController::class, 'update'])
                    ->name('update');

                Route::patch('/{empleado}/baja', [EmpleadoController::class, 'darDeBaja'])
                    ->name('darDeBaja');

                Route::patch('/{empleado}/reactivar', [EmpleadoController::class, 'reactivar'])
                    ->name('reactivar');

                // Envia un mail: es una accion, no una modificacion del recurso.
                Route::post('/{empleado}/reenviar-invitacion', [EmpleadoController::class, 'reenviarInvitacion'])
                    ->name('reenviarInvitacion');
            });


        // ========================================================
        // PROVEEDORES
        // ========================================================

        Route::prefix('proveedor')
            ->name('proveedores.')
            ->group(function () {

                Route::get('/', [ProveedorController::class, 'index'])
                    ->name('index');

                Route::get('/create', [ProveedorController::class, 'create'])
                    ->name('create');

                Route::post('/', [ProveedorController::class, 'store'])
                    ->name('store');

                Route::get('/{proveedor}/edit', [ProveedorController::class, 'edit'])
                    ->name('edit');

                Route::put('/{proveedor}', [ProveedorController::class, 'update'])
                    ->name('update');

                Route::patch('/{proveedor}/baja', [ProveedorController::class, 'darDeBaja'])
                    ->name('darDeBaja');

                Route::patch('/{proveedor}/reactivar', [ProveedorController::class, 'reactivar'])
                    ->name('reactivar');
            });


        // ========================================================
        // PROMOCIONES
        // ========================================================

        Route::prefix('promotion')
            ->name('promociones.')
            ->group(function () {

                Route::get('/', [PromocionController::class, 'index'])
                    ->name('index');

                Route::get('/create', [PromocionController::class, 'create'])
                    ->name('create');

                Route::post('/', [PromocionController::class, 'store'])
                    ->name('store');

                Route::get('/{promocion}/edit', [PromocionController::class, 'edit'])
                    ->name('edit');

                Route::put('/{promocion}', [PromocionController::class, 'update'])
                    ->name('update');

                Route::patch('/{promocion}/baja', [PromocionController::class, 'darDeBaja'])
                    ->name('darDeBaja');

                Route::patch('/{promocion}/reactivar', [PromocionController::class, 'reactivar'])
                    ->name('reactivar');
            });
    });
