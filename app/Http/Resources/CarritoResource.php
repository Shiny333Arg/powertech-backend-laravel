<?php

namespace App\Http\Resources;

use App\Models\Carrito;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * El carrito completo, valuado a precios de este momento.
 *
 * <b>Es la UNICA forma de respuesta del modulo.</b> Consultar, agregar,
 * cambiar cantidad, quitar y vaciar devuelven los cinco esto mismo. La app
 * mapea un solo modelo Dart y despues de cada operacion ya tiene los totales
 * recalculados, sin un GET de seguimiento y sin sumar nada por su cuenta.
 * Devolver "la linea afectada" obligaria a Flutter a recalcular el total, que
 * es exactamente la logica de negocio que no tiene que estar alla.
 *
 * <b>Envuelve el arreglo de CarritoService::resumen(), no el modelo
 * Carrito.</b> Los importes no existen en `carritos` ni en `carrito_lineas`:
 * se calculan en cada lectura contra el precio vigente y el MotorPromociones.
 *
 * <b>Lo que NO sale de aca:</b> `user_id`, `session_token`,
 * `mp_preference_id`, y los datos del cliente. El carrito es siempre el de
 * quien pregunta —se resuelve por token, no por id en la URL— asi que
 * devolver a quien pertenece es informacion que la app ya tiene y que no
 * conviene que viaje. `session_token` y `mp_preference_id` son estado interno
 * del checkout.
 *
 * <b>`total` es lo que el checkout va a aceptar cobrar</b>, no la suma de lo
 * que hay en pantalla: las lineas no disponibles quedan afuera. Ver el
 * docblock de CarritoService::resumen().
 */
class CarritoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $datos */
        $datos = $this->resource;

        /** @var Carrito $carrito */
        $carrito = $datos['carrito'];

        return [
            'id'     => $carrito->id,
            'estado' => $carrito->estado->value,

            // Unidades, no lineas: es el numero del globito del carrito.
            // Cuenta tambien las no disponibles, que el cliente metio y
            // sigue viendo.
            'cantidad_items'  => $datos['cantidad_items'],
            'cantidad_lineas' => $datos['lineas']->count(),
            'vacio'           => $datos['lineas']->isEmpty(),

            // Los tres importes van juntos para que la app pueda mostrar
            // "antes / ahorras / total" sin restar nada.
            'subtotal_lista'  => $datos['subtotal_lista'],
            'descuento_total' => $datos['descuento_total'],
            'total'           => $datos['total'],

            // Banderas de carrito, para el cartel de cabecera. La app no las
            // deriva recorriendo las lineas.
            'tiene_preventa'       => $datos['tiene_preventa'],
            'tiene_no_disponibles' => $datos['tiene_no_disponibles'],

            // Si hay algo que cobrar. Un carrito donde todo se despublico
            // tiene lineas y total cero: no es vacio, pero tampoco se puede
            // pagar. Es la bandera que habilita el boton de checkout, y la
            // decide el backend.
            'puede_comprar' => ! $datos['lineas']->isEmpty()
                && ! $datos['tiene_no_disponibles'],

            'lineas' => CarritoLineaResource::collection($datos['lineas'])->toArray($request),
        ];
    }
}
