<?php

use App\Http\Controllers\Api\V1\Gestion\Acciones\CambiarEstadoCategoriaController;
use App\Http\Controllers\Api\V1\Gestion\CategoriaController;
use App\Models\Categoria;
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
    });
