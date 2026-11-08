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
     * <b>`$urlRetorno` existe por el canal movil.</b> La URL a la que la
     * pasarela devuelve al cliente no puede ser la misma en los dos canales:
     * la del circuito web vive detras de `auth` y `verified`, y el navegador
     * del sistema que abre la app no tiene esa sesion —la app se autentica
     * con un token Bearer que el navegador no conoce—, asi que al volver
     * caeria en el login. El llamador movil pasa su propia ruta publica de
     * rebote; el web no pasa nada y queda identico a como estaba.
     *
     * @param  int  $totalCentavos  bruto a cobrar
     * @param  string|null  $urlRetorno  a donde vuelve el cliente; null usa la del circuito web
     * @throws \App\Exceptions\PasarelaPagoException si la pasarela no pudo crearla
     */
    public function crearPreferencia(
        Carrito $carrito,
        int $totalCentavos,
        ?string $urlRetorno = null,
    ): PreferenciaPago;

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
     * Estado crudo del ultimo pago hecho contra esta referencia, o null si no
     * hay ninguno.
     *
     * <b>Existe por el canal movil y resuelve una ambiguedad concreta.</b>
     * Cuando la app vuelve del navegador y todavia no hay venta, hay tres
     * situaciones que desde la base se ven identicas: el pago fue rechazado,
     * el cliente cancelo sin pagar, o pago bien y el webhook todavia no
     * llego. En la web no importaba —el retorno trae el `payment_id` en la
     * query string y con eso alcanza— pero el navegador del sistema vuelve a
     * una pagina publica que no puede avisarle nada a la app.
     *
     * Devuelve el estado tal como lo nombra la pasarela, sin traducir: quien
     * decide que hacer con cada uno es el controlador. Null significa que la
     * pasarela no registro ningun intento de pago para esa referencia.
     *
     * <b>No reemplaza a buscarPago().</b> Aquel confirma un pago puntual para
     * crear una venta; este solo informa. Ninguna venta nace de esto.
     *
     * @throws \App\Exceptions\PasarelaPagoException si no se pudo consultar
     */
    public function ultimoEstadoDeReferencia(string $referencia): ?string;

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
