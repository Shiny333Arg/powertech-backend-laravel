<?php

namespace App\Http\Controllers\Api\V1\Gestion\Acciones;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Notifications\InvitacionCliente;
use App\Support\CuentaDeCliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * POST /api/v1/gestion/clientes/{cliente}/invitacion
 *
 * Reenvía el enlace para que el cliente elija su contraseña.
 *
 * <b>POST y no PATCH, y el recurso es la invitación.</b> Lo que se crea es un
 * envío: cada llamada manda un correo nuevo con un enlace firmado nuevo, y la
 * ficha del cliente no cambia. Es el mismo criterio que usan las acciones del
 * circuito de compras —/aprobacion, /envio, /recepciones—: sub-recursos
 * sustantivados, no verbos en la URL. La ruta web era
 * POST .../reenviar-invitacion y ya decía en su comentario que es una acción y
 * no una modificación del recurso.
 *
 * <b>Va con 'cliente.editar'</b>, el mismo permiso que exigía el panel: quien
 * puede corregirle el correo es quien tiene que poder reenviarle el enlace a la
 * casilla corregida.
 *
 * <b>Sólo sirve mientras la cuenta no tenga contraseña.</b> Una vez elegida, el
 * camino es "¿Olvidaste tu contraseña?" como cualquier usuario, y reenviar una
 * invitación no haría nada útil. Un cliente sin cuenta —el de mostrador viejo—
 * tampoco tiene a dónde mandarla. Los dos casos son 422 y no 403: el permiso
 * está, lo que no corresponde es la operación sobre el estado en que está la
 * cuenta.
 *
 * <b>El 422 no distingue los dos casos en el mensaje</b>, y es a propósito: es
 * la frase que ya mostraba el panel.
 */
class ReenviarInvitacionClienteController extends Controller
{
    public function __invoke(Cliente $cliente): JsonResponse
    {
        $usuario = $cliente->user;

        if ($usuario === null || ! $usuario->invitacionPendiente()) {
            return response()->json([
                'message' => 'Este cliente ya activó su cuenta o no tiene una.',
                'errors'  => [
                    'estado' => ['Este cliente ya activó su cuenta o no tiene una.'],
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Un fallo del servidor de correo no es un error del cliente que llama:
        // la operación estaba bien formada y el sistema no pudo cumplirla, así
        // que 502 y no 422. La pantalla muestra "probá de nuevo en un rato",
        // igual que el panel.
        if (! CuentaDeCliente::invitar($usuario)) {
            return response()->json([
                'message' => 'No se pudo enviar el correo. Probá de nuevo en un rato.',
                'codigo'  => 'CORREO_NO_ENVIADO',
            ], Response::HTTP_BAD_GATEWAY);
        }

        return response()->json([
            'data' => [
                'email'             => $usuario->email,
                'horas_de_vigencia' => InvitacionCliente::HORAS_DE_VIGENCIA,
            ],
        ], Response::HTTP_ACCEPTED);
    }
}
