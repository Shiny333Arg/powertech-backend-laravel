{{--
    Aviso de que el pedido está listo para retirar.

    Recibe:
      $venta      modelo Venta en estado lista_retiro
      $numero     string, el número formateado (V-0000123)
      $nombre     ?string, nombres del cliente
      $urlPedido  string, enlace al detalle en "Mis compras"

    Sin importes ni detalle completo a propósito: eso vive en el pedido, que
    siempre está al día. Un mail con precios queda desactualizado si después
    hay una devolución parcial.
--}}

@component('mail::message')

# Tu pedido está listo

@if ($nombre)
Hola {{ $nombre }}, ya
@else
Ya
@endif
podés pasar a buscar tu pedido **{{ $numero }}** por el local.

@component('mail::panel')
**Llevá tu DNI y este número de pedido: {{ $numero }}**
@endcomponent

@component('mail::button', ['url' => $urlPedido])
Ver mi pedido
@endcomponent

Si no podés venir vos, avisanos antes: necesitamos saber quién lo va a retirar.

Gracias por tu compra,<br>
{{ config('app.name') }}

@component('mail::subcopy')
Si el botón no funciona, copiá este enlace en tu navegador:<br>
{{ $urlPedido }}
@endcomponent

@endcomponent