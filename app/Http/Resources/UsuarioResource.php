<?php

namespace App\Http\Resources;

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