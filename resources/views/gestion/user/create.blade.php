@extends('layouts.gestion')

@section('title', 'Nuevo empleado')

@section('content')

<!-- Contenido principal -->

<div class="container py-4">


    <!-- Título -->
    <section class="mb-4">
        <h1 class="h3 fw-bold mb-2">Alta de nuevo empleado</h1>
        <p class="text-muted">
            Complete los datos para registrar un nuevo empleado y su cuenta de acceso.
        </p>
    </section>

    <!-- Formulario -->
    <section class="mb-4">
        <form action="{{ route('gestion.empleados.store') }}" method="POST" autocomplete="off" class="p-4 rounded-4 bg-light shadow-sm border">

            @csrf

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

                <!-- Apellido -->
                <div class="col-md-6">
                    <label for="apellido" class="form-label fw-semibold">Apellido</label>

                    <input type="text" class="form-control rounded-3 @error('apellido') is-invalid @enderror" id="apellido" name="apellido" value="{{ old('apellido', $usuario->apellido) }}" placeholder="Ej: Spinetta" required maxlength="80" autofocus>

                    @error('apellido')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <!-- Nombres -->
                <div class="col-md-6">
                    <label for="nombres" class="form-label fw-semibold">
                        Nombres
                    </label>

                    <input
                        type="text" class="form-control rounded-3 @error('nombres') is-invalid @enderror" id="nombres" name="nombres" value="{{ old('nombres', $usuario->nombres) }}" placeholder="Ej: Luis Alberto" required maxlength="80">

                    @error('nombres')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <!-- Correo -->
                <div class="col-md-6">
                    <label for="email" class="form-label fw-semibold">
                        Correo electrónico
                    </label>

                    <input type="email" class="form-control rounded-3 @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $usuario->email) }}" placeholder="Ej: luis.spinetta@powertech.com.ar" required maxlength="150">

                    @error('email')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <!-- Cargo -->
                <div class="col-md-6">
                    <label for="rol_id" class="form-label fw-semibold">
                        Cargo
                    </label>

                    <select id="rol_id" name="rol_id" class="form-select rounded-3 @error('rol_id') is-invalid @enderror" required>

                        <option value="" hidden>
                            Seleccione cargo
                        </option>

                        @foreach ($roles as $rol)
                        <option
                            value="{{ $rol->id }}"
                            {{ old('rol_id', $usuario->rol_id) == $rol->id ? 'selected' : '' }}>
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

                <!-- Separador -->
                <div class="col-12">
                    <hr class="my-4">
                    <h2 class="h5 fw-bold mb-0 mt-2">
                        Datos del empleado
                    </h2>
                </div>

                <!-- Legajo -->
                <div class="col-md-6">
                    <label for="legajo" class="form-label fw-semibold">
                        Legajo
                    </label>

                    <input type="text" class="form-control rounded-3 @error('legajo') is-invalid @enderror" id="legajo" name="legajo" value="{{ old('legajo', $empleado->legajo) }}" placeholder="Ej: EMP001" required maxlength="20">

                    @error('legajo')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <!-- Tipo documento -->
                <div class="col-md-6">
                    <label for="tipo_documento_id" class="form-label fw-semibold">
                        Tipo de documento
                    </label>

                    <select id="tipo_documento_id" name="tipo_documento_id" class="form-select rounded-3 @error('tipo_documento_id') is-invalid @enderror" required>

                        <option value="" hidden>
                            Seleccione tipo de documento
                        </option>

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
                    <label for="nro_doc" class="form-label fw-semibold">
                        Número de documento
                    </label>

                    <input type="text" class="form-control rounded-3 @error('nro_doc') is-invalid @enderror" id="nro_doc" name="nro_doc" value="{{ old('nro_doc', $empleado->nro_doc) }}" placeholder="Ej: 12345678" required maxlength="20">

                    @error('nro_doc')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <!-- CUIL -->
                <div class="col-md-6">
                    <label for="cuil" class="form-label fw-semibold">
                        CUIL
                    </label>

                    <input type="text" class="form-control rounded-3 @error('cuil') is-invalid @enderror" id="cuil" name="cuil" value="{{ old('cuil', $empleado->cuil) }}" placeholder="Ej: 20-12345678-3" maxlength="13" required>

                    @error('cuil')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <!-- Fecha de nacimiento -->
                <div class="col-md-6">
                    <label for="fecha_nacimiento" class="form-label fw-semibold">
                        Fecha de nacimiento
                    </label>

                    <input type="date" class="form-control rounded-3 @error('fecha_nacimiento') is-invalid @enderror" id="fecha_nacimiento" name="fecha_nacimiento" value="{{ old('fecha_nacimiento', $empleado->fecha_nacimiento?->format('Y-m-d')) }}">

                    @error('fecha_nacimiento')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <!-- Teléfono -->
                <div class="col-md-6">
                    <label for="telefono" class="form-label fw-semibold">
                        Teléfono
                    </label>

                    <input type="text" class="form-control rounded-3 @error('telefono') is-invalid @enderror" id="telefono" name="telefono" value="{{ old('telefono', $empleado->telefono) }}" placeholder="Ej: 297 4123456" maxlength="30">

                    @error('telefono')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <!-- Fecha de ingreso -->
                <div class="col-md-6">
                    <label for="fecha_ingreso" class="form-label fw-semibold">
                        Fecha de ingreso
                    </label>

                    <input type="date" class="form-control rounded-3 @error('fecha_ingreso') is-invalid @enderror" id="fecha_ingreso" name="fecha_ingreso" value="{{ old('fecha_ingreso', $empleado->fecha_ingreso?->format('Y-m-d')) }}" required>

                    @error('fecha_ingreso')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <!-- Separador -->
                <div class="col-12">
                    <hr class="my-4">
                    <h2 class="h5 fw-bold mb-0 mt-2">
                        Domicilio particular
                    </h2>
                </div>

                <!-- Calle -->
                <div class="col-md-6">
                    <label for="calle" class="form-label fw-semibold">
                        Calle
                    </label>

                    <input type="text" class="form-control rounded-3 @error('direccion.calle') is-invalid @enderror" id="calle" name="direccion[calle]" value="{{ old('direccion.calle', $direccion?->calle) }}" placeholder="Ej: Av. San Martín" maxlength="120">

                    @error('direccion.calle')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <!-- Número -->
                <div class="col-md-3">
                    <label for="numero" class="form-label fw-semibold">
                        Número
                    </label>

                    <input type="text" class="form-control rounded-3 @error('direccion.numero') is-invalid @enderror" id="numero" name="direccion[numero]" value="{{ old('direccion.numero', $direccion?->numero) }}" placeholder="Ej: 123" maxlength="10">

                    @error('direccion.numero')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <!-- Piso / Departamento -->
                <div class="col-md-3">
                    <label for="piso_depto" class="form-label fw-semibold">
                        Piso / Departamento
                    </label>

                    <input type="text" class="form-control rounded-3 @error('direccion.piso_depto') is-invalid @enderror" id="piso_depto" name="direccion[piso_depto]" value="{{ old('direccion.piso_depto', $direccion?->piso_depto) }}" placeholder="Ej: 2° B" maxlength="20">

                    @error('direccion.piso_depto')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <!-- Código postal -->
                <div class="col-md-4">
                    <label for="codigo_postal" class="form-label fw-semibold">
                        Código postal
                    </label>

                    <input type="text" class="form-control rounded-3 @error('direccion.codigo_postal') is-invalid @enderror" id="codigo_postal" name="direccion[codigo_postal]" value="{{ old('direccion.codigo_postal', $direccion?->codigo_postal) }}" placeholder="Ej: 9011" maxlength="10">

                    @error('direccion.codigo_postal')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <!-- Localidad -->
                <div class="col-md-4">
                    <label for="localidad" class="form-label fw-semibold">
                        Localidad
                    </label>

                    <input type="text" class="form-control rounded-3 @error('direccion.localidad') is-invalid @enderror" id="localidad" name="direccion[localidad]" value="{{ old('direccion.localidad', $direccion?->localidad) }}" placeholder="Ej: Caleta Olivia" maxlength="80">

                    @error('direccion.localidad')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <!-- Provincia -->
                <div class="col-md-4">
                    <label for="provincia" class="form-label fw-semibold">
                        Provincia
                    </label>

                    <input type="text" class="form-control rounded-3 @error('direccion.provincia') is-invalid @enderror" id="provincia" name="direccion[provincia]" value="{{ old('direccion.provincia', $direccion?->provincia) }}" placeholder="Ej: Santa Cruz" maxlength="60">

                    @error('direccion.provincia')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

            </div>

            <!-- Botones -->
            <div class="mt-5 d-flex flex-wrap gap-3">

                <button type="submit" class="btn btn-success px-4 fw-semibold shadow-sm">
                    Guardar y enviar invitación
                </button>

                <a href="{{ route('gestion.empleados.index') }}" class="btn btn-outline-secondary px-4 fw-semibold shadow-sm">
                    Volver al listado
                </a>

            </div>

        </form>
    </section>


</div>

@endsection