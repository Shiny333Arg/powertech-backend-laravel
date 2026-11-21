<?php

namespace App\Actions;

use App\Models\User;

/**
 * Revoca TODOS los tokens de acceso del usuario indicado.
 *
 * <b>Hermana de RevocarTokenDeAcceso, no su reemplazo.</b> Aquella borra el
 * token con el que llegó un request —cerrar sesión en el teléfono no tiene por
 * qué cerrarla en la tablet—; esta borra todos, que es lo que corresponde
 * cuando la cuenta deja de existir para el sistema. Las dos viven acá y no
 * sueltas en un controlador para que el borrado de tokens siga concentrado en
 * app/Actions, que es lo que pide el requisito 3 de la consigna.
 *
 * <b>Un solo invocador: la baja de un cliente</b>
 * (Api\V1\Gestion\Acciones\CambiarEstadoClienteController). Es el caso que el
 * comentario de Api\V1\AuthController::logout ya anticipaba: "para una baja de
 * cuenta —donde sí hay que revocar todo— el lugar es la pantalla de gestión".
 *
 * <b>Por qué revocar y no confiar en el middleware.</b> `api.activo`
 * (VerificarCuentaActivaApi) ya corta a una cuenta dada de baja y revoca el
 * token con el que la encontró, así que los tokens quedan inservibles de todas
 * formas. Borrarlos igual cierra la ventana en que la fila todavía existe: un
 * token robado deja de poder presentarse en lugar de ser rechazado, y la tabla
 * no acumula credenciales de cuentas que ya no entran. El corte no depende de
 * esta acción —depende del middleware—, pero el borrado lo hace inmediato.
 *
 * Está acotada a los tokens del propio usuario, como su hermana: arranca de
 * $user->tokens(), así que no puede alcanzar la credencial de un tercero.
 *
 * Es idempotente: si no había tokens borra cero filas y no lanza nada. Dar de
 * baja a un cliente que nunca usó la app tiene que funcionar igual.
 *
 * No es final a propósito, por el mismo motivo que su hermana: un test que
 * compruebe qué pasa si la revocación falla necesita sustituirla por una que
 * lance.
 */
class RevocarTodosLosTokens
{
    /**
     * @return int cuántos tokens se borraron. Cero es éxito, no fallo: un
     *             fallo real de la base sale como excepción y corta la
     *             transacción que envuelve la baja.
     */
    public function __invoke(User $user): int
    {
        return $user->tokens()->delete();
    }
}
