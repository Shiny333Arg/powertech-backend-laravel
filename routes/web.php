<?php

use App\Http\Controllers\CarritoController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RetornoMovilController;
use App\Http\Controllers\gestion\BusquedaController;
use App\Http\Controllers\gestion\CategoriaController;
use App\Http\Controllers\gestion\ClienteController;
use App\Http\Controllers\gestion\ComprobanteController;
use App\Http\Controllers\gestion\DashboardController;
use App\Http\Controllers\gestion\DevolucionController;
use App\Http\Controllers\gestion\EmpleadoController;
use App\Http\Controllers\gestion\EntregaController;
use App\Http\Controllers\gestion\OrdenCompraController;
use App\Http\Controllers\gestion\PagoController;
use App\Http\Controllers\gestion\ProductoController;
use App\Http\Controllers\gestion\PromocionController;
use App\Http\Controllers\gestion\ProveedorController;
use App\Http\Controllers\gestion\RecepcionController;
use App\Http\Controllers\gestion\SesionController;
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
// CATÁLOGO / RESULTADOS DE BÚSQUEDA
// ========================================================
// Pública, igual que la ficha. Todos los filtros viajan por querystring:
// ?q=&categoria=&marca=&precio_min=&precio_max=&orden=&promocion=

Route::get('/items', [CatalogoController::class, 'index'])
    ->name('cliente.items.index');


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


// ========================================================
// WEBHOOK DE MERCADO PAGO
// ========================================================
// Va ACÁ ARRIBA, fuera de todo grupo, y no es un descuido: la llamada
// llega desde los servidores de Mercado Pago, no desde un navegador.
// No tiene sesión, ni cookies, ni usuario autenticado, así que 'auth'
// la rechazaría y se perderían todas las acreditaciones.
//
// Es el camino AUTORITATIVO del cobro: llega aunque el cliente cierre
// la pestaña después de pagar, y Mercado Pago lo reintenta. Lo que
// reemplaza a la autenticación es la firma HMAC del aviso, que se
// verifica en PasarelaMercadoPago::esAvisoConfiable().
//
// FALTA UN PASO FUERA DE ESTE ARCHIVO: hay que excluir esta URI de la
// verificación CSRF. Una llamada POST de Mercado Pago no trae token.
//   Laravel 11/12 -> bootstrap/app.php, en withMiddleware():
//       $middleware->validateCsrfTokens(except: ['checkout/webhook']);
//   Laravel 10 o anterior -> app/Http/Middleware/VerifyCsrfToken.php:
//       protected $except = ['checkout/webhook'];

Route::post('/checkout/webhook', [CheckoutController::class, 'webhook'])
    ->name('cliente.checkout.webhook');


// Rebote del checkout de la APP. Publica, igual que el webhook y por un
// motivo parecido: quien la abre no tiene sesion de Laravel.
//
// El cliente que paga desde Flutter lo hace en el navegador del sistema. Ese
// navegador no conoce el token Bearer de la app, asi que 'cliente.checkout.
// retorno' —que vive detras de auth y verified— lo mandaria al login al
// volver de Mercado Pago.
//
// No confirma nada ni toca la compra: muestra una pagina que invita a volver
// a la app. La venta la crea el webhook y la app la consulta con
// GET /api/v1/checkout/estado. Ver RetornoMovilController.
Route::get('/checkout/retorno-movil', RetornoMovilController::class)
    ->name('cliente.checkout.retorno-movil');


// ============================================================
// TIENDA - CLIENTE LOGUEADO
// ============================================================
//
// Todo lo de acá adentro exige sesión de cliente. 'ambito:tienda' va
// después de 'auth' y manda a /gestion al empleado que entre por error.
//
// 'verified' se aplica por bloque y no al grupo entero: un cliente con
// el correo sin confirmar puede navegar y ver su historial, pero no
// armar pedidos ni pagar.

Route::middleware(['auth', 'ambito:tienda'])
    ->group(function () {


        // ========================================================
        // PERFIL (datos personales y fiscales)
        // ========================================================

        Route::get('/perfil', [ProfileController::class, 'edit'])
            ->name('profile.edit');

        Route::patch('/perfil', [ProfileController::class, 'update'])
            ->name('profile.update');


        // ========================================================
        // CARRITO
        // ========================================================
        // El carrito NO es un recurso del cliente sino el suyo propio: no
        // hay index de carritos ni {carrito} en la URL. Lo que se manipula
        // es la línea, y la línea se valida contra el carrito abierto de
        // quien pide.

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


        // ========================================================
        // CHECKOUT
        // ========================================================
        // Solo retiro en el local: el circuito de envío y tracking no está
        // implementado todavía.
        //
        // La venta online nace PAGADA: sin pago acreditado no hay venta, y
        // sin venta no hay stock comprometido. Por eso no existe una ruta
        // de "crear pedido" separada del pago.
        //
        // El cobro ocurre fuera de este sitio, así que el circuito tiene
        // tres pasos y la vuelta llega por dos caminos:
        //
        //   index    el resumen, antes de pagar
        //   pagar    crea la preferencia y redirige a Mercado Pago
        //   retorno  el cliente vuelve del sitio de Mercado Pago
        //
        // El cuarto, el webhook, está arriba y fuera de este grupo porque
        // no llega desde un navegador.
        //
        // `retorno` va adentro de 'auth' a propósito: Mercado Pago vuelve
        // con una navegación GET de primer nivel, y la cookie de sesión de
        // Laravel es SameSite=Lax, que sí viaja en ese caso. Si igual se
        // perdiera, el webhook ya creó la venta y el cliente la encuentra
        // en "Mis compras".

        Route::prefix('checkout')
            ->name('cliente.checkout.')
            ->middleware('verified')
            ->group(function () {

                Route::get('/', [CheckoutController::class, 'index'])
                    ->name('index');

                Route::post('/pagar', [CheckoutController::class, 'pagar'])
                    ->name('pagar');

                Route::get('/retorno', [CheckoutController::class, 'retorno'])
                    ->name('retorno');
            });


        // ========================================================
        // MIS COMPRAS
        // ========================================================
        // Solo lectura. El cliente no cancela ni modifica desde acá:
        // cancelar una venta pagada libera stock comprometido y, si ya
        // tiene CAE, exige nota de crédito. Es una decisión de la empresa,
        // no autogestión.
        //
        // Sin 'verified': consultar el historial no requiere correo
        // confirmado. Solo comprar lo requiere.

        Route::prefix('compras')
            ->name('cliente.compras.')
            ->group(function () {

                Route::get('/', [CompraController::class, 'index'])
                    ->name('index');

                Route::get('/{venta}', [CompraController::class, 'show'])
                    ->name('show');
            });
    });


// ============================================================
// GESTIÓN
// ============================================================
//
// Solo empleados logueados. Un cliente que abre /gestion vuelve a la tienda.

// Intercambio del token de gestion por la sesion Blade.
//
// <b>FUERA del grupo de abajo, y tiene que estar fuera.</b> La URL lleva el
// prefijo /gestion/ pero la ruta no pertenece al grupo: ese grupo tiene 'auth'
// y en este momento todavia no hay sesion —es justo la peticion que la crea—,
// asi que adentro 'auth' correria primero y redirigiria al login devolviendole
// HTML al AJAX.
//
// Pertenece al grupo 'web', que es lo que le da sesion y CSRF. El CSRF se
// valida con el <meta name="csrf-token"> que ya imprime layouts/auth.blade.php;
// NO va en la lista de excepciones de bootstrap/app.php. Throttle propio: es
// una puerta de entrada.
//
// No acepta credenciales: lo unico que recibe es un Bearer. Ver SesionController.
Route::post('gestion/sesion', [SesionController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('gestion.sesion');

Route::prefix('gestion')
    ->name('gestion.')
    ->middleware(['auth', 'ambito:gestion', 'gestion.token'])
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
        //
        // <b>Solo quedan las tres rutas que entregan paginas Blade.</b> Las
        // cinco que ejecutaban operaciones de negocio —store, update, baja,
        // reactivar y destroy— se retiraron: la pantalla dejo de usarlas al
        // migrarse a la API, y mientras siguieran publicadas eran una via
        // alternativa de acceso, que es lo que el requisito 3 de la Etapa 3
        // pide cerrar.
        //
        // Lo que antes hacia cada una, ahora:
        //
        //   POST   category                -> POST   /api/v1/gestion/categorias
        //   PUT    category/{c}            -> PUT    /api/v1/gestion/categorias/{c}
        //   PATCH  category/{c}/baja       -> PATCH  .../{c}/estado  {activo:false}
        //   PATCH  category/{c}/reactivar  -> PATCH  .../{c}/estado  {activo:true}
        //   DELETE category/{c}            -> DELETE /api/v1/gestion/categorias/{c}
        //
        // Los permisos no cambiaron: los exige la misma CategoriaPolicy, ahora
        // desde el 'can:' de cada ruta de la API. El 409 de "tiene productos
        // asociados" lo sigue decidiendo la foreign key, no la pantalla.
        //
        // Con esto, los tres modulos migrados —categorias, productos y
        // proveedores— ya no tienen ninguna ruta web de negocio. Los que
        // faltan migrar conservan las suyas.

        Route::prefix('category')
            ->name('categorias.')
            ->group(function () {

                Route::get('/', [CategoriaController::class, 'index'])
                    ->name('index');

                Route::get('/create', [CategoriaController::class, 'create'])
                    ->name('create');

                Route::get('/{categoria}/edit', [CategoriaController::class, 'edit'])
                    ->name('edit');
            });


        // ========================================================
        // PRODUCTOS
        // ========================================================
        //
        // <b>Solo quedan las tres rutas que entregan paginas Blade.</b> Las
        // seis que ejecutaban operaciones de negocio —store, update, baja,
        // reactivar, publicar y despublicar— se retiraron al migrar la
        // pantalla a la API: el requisito 3 de la Etapa 3 pide que las rutas
        // web anteriores dejen de estar disponibles como via alternativa de
        // acceso, y mientras existieran lo estaban, aunque ninguna pantalla
        // las usara.
        //
        // Lo que antes hacia cada una, ahora:
        //
        //   POST  item                     -> POST  /api/v1/gestion/productos
        //   PUT   item/{p}                 -> PUT   /api/v1/gestion/productos/{p}
        //   PATCH item/{p}/baja            -> PATCH .../{p}/estado  {activo:false}
        //   PATCH item/{p}/reactivar       -> PATCH .../{p}/estado  {activo:true}
        //   PATCH item/{p}/publicar        -> PATCH .../{p}/publicacion {publicado_app:true}
        //   PATCH item/{p}/despublicar     -> PATCH .../{p}/publicacion {publicado_app:false}
        //
        // Los permisos no cambiaron: los exige la misma ProductoPolicy, ahora
        // desde el 'can:' de cada ruta de la API.
        //
        // Las categorias conservan las suyas a proposito: se retiran cuando se
        // retiren las del resto de los modulos, para no dejar a medias un
        // modulo que ya esta migrado y en uso.

        Route::prefix('item')
            ->name('items.')
            ->group(function () {

                Route::get('/', [ProductoController::class, 'index'])
                    ->name('index');

                Route::get('/create', [ProductoController::class, 'create'])
                    ->name('create');

                Route::get('/{producto}/edit', [ProductoController::class, 'edit'])
                    ->name('edit');

                // La superposicion con stock.ajustar y stock.show se resolvio en
                // favor del bloque STOCK: ahi viven el ajuste con motivo y el
                // kardex. Las dos rutas que habia aca —items.ajustarStock e
                // items.movimientos— apuntaban a metodos comentados, asi que
                // respondian 500 al invocarse.
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
        //
        // <b>Solo quedan las cuatro rutas que ENTREGAN paginas.</b> Las cuatro
        // que ejecutaban operaciones de negocio se retiraron en la Etapa 3 y
        // viven en /api/v1/gestion/clientes, que es el unico camino para
        // escribir un cliente desde el panel:
        //
        //   POST   clientes.store                 -> POST   /clientes
        //   PUT    clientes.update                -> PUT    /clientes/{cliente}
        //   PATCH  clientes.baja                  -> PATCH  /clientes/{cliente}/estado  {activo:false}
        //   PATCH  clientes.reactivar             -> PATCH  /clientes/{cliente}/estado  {activo:true}
        //   POST   clientes.reenviarInvitacion    -> POST   /clientes/{cliente}/invitacion
        //
        // La baja y la reactivacion eran dos rutas y ahora son un endpoint:
        // son la misma decision —y el mismo permiso, cliente.baja— mirada desde
        // los dos lados, y la diferencia esta en que un formulario Blade no
        // puede mandar un booleano en el cuerpo y un cliente JSON si.
        //
        // Con ellas se fueron los cinco metodos del controlador que existian
        // solo para servirlas.

        Route::prefix('customer')
            ->name('clientes.')
            ->group(function () {

                Route::get('/', [ClienteController::class, 'index'])
                    ->name('index');

                Route::get('/create', [ClienteController::class, 'create'])
                    ->name('create');

                Route::get('/{cliente}', [ClienteController::class, 'show'])
                    ->name('show');

                Route::get('/{cliente}/edit', [ClienteController::class, 'edit'])
                    ->name('edit');
            });


        // ========================================================
        // DIRECCIONES
        // ========================================================
        // <b>El grupo se retiro completo en la Etapa 3.</b> Era una sola tabla
        // polimorfica para clientes, proveedores y empleados, pero su UNICO
        // consumidor era la ficha del cliente: el proveedor y el empleado
        // mandan su domicilio anidado bajo 'direccion' en el cuerpo de su
        // propio formulario, y nunca usaron estas rutas. Al migrar clientes
        // quedo sin nadie que lo llame.
        //
        //   POST   direcciones.store    -> POST   /api/v1/gestion/clientes/{cliente}/direcciones
        //   PUT    direcciones.update   -> PUT    /api/v1/gestion/clientes/{cliente}/direcciones/{direccion}
        //   DELETE direcciones.destroy  -> DELETE /api/v1/gestion/clientes/{cliente}/direcciones/{direccion}
        //
        // Se mantiene lo que importaba del diseño viejo: el dueño sale de la URL
        // y nunca del cuerpo —si el tipo llegara del formulario, cualquiera
        // podria colgarle una direccion fiscal a otro— y la direccion SI se
        // borra, porque no es un documento: los envios copian el domicilio en
        // lugar de apuntar aca, justamente para que un despacho ya hecho no
        // cambie de destino si alguien edita el perfil.
        //
        // Lo que se fue es el discriminador {tipo}: en la API el cliente es el
        // unico que necesita estas rutas, asi que el sub-recurso cuelga de
        // /clientes/{cliente} y no hay un parametro libre que defender.
        //
        // App\Http\Controllers\gestion\DireccionController se elimino con el
        // grupo. App\Http\Requests\DireccionRequest NO: lo reutiliza
        // Api\V1\Gestion\ClienteDireccionController, con las mismas reglas.


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
        //
        // <b>Primera entrega de la migración del módulo: el alta, la edición y
        // la recepción de mercadería pasaron a la API.</b> Las cuatro rutas que
        // ejecutaban esas operaciones se retiraron, por el mismo motivo que en
        // productos, proveedores y clientes: el requisito 3 de la Etapa 3 pide
        // que las rutas web anteriores dejen de estar disponibles como vía
        // alternativa de acceso, y mientras existieran lo estaban.
        //
        // Lo que antes hacía cada una, ahora:
        //
        //   POST purchase-order              -> POST /api/v1/gestion/compras
        //   PUT  purchase-order/{o}          -> PUT  /api/v1/gestion/compras/{o}
        //   GET  purchase-order/suggestions  -> GET  /api/v1/gestion/compras/sugerencias
        //   POST purchase-order/{o}/recibir  -> POST /api/v1/gestion/compras/{o}/recepciones
        //
        // Los permisos no cambiaron: los exige la misma OrdenCompraPolicy,
        // ahora desde el 'can:' de cada ruta de la API, y con ella las dos
        // reglas que no son obvias —quien crea no aprueba, y el estado manda
        // sobre el permiso—. La validación sigue siendo la del mismo
        // OrdenCompraRequest y del mismo RecepcionRequest.
        //
        // <b>Las cuatro transiciones del documento siguen publicadas acá, a
        // propósito.</b> Aprobar, enviar, cancelar y cerrar con faltante viven
        // en los formularios del detalle —show.blade.php—, que no se migró en
        // esta entrega. Son la segunda, y sus endpoints ya existen desde E.2.a
        // y E.2.b. Retirarlas ahora dejaría el detalle sin poder operar.
        //
        // <b>Las cinco GET que entregan páginas se conservan todas</b>,
        // incluida /{orden}/recibir: la pantalla de recepción se migró, pero la
        // página la sigue sirviendo Blade, y su GET es además donde la policy
        // decide si ese pedido admite una entrega.
        //
        // Sin destroy, por el mismo criterio que ventas: la orden no se
        // elimina ni se da de baja. Lo que en otro CRUD seria la D es
        // 'cancelar', que conserva el numero y el historial y —esto es lo que
        // importa para el circuito— vuelve a habilitar la reposicion
        // automatica de sus productos.
        //
        // /create va antes de /{orden} para que no lo capture el wildcard.

        Route::prefix('purchase-order')
            ->name('compras.')
            ->group(function () {

                Route::get('/', [OrdenCompraController::class, 'index'])
                    ->name('index');

                Route::get('/create', [OrdenCompraController::class, 'create'])
                    ->name('create');

                Route::get('/{orden}', [OrdenCompraController::class, 'show'])
                    ->name('show');

                // Solo un borrador se edita: una orden aprobada ya paso por
                // la decision de un administrativo y una enviada ya esta en
                // manos del proveedor. El 403 lo sigue dando el controlador
                // antes de entregar la pagina, asi que el enlace directo
                // tampoco entra.
                Route::get('/{orden}/edit', [OrdenCompraController::class, 'edit'])
                    ->name('edit');

                // Requerimiento explicito: ninguna orden sale sin que un
                // administrativo la apruebe.
                Route::post('/{orden}/aprobar', [OrdenCompraController::class, 'aprobar'])
                    ->name('aprobar');

                // Manda el remito por correo. Es el unico canal de pedido.
                Route::post('/{orden}/enviar', [OrdenCompraController::class, 'enviar'])
                    ->name('enviar');

                Route::post('/{orden}/cancelar', [OrdenCompraController::class, 'cancelar'])
                    ->name('cancelar');

                // La entrada de mercadería: la pagina del formulario. Lo que se
                // registra NO es un PUT sobre la orden —lo que se crea es un
                // movimiento de stock—, y desde esta entrega lo registra
                // POST /api/v1/gestion/compras/{orden}/recepciones.
                Route::get('/{orden}/recibir', [RecepcionController::class, 'create'])
                    ->name('recibir');

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
        //
        // <b>Solo quedan las tres rutas que entregan paginas Blade.</b> Las
        // cuatro que ejecutaban operaciones de negocio —store, update, baja y
        // reactivar— se retiraron al migrar la pantalla a la API, por el mismo
        // motivo que en productos: el requisito 3 de la Etapa 3 pide que las
        // rutas web anteriores dejen de estar disponibles como via alternativa
        // de acceso, y mientras existieran lo estaban.
        //
        // Lo que antes hacia cada una, ahora:
        //
        //   POST  proveedor                -> POST  /api/v1/gestion/proveedores
        //   PUT   proveedor/{p}            -> PUT   /api/v1/gestion/proveedores/{p}
        //   PATCH proveedor/{p}/baja       -> PATCH .../{p}/estado  {activo:false}
        //   PATCH proveedor/{p}/reactivar  -> PATCH .../{p}/estado  {activo:true}
        //
        // Los permisos no cambiaron: los exige la misma ProveedorPolicy, ahora
        // desde el 'can:' de cada ruta de la API. El domicilio fiscal sigue
        // viajando en el mismo cuerpo del alta y de la edicion, bajo la clave
        // 'direccion', y lo guarda el mismo App\Support\DomicilioFiscal.

        Route::prefix('proveedor')
            ->name('proveedores.')
            ->group(function () {

                Route::get('/', [ProveedorController::class, 'index'])
                    ->name('index');

                Route::get('/create', [ProveedorController::class, 'create'])
                    ->name('create');

                Route::get('/{proveedor}/edit', [ProveedorController::class, 'edit'])
                    ->name('edit');
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
