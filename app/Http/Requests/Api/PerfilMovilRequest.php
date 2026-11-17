<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Edicion del perfil desde la app.
 *
 * <b>Hereda toda la validacion de ProfileUpdateRequest y no reescribe
 * ninguna.</b> Las reglas de nombre y correo, las fiscales, la coherencia
 * entre condicion de IVA y tipo de documento, el documento que queda fijo
 * cuando hay compras, los mensajes y los dos metodos que separan las columnas
 * de `users` de las de `clientes`: todo eso ya estaba escrito para la web y
 * vale igual aca. Es lo que el plan de la API anoto como reutilizable sin
 * modificar.
 *
 * Lo UNICO que cambia es la respuesta cuando la autorizacion falla, y por eso
 * esta clase existe en lugar de usar ProfileUpdateRequest directo.
 *
 * <b>Que hace failedAuthorization y por que.</b> `authorize()` del padre
 * devuelve false cuando el usuario no tiene ficha en `clientes`. La web
 * resuelve ese caso con un 404 en `edit()` y no llega nunca al update. En la
 * API, sin esto, Laravel contesta 403 con "This action is unauthorized.": en
 * ingles y sin nada que la app pueda mirar aparte del texto. Flutter ya
 * distingue dos 403 por el campo `codigo` —ver VerificarEmailVerificadoApi y
 * errores.dart— y los trata distinto: uno significa "esta cuenta no es de la
 * tienda" y corresponde cerrar sesion. Una cuenta sin ficha de cliente NO es
 * ese caso: la credencial es buena, falta que un empleado la vincule. Sin el
 * `codigo` propio, la app le diria a ese cliente que su cuenta no sirve.
 *
 * En condiciones normales la app no llega hasta aca: `GET /perfil` devuelve
 * `usuario.cliente` en null y la pantalla muestra el aviso en lugar del
 * formulario. Esto cubre el caso en que el estado local quedo viejo.
 */
class PerfilMovilRequest extends ProfileUpdateRequest
{
    /**
     * El usuario no tiene ficha en `clientes`.
     *
     * Se lanza una respuesta ya armada en lugar de una AuthorizationException
     * porque el render de excepciones de bootstrap/app.php deja pasar las de
     * autorizacion —y hace bien: 403 ya es el codigo correcto—. Lo que hay
     * que cambiar es el cuerpo, no el estado.
     */
    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Tu cuenta todavía no tiene una ficha de cliente, así que no podemos '
                .'guardar tus datos de facturación. Acercate al local para que te la vinculen.',
            'codigo' => 'sin_ficha_de_cliente',
        ], 403));
    }
}
