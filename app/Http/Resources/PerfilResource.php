<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * El perfil del cliente como lo necesita el FORMULARIO de la app.
 *
 * <b>Reusa UsuarioResource entero en vez de aplanar los campos.</b> Es el
 * mismo contrato que devuelve `/auth/yo`, asi que la app parsea el modelo
 * `Usuario` que ya tiene y no un segundo modelo con los mismos datos y otra
 * forma. Dos formas del usuario terminarian mostrando cosas distintas en "Mi
 * cuenta" y en el formulario de edicion, que es justo donde se nota.
 *
 * <b>Lo que este endpoint agrega, y la razon de que exista.</b>
 * `documento_bloqueado`, que no esta en `/auth/yo` y que el formulario
 * necesita para deshabilitar el tipo y el numero de documento. Es una
 * consulta a `ventas` y por eso no se metio en UsuarioResource: `/auth/yo`
 * corre en cada arranque de la app y en cada refresco de sesion, y no tiene
 * por que preguntar si el cliente tiene compras.
 *
 * <b>Las listas desplegables no viajan aca.</b> Tipos de documento y
 * condiciones de IVA ya los sirve `GET /datos-fiscales`, que es publico
 * —el registro los necesita antes de que exista la cuenta— y que la app ya
 * consume. Repetirlos seria un segundo lugar de donde sacarlos.
 *
 * El mismo cuerpo lo devuelven el GET y el PATCH: despues de guardar, la app
 * recibe el estado nuevo completo —incluido `email_verificado`, que se cae a
 * false si cambio el correo— sin una llamada de seguimiento.
 */
class PerfilResource extends JsonResource
{
    /**
     * Texto del aviso cuando el documento no se puede cambiar.
     *
     * <b>Lo escribe el backend y no Flutter</b> porque la condicion la decide
     * el backend. Es el mismo aviso que muestra
     * `cliente/perfil/edit.blade.php` sobre el formulario web, y es distinto
     * del mensaje de validacion de ProfileUpdateRequest: aquel aparece cuando
     * alguien intenta el cambio igual, este explica de entrada por que los
     * campos estan deshabilitados. La web tambien tiene los dos textos.
     */
    public const AVISO_DOCUMENTO_BLOQUEADO = 'Tu documento no se puede cambiar desde acá porque ya '
        .'tenés compras registradas con él. Si hay un error, comunicate con PowerTech.';

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array{usuario: \App\Models\User, documento_bloqueado: bool} $datos */
        $datos = $this->resource;

        $bloqueado = $datos['documento_bloqueado'];

        return [
            'usuario' => new UsuarioResource($datos['usuario']),

            // Los dos campos del documento se deshabilitan con esto. La app no
            // lo deduce de "tiene compras": no tiene por que conocer la regla,
            // y ademas el historial puede estar vacio en pantalla y la cuenta
            // tener una compra de mostrador.
            'documento_bloqueado' => $bloqueado,

            // null cuando no hay nada que avisar, para que la app pregunte por
            // el texto y no por la bandera.
            'motivo_documento_bloqueado' => $bloqueado ? self::AVISO_DOCUMENTO_BLOQUEADO : null,

            // <b>Es un booleano, no la contraseña ni su hash.</b> Lo necesita
            // el formulario para decidir si pedir la contraseña actual:
            // `users.password` es nullable y quien se registro con Google
            // todavia no eligio una, asi que la primera que elija la crea. Es
            // la misma pregunta que hace cliente/perfil/edit.blade.php con
            // `$user->password !== null`, y la misma condicion que evalua
            // PasswordMovilRequest.
            //
            // Hoy un usuario sin contraseña no puede entrar a la app —el
            // login movil es correo mas contraseña— asi que esto siempre
            // llega en true. Viaja igual: el dia que la app acepte Google, el
            // formulario ya hace lo correcto.
            'tiene_password' => $datos['usuario']->password !== null,
        ];
    }
}
