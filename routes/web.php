<?php

use App\Http\Controllers\HelpController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CarritoController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CompraController;
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
use App\Http\Controllers\gestion\RecepcionController;
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

Route::get('/', [HomeController::class, 'index'])
    ->name('cliente.home');


// ========================================================
// FICHA DE PRODUCTO
// ========================================================
// Pública: el invitado ve la ficha sin precio ni compra.

Route::get('/item/{producto}', [HomeController::class, 'producto'])
    ->name('cliente.items.show');


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

Route::middleware(['auth', 'ambito:tienda'])
    ->group(function () {

        // ========================================================
        // PERFIL (datos personales y fiscales)
        // ========================================================

        Route::get('/perfil', [ProfileController::class, 'edit'])
            ->name('profile.edit');

        Route::patch('/perfil', [ProfileController::class, 'update'])
            ->name('profile.update');
    });

// ========================================================
// MIS COMPRAS
// ========================================================
// Solo lectura. El cliente no cancela ni modifica desde aca: cancelar
// una venta pagada libera stock comprometido y, si ya tiene CAE, exige
// nota de credito. Es una decision de la empresa, no autogestion.

Route::prefix('compras')
    ->name('cliente.compras.')
    ->group(function () {

        Route::get('/', [CompraController::class, 'index'])
            ->name('index');

        Route::get('/{venta}', [CompraController::class, 'show'])
            ->name('show');
    });




// ========================================================
// CHECKOUT
// ========================================================
// Solo retiro en el local: el circuito de envio y tracking no esta
// implementado todavia.
//
// La venta online nace PAGADA: sin pago acreditado no hay venta, y sin
// venta no hay stock comprometido. Por eso no hay una ruta de "crear
// pedido" separada del pago.

Route::prefix('checkout')
    ->name('cliente.checkout.')
    ->middleware('verified')
    ->group(function () {

        Route::get('/', [CheckoutController::class, 'index'])
            ->name('index');

        Route::post('/', [CheckoutController::class, 'store'])
            ->name('store');
    });



// ========================================================
// CARRITO
// ========================================================
// 'verified' aplica la regla del layout del lado del servidor: un cliente
// con el correo sin confirmar navega, pero no arma pedidos.
//
// El carrito NO es un recurso del cliente sino el suyo propio: no hay
// index de carritos ni {carrito} en la URL. Lo que se manipula es la
// linea, y la linea se valida contra el carrito activo de quien pide.

Route::prefix('carrito')
    ->name('cliente.carrito.')
    ->middleware('verified')
    ->group(function () {

        Route::get('/', [CarritoController::class, 'index'])
            ->name('index');

        Route::post('/', [CarritoController::class, 'store'])
            ->name('store');

        Route::patch('/{linea}', [CarritoController::class, 'update'])
            ->name('update');

        Route::delete('/{linea}', [CarritoController::class, 'destroy'])
            ->name('destroy');
    });




// ============================================================
// GESTIÓN
// ============================================================
//
// Solo empleados logueados. Un cliente que abre /gestion vuelve a la tienda.

Route::prefix('gestion')
    ->name('gestion.')
    ->middleware(['auth', 'ambito:gestion'])
    ->group(function () {


        // ========================================================
        // HOME
        // ========================================================

        Route::get('/', [DashboardController::class, 'index'])
            ->name('home');


        // ========================================================
        // MI CUENTA
        // ========================================================
        // El empleado solo puede cambiar su contraseña

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
        // La unica puerta del circuito comercial. Sin destroy: la venta no se
        // elimina ni se da de baja. Lo que en otro CRUD seria la D es
        // 'cancelar', que libera el stock comprometido y conserva el numero y
        // el historial.
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
        // ACCIONES DEL CIRCUITO (cuelgan de la venta)
        // ========================================================
        // Cobro, entrega y devolucion no son recursos: son transiciones de una
        // venta concreta. Por eso viven bajo sale/{venta} y no tienen listado
        // propio. Cada una conserva su VentaPolicy —cobrar, entregar,
        // devolver— asi que el permiso que las habilita no cambia.

        Route::prefix('sale/{venta}')
            ->group(function () {


                // ------------------------------------------------
                // COBRO
                // ------------------------------------------------
                // La caja. Un pago no se edita ni se borra: si se cobro de
                // menos se agrega otro pago, si se cobro de mas se devuelve
                // con nota de credito. Por eso solo hay alta.
                //
                // El historial de caja del cajero queda en su bloque del
                // dashboard; las ventas por cobrar, en el listado filtrado
                // por estado=confirmada.

                Route::name('cobros.')
                    ->group(function () {

                        Route::get('/payment', [PagoController::class, 'create'])
                            ->name('create');

                        Route::post('/payment', [PagoController::class, 'store'])
                            ->name('store');
                    });


                // ------------------------------------------------
                // ENTREGA
                // ------------------------------------------------
                // El deposito. Es el unico lugar del sistema donde baja el
                // stock fisico, y baja cuando la mercaderia sale, no cuando
                // se cobra.

                Route::name('entregas.')
                    ->group(function () {

                        Route::post('/lista-retiro', [EntregaController::class, 'listaRetiro'])
                            ->name('lista-retiro');

                        Route::post('/despachar', [EntregaController::class, 'despachar'])
                            ->name('despachar');

                        Route::post('/entregar', [EntregaController::class, 'entregar'])
                            ->name('entregar');
                    });


                // ------------------------------------------------
                // DEVOLUCION
                // ------------------------------------------------
                // Emite nota de credito y reingresa el stock fisico.

                Route::prefix('return')
                    ->name('devoluciones.')
                    ->group(function () {

                        Route::get('/', [DevolucionController::class, 'create'])
                            ->name('create');

                        Route::post('/', [DevolucionController::class, 'store'])
                            ->name('store');
                    });
            });


        // ========================================================
        // COMPROBANTES
        // ========================================================
        // Solo lectura y reintento. No hay alta manual —el comprobante nace
        // de acreditar un pago o de una devolucion— ni edicion ni borrado:
        // es inmutable una vez que tiene CAE.
        //
        // El acceso habitual es desde el detalle de la venta. El listado se
        // conserva porque es el unico lugar donde se ve el estado ante ARCA
        // de todos los comprobantes a la vez: un rechazo no deja rastro en la
        // venta, que queda cobrada y entregada igual.

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

                // Envia un mail: es una accion, no una modificacion del recurso.
                Route::post('/{cliente}/reenviar-invitacion', [ClienteController::class, 'reenviarInvitacion'])
                    ->name('reenviarInvitacion');
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
        // /create y /suggestions van antes de /{orden} para que no los
        // capture el wildcard.

        Route::prefix('purchase-order')
            ->name('compras.')
            ->group(function () {

                Route::get('/', [OrdenCompraController::class, 'index'])
                    ->name('index');

                Route::get('/create', [OrdenCompraController::class, 'create'])
                    ->name('create');

                // Los productos bajo su punto de reposicion que ningun
                // proveedor tiene todavia, para que el alta los cargue de una.
                // Devuelve JSON: el cliente es un fetch del formulario.
                //
                // Deja afuera lo que ya esta en una orden aprobada, enviada o
                // recibida a medias. Lo que solo figura en un BORRADOR entra
                // igual: un borrador no es un compromiso y este pedido lo
                // reemplaza. Ese borrador se da de baja solo cuando el pedido
                // nuevo se apruebe, en OrdenCompraService.
                Route::get('/suggestions', [OrdenCompraController::class, 'sugerencias'])
                    ->name('sugerencias');

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

                // La entrada de mercadería. GET arma el formulario, POST registra la entrega.
                // No es un PUT sobre la orden: lo que se crea es un movimiento de stock.
                Route::get('/{orden}/recibir', [RecepcionController::class, 'create'])
                    ->name('recibir');

                Route::post('/{orden}/recibir', [RecepcionController::class, 'store'])
                    ->name('recibir.store');

                // Da por terminado un pedido que quedó a medio recibir. Sin esto, una orden
                // en recibida_parcial bloquea la reposición de sus productos para siempre.
                Route::post('/{orden}/cerrar', [RecepcionController::class, 'cerrar'])
                    ->name('cerrar');
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
