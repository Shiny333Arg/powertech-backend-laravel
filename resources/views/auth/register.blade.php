{{--
    Registro de CLIENTES. Pide ademas los datos fiscales, porque sin ellos no
    se le puede facturar (RegistroClienteRequest).
--}}
@extends('layouts.auth')

@section('title', 'Crear cuenta')
@section('titulo', 'Creá tu cuenta')
@section('ancho', '680px')

@php
    $reglas = \App\Http\Requests\Auth\RegistroClienteRequest::class;

    // Preseleccion: lo mas comun es consumidor final con DNI.
    $dniId             = $tiposDocumento->firstWhere('codigo_afip', $reglas::DOC_DNI)?->id;
    $consumidorFinalId = $condicionesIva->firstWhere('codigo_afip', $reglas::IVA_CONSUMIDOR_FINAL)?->id;
@endphp

@section('content')

<form method="POST" action="{{ route('register') }}">
    @csrf

    <div class="row g-3">

        {{-- ================= Tus datos ================= --}}

        <div class="col-md-6">
            <label for="nombres" class="form-label fw-semibold">Nombres</label>
            <input type="text" id="nombres" name="nombres" value="{{ old('nombres') }}"
                class="form-control rounded-3 @error('nombres') is-invalid @enderror"
                required autofocus maxlength="80" autocomplete="given-name">
            @error('nombres')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6">
            <label for="apellido" class="form-label fw-semibold">Apellido</label>
            <input type="text" id="apellido" name="apellido" value="{{ old('apellido') }}"
                class="form-control rounded-3 @error('apellido') is-invalid @enderror"
                required maxlength="80" autocomplete="family-name">
            @error('apellido')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-7">
            <label for="email" class="form-label fw-semibold">Correo electrónico</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}"
                class="form-control rounded-3 @error('email') is-invalid @enderror"
                required maxlength="150" autocomplete="email" aria-describedby="email-ayuda">
            <div id="email-ayuda" class="form-text">Con este correo vas a ingresar. Te vamos a pedir que lo confirmes.</div>
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-5">
            <label for="telefono" class="form-label fw-semibold">Teléfono <span class="fw-normal text-muted">(opcional)</span></label>
            <input type="tel" id="telefono" name="telefono" value="{{ old('telefono') }}"
                class="form-control rounded-3 @error('telefono') is-invalid @enderror"
                maxlength="30" autocomplete="tel">
            @error('telefono')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6">
            <x-password-input name="password" label="Contraseña" autocomplete="new-password"
                ayuda="Al menos 8 caracteres." class="mb-0" />
        </div>

        <div class="col-md-6">
            <x-password-input name="password_confirmation" label="Repetí la contraseña" autocomplete="new-password"
                class="mb-0" />
        </div>

        {{-- ================= Datos para tu factura ================= --}}

        <div class="col-12 mt-4">
            <h2 class="h6 fw-bold mb-1">Datos para tu factura</h2>
            <p class="small text-muted mb-0">
                Los necesitamos para emitir la factura de tus compras. Si sos responsable inscripto,
                monotributista o exento, identificate con tu CUIT.
            </p>
        </div>

        <div class="col-md-4">
            <label for="tipo_documento_id" class="form-label fw-semibold">Documento</label>
            <select id="tipo_documento_id" name="tipo_documento_id" required
                class="form-select rounded-3 @error('tipo_documento_id') is-invalid @enderror">
                @foreach ($tiposDocumento as $tipo)
                    <option value="{{ $tipo->id }}" @selected(old('tipo_documento_id', $dniId) == $tipo->id)>
                        {{ $tipo->nombre }}
                    </option>
                @endforeach
            </select>
            @error('tipo_documento_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-8">
            <label for="nro_doc" class="form-label fw-semibold">Número</label>
            <input type="text" id="nro_doc" name="nro_doc" value="{{ old('nro_doc') }}"
                class="form-control rounded-3 @error('nro_doc') is-invalid @enderror"
                required maxlength="20" inputmode="numeric" placeholder="Sin puntos ni guiones">
            @error('nro_doc')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6">
            <label for="condicion_iva_id" class="form-label fw-semibold">Condición frente al IVA</label>
            <select id="condicion_iva_id" name="condicion_iva_id" required
                class="form-select rounded-3 @error('condicion_iva_id') is-invalid @enderror">
                @foreach ($condicionesIva as $condicion)
                    <option value="{{ $condicion->id }}" @selected(old('condicion_iva_id', $consumidorFinalId) == $condicion->id)>
                        {{ $condicion->nombre }}
                    </option>
                @endforeach
            </select>
            @error('condicion_iva_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6">
            <label for="razon_social" class="form-label fw-semibold">Razón social <span class="fw-normal text-muted">(opcional)</span></label>
            <input type="text" id="razon_social" name="razon_social" value="{{ old('razon_social') }}"
                class="form-control rounded-3 @error('razon_social') is-invalid @enderror"
                maxlength="150" autocomplete="organization" aria-describedby="razon-social-ayuda">
            <div id="razon-social-ayuda" class="form-text">Solo si facturás a nombre de una empresa. Si lo dejás vacío, usamos tu nombre.</div>
            @error('razon_social')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

    </div>

    <button type="submit" class="btn btn-primary w-100 rounded-3 fw-semibold mt-4">Crear cuenta</button>
</form>

<hr class="my-4">

<p class="text-center small mb-0">
    ¿Ya tenés cuenta?
    <a href="{{ route('login') }}" class="fw-semibold">Ingresá</a>
</p>

@endsection
