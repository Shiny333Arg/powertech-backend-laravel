<?php

use App\Http\Controllers\Api\V1\Gestion\Acciones\AjustarStockController;
use App\Http\Controllers\Api\V1\Gestion\Acciones\AprobarOrdenCompraController;
use App\Http\Controllers\Api\V1\Gestion\Acciones\CambiarEstadoCategoriaController;
use App\Http\Controllers\Api\V1\Gestion\Acciones\CambiarEstadoClienteController;
use App\Http\Controllers\Api\V1\Gestion\Acciones\CambiarEstadoProductoController;
use App\Http\Controllers\Api\V1\Gestion\Acciones\CambiarEstadoProveedorController;
use App\Http\Controllers\Api\V1\Gestion\Acciones\CambiarPublicacionProductoController;
use App\Http\Controllers\Api\V1\Gestion\Acciones\CancelarOrdenCompraController;
use App\Http\Controllers\Api\V1\Gestion\Acciones\CerrarOrdenCompraController;
use App\Http\Controllers\Api\V1\Gestion\Acciones\EnviarOrdenCompraController;
use App\Http\Controllers\Api\V1\Gestion\Acciones\RegistrarRecepcionController;
use App\Http\Controllers\Api\V1\Gestion\Acciones\ReenviarInvitacionClienteController;
use App\Http\Controllers\Api\V1\Gestion\CategoriaController;
use App\Http\Controllers\Api\V1\Gestion\ClienteController;
use App\Http\Controllers\Api\V1\Gestion\ClienteDireccionController;
use App\Http\Controllers\Api\V1\Gestion\OrdenCompraController;
use App\Http\Controllers\Api\V1\Gestion\ProductoController;
use App\Http\Controllers\Api\V1\Gestion\ProveedorController;
use App\Http\Controllers\Api\V1\Gestion\StockController;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\OrdenCompra;
use App\Models\Producto;
use App\Models\Proveedor;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API DE GESTION (PANEL DE EMPLEADOS)
|--------------------------------------------------------------------------
|
| El ambito administrativo de la API. Hermano de routes/api.php, que atiende a
| la tienda: mismos servicios, mismas reglas de negocio, misma forma de
| respuesta; lo que cambia es QUIEN entra y QUE ve.
|
| <b>Archivo aparte y no un grupo mas dentro de api.php.</b> Las dos cadenas de
| middleware son distintas y excluyentes —un token de tienda no entra aca y uno
| de gestion no entra alla—, y tenerlas en archivos separados evita que un
| grupo herede por descuido el middleware del vecino. Lo carga el cierre
| `then:` de bootstrap/app.php, que le pone el grupo 'api' y el prefijo 'api';
| por eso aca el prefijo arranca en 'v1/gestion' y las URL finales quedan
| /api/v1/gestion/...
|
| <b>Las cuatro capas del grupo, cada una respondiendo una pregunta distinta:</b>
|
|   auth:sanctum       el token existe, no vencio y no fue revocado       401
|   api.activo         la cuenta sigue activa                             401
|   api.ambito:gestion esta cuenta pertenece al panel, no a la tienda     403
|   abilities:gestion  este token se emitio PARA el panel                 403
|
| 'api.ambito' y 'abilities' no son redundantes: el primero mira quien es el
| usuario AHORA —un empleado al que le cambiaron el rol tiene un token con la
| ability vieja— y el segundo para que se emitio el token —uno de la tienda
| tiene al usuario correcto y la ability equivocada—.
|
| <b>La quinta capa va por ruta: 'can:'.</b> El permiso fino lo decide la
| policy del recurso, una por ruta y a la vista. No se usa authorizeResource()
| en el controlador: cubre solo los siete metodos de recurso y deja las
| acciones de estado sin autorizar, que es exactamente el agujero que F1 cerro
| en el panel. Aca cada ruta dice su permiso y ninguna puede quedar sin el.
|
| <b>Las policies son las MISMAS que usa el panel Blade</b> (CategoriaPolicy,
| ProductoPolicy...), sin una version "api" en paralelo: los permisos de un rol
| no cambian segun por donde entre la peticion. Y `tienePermiso()` consulta la
| base en cada request, asi que quitarle un permiso a un rol se refleja en la
| llamada siguiente aunque el token siga vivo.
|
*/

Route::prefix('v1/gestion')
    ->name('api.v1.gestion.')
    ->middleware([
        'throttle:60,1',
        'auth:sanctum',
        'api.activo',
        'api.ambito:gestion',
        'abilities:gestion',
    ])
    ->group(function () {

        // ============================================================
        // CATEGORIAS
        // ============================================================
        //
        // La unica entidad del catalogo que admite borrado fisico, y solo si
        // ningun producto la referencia. Por eso tiene las dos bajas:
        //
        //   PATCH /{categoria}/estado  baja logica, reversible   permiso categoria.baja
        //   DELETE /{categoria}        borrado fisico            permiso categoria.eliminar
        //
        // Son permisos distintos a proposito: sacar de operacion no es lo
        // mismo que borrar.
        //
        // <b>PUT y no PATCH para editar.</b> El cuerpo reemplaza el recurso
        // completo, que es lo que CategoriaRequest ya valida para el
        // formulario web. PATCH queda reservado para las acciones de estado,
        // donde si corresponde: cambian un campo y nada mas.

        Route::prefix('categorias')
            ->name('categorias.')
            ->group(function () {

                Route::get('/', [CategoriaController::class, 'index'])
                    ->middleware('can:viewAny,'.Categoria::class)
                    ->name('index');

                Route::post('/', [CategoriaController::class, 'store'])
                    ->middleware('can:create,'.Categoria::class)
                    ->name('store');

                Route::get('/{categoria}', [CategoriaController::class, 'show'])
                    ->whereNumber('categoria')
                    ->middleware('can:view,categoria')
                    ->name('show');

                Route::put('/{categoria}', [CategoriaController::class, 'update'])
                    ->whereNumber('categoria')
                    ->middleware('can:update,categoria')
                    ->name('update');

                // Baja logica y reactivacion en un solo endpoint: son la misma
                // decision —y el mismo permiso— mirada desde los dos lados.
                Route::patch('/{categoria}/estado', CambiarEstadoCategoriaController::class)
                    ->whereNumber('categoria')
                    ->middleware('can:cambiarEstado,categoria')
                    ->name('estado');

                Route::delete('/{categoria}', [CategoriaController::class, 'destroy'])
                    ->whereNumber('categoria')
                    ->middleware('can:delete,categoria')
                    ->name('destroy');
            });

        // ============================================================
        // PRODUCTOS
        // ============================================================
        //
        // <b>No hay DELETE, y la ausencia es la regla.</b> Un producto esta
        // referenciado por lineas de venta, de comprobante, de orden de compra
        // y por los movimientos de stock: eliminarlo dejaria esa historia sin
        // sujeto. La unica baja es PATCH .../estado con activo=false.
        //
        // Tres endpoints de escritura y tres permisos distintos, porque son
        // tres decisiones distintas:
        //
        //   PUT   /{producto}              datos del producto   producto.editar
        //   PATCH /{producto}/estado       ¿opera?              producto.baja
        //   PATCH /{producto}/publicacion  ¿se ve en la tienda? producto.publicar
        //
        // Separar publicacion de estado no es ceremonia: 'producto.publicar' lo
        // tienen el administrador y el administrativo, y no el vendedor ni el
        // cajero. Si publicar fuera un campo mas del PUT, cualquiera que pueda
        // editar precios podria publicar.
        //
        // <b>Imagenes.</b> El alta y la edicion aceptan multipart/form-data.
        // Para mandar archivo en la edicion hay que usar POST con _method=PUT:
        // PHP no parsea el cuerpo multipart de un PUT. Es la misma convencion
        // del formulario Blade con @method('PUT'), asi que no hace falta
        // declarar una ruta aparte.

        Route::prefix('productos')
            ->name('productos.')
            ->group(function () {

                Route::get('/', [ProductoController::class, 'index'])
                    ->middleware('can:viewAny,'.Producto::class)
                    ->name('index');

                Route::post('/', [ProductoController::class, 'store'])
                    ->middleware('can:create,'.Producto::class)
                    ->name('store');

                Route::get('/{producto}', [ProductoController::class, 'show'])
                    ->whereNumber('producto')
                    ->middleware('can:view,producto')
                    ->name('show');

                Route::put('/{producto}', [ProductoController::class, 'update'])
                    ->whereNumber('producto')
                    ->middleware('can:update,producto')
                    ->name('update');

                Route::patch('/{producto}/estado', CambiarEstadoProductoController::class)
                    ->whereNumber('producto')
                    ->middleware('can:cambiarEstado,producto')
                    ->name('estado');

                Route::patch('/{producto}/publicacion', CambiarPublicacionProductoController::class)
                    ->whereNumber('producto')
                    ->middleware('can:publicar,producto')
                    ->name('publicacion');

                // ----------------------------------------------------
                // Stock de un producto
                // ----------------------------------------------------
                //
                // Cuelgan del producto y no de /stock porque son sobre UN
                // producto, y asi el id sale de la URL en lugar del cuerpo:
                // no hay forma de consultar —ni de ajustar— un producto
                // distinto del que dice la ruta.
                //
                // Permisos por policy y no por el middleware 'permiso:', a
                // diferencia del kardex global: aca hay un modelo, asi que se
                // usan `ProductoPolicy::verStock()` y `::ajustarStock()`, que
                // estaban escritas desde siempre y no las llamaba nadie. Las
                // dos devuelven exactamente `tienePermiso('stock.ver')` y
                // `tienePermiso('stock.ajustar')`, de modo que el criterio es
                // identico al del panel.

                Route::get('/{producto}/stock', [StockController::class, 'show'])
                    ->whereNumber('producto')
                    ->middleware('can:verStock,producto')
                    ->name('stock');

                Route::post('/{producto}/stock/ajustes', AjustarStockController::class)
                    ->whereNumber('producto')
                    ->middleware('can:ajustarStock,producto')
                    ->name('stock.ajustes');
            });

        // ============================================================
        // PROVEEDORES
        // ============================================================
        //
        // <b>No hay DELETE, y la ausencia es la regla.</b> El proveedor esta
        // referenciado por productos y por ordenes de compra: la orden dice a
        // quien se le compro, asi que eliminar al proveedor dejaria esa
        // historia sin sujeto. ProveedorPolicy no tiene metodo delete() —nunca
        // lo tuvo—, la ruta web tampoco existe y el guard del modelo rechazaria
        // el delete() igual. Es el mismo criterio que productos, y la razon por
        // la que categorias si lo admite esta explicada mas arriba: ahi el
        // borrado solo pasa si nadie la referencia.
        //
        // Dos endpoints de escritura y dos permisos distintos, porque son dos
        // decisiones distintas:
        //
        //   PUT   /{proveedor}         datos del proveedor   proveedor.editar
        //   PATCH /{proveedor}/estado  ¿opera?               proveedor.baja
        //
        // <b>Un solo endpoint de estado para las dos direcciones</b>, mientras
        // el panel tiene dos rutas (/baja y /reactivar). No es una divergencia
        // de criterio: es la misma policy y el mismo permiso, y la diferencia
        // esta en que un formulario Blade no puede mandar un booleano en el
        // cuerpo y un cliente JSON si.
        //
        // <b>El domicilio fiscal viaja en el cuerpo del alta y de la edicion</b>
        // bajo la clave 'direccion', igual que en el formulario, y no tiene
        // endpoint propio: es uno solo por proveedor y un CRUD anidado para una
        // fila seria ruido. Lo guarda App\Support\DomicilioFiscal, el mismo que
        // usa el controlador web.

        Route::prefix('proveedores')
            ->name('proveedores.')
            ->group(function () {

                Route::get('/', [ProveedorController::class, 'index'])
                    ->middleware('can:viewAny,'.Proveedor::class)
                    ->name('index');

                Route::post('/', [ProveedorController::class, 'store'])
                    ->middleware('can:create,'.Proveedor::class)
                    ->name('store');

                Route::get('/{proveedor}', [ProveedorController::class, 'show'])
                    ->whereNumber('proveedor')
                    ->middleware('can:view,proveedor')
                    ->name('show');

                Route::put('/{proveedor}', [ProveedorController::class, 'update'])
                    ->whereNumber('proveedor')
                    ->middleware('can:update,proveedor')
                    ->name('update');

                // Baja logica y reactivacion en un solo endpoint: son la misma
                // decision —y el mismo permiso— mirada desde los dos lados.
                Route::patch('/{proveedor}/estado', CambiarEstadoProveedorController::class)
                    ->whereNumber('proveedor')
                    ->middleware('can:cambiarEstado,proveedor')
                    ->name('estado');
            });

        // ============================================================
        // CLIENTES
        // ============================================================
        //
        // <b>No hay DELETE, y la ausencia es la regla.</b> El cliente esta
        // referenciado por ventas y por comprobantes emitidos, que son historia
        // fiscal y no pueden quedar sin sujeto: la factura dice a quien se le
        // vendio. ClientePolicy::delete() devuelve false siempre —nunca hizo
        // otra cosa—, la ruta web tampoco existia y el guard del modelo
        // (ProtegeContraBorrado) rechazaria el delete() igual. Es el mismo
        // criterio que productos y proveedores.
        //
        // La unica baja es PATCH .../estado con activo=false.
        //
        // <b>Lo propio de este modulo: el cliente son DOS filas.</b> `users`
        // —con que correo entra a la tienda— y `clientes` —a quien se le
        // factura—. El alta las escribe juntas en una transaccion y manda la
        // invitacion para que el cliente elija su contraseña; la edicion, si le
        // cambian el correo, desverifica la cuenta. Las dos reglas son las que
        // ya tenia el panel y viven en el controlador, no en la ruta.
        //
        // Cuatro permisos y cuatro decisiones distintas:
        //
        //   GET    /clientes                     listar y detalle   cliente.ver
        //   POST   /clientes                     alta               cliente.crear
        //   PUT    /clientes/{cliente}           datos              cliente.editar
        //   PATCH  /clientes/{cliente}/estado    ¿opera?            cliente.baja
        //
        // <b>'cliente.baja' es el unico que no tienen los cuatro roles.</b>
        // Segun la matriz de PermisoSeeder, el vendedor y el cajero tienen ver,
        // crear y editar: el mostrador carga y corrige clientes, pero sacar a
        // uno de la operacion es del administrador y del administrativo. Es la
        // diferencia con proveedores, donde los dos roles que entran tienen los
        // cuatro permisos, y la razon por la que aca los botones por permiso no
        // son decorativos.
        //
        // <b>El reenvio de la invitacion va con 'cliente.editar'</b>, el mismo
        // permiso que exigia el panel: quien puede corregirle el correo es quien
        // tiene que poder reenviarle el enlace a la casilla corregida. Es POST
        // sobre /invitacion —un sub-recurso sustantivado, no un verbo— porque lo
        // que se crea es un envio y la ficha del cliente no cambia: el mismo
        // criterio de /aprobacion y /envio en compras.
        //
        // <b>El historial de compras va bajo 'cliente.ver' y no 'venta.ver'</b>,
        // que es el permiso del modulo de ventas. No es un descuido: es el
        // permiso con el que el panel ya mostraba esa tabla dentro de la ficha,
        // y pedir el otro le sacaria al vendedor y al cajero una vista que hoy
        // tienen. El detalle de cada venta sigue exigiendo lo suyo.
        //
        // <b>Y las direcciones son varias</b>, a diferencia del domicilio unico
        // del proveedor: fiscal, de envio y particular, con una predeterminada
        // por tipo. Por eso tienen sub-recurso propio en lugar de viajar
        // anidadas en el cuerpo del alta, y por eso son el unico DELETE de este
        // bloque —la direccion no es un documento: los envios copian el
        // domicilio en lugar de apuntar a ella, justamente para que un despacho
        // ya hecho no cambie de destino si alguien edita el perfil—.

        Route::prefix('clientes')
            ->name('clientes.')
            ->group(function () {

                Route::get('/', [ClienteController::class, 'index'])
                    ->middleware('can:viewAny,'.Cliente::class)
                    ->name('index');

                Route::post('/', [ClienteController::class, 'store'])
                    ->middleware('can:create,'.Cliente::class)
                    ->name('store');

                Route::get('/{cliente}', [ClienteController::class, 'show'])
                    ->whereNumber('cliente')
                    ->middleware('can:view,cliente')
                    ->name('show');

                Route::put('/{cliente}', [ClienteController::class, 'update'])
                    ->whereNumber('cliente')
                    ->middleware('can:update,cliente')
                    ->name('update');

                // Baja logica y reactivacion en un solo endpoint: son la misma
                // decision —y el mismo permiso— mirada desde los dos lados.
                Route::patch('/{cliente}/estado', CambiarEstadoClienteController::class)
                    ->whereNumber('cliente')
                    ->middleware('can:baja,cliente')
                    ->name('estado');

                // El metodo de la policy se llama baja() y no cambiarEstado()
                // como en categorias, productos y proveedores. Es el nombre que
                // tiene desde que se escribio y el que usan las dos pantallas:
                // renombrarlo seria tocar la policy, la vista y el controlador
                // web para que diga lo mismo. Devuelve exactamente
                // tienePermiso('cliente.baja').

                Route::post('/{cliente}/invitacion', ReenviarInvitacionClienteController::class)
                    ->whereNumber('cliente')
                    ->middleware('can:update,cliente')
                    ->name('invitacion');

                Route::get('/{cliente}/ventas', [ClienteController::class, 'ventas'])
                    ->whereNumber('cliente')
                    ->middleware('can:view,cliente')
                    ->name('ventas');

                // ----------------------------------------------------
                // Direcciones de un cliente
                // ----------------------------------------------------
                //
                // Cuelgan del cliente, asi que el dueño sale de la URL y nunca
                // del cuerpo: si el tipo polimorfico llegara del formulario,
                // cualquiera podria colgarle una direccion fiscal a otro.
                //
                // Las tres van con 'cliente.editar': la autorizacion se delega
                // en el dueño, igual que en el panel, y es lo que dice la
                // descripcion del permiso en PermisoSeeder —"Editar clientes y
                // sus direcciones"—.

                Route::prefix('{cliente}/direcciones')
                    ->name('direcciones.')
                    ->whereNumber('cliente')
                    ->middleware('can:update,cliente')
                    ->group(function () {

                        Route::post('/', [ClienteDireccionController::class, 'store'])
                            ->name('store');

                        Route::put('/{direccion}', [ClienteDireccionController::class, 'update'])
                            ->whereNumber('direccion')
                            ->name('update');

                        Route::delete('/{direccion}', [ClienteDireccionController::class, 'destroy'])
                            ->whereNumber('direccion')
                            ->name('destroy');
                    });
            });

        // ============================================================
        // COMPRAS (PEDIDOS A PROVEEDORES) — E.1 + E.2
        // ============================================================
        //
        // <b>El prefijo es 'compras' para que coincida con el del panel</b>
        // (/gestion/compras) y con el modulo de los permisos. No hay que
        // confundirlo con el 'compras' de la tienda en routes/api.php, que es
        // el historial de COMPRAS DEL CLIENTE —ventas—: son dos documentos
        // distintos que en castellano se llaman igual, viven en archivos de
        // rutas distintos y no comparten ni modelo ni Resource.
        //
        // Un permiso por operacion, y cada uno es una decision distinta:
        //
        //   GET  /compras                       listar      compra.ver
        //   GET  /compras/{orden}               detalle     compra.ver
        //   POST /compras                       borrador    compra.crear
        //   POST /compras/{orden}/aprobacion    aprobar     compra.aprobar
        //   POST /compras/{orden}/envio         enviar      compra.enviar
        //   POST /compras/{orden}/cancelacion   cancelar    compra.cancelar
        //   POST /compras/{orden}/recepciones   recibir     compra.recibir
        //   POST /compras/{orden}/cierre        cerrar      compra.recibir
        //
        // <b>Las acciones son sub-recursos sustantivados y no verbos en la
        // URL</b> —/aprobacion y no /aprobar—: es el criterio que fijo el plan
        // de la etapa para todo el circuito comercial, y el que deja que
        // /recepciones sea lo que realmente es, una coleccion a la que cada
        // entrega agrega un elemento.
        //
        // <b>POST y no PATCH /estado</b>, a diferencia de categorias, productos
        // y proveedores. Alla el estado es un campo que el cuerpo fija; aca
        // cada transicion tiene reglas, efectos y permisos propios —aprobar
        // reparte por proveedor y anula borradores, enviar manda un correo,
        // recibir mueve inventario—. Un solo PATCH las esconderia detras de un
        // campo y haria imposible darles permisos separados.
        //
        // <b>'compra.recibir' cubre las dos ultimas, y no es un descuido.</b>
        // Recibir mercaderia y dar por cerrado lo que no vino son la misma
        // tarea mirada desde los dos lados, la decide el deposito y ninguna de
        // las dos mueve dinero ni autoriza nada. Es lo que ya dice la
        // descripcion del permiso en PermisoSeeder y lo que ya hacia el panel.
        //
        // <b>La recepcion es el UNICO endpoint de la API que toca el
        // inventario.</b> La compra afecta el stock en un solo punto del
        // circuito —RecepcionService con StockService— y es este. Aprobar,
        // enviar, cancelar y cerrar mueven el documento y nada mas.
        //
        // <b>Sin PUT todavia.</b> La edicion del detalle existe en el panel y
        // tiene su permiso ('compra.editar'), pero no entra en esta entrega.
        //
        // <b>Y sin DELETE, nunca.</b> La orden de compra no se elimina: su fin
        // de vida es un estado —'recibida' o 'cancelada'—, igual que el de una
        // venta. OrdenCompraPolicy no tiene metodo delete() y la ruta web
        // tampoco existe.

        Route::prefix('compras')
            ->name('compras.')
            ->group(function () {

                Route::get('/', [OrdenCompraController::class, 'index'])
                    ->middleware('can:viewAny,'.OrdenCompra::class)
                    ->name('index');

                Route::post('/', [OrdenCompraController::class, 'store'])
                    ->middleware('can:create,'.OrdenCompra::class)
                    ->name('store');

                // Sirve cualquier orden, incluida una hija del reparto: el
                // listado muestra solo las raiz, pero una hija es una orden
                // real y su detalle es donde se la mira.
                Route::get('/{orden}', [OrdenCompraController::class, 'show'])
                    ->whereNumber('orden')
                    ->middleware('can:view,orden')
                    ->name('show');

                // ----------------------------------------------------
                // Transiciones del documento — E.2.a
                // ----------------------------------------------------
                //
                // Las tres son controladores de accion unica, que es la forma
                // del proyecto para las transiciones: con un solo __invoke no
                // hay manera de que la operacion quede sin su linea de
                // autorizacion, que es el agujero que F1 cerro en el panel.
                //
                // <b>El `can:` no es solo el permiso.</b> Los tres metodos de
                // OrdenCompraPolicy miran ademas el estado, y aprobar() mira
                // tambien quien creo el pedido: quien lo cargo no lo puede
                // aprobar salvo que tenga 'compra.aprobar_propias', que solo
                // tiene el administrador. Es la unica separacion de funciones
                // del circuito de compras y responde 403, igual que en el
                // panel. El 422 queda para la carrera: dos peticiones
                // simultaneas pasan las dos por la policy y la segunda choca
                // con el estado ya cambiado.

                Route::post('/{orden}/aprobacion', AprobarOrdenCompraController::class)
                    ->whereNumber('orden')
                    ->middleware('can:aprobar,orden')
                    ->name('aprobacion');

                // Sobre un pedido repartido manda el remito de cada proveedor
                // por separado, y un fallo no arrastra a los demas: la orden
                // que no salio queda en 'aprobada' y se reintenta sola.
                Route::post('/{orden}/envio', EnviarOrdenCompraController::class)
                    ->whereNumber('orden')
                    ->middleware('can:enviar,orden')
                    ->name('envio');

                // Lo que en otro CRUD seria la D. Conserva el numero y el
                // historial, y vuelve a habilitar la reposicion automatica de
                // los productos del pedido.
                Route::post('/{orden}/cancelacion', CancelarOrdenCompraController::class)
                    ->whereNumber('orden')
                    ->middleware('can:cancelar,orden')
                    ->name('cancelacion');

                // ----------------------------------------------------
                // Entrada de mercaderia — E.2.b
                // ----------------------------------------------------
                //
                // <b>En plural y con POST porque es una coleccion de verdad:</b>
                // cada peticion agrega una entrega al pedido, y un pedido puede
                // recibir tres remitos distintos. Por eso responde 201 y por eso
                // el historial de lo que ya entro viaja en el detalle, en
                // 'data.recepciones'.
                //
                // <b>No es un PUT sobre la orden</b>, y el panel tomo la misma
                // decision: lo que se crea es un movimiento de stock. La orden
                // cambia de estado como consecuencia del acumulado, no porque
                // alguien le mande un estado.
                //
                // <b>La policy mira mas que el permiso:</b> recibir() exige
                // esRecibible(), que deja afuera el borrador, la aprobada sin
                // enviar, la ya recibida, la cancelada y el pedido repartido
                // —la mercaderia se recibe en la orden de cada proveedor—.

                Route::post('/{orden}/recepciones', RegistrarRecepcionController::class)
                    ->whereNumber('orden')
                    ->middleware('can:recibir,orden')
                    ->name('recepciones');

                // Da por terminado un pedido que quedo a medio recibir. Sin
                // esto, una orden en 'recibida_parcial' bloquea la reposicion
                // de sus productos para siempre: ese estado es ABIERTO y el
                // restock da el faltante por pedido.
                Route::post('/{orden}/cierre', CerrarOrdenCompraController::class)
                    ->whereNumber('orden')
                    ->middleware('can:cerrar,orden')
                    ->name('cierre');
            });

        // ============================================================
        // STOCK (KARDEX GLOBAL)
        // ============================================================
        //
        // El historico de todos los productos, que es lo que se mira para
        // auditar: quien movio que, cuando y por que.
        //
        // <b>Solo lectura.</b> La unica escritura de inventario de toda la API
        // es el ajuste, y cuelga del producto. No hay un POST /stock/*: un
        // movimiento suelto, sin producto en la URL, es la clase de endpoint
        // que invita a escribir saldos a mano por fuera de los servicios.
        //
        // Se protege con el middleware 'permiso:' y no con 'can:' porque no
        // hay modelo sobre el cual pedir una policy. Es la misma clave que
        // exige el panel.

        Route::prefix('stock')
            ->name('stock.')
            ->group(function () {

                Route::get('/movimientos', [StockController::class, 'movimientos'])
                    ->middleware('permiso:stock.ver')
                    ->name('movimientos');
            });
    });
