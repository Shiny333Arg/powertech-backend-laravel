{{--
    Campo de contraseña con boton para mostrarla u ocultarla.

    <x-password-input name="password" label="Contraseña" />
    <x-password-input name="password" label="Nueva contraseña"
                      autocomplete="new-password" bag="updatePassword"
                      ayuda="Al menos 8 caracteres." />

    bag: bolsa de errores. PasswordController valida con
    validateWithBag('updatePassword'), asi que ese formulario la necesita.
--}}
@props([
    'name',
    'label',
    'id'           => null,
    'autocomplete' => 'current-password',
    'bag'          => 'default',
    'required'     => true,
    'ayuda'        => null,
])

@php
    $id    ??= $name;
    $error   = $errors->getBag($bag)->first($name);
@endphp

<div {{ $attributes->merge(['class' => 'mb-3']) }}>
    <label for="{{ $id }}" class="form-label fw-semibold">{{ $label }}</label>

    <div class="input-group has-validation">
        <input
            type="password"
            id="{{ $id }}"
            name="{{ $name }}"
            class="form-control rounded-start-3 @if ($error) is-invalid @endif"
            autocomplete="{{ $autocomplete }}"
            @if ($ayuda) aria-describedby="{{ $id }}-ayuda" @endif
            @required($required)>

        <button
            type="button"
            class="btn btn-outline-secondary rounded-end-3"
            data-mostrar-password="{{ $id }}"
            aria-controls="{{ $id }}"
            aria-pressed="false"
            aria-label="Mostrar contraseña">
            <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z" />
                <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0" />
            </svg>
        </button>

        @if ($error)
            <div class="invalid-feedback">{{ $error }}</div>
        @endif
    </div>

    @if ($ayuda)
        <div id="{{ $id }}-ayuda" class="form-text">{{ $ayuda }}</div>
    @endif
</div>

@once
    <script>
        // Un solo listener para todos los campos de la pagina.
        document.addEventListener('click', function (evento) {
            const boton = evento.target.closest('[data-mostrar-password]');
            if (! boton) return;

            const campo   = document.getElementById(boton.dataset.mostrarPassword);
            const mostrar = campo.type === 'password';

            campo.type = mostrar ? 'text' : 'password';
            boton.setAttribute('aria-pressed', String(mostrar));
            boton.setAttribute('aria-label', mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña');
            boton.classList.toggle('active', mostrar);
        });
    </script>
@endonce
