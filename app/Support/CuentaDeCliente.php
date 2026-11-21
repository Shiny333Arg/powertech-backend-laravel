<?php

namespace App\Support;

use App\Enums\AmbitoRol;
use App\Models\Rol;
use App\Models\User;
use App\Notifications\InvitacionCliente;
use RuntimeException;
use Throwable;

/**
 * Lo que un cliente necesita de `users`: con qué rol nace su cuenta y cómo se
 * le avisa que la tiene.
 *
 * <b>Vivía dentro de ClienteController como dos métodos privados.</b> Se movió
 * acá al migrar el módulo a la API, por el mismo motivo que
 * App\Support\DomicilioFiscal: ahora hay DOS lugares que invitan —el alta y la
 * edición en Api\V1\Gestion\ClienteController, y el reenvío en
 * Acciones\ReenviarInvitacionClienteController—, y dos copias de la misma
 * regla divergen. No es una regla nueva: es la de siempre, en un solo lugar.
 *
 * La contraseña no pasa por acá ni por ninguna pantalla del panel. El cliente
 * nace sin ella y la elige él mismo desde el enlace firmado que recibe por
 * correo; aceptarlo también le verifica la casilla.
 */
class CuentaDeCliente
{
    /**
     * El único rol de ámbito tienda.
     *
     * No se elige en el formulario: un cliente cargado por un empleado tiene
     * exactamente los mismos permisos que uno que se registró solo.
     */
    public static function rol(): int
    {
        return Rol::where('nombre', 'cliente')
            ->where('ambito', AmbitoRol::Tienda->value)
            ->value('id') ?? throw new RuntimeException(
                'No existe el rol "cliente". Corré RolSeeder.'
            );
    }

    /**
     * Manda el enlace para elegir la contraseña. Devuelve si salió.
     *
     * <b>Un fallo del servidor de correo no tiene que tirar abajo el alta.</b>
     * Se registra en el log y quien llama avisa para reintentar: que no se le
     * pueda mandar el correo no es razón para perder el cliente, sobre todo si
     * hay una venta esperando para cargarse a su nombre. Es el mismo criterio
     * que tenía el controlador web.
     */
    public static function invitar(User $usuario): bool
    {
        try {
            $usuario->notify(new InvitacionCliente());

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }
}
