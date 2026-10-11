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
use App\Http\Controllers\gestion\RecepcionController;
use App\Http\Controllers\gestion\StockController;
use App\Http\Controllers\gestion\VentaController;
use Illuminate\Support\Facades\Route;


// ============================================================
// LAS TRES CAPAS DE CONTROL DE ACCESO
// ============================================================
//
// Conviene tenerlas separadas en la cabeza, porque fallan distinto:
//
//   auth        ¿hay alguien? -> si no, al login
//   ambito      ¿de que aplicacion es? -> si no, al inicio de LA SUYA
//   can/permiso ¿puede hacer ESTO? -> si no, 403
//
// El ambito es pertenencia y la autorizacion es capacidad. Un cajero
// pertenece al ambito gestion —entra a /gestion sin problema— y aun asi no
// tiene por que abrir /gestion/empleado/create. Antes de esta version eso
// ultimo no estaba controlado en ningun lado: cualquier empleado llegaba a
// cualquier pantalla de gestion pegando la URL.
//
// 'can:' es la forma preferida porque pasa por la policy, y la policy mira
// permiso Y estado: tener 'venta.editar' no alcanza para editar una venta ya
// cobrada. 'permiso:' es para las rutas que no tienen modelo que consultar.
//
// Esconder el enlace en el menu NO es control de acceso. El header oculta lo
// que no corresponde para que nadie choque contra un 403 de casualidad, pero
// quien protege la pantalla es esta capa.


// ============================================================
// AUTENTICACIÓN
// ============================================================
//
// Login, registro, "olvidé mi contraseña", invitación de empleados,
// verificación de correo, cambio de contraseña y logout.

require __DIR__ . '/auth.php';


// ============================================================
// TIENDA
// ============================================================
//
// 'ambito:tienda' sin 'auth': el middleware deja pasar a los invitados —la
// vitrina es publica— y rebota a los empleados al tablero de gestion. Un
// empleado que quiera comprar se hace una cuenta de cliente aparte.
//
// Antes estas dos rutas no tenian middleware alguno y eran el agujero por el
// que un empleado logueado navegaba la tienda.

Route::middleware('ambito:tienda')
    ->group(function () {

        // ========================================================
        // HOME
        // ========================================================

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


        // ========================================================
        // CLIENTE LOGUEADO
        // ========================================================
        //
        // Cuando exista el checkout, sus rutas llevan ademas 'verified':
        // no se compra con un correo sin confirmar.

        Route::middleware('auth')->group(function () {

            // ----------------------------------------------------
            // PERFIL (datos personales y fiscales)
            // ----------------------------------------------------
            // Los nombres profile.* son los de Breeze. La contraseña se
            // cambia con password.update (routes/auth.php).

            Route::get('/perfil', [ProfileController::class, 'edit'])
                ->name('profile.edit');

            Route::patch('/perfil', [ProfileController::class, 'update'])
                ->name('profile.update');
        });
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
//
// Sobre los nombres que aparecen en 'can:': son metodos de las policies, no
// claves de permiso. 'can:cambiarEstado,producto' llama a
// ProductoPolicy::cambiarEstado(), que adentro consulta 'producto.baja'. El
// mapeo entre accion y clave vive en la policy, en un solo lugar.

Route::prefix('gestion')
    ->name('gestion.')
    ->middleware(['auth', 'ambito:gestion'])
    ->group(function () {


        // ========================================================
        // HOME
        // ========================================================
        // Sin autorizacion propia: el tablero es la pantalla de llegada de
        // cualquier empleado y se arma solo con los bloques que los permisos
        // de quien entra habilitan. Bloquearlo dejaria a un rol nuevo sin
        // ningun lugar donde caer despues del login.

        Route::get('/', [DashboardController::class, 'index'])
            ->name('home');


        // ========================================================
        // MI CUENTA
        // ========================================================
        // Tampoco lleva permiso: son los datos propios. El formulario de esta
        // vista envia a password.update (routes/auth.php).

        Route::view('/mi-cuenta', 'gestion.user.my-account')
            ->name('mi-cuenta');


        // ========================================================
        // CATEGORÍAS
        // ========================================================

        Route::prefix('category')
            ->name('categorias.')
            ->group(function () {

                Route::get('/', [CategoriaController::class, 'index'])
                    ->middleware('can:viewAny,App\Models\Categoria')
                    ->name('index');

                Route::get('/create', [CategoriaController::class, 'create'])
                    ->middleware('can:create,App\Models\Categoria')
                    ->name('create');

                Route::post('/', [CategoriaController::class, 'store'])
                    ->middleware('can:create,App\Models\Categoria')
                    ->name('store');

                Route::get('/{categoria}/edit', [CategoriaController::class, 'edit'])
                    ->middleware('can:update,categoria')
                    ->name('edit');

                Route::put('/{categoria}', [CategoriaController::class, 'update'])
                    ->middleware('can:update,categoria')
                    ->name('update');

                Route::patch('/{categoria}/baja', [CategoriaController::class, 'darDeBaja'])
                    ->middleware('can:cambiarEstado,categoria')
                    ->name('baja');

                Route::patch('/{categoria}/reactivar', [CategoriaController::class, 'reactivar'])
                    ->middleware('can:cambiarEstado,categoria')
                    ->name('reactivar');

                // Borrado fisico, con permiso propio: solo una categoria sin
                // productos, y solo para quien tenga 'categoria.eliminar'.
                Route::delete('/{categoria}', [CategoriaController::class, 'destroy'])
                    ->middleware('can:delete,categoria')
                    ->name('destroy');
            });


        // ========================================================
        // PRODUCTOS
        // ========================================================

        Route::prefix('item')
            ->name('items.')
            ->group(function () {

                Route::get('/', [ProductoController::class, 'index'])
                    ->middleware('can:viewAny,App\Models\Producto')
                    ->name('index');

                Route::get('/create', [ProductoController::class, 'create'])
                    ->middleware('can:create,App\Models\Producto')
                    ->name('create');

                Route::post('/', [ProductoController::class, 'store'])
                    ->middleware('can:create,App\Models\Producto')
                    ->name('store');

                Route::get('/{producto}/edit', [ProductoController::class, 'edit'])
                    ->middleware('can:update,producto')
                    ->name('edit');

                Route::put('/{producto}', [ProductoController::class, 'update'])
                    ->middleware('can:update,producto')
                    ->name('update');

                Route::patch('/{producto}/baja', [ProductoController::class, 'darDeBaja'])
                    ->middleware('can:cambiarEstado,producto')
                    ->name('darDeBaja');

                Route::patch('/{producto}/reactivar', [ProductoController::class, 'reactivar'])
                    ->middleware('can:cambiarEstado,producto')
                    ->name('reactivar');

                // Publicar es decision aparte de estar en operacion, y tiene
                // su propia clave: un producto activo que no se publica
                // —reservado, de exhibicion— es un caso normal del rubro.
                Route::patch('/{producto}/publicar', [ProductoController::class, 'publicar'])
                    ->middleware('can:publicar,producto')
                    ->name('publicar');

                Route::patch('/{producto}/despublicar', [ProductoController::class, 'despublicar'])
                    ->middleware('can:publicar,producto')
                    ->name('despublicar');

                // OJO: estas dos se superponen con stock.ajustar y stock.show.
                // Ver la nota al pie del bloque STOCK: hay que quedarse con una.
                //
                // Mientras convivan, al menos autorizan igual: las dos puertas
                // piden la misma clave del modulo stock, no la del catalogo.
                Route::patch('/{producto}/stock', [ProductoController::class, 'ajustarStock'])
                    ->middleware('can:ajustarStock,producto')
                    ->name('ajustarStock');

                Route::get('/{producto}/movimientos', [ProductoController::class, 'movimientos'])
                    ->middleware('can:verStock,producto')
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
        //
        // El alcance —ver todas o solo las propias— NO se decide aca: lo
        // resuelve VentaPolicy::alcanza() con 'venta.ver_todas', y el index
        // filtra la consulta con la misma clave. El vendedor entra a la misma
        // ruta que el administrativo y ve menos filas.

        Route::prefix('sale')
            ->name('ventas.')
            ->group(function () {

                Route::get('/', [VentaController::class, 'index'])
                    ->middleware('can:viewAny,App\Models\Venta')
                    ->name('index');

                Route::get('/create', [VentaController::class, 'create'])
                    ->middleware('can:create,App\Models\Venta')
                    ->name('create');

                Route::post('/', [VentaController::class, 'store'])
                    ->middleware('can:create,App\Models\Venta')
                    ->name('store');

                Route::get('/{venta}', [VentaController::class, 'show'])
                    ->middleware('can:view,venta')
                    ->name('show');

                // Solo un presupuesto se edita: una venta confirmada ya
                // comprometio stock y una pagada ya tiene CAE. Esa regla esta
                // en la policy, no aca.
                Route::get('/{venta}/edit', [VentaController::class, 'edit'])
                    ->middleware('can:update,venta')
                    ->name('edit');

                Route::put('/{venta}', [VentaController::class, 'update'])
                    ->middleware('can:update,venta')
                    ->name('update');

                // Validar confirma el presupuesto, habilita el cobro y
                // compromete las unidades.
                Route::post('/{venta}/validar', [VentaController::class, 'validar'])
                    ->middleware('can:validar,venta')
                    ->name('validar');

                Route::post('/{venta}/cancelar', [VentaController::class, 'cancelar'])
                    ->middleware('can:cancelar,venta')
                    ->name('cancelar');
            });


        // ========================================================
        // DEVOLUCIONES
        // ========================================================
        // Cuelgan de la venta porque no existen sin ella. Emiten nota de
        // credito y reingresan el stock fisico: por eso 'venta.devolver' es
        // una clave aparte de 'venta.anular'. Devolver mueve dinero.

        Route::prefix('sale/{venta}/return')
            ->name('devoluciones.')
            ->group(function () {

                Route::get('/', [DevolucionController::class, 'create'])
                    ->middleware('can:devolver,venta')
                    ->name('create');

                Route::post('/', [DevolucionController::class, 'store'])
                    ->middleware('can:devolver,venta')
                    ->name('store');
            });


        // ========================================================
        // COBROS
        // ========================================================
        // La caja. Un pago no se edita ni se borra: si se cobro de menos se
        // agrega otro pago, si se cobro de mas se devuelve con nota de
        // credito. Por eso solo hay lectura y alta.
        //
        // Los listados autorizan contra PagoPolicy; el alta, contra
        // VentaPolicy::cobrar, que ademas de la clave exige que la venta este
        // confirmada y validada. Un presupuesto sin validar no se cobra.

        Route::prefix('payment')
            ->name('cobros.')
            ->group(function () {

                Route::get('/', [PagoController::class, 'index'])
                    ->middleware('can:viewAny,App\Models\Pago')
                    ->name('index');

                Route::get('/pending', [PagoController::class, 'pendientes'])
                    ->middleware('can:viewAny,App\Models\Pago')
                    ->name('pendientes');

                Route::get('/{venta}/create', [PagoController::class, 'create'])
                    ->middleware('can:cobrar,venta')
                    ->name('create');

                Route::post('/{venta}', [PagoController::class, 'store'])
                    ->middleware('can:cobrar,venta')
                    ->name('store');
            });


        // ========================================================
        // ENTREGAS
        // ========================================================
        // El deposito. Es el unico lugar del sistema donde baja el stock
        // fisico, y baja cuando la mercaderia sale, no cuando se cobra.
        //
        // El listado usa 'permiso:' porque no recibe ningun modelo; las tres
        // acciones usan 'can:' sobre la venta, para que la policy verifique
        // ademas que la transicion sea posible desde el estado actual.

        Route::prefix('delivery')
            ->name('entregas.')
            ->group(function () {

                Route::get('/', [EntregaController::class, 'index'])
                    ->middleware('permiso:venta.entregar')
                    ->name('index');

                Route::post('/{venta}/lista-retiro', [EntregaController::class, 'listaRetiro'])
                    ->middleware('can:entregar,venta')
                    ->name('lista-retiro');

                Route::post('/{venta}/despachar', [EntregaController::class, 'despachar'])
                    ->middleware('can:entregar,venta')
                    ->name('despachar');

                Route::post('/{venta}/entregar', [EntregaController::class, 'entregar'])
                    ->middleware('can:entregar,venta')
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
                    ->middleware('can:viewAny,App\Models\Comprobante')
                    ->name('index');

                Route::get('/{comprobante}', [ComprobanteController::class, 'show'])
                    ->middleware('can:view,comprobante')
                    ->name('show');

                // La policy rechaza el reintento sobre uno ya autorizado:
                // pedir dos veces el CAE del mismo comprobante es duplicarlo.
                Route::post('/{comprobante}/reintentar', [ComprobanteController::class, 'reintentar'])
                    ->middleware('can:reintentar,comprobante')
                    ->name('reintentar');
            });


        // ========================================================
        // CLIENTES
        // ========================================================
        // Sin destroy: solo baja logica. Esta referenciado por ventas y
        // comprobantes emitidos.
        //
        // Ojo con el nombre del metodo: ClientePolicy usa 'baja', no
        // 'cambiarEstado' como las otras policies. Vale la pena unificarlo
        // alguna vez; mientras tanto, esto sigue a la policy real.

        Route::prefix('customer')
            ->name('clientes.')
            ->group(function () {

                Route::get('/', [ClienteController::class, 'index'])
                    ->middleware('can:viewAny,App\Models\Cliente')
                    ->name('index');

                Route::get('/create', [ClienteController::class, 'create'])
                    ->middleware('can:create,App\Models\Cliente')
                    ->name('create');

                Route::post('/', [ClienteController::class, 'store'])
                    ->middleware('can:create,App\Models\Cliente')
                    ->name('store');

                Route::get('/{cliente}', [ClienteController::class, 'show'])
                    ->middleware('can:view,cliente')
                    ->name('show');

                Route::get('/{cliente}/edit', [ClienteController::class, 'edit'])
                    ->middleware('can:update,cliente')
                    ->name('edit');

                Route::put('/{cliente}', [ClienteController::class, 'update'])
                    ->middleware('can:update,cliente')
                    ->name('update');

                Route::patch('/{cliente}/baja', [ClienteController::class, 'baja'])
                    ->middleware('can:baja,cliente')
                    ->name('baja');

                Route::patch('/{cliente}/reactivar', [ClienteController::class, 'reactivar'])
                    ->middleware('can:baja,cliente')
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
        //
        // AUTORIZACION: no se puede resolver con 'can:' porque el permiso que
        // corresponde depende de {tipo}, que recien se conoce en tiempo de
        // request. Editar el domicilio de un proveedor es editar al proveedor;
        // el de un empleado, al empleado. Se autoriza DENTRO del controlador
        // (ver el metodo autorizarDueno que acompaña a este cambio). Sin eso,
        // estas tres rutas quedan abiertas a cualquier empleado y son el
        // camino lateral para tocar fichas que no le corresponden.

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
        // Aca va 'permiso:' y no 'can:' porque ninguna de las cuatro recibe
        // un modelo sobre el que opinar: el ajuste llega con el producto en
        // el body, no en la URL.
        //
        // Ver y ajustar son claves distintas a proposito: el vendedor
        // consulta el stock al cargar una linea, pero el inventario lo
        // corrige el administrativo.
        //
        // SUPERPOSICION A RESOLVER: items.ajustarStock e items.movimientos
        // hacen lo mismo que stock.ajustar y stock.show. Conviene quedarse
        // con un solo camino y borrar el otro, o el ajuste va a existir en
        // dos pantallas con dos validaciones distintas.

        Route::prefix('stock')
            ->name('stock.')
            ->group(function () {

                Route::get('/', [StockController::class, 'index'])
                    ->middleware('permiso:stock.ver')
                    ->name('index');

                Route::post('/ajustes', [StockController::class, 'ajustar'])
                    ->middleware('permiso:stock.ajustar')
                    ->name('ajustar');

                Route::post('/carga-inicial', [StockController::class, 'cargaInicial'])
                    ->middleware('permiso:stock.ajustar')
                    ->name('carga-inicial');

                Route::get('/{producto}', [StockController::class, 'show'])
                    ->middleware('can:verStock,producto')
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
        //
        // La separacion de funciones —quien crea no aprueba lo que creo— vive
        // en OrdenCompraPolicy::aprobar, no aca: depende de comparar
        // usuario_creo_id con el usuario, y una ruta no sabe hacer eso.

        Route::prefix('purchase-order')
            ->name('compras.')
            ->group(function () {

                Route::get('/', [OrdenCompraController::class, 'index'])
                    ->middleware('can:viewAny,App\Models\OrdenCompra')
                    ->name('index');

                Route::get('/create', [OrdenCompraController::class, 'create'])
                    ->middleware('can:create,App\Models\OrdenCompra')
                    ->name('create');

                Route::post('/', [OrdenCompraController::class, 'store'])
                    ->middleware('can:create,App\Models\OrdenCompra')
                    ->name('store');

                Route::get('/{orden}', [OrdenCompraController::class, 'show'])
                    ->middleware('can:view,orden')
                    ->name('show');

                // Solo un borrador se edita: una orden aprobada ya paso por
                // la decision de un administrativo y una enviada ya esta en
                // manos del proveedor.
                Route::get('/{orden}/edit', [OrdenCompraController::class, 'edit'])
                    ->middleware('can:update,orden')
                    ->name('edit');

                Route::put('/{orden}', [OrdenCompraController::class, 'update'])
                    ->middleware('can:update,orden')
                    ->name('update');

                // Requerimiento explicito: ninguna orden sale sin que un
                // administrativo la apruebe.
                Route::post('/{orden}/aprobar', [OrdenCompraController::class, 'aprobar'])
                    ->middleware('can:aprobar,orden')
                    ->name('aprobar');

                // Manda el remito por correo. Es el unico canal de pedido.
                Route::post('/{orden}/enviar', [OrdenCompraController::class, 'enviar'])
                    ->middleware('can:enviar,orden')
                    ->name('enviar');

                Route::post('/{orden}/cancelar', [OrdenCompraController::class, 'cancelar'])
                    ->middleware('can:cancelar,orden')
                    ->name('cancelar');

                // La entrada de mercadería. GET arma el formulario, POST registra la entrega.
                // No es un PUT sobre la orden: lo que se crea es un movimiento de stock.
                Route::get('/{orden}/recibir', [RecepcionController::class, 'create'])
                    ->middleware('can:recibir,orden')
                    ->name('recibir');

                Route::post('/{orden}/recibir', [RecepcionController::class, 'store'])
                    ->middleware('can:recibir,orden')
                    ->name('recibir.store');

                // Da por terminado un pedido que quedó a medio recibir. Sin esto, una orden
                // en recibida_parcial bloquea la reposición de sus productos para siempre.
                Route::post('/{orden}/cerrar', [RecepcionController::class, 'cerrar'])
                    ->middleware('can:cerrar,orden')
                    ->name('cerrar');
            });


        // ========================================================
        // BÚSQUEDA (JSON)
        // ========================================================
        // Autocompletado de la pantalla de venta. Devuelve JSON, no vistas.
        //
        // Que devuelva JSON no la exime de autorizar: sin permiso, estos dos
        // endpoints son un volcado del catalogo y del padron de clientes para
        // cualquier empleado logueado. Cada uno pide la clave de lectura del
        // recurso que expone.

        Route::prefix('search')
            ->name('buscar.')
            ->group(function () {

                Route::get('/item', [BusquedaController::class, 'productos'])
                    ->middleware('permiso:producto.ver')
                    ->name('productos');

                Route::get('/customer', [BusquedaController::class, 'clientes'])
                    ->middleware('permiso:cliente.ver')
                    ->name('clientes');
            });


        // ========================================================
        // EMPLEADOS
        // ========================================================
        // El modulo mas sensible de gestion: de aca salen las credenciales y
        // los roles de todo el sistema. Antes de este cambio, cualquier
        // empleado llegaba a /gestion/empleado/create pegando la URL.

        Route::prefix('empleado')
            ->name('empleados.')
            ->group(function () {

                Route::get('/', [EmpleadoController::class, 'index'])
                    ->middleware('can:viewAny,App\Models\Empleado')
                    ->name('index');

                Route::get('/create', [EmpleadoController::class, 'create'])
                    ->middleware('can:create,App\Models\Empleado')
                    ->name('create');

                Route::post('/', [EmpleadoController::class, 'store'])
                    ->middleware('can:create,App\Models\Empleado')
                    ->name('store');

                Route::get('/{empleado}/edit', [EmpleadoController::class, 'edit'])
                    ->middleware('can:update,empleado')
                    ->name('edit');

                Route::put('/{empleado}', [EmpleadoController::class, 'update'])
                    ->middleware('can:update,empleado')
                    ->name('update');

                Route::patch('/{empleado}/baja', [EmpleadoController::class, 'darDeBaja'])
                    ->middleware('can:cambiarEstado,empleado')
                    ->name('darDeBaja');

                Route::patch('/{empleado}/reactivar', [EmpleadoController::class, 'reactivar'])
                    ->middleware('can:cambiarEstado,empleado')
                    ->name('reactivar');

                // Envia un mail: es una accion, no una modificacion del recurso.
                // Terminar un alta a medias lo puede hacer quien da de alta.
                Route::post('/{empleado}/reenviar-invitacion', [EmpleadoController::class, 'reenviarInvitacion'])
                    ->middleware('can:reenviarInvitacion,empleado')
                    ->name('reenviarInvitacion');
            });


        // ========================================================
        // PROVEEDORES
        // ========================================================

        Route::prefix('proveedor')
            ->name('proveedores.')
            ->group(function () {

                Route::get('/', [ProveedorController::class, 'index'])
                    ->middleware('can:viewAny,App\Models\Proveedor')
                    ->name('index');

                Route::get('/create', [ProveedorController::class, 'create'])
                    ->middleware('can:create,App\Models\Proveedor')
                    ->name('create');

                Route::post('/', [ProveedorController::class, 'store'])
                    ->middleware('can:create,App\Models\Proveedor')
                    ->name('store');

                Route::get('/{proveedor}/edit', [ProveedorController::class, 'edit'])
                    ->middleware('can:update,proveedor')
                    ->name('edit');

                Route::put('/{proveedor}', [ProveedorController::class, 'update'])
                    ->middleware('can:update,proveedor')
                    ->name('update');

                Route::patch('/{proveedor}/baja', [ProveedorController::class, 'darDeBaja'])
                    ->middleware('can:cambiarEstado,proveedor')
                    ->name('darDeBaja');

                Route::patch('/{proveedor}/reactivar', [ProveedorController::class, 'reactivar'])
                    ->middleware('can:cambiarEstado,proveedor')
                    ->name('reactivar');
            });


        // ========================================================
        // PROMOCIONES
        // ========================================================
        // El vendedor tiene 'promocion.ver' y nada mas: necesita saber que
        // descuento se aplica, pero no inventarlo.

        Route::prefix('promotion')
            ->name('promociones.')
            ->group(function () {

                Route::get('/', [PromocionController::class, 'index'])
                    ->middleware('can:viewAny,App\Models\Promocion')
                    ->name('index');

                Route::get('/create', [PromocionController::class, 'create'])
                    ->middleware('can:create,App\Models\Promocion')
                    ->name('create');

                Route::post('/', [PromocionController::class, 'store'])
                    ->middleware('can:create,App\Models\Promocion')
                    ->name('store');

                Route::get('/{promocion}/edit', [PromocionController::class, 'edit'])
                    ->middleware('can:update,promocion')
                    ->name('edit');

                Route::put('/{promocion}', [PromocionController::class, 'update'])
                    ->middleware('can:update,promocion')
                    ->name('update');

                Route::patch('/{promocion}/baja', [PromocionController::class, 'darDeBaja'])
                    ->middleware('can:cambiarEstado,promocion')
                    ->name('darDeBaja');

                Route::patch('/{promocion}/reactivar', [PromocionController::class, 'reactivar'])
                    ->middleware('can:cambiarEstado,promocion')
                    ->name('reactivar');
            });
    });
