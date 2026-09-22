{{--
    Perfil del CLIENTE (ProfileController). Dos formularios independientes:
    datos (PATCH profile.update) y contraseña (PUT password.update, con su
    propia bolsa de errores 'updatePassword').

    No hay "eliminar cuenta": los usuarios no se borran nunca.
--}}
@extends('layouts.cliente')

@section('title', 'Mi perfil')

@section('content')

<div class="row justify-content-center">
    <div class="col-lg-8">

        <h1 class="h3 fw-bold mb-1">Mi perfil</h1>
        <p class="text-muted mb-4">Tus datos de contacto y los que usamos para facturarte.</p>

        {{-- ======================================================
             DATOS PERSONALES Y FISCALES
             ====================================================== --}}
        <form method="POST" action="{{ route('profile.update') }}" class="card shadow-sm border-0 rounded-4 mb-4">
            @csrf
            @method('PATCH')

            <div class="card-body p-4">
                <h2 class="h5 fw-bold mb-3">Datos personales</h2>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="nombres" class="form-label fw-semibold">Nombres</label>
                        <input type="text" id="nombres" name="nombres" value="{{ old('nombres', $user->nombres) }}"
                            class="form-control rounded-3 @error('nombres') is-invalid @enderror"
                            required maxlength="80" autocomplete="given-name">
                        @error('nombres')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="apellido" class="form-label fw-semibold">Apellido</label>
                        <input type="text" id="apellido" name="apellido" value="{{ old('apellido', $user->apellido) }}"
                            class="form-control rounded-3 @error('apellido') is-invalid @enderror"
                            required maxlength="80" autocomplete="family-name">
                        @error('apellido')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-7">
                        <label for="email" class="form-label fw-semibold">
                            Correo electrónico
                            @if ($user->hasVerifiedEmail())
                                <span class="badge text-bg-success fw-normal ms-1">Confirmado</span>
                            @else
                                <span class="badge text-bg-warning fw-normal ms-1">Sin confirmar</span>
                            @endif
                        </label>
                        <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}"
                            class="form-control rounded-3 @error('email') is-invalid @enderror"
                            required maxlength="150" autocomplete="email" aria-describedby="email-ayuda">
                        <div id="email-ayuda" class="form-text">Si lo cambiás, te vamos a pedir que confirmes el nuevo.</div>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-5">
                        <label for="telefono" class="form-label fw-semibold">Teléfono <span class="fw-normal text-muted">(opcional)</span></label>
                        <input type="tel" id="telefono" name="telefono" value="{{ old('telefono', $cliente->telefono) }}"
                            class="form-control rounded-3 @error('telefono') is-invalid @enderror"
                            maxlength="30" autocomplete="tel">
                        @error('telefono')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <hr class="my-4">

                <h2 class="h5 fw-bold mb-1">Datos para tu factura</h2>
                <p class="small text-muted mb-3">
                    Si sos responsable inscripto, monotributista o exento, identificate con tu CUIT.
                </p>

                @if ($documentoBloqueado)
                    <div class="alert alert-light border small">
                        Tu documento no se puede cambiar desde acá porque ya tenés compras registradas con él.
                        Si hay un error, comunicate con PowerTech.
                    </div>
                @endif

                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="tipo_documento_id" class="form-label fw-semibold">Documento</label>

                        {{-- Un select deshabilitado no se envia: el valor viaja en el hidden. --}}
                        @if ($documentoBloqueado)
                            <input type="hidden" name="tipo_documento_id" value="{{ $cliente->tipo_documento_id }}">
                        @endif

                        <select id="tipo_documento_id" @unless ($documentoBloqueado) name="tipo_documento_id" @endunless
                            class="form-select rounded-3 @error('tipo_documento_id') is-invalid @enderror"
                            required @disabled($documentoBloqueado)>
                            @foreach ($tiposDocumento as $tipo)
                                <option value="{{ $tipo->id }}"
                                    @selected(old('tipo_documento_id', $cliente->tipo_documento_id) == $tipo->id)>
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
                        <input type="text" id="nro_doc" name="nro_doc" value="{{ old('nro_doc', $cliente->nro_doc) }}"
                            class="form-control rounded-3 @error('nro_doc') is-invalid @enderror"
                            required maxlength="20" inputmode="numeric" @readonly($documentoBloqueado)>
                        @error('nro_doc')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="condicion_iva_id" class="form-label fw-semibold">Condición frente al IVA</label>
                        <select id="condicion_iva_id" name="condicion_iva_id" required
                            class="form-select rounded-3 @error('condicion_iva_id') is-invalid @enderror">
                            @foreach ($condicionesIva as $condicion)
                                <option value="{{ $condicion->id }}"
                                    @selected(old('condicion_iva_id', $cliente->condicion_iva_id) == $condicion->id)>
                                    {{ $condicion->nombre }}
                                </option>
                            @endforeach
                        </select>
                        @error('condicion_iva_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="razon_social" class="form-label fw-semibold">Razón social</label>
                        <input type="text" id="razon_social" name="razon_social"
                            value="{{ old('razon_social', $cliente->razon_social) }}"
                            class="form-control rounded-3 @error('razon_social') is-invalid @enderror"
                            maxlength="150" autocomplete="organization" aria-describedby="razon-social-ayuda">
                        <div id="razon-social-ayuda" class="form-text">Es el nombre que figura en tus facturas. Si lo dejás vacío, usamos tu nombre.</div>
                        @error('razon_social')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <button type="submit" class="btn btn-primary fw-semibold px-4 mt-4">Guardar cambios</button>
            </div>
        </form>

        {{-- ======================================================
             CONTRASEÑA
             ====================================================== --}}
        <form method="POST" action="{{ route('password.update') }}" class="card shadow-sm border-0 rounded-4">
            @csrf
            @method('PUT')

            <div class="card-body p-4">
                <h2 class="h5 fw-bold mb-3">Contraseña</h2>

                @if ($user->password !== null)
                    <x-password-input name="current_password" id="current_password" label="Contraseña actual"
                        autocomplete="current-password" bag="updatePassword" />
                @else
                    {{-- Cliente registrado con Google: todavia no tiene contraseña propia. --}}
                    <p class="small text-muted">
                        Hoy ingresás con Google. Si elegís una contraseña, también vas a poder ingresar con tu correo.
                    </p>
                @endif

                <div class="row g-3">
                    <div class="col-md-6">
                        <x-password-input name="password" id="nueva_password" label="Nueva contraseña"
                            autocomplete="new-password" bag="updatePassword" ayuda="Al menos 8 caracteres." class="mb-0" />
                    </div>
                    <div class="col-md-6">
                        <x-password-input name="password_confirmation" id="nueva_password_confirmation"
                            label="Repetí la nueva contraseña" autocomplete="new-password" bag="updatePassword" class="mb-0" />
                    </div>
                </div>

                <button type="submit" class="btn btn-primary fw-semibold px-4 mt-4">Cambiar contraseña</button>
            </div>
        </form>

    </div>
</div>

@endsection
