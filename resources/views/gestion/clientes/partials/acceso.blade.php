{{--
    Estado de la cuenta de un cliente en la tienda y, si corresponde, el botón
    para reenviarle la invitación. Se usa en la ficha y sirve para el listado:

      @include('gestion.clientes.partials.acceso', ['cliente' => $cliente])
      @include('gestion.clientes.partials.acceso', ['cliente' => $cliente, 'detalle' => true])

    Con 'detalle' agrega una línea explicando el estado.

    Lo que este bloque dice es SOLO sobre la tienda online. Ninguno de estos
    estados frena una venta, un cobro ni una factura de mostrador: para eso
    alcanza con que el cliente exista y tenga sus datos fiscales. Por eso el
    detalle lo aclara donde podría dar lugar a duda.
--}}
@php
    $usuario    = $cliente->user;
    $pendiente  = $usuario?->invitacionPendiente() ?? false;
    $verificado = $usuario?->email_verified_at !== null;
    $detalle  ??= false;
@endphp

<div class="d-flex flex-wrap align-items-center gap-2">

    @if ($usuario === null)
        <span class="badge text-bg-light border">Sin cuenta</span>
    @elseif (! $usuario->activo)
        <span class="badge text-bg-secondary">Acceso bloqueado</span>
    @elseif ($pendiente)
        <span class="badge text-bg-warning">Invitación pendiente</span>
    @elseif (! $verificado)
        <span class="badge text-bg-warning">Correo sin verificar</span>
    @else
        <span class="badge text-bg-success">Activa</span>
    @endif

    @if ($usuario?->activo && $pendiente)
        @can('update', $cliente)
            <form method="POST" action="{{ route('gestion.clientes.reenviarInvitacion', $cliente) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-primary">Reenviar invitación</button>
            </form>
        @endcan
    @endif

</div>

@if ($detalle)

    <p class="small text-muted mt-2 mb-0">
        @if ($usuario === null)
            Cliente de mostrador: se le puede vender y facturar, pero no tiene con qué
            entrar a la tienda. Si más adelante se registra, la cuenta se vincula a esta
            ficha en lugar de duplicarla.
        @elseif (! $usuario->activo)
            No puede ingresar a la tienda. Igual se le puede seguir vendiendo desde el mostrador.
        @elseif ($pendiente)
            Todavía no eligió su contraseña. El enlace que recibió en {{ $usuario->email }} vence a las
            {{ \App\Notifications\InvitacionCliente::HORAS_DE_VIGENCIA }} horas; si se le venció, reenviáselo.
            Mientras tanto se le puede vender y facturar con normalidad.
        @elseif (! $verificado)
            Le cambiaron el correo y todavía no confirmó {{ $usuario->email }}. Hasta que lo haga
            no puede comprar online, pero sí desde el mostrador.
        @else
            Ingresa con {{ $usuario->email }}. Si olvida la contraseña, la recupera desde
            "¿Olvidaste tu contraseña?" en la pantalla de ingreso.
        @endif
    </p>

@endif