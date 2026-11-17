{{--
    Rebote del checkout movil.

    La pagina a la que Mercado Pago devuelve al cliente que pago desde la app.
    Se abre en el NAVEGADOR DEL SISTEMA, que no tiene sesion de Laravel: por
    eso la ruta es publica y por eso esta vista no extiende el layout de la
    tienda, que asume usuario autenticado y navegacion.

    <b>No confirma nada.</b> Lo que diga la query string es solo el texto que
    se muestra. Quien decide si hubo venta es el webhook, y la app lo consulta
    con GET /api/v1/checkout/estado cuando vuelve al frente.

    Recibe:
      $estadoSugerido   aprobado | pendiente | rechazado | desconocido
      $esquema          esquema del deep link de la app (config movil.esquema)
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Volvé a PowerTech</title>
    <style>
        :root { color-scheme: light dark; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            background: #f4f4f5;
            color: #18181b;
        }
        @media (prefers-color-scheme: dark) {
            body { background: #18181b; color: #f4f4f5; }
            .tarjeta { background: #27272a !important; }
            .detalle { color: #a1a1aa !important; }
        }
        .tarjeta {
            background: #fff;
            border-radius: 16px;
            padding: 32px 24px;
            max-width: 380px;
            width: 100%;
            text-align: center;
            box-shadow: 0 1px 3px rgb(0 0 0 / .1);
        }
        .icono { font-size: 48px; line-height: 1; margin-bottom: 16px; }
        h1 { font-size: 20px; margin: 0 0 8px; }
        .detalle { font-size: 15px; line-height: 1.5; color: #52525b; margin: 0 0 24px; }
        .boton {
            display: block;
            padding: 14px 20px;
            border-radius: 10px;
            background: #2563eb;
            color: #fff;
            text-decoration: none;
            font-weight: 600;
        }
        .nota { font-size: 13px; color: #71717a; margin: 16px 0 0; }
    </style>
</head>
<body>
    <div class="tarjeta">
        @if ($estadoSugerido === 'rechazado')
            <div class="icono">⚠️</div>
            <h1>El pago no se completó</h1>
            <p class="detalle">
                Volvé a la aplicación: tu carrito quedó como estaba y podés
                intentar de nuevo con otro medio de pago.
            </p>
        @elseif ($estadoSugerido === 'pendiente')
            <div class="icono">⏳</div>
            <h1>Estamos confirmando tu pago</h1>
            <p class="detalle">
                Volvé a la aplicación. En cuanto Mercado Pago nos confirme la
                operación vas a ver el pedido ahí.
            </p>
        @else
            <div class="icono">✅</div>
            <h1>Listo</h1>
            <p class="detalle">
                Volvé a la aplicación para ver el resultado de la operación.
            </p>
        @endif

        {{--
            El deep link solo funciona si la app registro el esquema en
            AndroidManifest.xml e Info.plist. Si no esta registrado, el enlace
            no hace nada y el cliente vuelve con el gesto de siempre: el
            circuito no depende de este boton.
        --}}
        <a class="boton" href="{{ $esquema }}://checkout/retorno">Volver a PowerTech</a>

        <p class="nota">
            Si el botón no funciona, cerrá esta pestaña y volvé a la aplicación.
        </p>
    </div>
</body>
</html>
