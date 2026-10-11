{{--
    Badge de estado. Sirve para cualquier enum del dominio: venta, pago,
    comprobante u orden de compra.

    Uso:  @include('gestion.partials.estado', ['estado' => $venta->estado])

    El color dice en qué punto del circuito está el documento:
    gris espera · amarillo pide una acción · azul avanza · verde cerrado ·
    rojo cancelado o rechazado.
--}}

@php
    $clave = $estado instanceof \BackedEnum ? $estado->value : (string) $estado;

    // Si el enum usa el trait ConEtiqueta se respeta su texto; si no, se arma
    // uno legible para que ninguna pantalla muestre "pendiente_stock".
    $texto = (is_object($estado) && method_exists($estado, 'etiqueta'))
        ? $estado->etiqueta()
        : ucfirst(str_replace('_', ' ', $clave));

    $color = match ($clave) {
        'presupuesto'                        => 'secondary',
        'confirmada', 'lista_retiro'         => 'warning',
        'pagada', 'acreditado', 'autorizado' => 'info',
        'pendiente_stock'                    => 'danger',
        'en_preparacion', 'despachada'       => 'primary',
        'entregada', 'recibida'              => 'success',
        'devuelta', 'devuelta_parcial'       => 'warning',
        'cancelada', 'vencida', 'rechazado'  => 'danger',
        'pendiente'                          => 'secondary',
        'devuelto'                           => 'warning',
        default                              => 'secondary',
    };
@endphp

<span class="badge text-bg-{{ $color }} text-nowrap">{{ $texto }}</span>
