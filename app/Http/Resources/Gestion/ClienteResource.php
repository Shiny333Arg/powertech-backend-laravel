<?php

namespace App\Http\Resources\Gestion;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Cliente visto desde el panel.
 *
 * Los campos se enumeran uno por uno, nunca toArray() del modelo: es la misma
 * regla que CategoriaResource, ProductoResource y ProveedorResource, y lo que
 * impide que una columna agregada mañana se publique sin que nadie lo decida.
 *
 * <b>Hermano de PerfilResource, no el mismo.</b> El cliente que mira su perfil
 * desde la tienda ve sus datos y nada más; el empleado ve además el estado de
 * la cuenta —si la invitación sigue pendiente, si el correo está verificado, si
 * el acceso está bloqueado—, que es lo que decide qué puede hacer el mostrador
 * y qué no. Son dos públicos distintos y por eso dos resources distintos.
 *
 * <b>Un cliente son DOS filas:</b> `users` (con qué correo entra a la tienda) y
 * `clientes` (a quién se le factura). De ahí el bloque 'cuenta', que puede ser
 * null: el cliente de mostrador viejo existe sin tener cuenta.
 *
 * @mixin Cliente
 */
class ClienteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'razon_social' => $this->razon_social,

            // Los dígitos tal como están en la columna. ReglasDatosCliente los
            // normaliza al guardar —"20-12345678-3" y "20123456783" son el
            // mismo documento— justamente para que el índice único sirva.
            'nro_doc' => $this->nro_doc,

            'telefono' => $this->telefono,

            // Juntos deciden si corresponde factura A o B, así que viajan los
            // dos con id y nombre: el id es el que el formulario manda de
            // vuelta y el nombre es lo que muestra la grilla.
            'tipo_documento' => $this->tipoDocumento ? [
                'id'          => $this->tipoDocumento->id,
                'nombre'      => $this->tipoDocumento->nombre,
                'codigo_afip' => (int) $this->tipoDocumento->codigo_afip,
            ] : null,

            'condicion_iva' => $this->condicionIva ? [
                'id'          => $this->condicionIva->id,
                'nombre'      => $this->condicionIva->nombre,
                'codigo_afip' => (int) $this->condicionIva->codigo_afip,
            ] : null,

            // Baja lógica del CLIENTE: lo saca de la operación de mostrador.
            // No es el 'activo' de su user, que es el acceso a la tienda: son
            // dos decisiones distintas y por eso dos columnas.
            'activo' => (bool) $this->activo,

            // ----------------------------------------------------------
            // La cuenta en la tienda
            // ----------------------------------------------------------
            //
            // Viaja sólo cuando la consulta cargó la relación —el listado no la
            // necesita—, y es null cuando el cliente no tiene cuenta. La clave
            // que falta y la clave en null no son lo mismo.
            //
            // 'invitacion_pendiente' sale de User::invitacionPendiente(), que
            // lee el alias calculado por la base cuando el controlador no trajo
            // la columna password: traer hashes a una pantalla no tiene sentido,
            // y sin el alias el modelo los daría por nulos —o sea pendientes—
            // para todos.
            'cuenta' => $this->whenLoaded('user', fn () => $this->user ? [
                'id'                   => $this->user->id,
                'email'                => $this->user->email,
                'nombres'              => $this->user->nombres,
                'apellido'             => $this->user->apellido,
                'activo'               => (bool) $this->user->activo,
                'email_verificado'     => $this->user->email_verified_at !== null,
                'invitacion_pendiente' => $this->user->invitacionPendiente(),
            ] : null),

            // ----------------------------------------------------------
            // Direcciones
            // ----------------------------------------------------------
            //
            // <b>Son varias y de tipos distintos</b> —fiscal, envío,
            // particular—, a diferencia del domicilio único del proveedor: por
            // eso viajan como colección y tienen su propio sub-recurso para
            // agregarlas y borrarlas, en lugar de ir anidadas en el cuerpo del
            // alta. Viajan en el detalle y no en el listado: la grilla no las
            // muestra y serían 25 filas más por página de una tabla polimórfica.
            //
            // whenLoaded() con cierre y no DireccionResource::collection() a
            // secas: ResourceCollection no reconoce el MissingValue, así que sin
            // el cierre una respuesta sin la relación cargada emitiría un
            // elemento basura en lugar de omitir la clave.
            'direcciones' => $this->whenLoaded(
                'direcciones',
                fn () => DireccionResource::collection($this->direcciones)
            ),
        ];
    }
}
