@extends('layouts.gestion')

@section('title', 'PowerTech')

@section('content')

{{-- Título --}}
<section class="mb-4">

    <h1 class="h3 fw-bold mb-2">Editar cliente</h1>

    {{-- «Razón social · correo». Lo escribe el JS con lo que trae el detalle. --}}
    <p class="text-muted mb-0" id="subtituloCliente"></p>

</section>

{{-- Cambiar el correo lo desverifica: el empleado tiene que saberlo antes de
     tocarlo, no después de que el cliente no pueda entrar a la tienda.

     Arranca escondido: sólo corresponde si el cliente YA activó su cuenta, y eso
     lo dice `cuenta.invitacion_pendiente` en el detalle. Al que todavía no
     eligió su contraseña se le reenvía la invitación, que es otra cosa. --}}
<div class="alert alert-warning d-flex align-items-start gap-2 mb-4 d-none"
    id="avisoCambioDeCorreo"
    role="alert">

    <svg xmlns="http://www.w3.org/2000/svg"
        width="18"
        height="18"
        fill="currentColor"
        viewBox="0 0 16 16"
        class="flex-shrink-0 mt-1">

        <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5m.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2" />
    </svg>

    <div>
        Si cambiás el correo, la cuenta queda sin verificar hasta que el cliente
        confirme la casilla nueva. No le corta la operación de mostrador, pero no
        va a poder comprar en la tienda online mientras tanto.
    </div>

</div>

{{-- Formulario --}}
<section class="mb-4">

    @include('gestion.clientes.partials.formulario', [
        'textoDelBoton'  => 'Guardar cambios',
        'ayudaDelCorreo' => 'Si lo cambiás, el cliente va a tener que confirmar la casilla nueva.',
    ])

</section>

@endsection

@push('scripts')
<script type="module">
    import { iniciar } from '{{ Vite::asset('resources/js/gestion/clientes/formulario.js') }}';

    iniciar({
        // Sólo el id: los datos del cliente los pide la pantalla a
        // GET /api/v1/gestion/clientes/{cliente}.
        id: {{ $cliente->id }},

        urlFicha: '{{ route('gestion.clientes.show', ['cliente' => '__ID__']) }}',
    });
</script>
@endpush
