@extends('layouts.app')

@section('title', 'PowerTech')

@section('content')

<!-- Contenido principal -->

<div class="container py-4">


<!-- Título -->
<section class="mb-4">
    <h1 class="h3 fw-bold mb-2">Editar empleado</h1>
    <p class="text-muted">
        Los campos están bloqueados por defecto.
    </p>
</section>

<!-- Formulario -->
<section class="mb-4">
    <form
        id="editarEmpleadoForm"
        action="{{ route('user.update', $empleado) }}"
        method="POST"
        autocomplete="off"
        class="p-4 rounded-4 bg-light shadow-sm border">

        @csrf
        @method('PUT')

        {{-- Errores generales de validación --}}
        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>Revisá estos campos:</strong>

                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row g-4">

            <!-- Estado -->
            <div class="col-md-6">
                <label
                    for="estado"
                    class="form-label fw-semibold">
                    Estado de la cuenta
                </label>

                <input
                    type="text"
                    class="form-control rounded-3"
                    id="estado"
                    value="{{ $empleado->user->activo ? 'Activo' : 'Inactivo' }}"
                    disabled>
            </div>

            <!-- Fecha de creación -->
            <div class="col-md-6">
                <label
                    for="fecha_creacion"
                    class="form-label fw-semibold">
                    Fecha de creación
                </label>

                <input
                    type="text"
                    class="form-control-plaintext"
                    id="fecha_creacion"
                    value="{{ $empleado->user->created_at?->format('d/m/Y H:i') }}"
                    disabled>
            </div>

            <!-- Apellido -->
            <div class="col-md-6">
                <label
                    for="apellido"
                    class="form-label fw-semibold">
                    Apellido
                </label>

                <input
                    type="text"
                    class="form-control rounded-3 @error('apellido') is-invalid @enderror"
                    id="apellido"
                    name="apellido"
                    value="{{ old('apellido', $empleado->user->apellido) }}"
                    required
                    maxlength="80"
                    disabled>

                @error('apellido')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <!-- Nombres -->
            <div class="col-md-6">
                <label
                    for="nombres"
                    class="form-label fw-semibold">
                    Nombres
                </label>

                <input
                    type="text"
                    class="form-control rounded-3 @error('nombres') is-invalid @enderror"
                    id="nombres"
                    name="nombres"
                    value="{{ old('nombres', $empleado->user->nombres) }}"
                    required
                    maxlength="80"
                    disabled>

                @error('nombres')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <!-- Usuario -->
            <div class="col-md-6">
                <label
                    for="usuario"
                    class="form-label fw-semibold">
                    Nombre de usuario
                </label>

                <input
                    type="text"
                    class="form-control rounded-3 @error('usuario') is-invalid @enderror"
                    id="usuario"
                    name="usuario"
                    value="{{ old('usuario', $empleado->user->usuario) }}"
                    required
                    maxlength="30"
                    disabled>

                @error('usuario')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <!-- Cargo -->
            <div class="col-md-6">
                <label
                    for="rol_id"
                    class="form-label fw-semibold">
                    Cargo
                </label>

                <select
                    id="rol_id"
                    name="rol_id"
                    class="form-select rounded-3 @error('rol_id') is-invalid @enderror"
                    required
                    disabled>

                    @foreach ($roles as $rol)
                        <option
                            value="{{ $rol->id }}"
                            {{ old('rol_id', $empleado->user->rol_id) == $rol->id ? 'selected' : '' }}>
                            {{ ucfirst($rol->nombre) }}
                        </option>
                    @endforeach

                </select>

                @error('rol_id')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <!-- Correo -->
            <div class="col-md-6">
                <label
                    for="email"
                    class="form-label fw-semibold">
                    Correo electrónico
                </label>

                <input
                    type="email"
                    class="form-control rounded-3 @error('email') is-invalid @enderror"
                    id="email"
                    name="email"
                    value="{{ old('email', $empleado->user->email) }}"
                    maxlength="150"
                    disabled>

                @error('email')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <!-- Legajo -->
            <div class="col-md-6">
                <label
                    for="legajo"
                    class="form-label fw-semibold">
                    Legajo
                </label>

                <input
                    type="text"
                    class="form-control rounded-3 @error('legajo') is-invalid @enderror"
                    id="legajo"
                    name="legajo"
                    value="{{ old('legajo', $empleado->legajo) }}"
                    required
                    maxlength="20"
                    disabled>

                @error('legajo')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <!-- Tipo de documento -->
            <div class="col-md-6">
                <label
                    for="tipo_documento_id"
                    class="form-label fw-semibold">
                    Tipo de documento
                </label>

                <select
                    id="tipo_documento_id"
                    name="tipo_documento_id"
                    class="form-select rounded-3 @error('tipo_documento_id') is-invalid @enderror"
                    required
                    disabled>

                    @foreach ($tiposDocumento as $tipoDocumento)
                        <option
                            value="{{ $tipoDocumento->id }}"
                            {{ old('tipo_documento_id', $empleado->tipo_documento_id) == $tipoDocumento->id ? 'selected' : '' }}>
                            {{ $tipoDocumento->nombre }}
                        </option>
                    @endforeach

                </select>

                @error('tipo_documento_id')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <!-- Número documento -->
            <div class="col-md-6">
                <label
                    for="nro_doc"
                    class="form-label fw-semibold">
                    Número de documento
                </label>

                <input
                    type="text"
                    class="form-control rounded-3 @error('nro_doc') is-invalid @enderror"
                    id="nro_doc"
                    name="nro_doc"
                    value="{{ old('nro_doc', $empleado->nro_doc) }}"
                    required
                    maxlength="20"
                    disabled>

                @error('nro_doc')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <!-- CUIL -->
            <div class="col-md-6">
                <label
                    for="cuil"
                    class="form-label fw-semibold">
                    CUIL
                </label>

                <input
                    type="text"
                    class="form-control rounded-3 @error('cuil') is-invalid @enderror"
                    id="cuil"
                    name="cuil"
                    value="{{ old('cuil', $empleado->cuil) }}"
                    maxlength="13"
                    required
                    disabled>

                @error('cuil')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <!-- Fecha de nacimiento -->
            <div class="col-md-6">
                <label
                    for="fecha_nacimiento"
                    class="form-label fw-semibold">
                    Fecha de nacimiento
                </label>

                <input
                    type="date"
                    class="form-control rounded-3 @error('fecha_nacimiento') is-invalid @enderror"
                    id="fecha_nacimiento"
                    name="fecha_nacimiento"
                    value="{{ old('fecha_nacimiento', $empleado->fecha_nacimiento?->format('Y-m-d')) }}"
                    disabled>

                @error('fecha_nacimiento')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <!-- Teléfono -->
            <div class="col-md-6">
                <label
                    for="telefono"
                    class="form-label fw-semibold">
                    Teléfono
                </label>

                <input
                    type="text"
                    class="form-control rounded-3 @error('telefono') is-invalid @enderror"
                    id="telefono"
                    name="telefono"
                    value="{{ old('telefono', $empleado->telefono) }}"
                    maxlength="30"
                    disabled>

                @error('telefono')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <!-- Fecha de ingreso -->
            <div class="col-md-6">
                <label
                    for="fecha_ingreso"
                    class="form-label fw-semibold">
                    Fecha de ingreso
                </label>

                <input
                    type="date"
                    class="form-control rounded-3 @error('fecha_ingreso') is-invalid @enderror"
                    id="fecha_ingreso"
                    name="fecha_ingreso"
                    value="{{ old('fecha_ingreso', $empleado->fecha_ingreso?->format('Y-m-d')) }}"
                    required
                    disabled>

                @error('fecha_ingreso')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <!-- Domicilio -->
            <div class="col-12">
                <h2 class="h5 fw-bold mb-0 mt-2">
                    Domicilio particular
                </h2>
            </div>

            <!-- Calle -->
            <div class="col-md-6">
                <label
                    for="calle"
                    class="form-label fw-semibold">
                    Calle
                </label>

                <input
                    type="text"
                    class="form-control rounded-3 @error('direccion.calle') is-invalid @enderror"
                    id="calle"
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

            <!-- Número -->
            <div class="col-md-3">
                <label
                    for="numero"
                    class="form-label fw-semibold">
                    Número
                </label>

                <input
                    type="text"
                    class="form-control rounded-3 @error('direccion.numero') is-invalid @enderror"
                    id="numero"
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

            <!-- Piso / Departamento -->
            <div class="col-md-3">
                <label
                    for="piso_depto"
                    class="form-label fw-semibold">
                    Piso / Departamento
                </label>

                <input
                    type="text"
                    class="form-control rounded-3 @error('direccion.piso_depto') is-invalid @enderror"
                    id="piso_depto"
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

            <!-- Código postal -->
            <div class="col-md-4">
                <label
                    for="codigo_postal"
                    class="form-label fw-semibold">
                    Código postal
                </label>

                <input
                    type="text"
                    class="form-control rounded-3 @error('direccion.codigo_postal') is-invalid @enderror"
                    id="codigo_postal"
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

            <!-- Localidad -->
            <div class="col-md-4">
                <label
                    for="localidad"
                    class="form-label fw-semibold">
                    Localidad
                </label>

                <input
                    type="text"
                    class="form-control rounded-3 @error('direccion.localidad') is-invalid @enderror"
                    id="localidad"
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

            <!-- Provincia -->
            <div class="col-md-4">
                <label
                    for="provincia"
                    class="form-label fw-semibold">
                    Provincia
                </label>

                <input
                    type="text"
                    class="form-control rounded-3 @error('direccion.provincia') is-invalid @enderror"
                    id="provincia"
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

        <!-- Botones -->
        <div class="mt-5 d-flex flex-wrap gap-3">

            <button
                type="button"
                class="btn btn-warning px-4 fw-semibold shadow-sm"
                id="btnEditar">
                Editar
            </button>

            <button
                type="submit"
                class="btn btn-success px-4 fw-semibold shadow-sm"
                id="btnActualizar"
                disabled>
                Actualizar
            </button>

            <button
                type="button"
                class="btn btn-secondary px-4 fw-semibold shadow-sm"
                id="btnCancelar"
                disabled>
                Cancelar
            </button>

            <a
                href="{{ route('user.index') }}"
                class="btn btn-outline-secondary px-4 fw-semibold shadow-sm">
                Volver al listado
            </a>

        </div>

    </form>
</section>


</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {

        const form = document.getElementById('editarEmpleadoForm');
        const btnEditar = document.getElementById('btnEditar');
        const btnActualizar = document.getElementById('btnActualizar');
        const btnCancelar = document.getElementById('btnCancelar');

        const camposEditables = form.querySelectorAll(
            'input:not([type="hidden"]):not(#estado):not(#fecha_creacion), select'
        );

        // Guardamos los valores originales para poder cancelar.
        const valoresOriginales = new Map();

        camposEditables.forEach(campo => {
            valoresOriginales.set(campo.name, campo.value);
        });

        function habilitarEdicion() {
            camposEditables.forEach(campo => {
                campo.disabled = false;
            });

            btnEditar.disabled = true;
            btnActualizar.disabled = false;
            btnCancelar.disabled = false;
        }

        function cancelarEdicion() {
            camposEditables.forEach(campo => {
                if (valoresOriginales.has(campo.name)) {
                    campo.value = valoresOriginales.get(campo.name);
                }

                campo.disabled = true;
            });

            btnEditar.disabled = false;
            btnActualizar.disabled = true;
            btnCancelar.disabled = true;
        }

        btnEditar.addEventListener('click', habilitarEdicion);
        btnCancelar.addEventListener('click', cancelarEdicion);

    });
</script>

@endsection
