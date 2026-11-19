<?php

namespace App\Http\Resources;

use App\Enums\AmbitoRol;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * El usuario tal como lo ve la app: la credencial mas los datos fiscales de
 * su cliente.
 *
 * <b>Van juntos y no en dos endpoints</b> porque la pantalla de perfil es una
 * sola, y sobre todo porque CheckoutService::problemaDeFacturacion() depende
 * de condicion_iva y tipo_documento: sin esos dos campos la app no puede
 * explicarle al cliente por que no lo deja comprar, ni llevarlo al formulario
 * correcto.
 *
 * <b>No expone rol ni permisos.</b> La app de la tienda no tiene nada que
 * decidir con eso —no hay pantallas que mostrar u ocultar segun el rol— y el
 * ambito ya lo garantiza el middleware. Mandarlos seria filtrar la estructura
 * interna de autorizacion a un cliente que no la necesita.
 *
 * `cliente` puede venir null: la tabla admite un user sin cliente asociado.
 * No deberia pasar en una cuenta nacida del registro, pero la app tiene que
 * saber distinguirlo de un cliente con datos incompletos.
 *
 * @mixin \App\Models\User
 */
class UsuarioResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $cliente = $this->cliente;

        return [
            'id'              => $this->id,
            'nombres'         => $this->nombres,
            'apellido'        => $this->apellido,
            'nombre_completo' => $this->nombre_completo,
            'email'           => $this->email,

            // La app bloquea carrito y checkout cuando esto es false. Es la
            // misma regla que el middleware 'verified' aplica en la web, pero
            // la app necesita el dato ANTES de recibir el 403 para poder
            // mostrar la pantalla de "verifica tu correo" en lugar de un
            // error.
            'email_verificado' => $this->email_verified_at !== null,

            // El rol viaja para los dos ambitos: el requisito 6 pide que el
            // endpoint compartido devuelva "el usuario autenticado y sus
            // permisos", y el ambito es lo que le dice al cliente a que
            // aplicacion pertenece la cuenta.
            'rol' => $this->rol ? [
                'id'     => $this->rol->id,
                'nombre' => $this->rol->nombre,
                'ambito' => $this->rol->ambito?->value,
            ] : null,

            // Los permisos solo en gestion: un cliente no tiene ninguno y
            // mandarle una lista vacia solo agregaria ruido.
            //
            // <b>Son una ayuda para pintar la interfaz, no una autorizacion.</b>
            // El requisito 7 es explicito: los cambios de permisos deben
            // reflejarse en las solicitudes posteriores aunque el token siga
            // valido, y no se debe confiar exclusivamente en los permisos
            // almacenados en el cliente. Quien autoriza sigue siendo el
            // servidor, por request, con el middleware 'permiso' y las
            // policies. Esta lista se lee de la base en cada llamada, sin
            // cache, asi que un permiso quitado desaparece de la respuesta
            // siguiente.
            'permisos' => $this->when(
                $this->ambitoRol() === AmbitoRol::Gestion,
                fn () => $this->rol?->permisos->pluck('clave')->values()->all() ?? [],
            ),

            'cliente' => $cliente ? [
                'id'           => $cliente->id,
                'razon_social' => $cliente->razon_social,
                'telefono'     => $cliente->telefono,
                'nro_doc'      => $cliente->nro_doc,

                'tipo_documento' => $cliente->tipoDocumento ? [
                    'id'     => $cliente->tipoDocumento->id,
                    'nombre' => $cliente->tipoDocumento->nombre,
                ] : null,

                'condicion_iva' => $cliente->condicionIva ? [
                    'id'     => $cliente->condicionIva->id,
                    'nombre' => $cliente->condicionIva->nombre,
                ] : null,
            ] : null,
        ];
    }
}