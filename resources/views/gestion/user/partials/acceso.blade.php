{{--
    Estado de acceso de un empleado y, si corresponde, el boton para
    reenviarle la invitacion. Se usa en el listado y en la ficha:

      @include('gestion.user.partials.acceso', ['empleado' => $empleado])
      @include('gestion.user.partials.acceso', ['empleado' => $empleado, 'detalle' => true])

    Con 'detalle' agrega una linea explicando el estado (para la ficha).

    En el listado, invitacionPendiente() usa el alias que calcula la consulta
    de EmpleadoController::index, sin traer la contraseña.
--}}
@php
    $usuario   = $empleado->user;
    $pendiente = $usuario->invitacionPendiente();
    $detalle ??= false;
@endphp

<div class="d-flex flex-wrap align-items-center gap-2">
    @if (! $usuario->activo)
        <span class="badge text-bg-secondary">Dado de baja</span>
    @elseif ($pendiente)
        <span class="badge text-bg-warning">Invitación pendiente</span>
    @else
        <span class="badge text-bg-success">Activo</span>
    @endif

    @if ($usuario->activo && $pendiente)
        @can('reenviarInvitacion', $empleado)
            <form method="POST" action="{{ route('gestion.empleados.reenviarInvitacion', $empleado) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-primary">Reenviar invitación</button>
            </form>
        @endcan
    @endif
</div>

@if ($detalle)
    <p class="small text-muted mt-2 mb-0">
        @if (! $usuario->activo)
            No puede ingresar al sistema. Reactivalo para devolverle el acceso.
        @elseif ($pendiente)
            Todavía no eligió su contraseña. El enlace que recibió en {{ $usuario->email }} vence a las
            {{ \App\Notifications\InvitacionEmpleado::HORAS_DE_VIGENCIA }} horas; si se le venció, reenviáselo.
        @else
            Ingresa con {{ $usuario->email }}. Si olvida la contraseña, la recupera desde
            "¿Olvidaste tu contraseña?" en la pantalla de ingreso.
        @endif
    </p>
@endif
