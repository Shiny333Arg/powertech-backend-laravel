@extends('layouts.app')

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
    <form
        action="{{ route('proveedors.store') }}"
        method="POST"
        autocomplete="off"
        class="p-4 rounded-4 bg-light shadow-sm border">

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

            <!-- Razón social -->
            <div class="col-12">
                <label for="razon_social" class="form-label fw-semibold">
                    Razón social
                </label>

                <input
                    type="text"
                    class="form-control rounded-3 @error('razon_social') is-invalid @enderror"
                    id="razon_social"
                    name="razon_social"
                    value="{{ old('razon_social', $proveedor->razon_social) }}"
                    placeholder="Ej: Distribuidora Informática S.A."
                    required
                    maxlength="150"
                    autofocus>

                @error('razon_social')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <!-- CUIT -->
            <div class="col-md-6">
                <label for="cuit" class="form-label fw-semibold">
                    CUIT
                </label>

                <input
                    type="text"
                    class="form-control rounded-3 @error('cuit') is-invalid @enderror"
                    id="cuit"
                    name="cuit"
                    value="{{ old('cuit', $proveedor->cuit) }}"
                    placeholder="Ej: 30-12345678-9"
                    maxlength="13"
                    required>

                @error('cuit')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <!-- Condición IVA -->
            <div class="col-md-6">
                <label for="condicion_iva_id" class="form-label fw-semibold">
                    Condición frente al IVA
                </label>

                <select
                    class="form-select rounded-3 @error('condicion_iva_id') is-invalid @enderror"
                    id="condicion_iva_id"
                    name="condicion_iva_id"
                    required>

                    <option value="">Seleccione una condición</option>

                    @foreach ($condiciones as $condicion)
                        <option
                            value="{{ $condicion->id }}"
                            {{ old('condicion_iva_id', $proveedor->condicion_iva_id) == $condicion->id ? 'selected' : '' }}>
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

            <!-- Contacto -->
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
                    placeholder="Ej: Carlos Solari"
                    maxlength="100">

                @error('contacto')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <!-- Email -->
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
                    placeholder="Ej: contacto@proveedor.com"
                    maxlength="150">

                @error('email')
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

                <input
                    type="text"
                    class="form-control rounded-3 @error('telefono') is-invalid @enderror"
                    id="telefono"
                    name="telefono"
                    value="{{ old('telefono', $proveedor->telefono) }}"
                    placeholder="Ej: 297 4123456"
                    maxlength="30">

                @error('telefono')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <!-- Canal de pedido -->
            <div class="col-md-6">
                <label for="canal_pedido" class="form-label fw-semibold">
                    Canal de pedido
                </label>

                <select
                    class="form-select rounded-3 @error('canal_pedido') is-invalid @enderror"
                    id="canal_pedido"
                    name="canal_pedido"
                    required>

                    <option value="">Seleccione un canal</option>

                    @foreach ($canales as $valor => $nombre)
                        <option
                            value="{{ $valor }}"
                            {{ old('canal_pedido', $proveedor->canal_pedido?->value ?? $proveedor->canal_pedido) == $valor ? 'selected' : '' }}>
                            {{ $nombre }}
                        </option>
                    @endforeach
                </select>

                @error('canal_pedido')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <!-- Portal URL -->
            <div class="col-md-6">
                <label for="portal_url" class="form-label fw-semibold">
                    Portal de pedidos
                </label>

                <input
                    type="url"
                    class="form-control rounded-3 @error('portal_url') is-invalid @enderror"
                    id="portal_url"
                    name="portal_url"
                    value="{{ old('portal_url', $proveedor->portal_url) }}"
                    placeholder="Ej: https://www.proveedor.com"
                    maxlength="255">

                <div class="form-text">
                    Obligatorio si el canal de pedido es un portal externo.
                </div>

                @error('portal_url')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <!-- Plazo de entrega -->
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
                        placeholder="Ej: 7">

                    <span class="input-group-text">
                        días
                    </span>
                </div>

                @error('plazo_entrega_dias')
                    <div class="text-danger small mt-1">
                        {{ $message }}
                    </div>
                @enderror
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
                    class="form-control rounded-3 @error('direccion.calle') is-invalid @enderror"
                    id="calle"
                    name="direccion[calle]"
                    value="{{ old('direccion.calle', $direccion?->calle) }}"
                    placeholder="Ej: Av. San Martín"
                    maxlength="120">

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

                <input
                    type="text"
                    class="form-control rounded-3 @error('direccion.numero') is-invalid @enderror"
                    id="numero"
                    name="direccion[numero]"
                    value="{{ old('direccion.numero', $direccion?->numero) }}"
                    placeholder="Ej: 123"
                    maxlength="10">

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

                <input
                    type="text"
                    class="form-control rounded-3 @error('direccion.piso_depto') is-invalid @enderror"
                    id="piso_depto"
                    name="direccion[piso_depto]"
                    value="{{ old('direccion.piso_depto', $direccion?->piso_depto) }}"
                    placeholder="Ej: 2° B"
                    maxlength="20">

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

                <input
                    type="text"
                    class="form-control rounded-3 @error('direccion.codigo_postal') is-invalid @enderror"
                    id="codigo_postal"
                    name="direccion[codigo_postal]"
                    value="{{ old('direccion.codigo_postal', $direccion?->codigo_postal) }}"
                    placeholder="Ej: 9011"
                    maxlength="10">

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

                <input
                    type="text"
                    class="form-control rounded-3 @error('direccion.localidad') is-invalid @enderror"
                    id="localidad"
                    name="direccion[localidad]"
                    value="{{ old('direccion.localidad', $direccion?->localidad) }}"
                    placeholder="Ej: Caleta Olivia"
                    maxlength="80">

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

                <input
                    type="text"
                    class="form-control rounded-3 @error('direccion.provincia') is-invalid @enderror"
                    id="provincia"
                    name="direccion[provincia]"
                    value="{{ old('direccion.provincia', $direccion?->provincia) }}"
                    placeholder="Ej: Santa Cruz"
                    maxlength="60">

                @error('direccion.provincia')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <!-- Estado -->
            <div class="col-12">
                <div class="form-check">

                    <input
                        type="hidden"
                        name="activo"
                        value="0">

                    <input
                        type="checkbox"
                        class="form-check-input"
                        id="activo"
                        name="activo"
                        value="1"
                        {{ old('activo', $proveedor->activo ?? true) ? 'checked' : '' }}>

                    <label for="activo" class="form-check-label fw-semibold">
                        En operación
                    </label>

                </div>

                <div class="form-text">
                    Los proveedores fuera de operación no se utilizan para nuevas operaciones de compra.
                </div>

                @error('activo')
                    <div class="text-danger small mt-1">
                        {{ $message }}
                    </div>
                @enderror
            </div>

        </div>

        <!-- Botones -->
        <div class="mt-5 d-flex flex-wrap gap-3">

            <button
                type="submit"
                class="btn btn-success px-4 fw-semibold shadow-sm">
                Guardar
            </button>

            <a
                href="{{ route('proveedors.index') }}"
                class="btn btn-outline-secondary px-4 fw-semibold shadow-sm">
                Volver al listado
            </a>

        </div>

    </form>
</section>


</div>

@endsection
