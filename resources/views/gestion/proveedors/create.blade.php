@extends('layouts.gestion')

@section('title', 'PowerTech')

@section('content')

<!-- Contenido principal -->

<div class="container py-4">


<!-- Título -->
<section class="mb-4">
    <h1 class="h3 fw-bold mb-2">Registrar nuevo proveedor</h1>
    <p class="text-muted">
        Complete los datos para guardar un nuevo proveedor en el sistema.
    </p>
</section>

<!-- Formulario -->
<section class="mb-4">
    {{-- Fallas de la API que no son de validacion: sin permiso, sin red. --}}
    <div id="avisosProveedor" aria-live="polite"></div>

    <form
        autocomplete="off"
        class="p-4 rounded-4 bg-light shadow-sm border"
        id="formularioProveedor">

        {{-- Errores de validación que devuelve la API. --}}
        <div id="erroresProveedor"></div>

        <div class="row g-4">

            <!-- Razón social -->
            <div class="col-12">
                <label for="razon_social" class="form-label fw-semibold">
                    Razón social
                </label>

                <input
                    type="text"
                    class="form-control rounded-3"
                    id="razon_social"
                    name="razon_social"
                    placeholder="Ej: Distribuidora Informática S.A."
                    required
                    maxlength="150"
                    autofocus>

                <div class="invalid-feedback" id="error-razon_social"></div>
            </div>

            <!-- CUIT -->
            <div class="col-md-6">
                <label for="cuit" class="form-label fw-semibold">
                    CUIT
                </label>

                <input
                    type="text"
                    class="form-control rounded-3"
                    id="cuit"
                    name="cuit"
                    placeholder="Ej: 30-12345678-9"
                    maxlength="13"
                    required>

                <div class="invalid-feedback" id="error-cuit"></div>
            </div>

            <!-- Condición IVA -->
            <div class="col-md-6">
                <label for="condicion_iva_id" class="form-label fw-semibold">
                    Condición frente al IVA
                </label>

                <select
                    class="form-select rounded-3"
                    id="condicion_iva_id"
                    name="condicion_iva_id"
                    required>

                    <option value="">Seleccione una condición</option>

                    @foreach ($condiciones as $condicion)
                        <option
                            value="{{ $condicion->id }}">
                            {{ $condicion->nombre }}
                        </option>
                    @endforeach
                </select>

                <div class="invalid-feedback" id="error-condicion_iva_id"></div>
            </div>

            <!-- Contacto -->
            <div class="col-md-6">
                <label for="contacto" class="form-label fw-semibold">
                    Persona de contacto
                </label>

                <input
                    type="text"
                    class="form-control rounded-3"
                    id="contacto"
                    name="contacto"
                    placeholder="Ej: Carlos Solari"
                    maxlength="100">

                <div class="invalid-feedback" id="error-contacto"></div>
            </div>

            <!-- Email -->
            <div class="col-md-6">
                <label for="email" class="form-label fw-semibold">
                    Correo electrónico
                </label>

                <input
                    type="email"
                    class="form-control rounded-3"
                    id="email"
                    name="email"
                    placeholder="Ej: contacto@proveedor.com"
                    maxlength="150">

                <div class="invalid-feedback" id="error-email"></div>
            </div>

            <!-- Teléfono -->
            <div class="col-md-6">
                <label for="telefono" class="form-label fw-semibold">
                    Teléfono
                </label>

                <input
                    type="text"
                    class="form-control rounded-3"
                    id="telefono"
                    name="telefono"
                    placeholder="Ej: 297 4123456"
                    maxlength="30">

                <div class="invalid-feedback" id="error-telefono"></div>
            </div>

            <!-- Canal de pedido -->
            <div class="col-md-6">
                <label for="canal_pedido" class="form-label fw-semibold">
                    Canal de pedido
                </label>

                <select
                    class="form-select rounded-3"
                    id="canal_pedido"
                    name="canal_pedido"
                    required>

                    <option value="">Seleccione un canal</option>

                    {{--
                        El correo queda elegido de entrada, que es el canal con
                        el que venia el formulario cuando el controlador
                        precargaba un Proveedor en blanco.
                    --}}
                    @foreach ($canales as $valor => $nombre)
                        <option
                            value="{{ $valor }}"
                            @selected($valor === \App\Enums\CanalPedido::Email->value)>
                            {{ $nombre }}
                        </option>
                    @endforeach
                </select>

                <div class="invalid-feedback" id="error-canal_pedido"></div>
            </div>

            {{--
                Portal externo. El id lo usa el JS para mostrarlo solo cuando
                el canal es 'portal_externo', igual que en la edicion.
            --}}
            <div class="col-md-6" id="portalUrlContainer">
                <label for="portal_url" class="form-label fw-semibold">
                    Portal de pedidos
                </label>

                <input
                    type="url"
                    class="form-control rounded-3"
                    id="portal_url"
                    name="portal_url"
                    placeholder="Ej: https://www.proveedor.com"
                    maxlength="255">

                <div class="form-text">
                    Obligatorio si el canal de pedido es un portal externo.
                </div>

                <div class="invalid-feedback" id="error-portal_url"></div>
            </div>

            <!-- Plazo de entrega -->
            <div class="col-md-6">
                <label for="plazo_entrega_dias" class="form-label fw-semibold">
                    Plazo de entrega
                </label>

                <div class="input-group">
                    <input
                        type="number"
                        class="form-control rounded-start-3"
                        id="plazo_entrega_dias"
                        name="plazo_entrega_dias"
                        min="0"
                        max="365"
                        placeholder="Ej: 7">

                    <span class="input-group-text">
                        días
                    </span>
                </div>

                <div class="text-danger small mt-1" id="error-plazo_entrega_dias"></div>
            </div>

            <hr class="my-4">

            <!-- Domicilio fiscal -->
            <div class="col-12">
                <h2 class="h5 fw-bold mb-0 mt-2">
                    Domicilio fiscal
                </h2>
            </div>

            <!-- Calle -->
            <div class="col-md-6">
                <label for="calle" class="form-label fw-semibold">
                    Calle
                </label>

                <input
                    type="text"
                    class="form-control rounded-3"
                    id="calle"
                    name="direccion[calle]"
                    placeholder="Ej: Av. San Martín"
                    maxlength="120">

                <div class="invalid-feedback" id="error-direccion.calle"></div>
            </div>

            <!-- Número -->
            <div class="col-md-3">
                <label for="numero" class="form-label fw-semibold">
                    Número
                </label>

                <input
                    type="text"
                    class="form-control rounded-3"
                    id="numero"
                    name="direccion[numero]"
                    placeholder="Ej: 123"
                    maxlength="10">

                <div class="invalid-feedback" id="error-direccion.numero"></div>
            </div>

            <!-- Piso / Departamento -->
            <div class="col-md-3">
                <label for="piso_depto" class="form-label fw-semibold">
                    Piso / Departamento
                </label>

                <input
                    type="text"
                    class="form-control rounded-3"
                    id="piso_depto"
                    name="direccion[piso_depto]"
                    placeholder="Ej: 2° B"
                    maxlength="20">

                <div class="invalid-feedback" id="error-direccion.piso_depto"></div>
            </div>

            <!-- Código postal -->
            <div class="col-md-4">
                <label for="codigo_postal" class="form-label fw-semibold">
                    Código postal
                </label>

                <input
                    type="text"
                    class="form-control rounded-3"
                    id="codigo_postal"
                    name="direccion[codigo_postal]"
                    placeholder="Ej: 9011"
                    maxlength="10">

                <div class="invalid-feedback" id="error-direccion.codigo_postal"></div>
            </div>

            <!-- Localidad -->
            <div class="col-md-4">
                <label for="localidad" class="form-label fw-semibold">
                    Localidad
                </label>

                <input
                    type="text"
                    class="form-control rounded-3"
                    id="localidad"
                    name="direccion[localidad]"
                    placeholder="Ej: Caleta Olivia"
                    maxlength="80">

                <div class="invalid-feedback" id="error-direccion.localidad"></div>
            </div>

            <!-- Provincia -->
            <div class="col-md-4">
                <label for="provincia" class="form-label fw-semibold">
                    Provincia
                </label>

                <input
                    type="text"
                    class="form-control rounded-3"
                    id="provincia"
                    name="direccion[provincia]"
                    placeholder="Ej: Santa Cruz"
                    maxlength="60">

                <div class="invalid-feedback" id="error-direccion.provincia"></div>
            </div>

            {{--
                Aca habia un casillero "En operacion".

                Lo saco la migracion a la API, y no es un olvido: el alta por
                POST /api/v1/gestion/proveedores deja al proveedor activo y
                descarta el 'activo' del cuerpo a proposito —dar de alta a
                alguien ya dado de baja no es una operacion que tenga sentido—.
                El estado tiene su propio endpoint, su propio permiso
                (proveedor.baja) y sus botones en el listado y en la edicion.
            --}}

        </div>

        <!-- Botones -->
        <div class="mt-5 d-flex flex-wrap gap-3">

            <button
                type="submit"
                id="botonGuardar"
                class="btn btn-success px-4 fw-semibold shadow-sm">
                Guardar
            </button>

            <a
                href="{{ route('gestion.proveedores.index') }}"
                class="btn btn-outline-secondary px-4 fw-semibold shadow-sm">
                Volver al listado
            </a>

        </div>

    </form>
</section>


</div>

@endsection

@push('scripts')
<script type="module">
    import { iniciar } from '{{ Vite::asset('resources/js/gestion/proveedores/formulario.js') }}';

    iniciar({
        urlListado: '{{ route('gestion.proveedores.index') }}',
    });
</script>
@endpush
