<?php

/*
|--------------------------------------------------------------------------
| Mercado Pago Checkout Pro
|--------------------------------------------------------------------------
|
| La venta online nace `pagada`, asi que el pago ocurre ANTES de que exista
| la venta y la referencia externa que viaja a Mercado Pago es el CARRITO.
| Ver App\Services\CheckoutService.
|
| IMPORTANTE para desarrollo: Mercado Pago rechaza back_urls y
| notification_url que apunten a localhost o a un host sin DNS publico. Hay
| que exponer el entorno local (ngrok, expose, Cloudflare Tunnel) y poner esa
| URL en APP_URL, porque de ahi salen las dos.
|
*/

return [

    /*
    |----------------------------------------------------------------------
    | Credenciales
    |----------------------------------------------------------------------
    |
    | Las de prueba empiezan con TEST-. Se saca de
    | mercadopago.com.ar/developers -> Tus integraciones -> Credenciales.
    |
    | El access token es un secreto de servidor: no se expone en el frontend
    | ni se versiona. La public key solo hace falta para Checkout Bricks,
    | que no es lo que usamos, pero queda declarada por si se migra.
    |
    */

    'access_token' => env('MP_ACCESS_TOKEN'),
    'public_key'   => env('MP_PUBLIC_KEY'),

    /*
    |----------------------------------------------------------------------
    | Secreto del webhook
    |----------------------------------------------------------------------
    |
    | Mercado Pago firma cada notificacion con HMAC-SHA256 en la cabecera
    | x-signature. Sin verificar esa firma, cualquiera que conozca la URL
    | puede avisar que un pago se acredito y disparar la creacion de una
    | venta con stock comprometido y factura emitida.
    |
    | Se obtiene al configurar el webhook en el panel de Mercado Pago. Si
    | queda vacio la verificacion se SALTEA y se deja un warning en el log:
    | sirve para la primera prueba local, nunca para produccion.
    |
    */

    'webhook_secret' => env('MP_WEBHOOK_SECRET'),

    /*
    |----------------------------------------------------------------------
    | API
    |----------------------------------------------------------------------
    */

    'base_uri' => env('MP_BASE_URI', 'https://api.mercadopago.com'),

    'timeout' => (int) env('MP_TIMEOUT', 15),

    'currency' => env('MP_CURRENCY', 'ARS'),

    /*
    |----------------------------------------------------------------------
    | Cual de los dos links de pago usar
    |----------------------------------------------------------------------
    |
    | La preferencia vuelve con dos: `init_point` y `sandbox_init_point`.
    |
    | Con credenciales TEST- la documentacion actual indica usar init_point,
    | que abre el checkout en modo prueba; sandbox_init_point quedo como
    | herencia de integraciones viejas y en algunas cuentas ya no responde.
    | Por eso el default es false.
    |
    | Si al redirigir cae en un error de Mercado Pago o en una pantalla que
    | no corresponde, poner MP_USAR_SANDBOX_INIT_POINT=true y reintentar: es
    | lo unico que hay que cambiar.
    |
    */

    'usar_sandbox_init_point' => (bool) env('MP_USAR_SANDBOX_INIT_POINT', false),

    /*
    |----------------------------------------------------------------------
    | Volver solo al sitio despues de pagar
    |----------------------------------------------------------------------
    |
    | Con auto_return activo, Mercado Pago redirige al cliente de vuelta a
    | back_urls.success en cuanto aprueba el pago, sin que tenga que apretar
    | nada. Es la experiencia deseable y es el default.
    |
    | <b>El costo es que Mercado Pago valida back_urls con mucha mas dureza
    | cuando auto_return esta activo</b>, y si la rechaza responde un 400
    | confuso: «auto_return invalid. back_url.success must be defined», aunque
    | la URL se este mandando. Hay dominios de tunel de desarrollo que no le
    | gustan, y ahi no hay nada que corregir del lado nuestro.
    |
    | Poniendo MP_AUTO_RETURN=false la preferencia se crea sin auto_return y
    | esa validacion no corre. El circuito sigue funcionando completo: el
    | cliente vuelve apretando «Volver al sitio» en la pantalla de Mercado
    | Pago, y si no vuelve nunca, el webhook —que es el camino autoritativo—
    | crea la venta igual.
    |
    */

    'auto_return' => (bool) env('MP_AUTO_RETURN', true),

    /*
    |----------------------------------------------------------------------
    | Medios de pago
    |----------------------------------------------------------------------
    |
    | <b>Por defecto se excluyen los medios diferidos</b> —ticket (Rapipago,
    | Pago Facil) y atm (cajero)— y la razon es estructural, no comercial.
    |
    | La venta nace pagada: sin acreditacion no hay venta. Un pago en
    | efectivo tarda 1 a 3 dias habiles, asi que el cliente se quedaria con
    | un cupon pagado y sin ningun pedido en "Mis compras" hasta que
    | acredite. Y cuando acredite, el precio y las promociones pueden haber
    | cambiado: el carrito no congela precios, los congela la venta.
    |
    | Con medios instantaneos esa ventana es de segundos y el problema no
    | existe en la practica.
    |
    | Si se habilitan los diferidos hay que resolver antes dos cosas: que ve
    | el cliente mientras espera, y que pasa si el total cambio al acreditar.
    |
    */

    'excluir_tipos_pago' => array_filter(
        explode(',', (string) env('MP_EXCLUIR_TIPOS', 'ticket,atm'))
    ),

    /** Maximo de cuotas ofrecidas. 1 = solo un pago. */
    'cuotas_maximas' => (int) env('MP_CUOTAS_MAXIMAS', 12),

    /*
    |----------------------------------------------------------------------
    | Vencimiento de la preferencia
    |----------------------------------------------------------------------
    |
    | Minutos que el cliente tiene para pagar antes de que el link muera.
    | Corto a proposito: acota la ventana en la que el precio puede cambiar
    | entre que se creo la preferencia y que el pago se acredita.
    |
    */

    'minutos_vigencia' => (int) env('MP_MINUTOS_VIGENCIA', 30),

];