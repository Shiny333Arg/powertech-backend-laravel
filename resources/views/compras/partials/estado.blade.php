{{--
    Chip de estado de un pedido, en el lenguaje del cliente.

    Recibe:
      $estado  EstadoVenta

    No reusa el partial de gestion a proposito: el mostrador necesita ver el
    estado del circuito ("confirmada", "pendiente de stock") y el cliente
    necesita saber que le pasa a SU pedido. Son dos vocabularios distintos
    sobre el mismo dato.
--}}

@php
    $color = match ($estado) {
        \App\Enums\EstadoVenta::Confirmada      => 'warning',
        \App\Enums\EstadoVenta::Pagada          => 'info',
        \App\Enums\EstadoVenta::PendienteStock  => 'warning',
        \App\Enums\EstadoVenta::EnPreparacion   => 'info',
        \App\Enums\EstadoVenta::Despachada      => 'info',
        \App\Enums\EstadoVenta::ListaRetiro     => 'success',
        \App\Enums\EstadoVenta::Entregada       => 'success',
        \App\Enums\EstadoVenta::DevueltaParcial => 'secondary',
        \App\Enums\EstadoVenta::Devuelta        => 'secondary',
        \App\Enums\EstadoVenta::Cancelada       => 'danger',
        default                                 => 'secondary',
    };

    $texto = match ($estado) {
        \App\Enums\EstadoVenta::Confirmada     => 'Pendiente de pago',
        \App\Enums\EstadoVenta::Pagada         => 'Pago acreditado',
        \App\Enums\EstadoVenta::PendienteStock => 'Esperando reposición',
        \App\Enums\EstadoVenta::EnPreparacion  => 'En preparación',
        \App\Enums\EstadoVenta::ListaRetiro    => 'Listo para retirar',
        \App\Enums\EstadoVenta::Entregada      => 'Entregado',
        default                                => $estado->etiqueta(),
    };
@endphp

<span class="badge text-bg-{{ $color }} rounded-pill px-3 py-2">
    {{ $texto }}
</span>
