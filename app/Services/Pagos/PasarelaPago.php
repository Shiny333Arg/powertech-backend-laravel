<?php

namespace App\Services\Pagos;

use App\Models\Carrito;
use Illuminate\Http\Request;

/**
 * El borde entre el checkout y la pasarela de pago.
 *
 * Existe para que CheckoutService no sepa que la pasarela es Mercado Pago.
 * Es el mismo patron que AutorizadorComprobante con ARCA: el dominio depende
 * de esta interfaz y la implementacion concreta se inyecta.
 *
 * <b>Las tres operaciones son las tres del circuito:</b>
 *   crearPreferencia  antes de redirigir: pedir el link de pago
 *   buscarPago        al volver o al recibir el aviso: preguntar que paso
 *   esAvisoConfiable  antes de creerle al aviso: verificar que lo firmo ella
 *
 * No hay una operacion de "cobrar": Checkout Pro cobra en su propio sitio.
 * Nosotros creamos el link, el cliente paga alla, y despues preguntamos.
 */
interface PasarelaPago
{
    /**
     * Pide un link de pago por el total del carrito.
     *
     * El importe lo decide el llamador y viaja en centavos: la pasarela no
     * puede recalcularlo ni el cliente proponerlo.
     *
     * @param  int  $totalCentavos  bruto a cobrar
     * @throws \App\Exceptions\PasarelaPagoException si la pasarela no pudo crearla
     */
    public function crearPreferencia(Carrito $carrito, int $totalCentavos): PreferenciaPago;

    /**
     * Estado de un pago segun la pasarela, consultado contra su API.
     *
     * Devuelve null si el pago existe pero todavia NO esta acreditado
     * (pendiente, en revision, rechazado, devuelto). Solo un pago acreditado
     * produce un ResultadoPago, porque es lo unico con lo que se puede crear
     * una venta.
     *
     * <b>Nunca se confia en lo que llega por la query string o el body.</b>
     * El cliente puede escribir ?status=approved en la URL de retorno, y el
     * aviso del webhook solo trae un id. La verdad se pregunta a la pasarela.
     *
     * @throws \App\Exceptions\PasarelaPagoException si no se pudo consultar
     */
    public function buscarPago(string $idPagoExterno): ?ResultadoPago;

    /**
     * El aviso viene firmado por la pasarela y la firma es valida.
     *
     * Sin esto, cualquiera que descubra la URL del webhook puede avisar que
     * un pago se acredito y disparar una venta con stock comprometido y
     * factura emitida.
     */
    public function esAvisoConfiable(Request $request): bool;

    /**
     * Id del pago que trae un aviso, o null si el aviso no es de un pago.
     *
     * La pasarela notifica varios tipos de evento por la misma URL, y la
     * forma del payload cambia segun como este configurado el webhook.
     * Normalizar eso es responsabilidad de la implementacion, no del
     * controlador.
     */
    public function idPagoDelAviso(Request $request): ?string;
}
