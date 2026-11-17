<?php

namespace App\Http\Resources;

use App\Enums\ModoEntrega;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * El resumen previo al pago: que se lleva, cuanto sale, como lo retira y
 * que falta para poder pagar.
 *
 * <b>Reusa CarritoResource entero en vez de aplanar las lineas.</b> Son los
 * mismos importes calculados por el mismo `CarritoService::resumen()`, asi
 * que la pantalla de checkout puede reutilizar el modelo Dart del carrito sin
 * un segundo parseo. Repetir las lineas con otra forma abriria la puerta a
 * que el resumen y el carrito muestren totales distintos, que es justo lo
 * que el cliente mira dos veces antes de pagar.
 *
 * <b>`puede_pagar` y `bloqueos` son lo que esta pantalla agrega.</b> El
 * carrito ya trae `puede_comprar`, pero esa bandera solo mira el carrito:
 * hay algo que cobrar y nada despublicado. Para pagar hace falta ademas que
 * el cliente pueda recibir factura, que es una condicion del CLIENTE y no del
 * carrito. Las dos banderas existen porque responden preguntas distintas y en
 * momentos distintos: `puede_comprar` habilita el boton que trae hasta aca,
 * `puede_pagar` habilita el que cobra.
 *
 * <b>Los bloqueos llegan con `codigo`</b> para que la app decida a donde
 * mandar al cliente —al carrito o al perfil— sin comparar textos.
 *
 * <b>Datos del cliente: solo lo que esta pantalla muestra.</b> Razon social,
 * documento y condicion de IVA son lo que va a salir impreso en la factura y
 * el cliente tiene que poder revisarlo antes de pagar. No viajan ids
 * internos, telefono ni nada mas: eso ya esta en `/auth/yo` para la pantalla
 * de perfil, que es donde se edita.
 */
class CheckoutResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $datos */
        $datos = $this->resource;

        /** @var \App\Models\Carrito $carrito */
        $carrito = $datos['resumen']['carrito'];

        /** @var \App\Models\Cliente|null $cliente */
        $cliente = $datos['cliente'];

        /** @var array<int, array{codigo: string, mensaje: string}> $bloqueos */
        $bloqueos = $datos['bloqueos'];

        return [
            'carrito' => new CarritoResource($datos['resumen']),

            'entrega' => [
                // Null hasta que el cliente elija. La app muestra el modo por
                // defecto pero no lo da por guardado: el webhook lee esta
                // columna y necesita que sea una eleccion real.
                'modo'          => $carrito->modo_entrega?->value,
                'observaciones' => $carrito->observaciones,

                // Las opciones las dicta el backend, no la app. Hoy hay una
                // sola; el dia que se habiliten los envios, la pantalla se
                // actualiza sin tocar Flutter.
                'modos_disponibles' => array_map(
                    fn (ModoEntrega $modo) => [
                        'valor'    => $modo->value,
                        'etiqueta' => $modo->etiqueta(),
                    ],
                    self::modosHabilitados(),
                ),
            ],

            'facturacion' => $cliente ? [
                'razon_social'   => $cliente->razon_social,
                'tipo_documento' => $cliente->tipoDocumento?->nombre,
                'nro_doc'        => $cliente->nro_doc,
                'condicion_iva'  => $cliente->condicionIva?->nombre,
            ] : null,

            // Lo que habilita el boton de pagar. Lo decide el backend.
            'puede_pagar' => $bloqueos === [],

            'bloqueos' => $bloqueos,
        ];
    }

    /**
     * Los modos de entrega que la tienda acepta hoy.
     *
     * Solo retiro: el circuito de envio y tracking no esta implementado y
     * ofrecerlo crearia pedidos que nadie puede despachar. Es la misma regla
     * que valida CheckoutMovilRequest, y cuando se habiliten los envios hay
     * que cambiarla en los dos lados.
     *
     * @return array<int, ModoEntrega>
     */
    private static function modosHabilitados(): array
    {
        return [ModoEntrega::Retiro];
    }
}
