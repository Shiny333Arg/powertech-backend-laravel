<?php

namespace App\Services\Pagos;

use App\Enums\MetodoPago;
use App\Exceptions\PasarelaPagoException;
use App\Models\Carrito;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Checkout Pro contra la API REST de Mercado Pago.
 *
 * <b>Se habla HTTP directo y no el SDK oficial.</b> El SDK cambio de forma
 * completa entre v2 y v3, arrastra sus propias dependencias y envuelve dos
 * endpoints que son un POST y un GET. Con el cliente HTTP de Laravel el
 * contrato queda a la vista en este archivo y no depende de la version de un
 * paquete.
 *
 * <b>Dos endpoints, nada mas:</b>
 *   POST /checkout/preferences   crear el link de pago
 *   GET  /v1/payments/{id}       preguntar que paso con un pago
 *
 * <b>No se le cree a nadie sobre el estado de un pago.</b> Ni a la query
 * string del retorno —que el cliente puede editar— ni al body del webhook,
 * que solo trae un id. El estado se consulta siempre contra la API.
 *
 * <b>Requisito de desarrollo:</b> Mercado Pago rechaza back_urls y
 * notification_url que apunten a localhost. Hace falta un tunel publico
 * (ngrok, expose, Cloudflare Tunnel) y que APP_URL apunte ahi.
 */
class PasarelaMercadoPago implements PasarelaPago
{
    /**
     * Tipos de pago de Mercado Pago mapeados al enum local.
     *
     * MetodoPago tiene tres valores y Mercado Pago distingue mas: el tipo
     * fino queda guardado sin traducir en `pagos.mp_payment_type`, asi que no
     * se pierde informacion. `account_money` cae en transferencia y no en
     * tarjeta porque es un movimiento de billetera.
     */
    private const METODOS = [
        'credit_card'   => MetodoPago::Tarjeta,
        'debit_card'    => MetodoPago::Tarjeta,
        'prepaid_card'  => MetodoPago::Tarjeta,
        'account_money' => MetodoPago::Transferencia,
        'bank_transfer' => MetodoPago::Transferencia,
        'ticket'        => MetodoPago::Efectivo,
        'atm'           => MetodoPago::Efectivo,
    ];

    public function crearPreferencia(Carrito $carrito, int $totalCentavos): PreferenciaPago
    {
        if ($totalCentavos <= 0) {
            throw PasarelaPagoException::porque('No se puede cobrar un importe de cero.');
        }

        $this->exigirUrlAlcanzable(route('cliente.checkout.retorno'));
        $this->exigirUrlAlcanzable(route('cliente.checkout.webhook'));

        $cuerpo = $this->cuerpoPreferencia($carrito, $totalCentavos);

        try {
            $respuesta = $this->cliente()->post('/checkout/preferences', $cuerpo);
        } catch (ConnectionException $e) {
            Log::error('Error de conexión con Mercado Pago', [
                'mensaje' => $e->getMessage(),
                'clase' => get_class($e),
            ]);

            throw PasarelaPagoException::porque(
                'No pudimos comunicarnos con Mercado Pago. Intentá de nuevo en unos minutos.'
            );
        }

        if ($respuesta->failed()) {
            $detalle = $respuesta->json();

            Log::error('Mercado Pago rechazó la preferencia', [
                'carrito' => $carrito->getKey(),
                'status'  => $respuesta->status(),
                'cuerpo'  => $detalle,
                // Sin el access token: lo que se manda, para poder ver cual
                // campo rechazo sin tener que reproducir la llamada.
                'enviado' => $this->cuerpoPreferencia($carrito, $totalCentavos),
            ]);

            // En desarrollo el motivo de Mercado Pago llega a la pantalla. En
            // produccion no: puede nombrar campos internos y no le dice nada
            // util a un cliente.
            $motivo = config('app.debug')
                ? ' (' . ($detalle['message'] ?? $respuesta->status()) . ')'
                : '';

            throw PasarelaPagoException::porque(
                "No pudimos generar el link de pago. Intentá de nuevo en unos minutos.{$motivo}"
            );
        }

        $datos = $respuesta->json();

        // Cual de los dos links usar depende de la cuenta y de la version de
        // la integracion: ver el comentario de config/mercadopago.php. El
        // fallback cubre la preferencia que solo devuelve uno.
        $url = config('mercadopago.usar_sandbox_init_point')
            ? ($datos['sandbox_init_point'] ?? $datos['init_point'] ?? null)
            : ($datos['init_point'] ?? $datos['sandbox_init_point'] ?? null);

        if (! isset($datos['id']) || ! $url) {
            throw PasarelaPagoException::porque(
                'Mercado Pago devolvió una preferencia sin link de pago.'
            );
        }

        return new PreferenciaPago(
            id: (string) $datos['id'],
            urlPago: $url,
        );
    }

    public function buscarPago(string $idPagoExterno): ?ResultadoPago
    {
        try {
            $respuesta = $this->cliente()->get("/v1/payments/{$idPagoExterno}");
        } catch (ConnectionException $e) {
            // Importa no tragarse esto: si el webhook no puede consultar, hay
            // que devolverle un error a Mercado Pago para que reintente.
            throw PasarelaPagoException::porque(
                "No pudimos comunicarnos con Mercado Pago para consultar el pago {$idPagoExterno}."
            );
        }

        if ($respuesta->status() === 404) {
            throw PasarelaPagoException::porque("Mercado Pago no conoce el pago {$idPagoExterno}.");
        }

        if ($respuesta->failed()) {
            throw PasarelaPagoException::porque(
                "No pudimos consultar el pago {$idPagoExterno} en Mercado Pago."
            );
        }

        $pago = $respuesta->json();

        // Solo un pago acreditado produce un resultado: es lo unico con lo
        // que se puede crear una venta. Pendiente, en revision, rechazado y
        // devuelto devuelven null y el circuito no avanza.
        if (($pago['status'] ?? null) !== 'approved') {
            return null;
        }

        $bruto = $this->aCentavos($pago['transaction_amount'] ?? 0);
        $neto = isset($pago['transaction_details']['net_received_amount'])
            ? $this->aCentavos($pago['transaction_details']['net_received_amount'])
            : null;

        return new ResultadoPago(
            metodo: self::METODOS[$pago['payment_type_id'] ?? ''] ?? MetodoPago::Tarjeta,
            montoCentavos: $bruto,
            cuotas: isset($pago['installments']) ? (int) $pago['installments'] : null,
            mpPaymentId: (string) $pago['id'],
            // La preferencia no viene en el pago: la guardamos nosotros en el
            // carrito al crearla.
            mpPreferenceId: null,
            mpMerchantOrderId: isset($pago['order']['id']) ? (string) $pago['order']['id'] : null,
            mpStatus: $pago['status'] ?? null,
            mpStatusDetail: $pago['status_detail'] ?? null,
            mpPaymentType: $pago['payment_type_id'] ?? null,
            comisionCentavos: $this->comisionDe($pago, $bruto, $neto),
            netoAcreditadoCentavos: $neto,
            referenciaExterna: $pago['external_reference'] ?? null,
        );
    }

    /**
     * Verifica la firma HMAC-SHA256 que Mercado Pago manda en x-signature.
     *
     * El manifiesto que se firma es `id:<data.id>;request-id:<x-request-id>;ts:<ts>;`
     * armado con los valores del aviso, salteando las partes que no vengan.
     *
     * <b>Sin secreto configurado devuelve true y deja un warning.</b> Es para
     * que la primera prueba local no se trabe antes de dar de alta el webhook
     * en el panel. En produccion, sin secreto, cualquiera que descubra la URL
     * puede fabricar una venta con stock comprometido y factura emitida.
     */
    public function esAvisoConfiable(Request $request): bool
    {
        $secreto = config('mercadopago.webhook_secret');

        if (blank($secreto)) {
            Log::warning('Webhook de Mercado Pago sin verificar: falta MP_WEBHOOK_SECRET.');

            return true;
        }

        $firma = $request->header('x-signature');
        $idPedido = $request->header('x-request-id');

        if (! $firma) {
            return false;
        }

        // x-signature viene como "ts=1704908010,v1=<hash>"
        $partes = [];

        foreach (explode(',', $firma) as $trozo) {
            [$clave, $valor] = array_pad(explode('=', trim($trozo), 2), 2, null);
            $partes[trim((string) $clave)] = trim((string) $valor);
        }

        $ts = $partes['ts'] ?? null;
        $hash = $partes['v1'] ?? null;

        if (! $ts || ! $hash) {
            return false;
        }

        // El id que se firma es el de la query string, en minusculas.
        $idPago = $request->query('data.id') ?? $request->query('id');

        $manifiesto = '';

        if ($idPago !== null) {
            $manifiesto .= 'id:' . strtolower((string) $idPago) . ';';
        }

        if ($idPedido) {
            $manifiesto .= 'request-id:' . $idPedido . ';';
        }

        $manifiesto .= 'ts:' . $ts . ';';

        return hash_equals(
            hash_hmac('sha256', $manifiesto, (string) $secreto),
            $hash,
        );
    }

    /**
     * Normaliza las distintas formas en que llega un aviso de pago.
     *
     * Mercado Pago manda el id en el body (`data.id`) con los webhooks
     * nuevos y en la query string (`id` + `topic`) con los IPN viejos, y
     * notifica tambien eventos que no son pagos. Esta es la unica funcion
     * que necesita conocer esas variantes.
     */
    public function idPagoDelAviso(Request $request): ?string
    {
        $tipo = $request->input('type')
            ?? $request->input('topic')
            ?? $request->query('type')
            ?? $request->query('topic');

        // merchant_order, plan, subscription y demas no se procesan: el pago
        // es el unico evento que crea una venta.
        if ($tipo !== null && ! in_array($tipo, ['payment', 'payments'], true)) {
            return null;
        }

        $id = $request->input('data.id')
            ?? $request->query('data.id')
            ?? $request->input('id')
            ?? $request->query('id');

        return $id !== null ? (string) $id : null;
    }

    // ------------------------------------------------------------------
    // Armado de la preferencia
    // ------------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    private function cuerpoPreferencia(Carrito $carrito, int $totalCentavos): array
    {
        $cliente = $carrito->user?->cliente;

        /*
         * <b>Una sola linea con el total, y no una por producto.</b> Mercado
         * Pago cobra la suma de los items, y si se mandaran las lineas del
         * carrito habria dos lugares calculando el mismo importe: el redondeo
         * por linea de MotorPromociones y el de Mercado Pago. Cualquier
         * diferencia de un centavo haria fallar la verificacion del importe
         * en CheckoutService y el cliente quedaria pagado sin pedido.
         *
         * El detalle real vive en venta_lineas, congelado al crear la venta.
         * Esto es nada mas la orden de cobro.
         */
        $cuerpo = [
            'items' => [[
                'id'          => (string) $carrito->getKey(),
                'title'       => 'Pedido PowerTech',
                'description' => 'Compra en la tienda online',
                'quantity'    => 1,
                'currency_id' => config('mercadopago.currency'),
                'unit_price'  => round($totalCentavos / 100, 2),
            ]],

            'external_reference' => ReferenciaCarrito::de($carrito),

            'back_urls' => [
                'success' => route('cliente.checkout.retorno'),
                'pending' => route('cliente.checkout.retorno'),
                'failure' => route('cliente.checkout.retorno'),
            ],

            'notification_url' => route('cliente.checkout.webhook'),

            'payment_methods' => [
                'excluded_payment_types' => array_map(
                    fn(string $tipo) => ['id' => trim($tipo)],
                    config('mercadopago.excluir_tipos_pago', []),
                ),
                'installments' => config('mercadopago.cuotas_maximas'),
            ],

            // El link muere solo. Acota la ventana en la que un precio puede
            // cambiar entre la creacion de la preferencia y la acreditacion.
            //
            // <b>El formato lleva milisegundos.</b> Mercado Pago exige
            // yyyy-MM-dd'T'HH:mm:ss.SSSXXX y rechaza la preferencia entera si
            // falta esa parte. Carbon::toIso8601String() NO los incluye, asi
            // que se formatea a mano: la 'v' son los milisegundos y la 'P' el
            // offset con dos puntos (-03:00).
            'expires'            => true,
            'expiration_date_to' => now()
                ->addMinutes((int) config('mercadopago.minutos_vigencia'))
                ->format('Y-m-d\TH:i:s.vP'),
        ];

        /*
         * auto_return hace que Mercado Pago devuelva al cliente sin que tenga
         * que apretar nada. Va APARTE y condicionado porque es el campo que
         * dispara la validacion dura de back_urls: cuando esta presente y
         * Mercado Pago no acepta el dominio de back_urls.success —cosa que
         * pasa con algunos dominios de tunel de desarrollo— rechaza la
         * preferencia entera con un mensaje que culpa a un campo que si se
         * esta mandando.
         *
         * Con MP_AUTO_RETURN=false la preferencia se crea igual y el circuito
         * no pierde nada esencial: el cliente vuelve con el boton «Volver al
         * sitio», y si no vuelve, el webhook crea la venta de todas formas.
         */
        if (config('mercadopago.auto_return')) {
            $cuerpo['auto_return'] = 'approved';
        }

        // Precargar los datos del pagador le ahorra tipeo al cliente. Es
        // opcional: si falta algo, Mercado Pago lo pide en su pantalla.
        if ($usuario = $carrito->user) {
            $cuerpo['payer'] = array_filter([
                'name'    => $usuario->nombres,
                'surname' => $usuario->apellido,
                'email'   => $usuario->email,
            ]);
        }

        /*
         * <b>El tipo de documento solo se manda si Mercado Pago lo conoce.</b>
         * Su catalogo para Argentina es corto y cerrado —DNI, CI, LC, LE,
         * CUIT, CUIL, CDI, Otro— y rechaza la preferencia ENTERA si recibe
         * otra cosa. `tipos_documento.nombre` es nuestro, pensado para
         * mostrarle al usuario ("Documento Nacional de Identidad",
         * "Consumidor final"), y no tiene por que coincidir.
         *
         * Ante la duda se omite: es un dato de comodidad para que el cliente
         * no lo tipee, y Mercado Pago lo pide en su pantalla si falta. Perder
         * el prellenado es barato; perder la venta, no.
         */
        if ($cliente?->nro_doc && $tipo = $this->tipoDocumentoDeMercadoPago($cliente)) {
            $cuerpo['payer']['identification'] = [
                'type'   => $tipo,
                'number' => preg_replace('/\D/', '', (string) $cliente->nro_doc),
            ];
        }

        return $cuerpo;
    }

    /**
     * Traduce nuestro tipo de documento al codigo de Mercado Pago, o null.
     *
     * Se compara contra el nombre normalizado en lugar del codigo de ARCA
     * porque los codigos de ARCA no son los de Mercado Pago: son dos
     * catalogos distintos que no tienen por que mapear uno a uno.
     */
    private function tipoDocumentoDeMercadoPago(mixed $cliente): ?string
    {
        $nombre = strtoupper((string) ($cliente->tipoDocumento?->nombre ?? ''));

        return match (true) {
            str_contains($nombre, 'CUIT') => 'CUIT',
            str_contains($nombre, 'CUIL') => 'CUIL',
            str_contains($nombre, 'CDI')  => 'CDI',
            str_contains($nombre, 'DNI')  => 'DNI',
            default                       => null,
        };
    }

    // ------------------------------------------------------------------
    // Auxiliares
    // ------------------------------------------------------------------

    /**
     * Mercado Pago tiene que poder llegar a esta URL desde internet.
     *
     * <b>Por que se comprueba antes de llamar a la API.</b> Mercado Pago
     * rechaza una back_url que no puede alcanzar, pero el error que devuelve
     * no dice eso: dice
     *
     *   auto_return invalid. back_url.success must be defined
     *
     * o sea que el campo no esta definido, cuando en realidad esta definido y
     * es invalido. Buscar un campo faltante que si se esta mandando cuesta un
     * rato largo. Esta comprobacion traduce el sintoma a la causa.
     *
     * <b>Y la causa casi nunca es APP_URL.</b> Durante un request HTTP,
     * route() arma la URL con el host y el esquema del REQUEST ENTRANTE, no
     * con APP_URL: APP_URL es el fallback para cuando no hay request
     * —consola, colas, mails—. Asi que navegar la tienda en localhost genera
     * back_urls de localhost por mas que APP_URL apunte al tunel. Ojo con
     * verificarlo desde tinker: ahi no hay request, cae al fallback y muestra
     * la URL correcta mientras el navegador manda la equivocada.
     *
     * Lo que se rechaza:
     *   localhost y 127.0.0.1    no existen para Mercado Pago
     *   un host sin punto        no es resoluble por DNS
     *   espacios en la URL       el clasico espacio de mas en el .env
     */
    private function exigirUrlAlcanzable(string $url): void
    {
        if (preg_match('/\s/', $url)) {
            throw PasarelaPagoException::porque(
                "La URL «{$url}» tiene un espacio. Suele ser un espacio sobrante "
                    . 'al final de APP_URL en el .env; corregilo y corré php artisan config:clear.'
            );
        }

        $host = (string) parse_url($url, PHP_URL_HOST);

        $esLocal = in_array($host, ['localhost', '127.0.0.1', '::1', '0.0.0.0'], true)
            || ! str_contains($host, '.');

        if ($esLocal) {
            throw PasarelaPagoException::porque(
                "Mercado Pago no puede alcanzar «{$url}»: necesita una URL pública. "
                    . 'Esta URL se arma con el host del navegador, no con APP_URL: '
                    . 'entrá a la tienda por la dirección del túnel (ngrok) en lugar de '
                    . 'localhost y hacé el checkout desde ahí.'
            );
        }
    }

    private function cliente(): PendingRequest
    {
        $token = config('mercadopago.access_token');

        if (blank($token)) {
            throw PasarelaPagoException::porque(
                'Falta MP_ACCESS_TOKEN: la tienda no puede cobrar.'
            );
        }

        return Http::baseUrl((string) config('mercadopago.base_uri'))
            ->withToken((string) $token)
            ->timeout((int) config('mercadopago.timeout'))
            ->acceptJson()
            ->asJson();
    }

    /**
     * Comision cobrada por Mercado Pago, en centavos.
     *
     * Se prefiere el neto informado —bruto menos neto es exacto— y se cae a
     * la suma de fee_details cuando el neto no viene.
     *
     * @param  array<string, mixed>  $pago
     */
    private function comisionDe(array $pago, int $bruto, ?int $neto): ?int
    {
        if ($neto !== null) {
            return max(0, $bruto - $neto);
        }

        if (empty($pago['fee_details']) || ! is_array($pago['fee_details'])) {
            return null;
        }

        $total = 0;

        foreach ($pago['fee_details'] as $cargo) {
            $total += $this->aCentavos($cargo['amount'] ?? 0);
        }

        return $total;
    }

    private function aCentavos(int|float|string|null $monto): int
    {
        return (int) round(((float) $monto) * 100);
    }
}
