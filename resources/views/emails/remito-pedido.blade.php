{{--
    Remito de pedido al proveedor.

    HTML de correo, no de sitio: tablas y estilos inline. Los clientes de mail
    ignoran las hojas de estilo externas y la mitad ignora tambien el <style>
    del head, asi que cada regla va pegada al elemento. Feo, pero es lo que
    llega igual a Outlook, Gmail y a un telefono.

    No se usa @vite ni el layout de la app: este documento se renderiza fuera
    del navegador del proveedor.
--}}
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido {{ $orden->numero }}</title>
</head>

<body style="margin:0; padding:0; background-color:#f4f5f7; font-family:Arial, Helvetica, sans-serif; color:#212529;">

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f5f7; padding:24px 12px;">
        <tr>
            <td align="center">

                <table role="presentation" width="640" cellpadding="0" cellspacing="0"
                       style="max-width:640px; width:100%; background-color:#ffffff; border-radius:8px; overflow:hidden;">

                    {{-- Encabezado --}}
                    <tr>
                        <td style="background-color:#212529; padding:20px 24px;">
                            <p style="margin:0; color:#ffffff; font-size:18px; font-weight:bold;">
                                {{ config('app.name', 'PowerTech') }}
                            </p>
                            <p style="margin:4px 0 0; color:#adb5bd; font-size:13px;">
                                Remito de pedido a proveedor
                            </p>
                        </td>
                    </tr>

                    {{-- Datos del documento --}}
                    <tr>
                        <td style="padding:24px 24px 8px;">
                            <p style="margin:0 0 16px; font-size:15px;">
                                Estimados de <strong>{{ $proveedor->razon_social }}</strong>:
                            </p>

                            <p style="margin:0 0 20px; font-size:15px; line-height:1.5;">
                                Les enviamos el siguiente pedido de mercadería. Por favor confirmen
                                disponibilidad y fecha de entrega respondiendo a este correo.
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                   style="font-size:14px; margin-bottom:20px;">
                                <tr>
                                    <td style="padding:4px 0; color:#6c757d; width:170px;">Número de pedido</td>
                                    <td style="padding:4px 0;"><strong>{{ $orden->numero }}</strong></td>
                                </tr>
                                <tr>
                                    <td style="padding:4px 0; color:#6c757d;">Fecha de emisión</td>
                                    <td style="padding:4px 0;">{{ now()->format('d/m/Y') }}</td>
                                </tr>
                                @if ($orden->fecha_entrega_estimada)
                                    <tr>
                                        <td style="padding:4px 0; color:#6c757d;">Entrega estimada</td>
                                        <td style="padding:4px 0;">{{ $orden->fecha_entrega_estimada->format('d/m/Y') }}</td>
                                    </tr>
                                @endif
                                @if ($proveedor->contacto)
                                    <tr>
                                        <td style="padding:4px 0; color:#6c757d;">Contacto</td>
                                        <td style="padding:4px 0;">{{ $proveedor->contacto }}</td>
                                    </tr>
                                @endif
                            </table>
                        </td>
                    </tr>

                    {{-- Detalle --}}
                    <tr>
                        <td style="padding:0 24px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                   style="font-size:14px; border-collapse:collapse;">
                                <thead>
                                    <tr>
                                        <th align="left" style="padding:8px 6px; border-bottom:2px solid #dee2e6; color:#6c757d; font-size:12px; text-transform:uppercase;">Código</th>
                                        <th align="left" style="padding:8px 6px; border-bottom:2px solid #dee2e6; color:#6c757d; font-size:12px; text-transform:uppercase;">Descripción</th>
                                        <th align="right" style="padding:8px 6px; border-bottom:2px solid #dee2e6; color:#6c757d; font-size:12px; text-transform:uppercase;">Cantidad</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($lineas as $linea)
                                        <tr>
                                            <td style="padding:10px 6px; border-bottom:1px solid #e9ecef; white-space:nowrap;">
                                                {{ $linea->producto->codigo }}
                                            </td>
                                            <td style="padding:10px 6px; border-bottom:1px solid #e9ecef;">
                                                {{ $linea->producto->nombre }}
                                                @if ($linea->producto->marca)
                                                    <span style="color:#6c757d;"> · {{ $linea->producto->marca }}</span>
                                                @endif
                                            </td>
                                            <td align="right" style="padding:10px 6px; border-bottom:1px solid #e9ecef; font-weight:bold; white-space:nowrap;">
                                                {{ $linea->cantidad_pedida }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="2" align="right" style="padding:12px 6px; color:#6c757d;">
                                            Total de unidades
                                        </td>
                                        <td align="right" style="padding:12px 6px; font-weight:bold;">
                                            {{ $lineas->sum('cantidad_pedida') }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </td>
                    </tr>

                    {{--
                        Los importes NO se envian. El costo del catalogo es una
                        estimacion nuestra para aprobar internamente, no un
                        precio acordado: mandarlo seria decirle al proveedor a
                        que precio esperamos que nos facture.
                    --}}

                    @if (filled($orden->observaciones) && $orden->origen === \App\Enums\OrigenOrdenCompra::Manual)
                        <tr>
                            <td style="padding:16px 24px 0;">
                                <p style="margin:0 0 4px; color:#6c757d; font-size:12px; text-transform:uppercase;">Observaciones</p>
                                <p style="margin:0; font-size:14px; line-height:1.5; white-space:pre-line;">{{ $orden->observaciones }}</p>
                            </td>
                        </tr>
                    @endif

                    {{-- Pie --}}
                    <tr>
                        <td style="padding:24px;">
                            <p style="margin:0; font-size:14px; line-height:1.5;">
                                Saludos cordiales,<br>
                                <strong>{{ config('app.name', 'PowerTech') }}</strong>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="background-color:#f8f9fa; padding:14px 24px; border-top:1px solid #e9ecef;">
                            <p style="margin:0; color:#6c757d; font-size:12px; line-height:1.5;">
                                Este pedido fue generado por el sistema de gestión de
                                {{ config('app.name', 'PowerTech') }}. Si necesitás aclarar algo,
                                respondé este correo.
                            </p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>

</html>
