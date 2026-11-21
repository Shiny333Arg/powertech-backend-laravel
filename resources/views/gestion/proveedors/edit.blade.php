
@extends('layouts.gestion')

@section('title', 'PowerTech')

@section('content')

<div class="container py-4">


    {{-- Título --}}
    <section class="mb-4">
        <h1 class="h3 fw-bold mb-2">Editar proveedor</h1>
        <p class="text-muted">
            Los campos están bloqueados por defecto. Presione "Editar" para modificar los datos.
        </p>
    </section>


    {{-- Formulario de edición --}}
    <section class="mb-4">

        {{-- Fallas de la API que no son de validacion: sin permiso, sin red. --}}
        <div id="avisosProveedor" aria-live="polite"></div>

        <form
            id="formularioProveedor"
            autocomplete="off"
            class="p-4 rounded-4 bg-light shadow-sm border">

            {{-- Errores de validación que devuelve la API. --}}
            <div id="erroresProveedor"></div>

            <div class="row g-4">

                {{-- ===================================================== --}}
                {{-- DATOS DEL PROVEEDOR --}}
                {{-- ===================================================== --}}

                {{-- Razón social --}}
                <div class="col-md-8">
                    <label for="razon_social" class="form-label fw-semibold">
                        Razón social
                    </label>

                    <input
                        type="text"
                        class="form-control rounded-3"
                        id="razon_social"
                        name="razon_social"
                        maxlength="150"
                        required
                        disabled>

                    <div class="invalid-feedback" id="error-razon_social"></div>
                </div>


                {{-- CUIT --}}
                <div class="col-md-4">
                    <label for="cuit" class="form-label fw-semibold">
                        CUIT
                    </label>

                    <input
                        type="text"
                        class="form-control rounded-3"
                        id="cuit"
                        name="cuit"
                        maxlength="13"
                        required
                        disabled>

                    <div class="invalid-feedback" id="error-cuit"></div>
                </div>


                {{-- Condición frente al IVA --}}
                <div class="col-md-6">
                    <label for="condicion_iva_id" class="form-label fw-semibold">
                        Condición frente al IVA
                    </label>

                    <select
                        id="condicion_iva_id"
                        name="condicion_iva_id"
                        class="form-select rounded-3"
                        required
                        disabled>

                        <option value="">Seleccione una condición</option>

                        @foreach($condiciones as $condicion)
                        <option
                            value="{{ $condicion->id }}">
                            {{ $condicion->nombre }}
                        </option>
                        @endforeach

                    </select>

                    <div class="invalid-feedback" id="error-condicion_iva_id"></div>
                </div>


                {{-- Contacto --}}
                <div class="col-md-6">
                    <label for="contacto" class="form-label fw-semibold">
                        Persona de contacto
                    </label>

                    <input
                        type="text"
                        class="form-control rounded-3"
                        id="contacto"
                        name="contacto"
                        maxlength="100"
                        disabled>

                    <div class="invalid-feedback" id="error-contacto"></div>
                </div>


                {{-- Email --}}
                <div class="col-md-6">
                    <label for="email" class="form-label fw-semibold">
                        Correo electrónico
                    </label>

                    <input
                        type="email"
                        class="form-control rounded-3"
                        id="email"
                        name="email"
                        maxlength="150"
                        disabled>

                    <div class="invalid-feedback" id="error-email"></div>
                </div>


                {{-- Teléfono --}}
                <div class="col-md-6">
                    <label for="telefono" class="form-label fw-semibold">
                        Teléfono
                    </label>

                    <input
                        type="text"
                        class="form-control rounded-3"
                        id="telefono"
                        name="telefono"
                        maxlength="30"
                        disabled>

                    <div class="invalid-feedback" id="error-telefono"></div>
                </div>


                {{-- ===================================================== --}}
                {{-- PEDIDOS --}}
                {{-- ===================================================== --}}

                {{-- Canal de pedido --}}
                <div class="col-md-6">
                    <label for="canal_pedido" class="form-label fw-semibold">
                        Canal de pedido
                    </label>

                    <select
                        id="canal_pedido"
                        name="canal_pedido"
                        class="form-select rounded-3"
                        required
                        disabled>

                        <option value="">Seleccione un canal</option>

                        @foreach($canales as $value => $label)
                        <option
                            value="{{ $value }}">
                            {{ $label }}
                        </option>
                        @endforeach

                    </select>

                    <div class="invalid-feedback" id="error-canal_pedido"></div>
                </div>


                {{-- Portal externo --}}
                <div class="col-md-6" id="portalUrlContainer">
                    <label for="portal_url" class="form-label fw-semibold">
                        URL del portal de pedidos
                    </label>

                    <input
                        type="url"
                        class="form-control rounded-3"
                        id="portal_url"
                        name="portal_url"
                        maxlength="255"
                        placeholder="https://ejemplo.com"
                        disabled>

                    <div class="form-text">
                        Solo se utiliza cuando el canal de pedido es un portal externo.
                    </div>

                    <div class="invalid-feedback" id="error-portal_url"></div>
                </div>


                {{-- Plazo de entrega --}}
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
                            disabled>

                        <span class="input-group-text rounded-end-3">
                            días
                        </span>
                    </div>

                    <div class="text-danger small mt-1" id="error-plazo_entrega_dias"></div>
                </div>


                {{-- ===================================================== --}}
                {{-- DOMICILIO FISCAL --}}
                {{-- ===================================================== --}}

                <div class="col-12">
                    <hr class="my-2">

                    <h2 class="h5 fw-bold mb-1">
                        Domicilio fiscal
                    </h2>

                    <p class="text-muted small mb-0">
                        El domicilio fiscal es opcional. Si se completa la calle,
                        también deben completarse los demás datos requeridos.
                    </p>
                </div>


                {{-- Calle --}}
                <div class="col-md-8">
                    <label for="direccion_calle" class="form-label fw-semibold">
                        Calle
                    </label>

                    <input
                        type="text"
                        class="form-control rounded-3"
                        id="direccion_calle"
                        name="direccion[calle]"
                        maxlength="120"
                        disabled>

                    <div class="invalid-feedback" id="error-direccion.calle"></div>
                </div>


                {{-- Número --}}
                <div class="col-md-4">
                    <label for="direccion_numero" class="form-label fw-semibold">
                        Número
                    </label>

                    <input
                        type="text"
                        class="form-control rounded-3"
                        id="direccion_numero"
                        name="direccion[numero]"
                        maxlength="10"
                        disabled>

                    <div class="invalid-feedback" id="error-direccion.numero"></div>
                </div>


                {{-- Piso / Departamento --}}
                <div class="col-md-4">
                    <label for="direccion_piso_depto" class="form-label fw-semibold">
                        Piso / Departamento
                    </label>

                    <input
                        type="text"
                        class="form-control rounded-3"
                        id="direccion_piso_depto"
                        name="direccion[piso_depto]"
                        maxlength="20"
                        disabled>

                    <div class="invalid-feedback" id="error-direccion.piso_depto"></div>
                </div>


                {{-- Código postal --}}
                <div class="col-md-4">
                    <label for="direccion_codigo_postal" class="form-label fw-semibold">
                        Código postal
                    </label>

                    <input
                        type="text"
                        class="form-control rounded-3"
                        id="direccion_codigo_postal"
                        name="direccion[codigo_postal]"
                        maxlength="10"
                        disabled>

                    <div class="invalid-feedback" id="error-direccion.codigo_postal"></div>
                </div>


                {{-- Localidad --}}
                <div class="col-md-4">
                    <label for="direccion_localidad" class="form-label fw-semibold">
                        Localidad
                    </label>

                    <input
                        type="text"
                        class="form-control rounded-3"
                        id="direccion_localidad"
                        name="direccion[localidad]"
                        maxlength="80"
                        disabled>

                    <div class="invalid-feedback" id="error-direccion.localidad"></div>
                </div>


                {{-- Provincia --}}
                <div class="col-md-4">
                    <label for="direccion_provincia" class="form-label fw-semibold">
                        Provincia
                    </label>

                    <input
                        type="text"
                        class="form-control rounded-3"
                        id="direccion_provincia"
                        name="direccion[provincia]"
                        maxlength="60"
                        disabled>

                    <div class="invalid-feedback" id="error-direccion.provincia"></div>
                </div>

            </div>

        </form>


        {{-- ========================================================= --}}
        {{-- BOTONES: TODOS FUERA DEL FORMULARIO --}}
        {{-- ========================================================= --}}

        <div class="mt-4 d-flex flex-wrap gap-3">

            {{-- Editar --}}
            <button
                type="button"
                class="btn btn-warning px-4 fw-semibold shadow-sm"
                id="btnEditar">
                Editar
            </button>


            {{-- Actualizar --}}
            <button
                type="submit"
                form="formularioProveedor"
                class="btn btn-success px-4 fw-semibold shadow-sm"
                id="btnActualizar"
                disabled>
                Actualizar
            </button>


            {{-- Cancelar --}}
            <button
                type="button"
                class="btn btn-secondary px-4 fw-semibold shadow-sm"
                id="btnCancelar"
                disabled>
                Cancelar
            </button>


            {{-- Volver --}}
            <a
                href="{{ route('gestion.proveedores.index') }}"
                class="btn btn-outline-secondary px-4 fw-semibold shadow-sm">
                Volver al listado
            </a>


            {{--
                Baja y reactivacion.

                Los dos botones se dibujan siempre y el JavaScript muestra el
                que corresponde cuando la API le dice si el proveedor esta
                activo: la pagina ya no sabe en que estado esta. Van contra
                PATCH /api/v1/gestion/proveedores/{id}/estado, que exige
                'proveedor.baja'.
            --}}
            @can('cambiarEstado', $proveedor)

            <button
                type="button"
                id="botonBaja"
                class="btn btn-danger px-4 fw-semibold shadow-sm d-none">
                Dar de baja
            </button>

            <button
                type="button"
                id="botonReactivar"
                class="btn btn-success px-4 fw-semibold shadow-sm d-none">
                Dar de alta
            </button>

            @endcan

        </div>

    </section>


</div>

@endsection


@push('scripts')
<script type="module">
    import { iniciar } from '{{ Vite::asset('resources/js/gestion/proveedores/formulario.js') }}';

    iniciar({
        id: {{ $proveedor->id }},
        urlListado: '{{ route('gestion.proveedores.index') }}',
    });
</script>
@endpush
