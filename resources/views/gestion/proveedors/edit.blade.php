
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

        <form
            id="editarProveedorForm"
            action="{{ route('gestion.proveedores.update', $proveedor) }}"
            method="POST"
            autocomplete="off"
            class="p-4 rounded-4 bg-light shadow-sm border">

            @csrf
            @method('PUT')

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
                        class="form-control rounded-3 @error('razon_social') is-invalid @enderror"
                        id="razon_social"
                        name="razon_social"
                        value="{{ old('razon_social', $proveedor->razon_social) }}"
                        maxlength="150"
                        required
                        disabled>

                    @error('razon_social')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>


                {{-- CUIT --}}
                <div class="col-md-4">
                    <label for="cuit" class="form-label fw-semibold">
                        CUIT
                    </label>

                    <input
                        type="text"
                        class="form-control rounded-3 @error('cuit') is-invalid @enderror"
                        id="cuit"
                        name="cuit"
                        value="{{ old('cuit', $proveedor->cuit) }}"
                        maxlength="13"
                        required
                        disabled>

                    @error('cuit')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>


                {{-- Condición frente al IVA --}}
                <div class="col-md-6">
                    <label for="condicion_iva_id" class="form-label fw-semibold">
                        Condición frente al IVA
                    </label>

                    <select
                        id="condicion_iva_id"
                        name="condicion_iva_id"
                        class="form-select rounded-3 @error('condicion_iva_id') is-invalid @enderror"
                        required
                        disabled>

                        <option value="">Seleccione una condición</option>

                        @foreach($condiciones as $condicion)
                        <option
                            value="{{ $condicion->id }}"
                            @selected(old('condicion_iva_id', $proveedor->condicion_iva_id) == $condicion->id)>
                            {{ $condicion->nombre }}
                        </option>
                        @endforeach

                    </select>

                    @error('condicion_iva_id')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>


                {{-- Contacto --}}
                <div class="col-md-6">
                    <label for="contacto" class="form-label fw-semibold">
                        Persona de contacto
                    </label>

                    <input
                        type="text"
                        class="form-control rounded-3 @error('contacto') is-invalid @enderror"
                        id="contacto"
                        name="contacto"
                        value="{{ old('contacto', $proveedor->contacto) }}"
                        maxlength="100"
                        disabled>

                    @error('contacto')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>


                {{-- Email --}}
                <div class="col-md-6">
                    <label for="email" class="form-label fw-semibold">
                        Correo electrónico
                    </label>

                    <input
                        type="email"
                        class="form-control rounded-3 @error('email') is-invalid @enderror"
                        id="email"
                        name="email"
                        value="{{ old('email', $proveedor->email) }}"
                        maxlength="150"
                        disabled>

                    @error('email')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>


                {{-- Teléfono --}}
                <div class="col-md-6">
                    <label for="telefono" class="form-label fw-semibold">
                        Teléfono
                    </label>

                    <input
                        type="text"
                        class="form-control rounded-3 @error('telefono') is-invalid @enderror"
                        id="telefono"
                        name="telefono"
                        value="{{ old('telefono', $proveedor->telefono) }}"
                        maxlength="30"
                        disabled>

                    @error('telefono')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
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
                        class="form-select rounded-3 @error('canal_pedido') is-invalid @enderror"
                        required
                        disabled>

                        <option value="">Seleccione un canal</option>

                        @foreach($canales as $value => $label)
                        <option
                            value="{{ $value }}"
                            @selected(old('canal_pedido', $proveedor->canal_pedido?->value) === $value)>
                            {{ $label }}
                        </option>
                        @endforeach

                    </select>

                    @error('canal_pedido')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>


                {{-- Portal externo --}}
                <div class="col-md-6" id="portalUrlContainer">
                    <label for="portal_url" class="form-label fw-semibold">
                        URL del portal de pedidos
                    </label>

                    <input
                        type="url"
                        class="form-control rounded-3 @error('portal_url') is-invalid @enderror"
                        id="portal_url"
                        name="portal_url"
                        value="{{ old('portal_url', $proveedor->portal_url) }}"
                        maxlength="255"
                        placeholder="https://ejemplo.com"
                        disabled>

                    <div class="form-text">
                        Solo se utiliza cuando el canal de pedido es un portal externo.
                    </div>

                    @error('portal_url')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>


                {{-- Plazo de entrega --}}
                <div class="col-md-6">
                    <label for="plazo_entrega_dias" class="form-label fw-semibold">
                        Plazo de entrega
                    </label>

                    <div class="input-group">
                        <input
                            type="number"
                            class="form-control rounded-start-3 @error('plazo_entrega_dias') is-invalid @enderror"
                            id="plazo_entrega_dias"
                            name="plazo_entrega_dias"
                            value="{{ old('plazo_entrega_dias', $proveedor->plazo_entrega_dias) }}"
                            min="0"
                            max="365"
                            disabled>

                        <span class="input-group-text rounded-end-3">
                            días
                        </span>
                    </div>

                    @error('plazo_entrega_dias')
                    <div class="text-danger small mt-1">
                        {{ $message }}
                    </div>
                    @enderror
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
                        class="form-control rounded-3 @error('direccion.calle') is-invalid @enderror"
                        id="direccion_calle"
                        name="direccion[calle]"
                        value="{{ old('direccion.calle', $direccion?->calle) }}"
                        maxlength="120"
                        disabled>

                    @error('direccion.calle')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>


                {{-- Número --}}
                <div class="col-md-4">
                    <label for="direccion_numero" class="form-label fw-semibold">
                        Número
                    </label>

                    <input
                        type="text"
                        class="form-control rounded-3 @error('direccion.numero') is-invalid @enderror"
                        id="direccion_numero"
                        name="direccion[numero]"
                        value="{{ old('direccion.numero', $direccion?->numero) }}"
                        maxlength="10"
                        disabled>

                    @error('direccion.numero')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>


                {{-- Piso / Departamento --}}
                <div class="col-md-4">
                    <label for="direccion_piso_depto" class="form-label fw-semibold">
                        Piso / Departamento
                    </label>

                    <input
                        type="text"
                        class="form-control rounded-3 @error('direccion.piso_depto') is-invalid @enderror"
                        id="direccion_piso_depto"
                        name="direccion[piso_depto]"
                        value="{{ old('direccion.piso_depto', $direccion?->piso_depto) }}"
                        maxlength="20"
                        disabled>

                    @error('direccion.piso_depto')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>


                {{-- Código postal --}}
                <div class="col-md-4">
                    <label for="direccion_codigo_postal" class="form-label fw-semibold">
                        Código postal
                    </label>

                    <input
                        type="text"
                        class="form-control rounded-3 @error('direccion.codigo_postal') is-invalid @enderror"
                        id="direccion_codigo_postal"
                        name="direccion[codigo_postal]"
                        value="{{ old('direccion.codigo_postal', $direccion?->codigo_postal) }}"
                        maxlength="10"
                        disabled>

                    @error('direccion.codigo_postal')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>


                {{-- Localidad --}}
                <div class="col-md-4">
                    <label for="direccion_localidad" class="form-label fw-semibold">
                        Localidad
                    </label>

                    <input
                        type="text"
                        class="form-control rounded-3 @error('direccion.localidad') is-invalid @enderror"
                        id="direccion_localidad"
                        name="direccion[localidad]"
                        value="{{ old('direccion.localidad', $direccion?->localidad) }}"
                        maxlength="80"
                        disabled>

                    @error('direccion.localidad')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>


                {{-- Provincia --}}
                <div class="col-md-4">
                    <label for="direccion_provincia" class="form-label fw-semibold">
                        Provincia
                    </label>

                    <input
                        type="text"
                        class="form-control rounded-3 @error('direccion.provincia') is-invalid @enderror"
                        id="direccion_provincia"
                        name="direccion[provincia]"
                        value="{{ old('direccion.provincia', $direccion?->provincia) }}"
                        maxlength="60"
                        disabled>

                    @error('direccion.provincia')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
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
                form="editarProveedorForm"
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


            {{-- Baja / Alta --}}
            @if($proveedor->activo)

            <form
                action="{{ route('gestion.proveedores.darDeBaja', ['proveedor' => $proveedor->id]) }}"
                method="POST"
                class="d-inline">

                @csrf
                @method('PATCH')

                <button
                    type="submit"
                    class="btn btn-danger px-4 fw-semibold shadow-sm"
                    onclick="return confirm('¿Está seguro de que desea dar de baja este proveedor?')">
                    Dar de baja
                </button>

            </form>

            @else

            <form
                action="{{ route('gestion.proveedors.reactivar', ['proveedor' => $proveedor->id]) }}"
                method="POST"
                class="d-inline">

                @csrf
                @method('PATCH')

                <button
                    type="submit"
                    class="btn btn-success px-4 fw-semibold shadow-sm"
                    onclick="return confirm('¿Está seguro de que desea dar de alta este proveedor?')">
                    Dar de alta
                </button>

            </form>

            @endif

        </div>

    </section>


</div>

@endsection


@push('scripts')

<script>
    document.addEventListener('DOMContentLoaded', function() {

        const form = document.getElementById('editarProveedorForm');
        const btnEditar = document.getElementById('btnEditar');
        const btnActualizar = document.getElementById('btnActualizar');
        const btnCancelar = document.getElementById('btnCancelar');

        const campos = form.querySelectorAll(
            'input:not([type="hidden"]), select, textarea'
        );

        // Guardamos los valores originales para poder cancelar.
        const valoresOriginales = {};

        campos.forEach(campo => {

            if (campo.type === 'checkbox') {

                valoresOriginales[campo.id] = campo.checked;

            } else if (campo.type !== 'file') {

                valoresOriginales[campo.id] = campo.value;

            }

        });


        function actualizarPortalUrl() {

            const canal = document.getElementById('canal_pedido');
            const portal = document.getElementById('portal_url');

            if (!canal || !portal) {
                return;
            }

            /*
             * El valor esperado para PortalExterno es obtenido desde
             * CanalPedido::PortalExterno->value.
             *
             * Si el enum cambia ese valor, modificar esta constante.
             */
            const esPortalExterno = canal.value === 'portal_externo';

            portal.closest('.col-md-6').style.display =
                esPortalExterno ? '' : 'none';

        }


        function activarEdicion() {

            campos.forEach(campo => {

                if (campo.type !== 'hidden') {
                    campo.disabled = false;
                }

            });

            btnEditar.disabled = true;
            btnActualizar.disabled = false;
            btnCancelar.disabled = false;

            actualizarPortalUrl();
        }


        function cancelarEdicion() {

            campos.forEach(campo => {

                if (campo.type === 'file') {

                    campo.value = '';
                    campo.disabled = true;

                    return;
                }

                if (campo.type === 'checkbox') {

                    campo.checked = valoresOriginales[campo.id];

                } else {

                    campo.value = valoresOriginales[campo.id];

                }

                campo.disabled = true;

            });

            btnEditar.disabled = false;
            btnActualizar.disabled = true;
            btnCancelar.disabled = true;

            actualizarPortalUrl();
        }


        btnEditar.addEventListener('click', activarEdicion);

        btnCancelar.addEventListener('click', cancelarEdicion);


        /*
         * Si cambia el canal de pedido mientras se está editando,
         * mostramos u ocultamos el campo de portal.
         */
        const canalPedido = document.getElementById('canal_pedido');

        if (canalPedido) {

            canalPedido.addEventListener('change', actualizarPortalUrl);

        }


        // Estado inicial.
        actualizarPortalUrl();

    });
</script>

@endpush
