<?php

use App\Http\Controllers\Api\V1\Gestion\Acciones\AjustarStockController;
use App\Http\Controllers\Api\V1\Gestion\Acciones\CambiarEstadoCategoriaController;
use App\Http\Controllers\Api\V1\Gestion\Acciones\CambiarEstadoProductoController;
use App\Http\Controllers\Api\V1\Gestion\Acciones\CambiarEstadoProveedorController;
use App\Http\Controllers\Api\V1\Gestion\Acciones\CambiarPublicacionProductoController;
use App\Http\Controllers\Api\V1\Gestion\CategoriaController;
use App\Http\Controllers\Api\V1\Gestion\OrdenCompraController;
use App\Http\Controllers\Api\V1\Gestion\ProductoController;
use App\Http\Controllers\Api\V1\Gestion\ProveedorController;
use App\Http\Controllers\Api\V1\Gestion\StockController;
use App\Models\Categoria;
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
        // COMPRAS (PEDIDOS A PROVEEDORES) — E.1
        // ============================================================
        //
        // <b>Tres endpoints y nada mas: consultar, ver el detalle y crear un
        // borrador.</b> Aprobar, enviar, recibir y cancelar son E.2. La
        // ausencia es deliberada: la policy ya tiene esos metodos y los
        // permisos ya existen en PermisoSeeder, asi que lo unico que falta es
        // la ruta. Por eso un POST a /{orden}/aprobar responde 405 y no 403.
        //
        // <b>Crear un borrador no toca el inventario.</b> La compra afecta el
        // stock en un solo punto del circuito, que es la recepcion de
        // mercaderia —RecepcionService con StockService—, y en E.1 ese punto no
        // tiene endpoint. Un borrador es una intencion de compra.
        //
        // <b>El prefijo es 'compras' para que coincida con el del panel</b>
        // (/gestion/compras) y con el modulo de los permisos. No hay que
        // confundirlo con el 'compras' de la tienda en routes/api.php, que es
        // el historial de COMPRAS DEL CLIENTE —ventas—: son dos documentos
        // distintos que en castellano se llaman igual, viven en archivos de
        // rutas distintos y no comparten ni modelo ni Resource.
        //
        // Dos permisos, uno por operacion:
        //
        //   GET  /compras           listar          compra.ver
        //   GET  /compras/{orden}   detalle         compra.ver
        //   POST /compras           crear borrador  compra.crear
        //
        // <b>Sin PUT en E.1.</b> La edicion del detalle existe en el panel y
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
