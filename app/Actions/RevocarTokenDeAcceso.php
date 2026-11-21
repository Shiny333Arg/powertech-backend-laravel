<?php

namespace App\Actions;

use App\Models\User;

/**
 * Revoca UN token de acceso del usuario indicado.
 *
 * <b>Es el unico lugar del sistema donde se borra UN token.</b> La invocan el
 * endpoint compartido de cierre de sesion (Api\V1\AuthController::logout, el
 * mismo para Flutter y para el panel), el intercambio de token por sesion
 * cuando reemplaza al token anterior (gestion\SesionController) y el
 * middleware de coherencia cuando encuentra una sesion con un token que ya no
 * sirve (VerificarTokenDeSesionGestion).
 *
 * Para borrarlos TODOS —solo en la baja de una cuenta— esta su hermana
 * RevocarTodosLosTokens. El borrado de tokens vive entero en app/Actions.
 *
 * Tenerla en una clase y no repetida en cada lugar es lo que pide el requisito
 * 3 de la consigna: la logica se reutiliza DENTRO del backend, sin duplicar
 * reglas y sin llamadas HTTP internas a la propia API.
 *
 * <b>Dos propiedades que no son adornos:</b>
 *
 *   Esta acotada a los tokens del usuario. Arranca de $user->tokens(), asi
 *   que un id que apunte al token de un tercero no borra nada. Importa porque
 *   en el modo cookie el id sale de la sesion, y una sesion manipulada no
 *   tiene que poder revocarle la credencial a otro empleado.
 *
 *   Es idempotente. Si el token ya no esta —porque la llamada anterior lo
 *   borro, porque lo revoco otro dispositivo o porque lo levanto el prune—
 *   borra cero filas y no lanza nada. El cierre de sesion tiene que poder
 *   repetirse sin error.
 *
 * No es final a proposito: el test que comprueba que una revocacion fallida NO
 * destruye la sesion necesita sustituirla por una que lance.
 */
class RevocarTokenDeAcceso
{
    /**
     * @return bool si habia una fila para borrar. false significa "ya no
     *              estaba", que es exito idempotente y no un fallo: un fallo
     *              real de la base sale como excepcion y corta el flujo.
     */
    public function __invoke(User $user, int|string $tokenId): bool
    {
        return $user->tokens()->whereKey($tokenId)->delete() > 0;
    }
}
