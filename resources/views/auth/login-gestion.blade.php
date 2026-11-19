{{--
    Ingreso del panel de gestión (empleados).

    Misma tarjeta y mismo diseño que el ingreso del storefront: solo cambia el
    mecanismo. Acá las credenciales se envían por JavaScript a
    POST /api/v1/auth/login —el único endpoint del sistema que las valida, como
    exige el requisito 6— y el token que vuelve se cambia por una sesión Blade
    en POST /gestion/sesion.

    El formulario tiene @csrf y method POST pero NO action: sin JavaScript no
    hace nada, y es correcto que así sea. El panel es asincrónico por el
    requisito 8, así que sin JS no hay panel al que entrar. Los clientes tienen
    su propia puerta, /login, que sigue siendo un POST tradicional sin una línea
    de JavaScript.
--}}
@extends('layouts.auth')

@section('title', 'Ingresar al panel')
@section('titulo', 'Panel de gestión')

@section('content')

<form id="form-ingreso-gestion" method="POST" novalidate>
    @csrf

    {{-- Errores que llegan por redirección: sesión vencida, token incoherente,
         o un empleado derivado desde el ingreso del storefront. --}}
    @error('email')
    <div class="alert alert-warning rounded-3" role="alert">{{ $message }}</div>
    @enderror

    <div class="mb-3">
        <label for="email" class="form-label fw-semibold">Correo electrónico</label>
        <input
            type="email"
            id="email"
            name="email"
            value="{{ old('email') }}"
            class="form-control rounded-3"
            required
            autofocus
            autocomplete="username">
    </div>

    <x-password-input name="password" label="Contraseña" autocomplete="current-password" />

    <div class="d-flex flex-wrap justify-content-center align-items-center gap-2 mb-4">
        <a href="{{ route('password.request') }}" class="small">¿Olvidaste tu contraseña?</a>
    </div>

    {{-- Donde se muestran los errores que devuelve la API. --}}
    <div id="error-ingreso" class="alert alert-danger rounded-3 d-none" role="alert" aria-live="polite"></div>

    <button id="boton-ingreso" type="submit" class="btn btn-primary w-100 rounded-3 fw-semibold">
        <span id="boton-ingreso-texto">Ingresar</span>
        <span id="boton-ingreso-cargando" class="spinner-border spinner-border-sm d-none" aria-hidden="true"></span>
    </button>
</form>

<hr class="my-4">

<p class="text-center small mb-0">
    ¿Sos cliente de la tienda?
    <a href="{{ route('login') }}" class="fw-semibold">Ingresá por acá</a>
</p>

<div>
    <hr class="my-4">
    <p class="fw-semibold text-center my-4">Cuentas de prueba:</p>
    <p class="fw-semibold">Administrativo: Luis Alberto Spinetta</p>
    <p>flaco@mail.com</p>
    <p>Cambiar.Esta.Clave</p>
    <hr class="my-4">
    <p class="fw-semibold">Vendedor: Gustavo Edgardo Cordera</p>
    <p>pelado@mail.com</p>
    <p>Cambiar.Esta.Clave</p>
    <hr class="my-4">
    <p class="fw-semibold">Cajero: Carlos Alberto Solari</p>
    <p>indio@mail.com</p>
    <p>Cambiar.Esta.Clave</p>
    <hr class="my-4">
    <p class="fw-semibold">Cliente: Patricio Rogelio Fontanet</p>
    <p>fontanet@mail.com</p>
    <p>Cambiar.Esta.Clave</p>
</div>


@endsection

@push('scripts')
<script type="module">
    import { ingresar, abrirSesionWeb, ErrorDeApi } from '{{ Vite::asset('resources/js/gestion/api.js') }}';

    const form      = document.getElementById('form-ingreso-gestion');
    const boton     = document.getElementById('boton-ingreso');
    const texto     = document.getElementById('boton-ingreso-texto');
    const cargando  = document.getElementById('boton-ingreso-cargando');
    const cajaError = document.getElementById('error-ingreso');

    form.addEventListener('submit', async (evento) => {
        evento.preventDefault();

        ocultarError();
        trabajando(true);

        // El token en claro vive solo en esta variable local y, si el
        // intercambio sale bien, en la sesión del servidor. Ante CUALQUIER
        // fallo se descarta sin usarse: es la única mitigación del lado del
        // cliente para un token que quedó emitido y sin sesión, y es gratis.
        let token = null;

        try {
            const ingreso = await ingresar(
                form.elements.email.value,
                form.elements.password.value,
            );

            token = ingreso.token;

            // Un cliente que entró por la puerta equivocada. El token de tienda
            // que acaba de emitirse no sirve para nada acá, así que se revoca en
            // el mismo flujo —por el endpoint compartido— y no queda un huérfano
            // de 30 días dando vueltas.
            if (ingreso.ambito !== 'gestion') {
                await descartar(token);
                token = null;

                window.location.assign('{{ route('login') }}');
                return;
            }

            const sesion = await abrirSesionWeb(token);

            window.location.assign(sesion.redirigir_a);
            return;

        } catch (error) {
            mostrarError(mensajeDe(error));
            trabajando(false);

        } finally {
            token = null;
        }
    });

    /** Revoca un token recién emitido que no vamos a usar. */
    async function descartar(token) {
        try {
            await fetch('/api/v1/auth/logout', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    Authorization: `Bearer ${token}`,
                },
            });
        } catch {
            // Si no se pudo, vence solo. No vale trabar el ingreso por esto.
        }
    }

    function mensajeDe(error) {
        if (! (error instanceof ErrorDeApi)) {
            return 'No pudimos conectarnos con el servidor. Revisá tu conexión.';
        }

        // 422 del login: el detalle por campo viene en 'errors.email' y es
        // donde caen "credenciales inválidas", "cuenta desactivada",
        // "demasiados intentos" y el rechazo por ámbito.
        const porCampo = error.errores?.email?.[0];

        return porCampo ?? error.message;
    }

    function mostrarError(mensaje) {
        cajaError.textContent = mensaje;
        cajaError.classList.remove('d-none');
    }

    function ocultarError() {
        cajaError.classList.add('d-none');
        cajaError.textContent = '';
    }

    function trabajando(si) {
        boton.disabled = si;
        texto.classList.toggle('d-none', si);
        cargando.classList.toggle('d-none', ! si);
    }
</script>
@endpush
