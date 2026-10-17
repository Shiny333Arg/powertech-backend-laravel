{{--
    Ficha del cliente. La comparten el alta y la edición.

    Variables que recibe:
      $accion          URL a la que se envía
      $metodo          'POST' | 'PUT'
      $cliente         Cliente|null   (null en el alta)
      $usuario         User|null      (null en el alta; $cliente->user en la edición)
      $tiposDocumento  Collection de TipoDocumento (necesita id, nombre, codigo_afip)
      $condicionesIva  Collection de CondicionIva  (necesita id, nombre, codigo_afip)

    Un cliente son DOS filas: `users` (con qué correo entra a la tienda) y
    `clientes` (a quién se le factura). De ahí los dos bloques del formulario.

    La contraseña NO está acá a propósito. El cliente nace sin ella y la elige
    él mismo desde el enlace que le llega por correo, igual que el empleado
    invitado: así nadie del mostrador la conoce, y que complete el enlace
    demuestra que la casilla que cargó el empleado es real.

    Obligatorio es lo que exige ReglasDatosCliente. Lo demás lleva "(opcional)"
    en la etiqueta: si no se marca, el empleado lo completa igual "por las
    dudas" y el alta se vuelve lenta.
--}}

@php
    // Las constantes viven en el trait, y un trait no expone sus constantes
    // por sí mismo: hay que leerlas desde una clase que lo use.
    // ProfileController hace lo mismo con esta misma clase.
    $reglas = \App\Http\Requests\Auth\RegistroClienteRequest::class;

    $esAlta   = $metodo === 'POST';
    $usuario ??= $cliente?->user;

    // El cliente de mostrador viejo puede no tener cuenta. En ese caso no hay
    // nada que editar en el bloque de la cuenta.
    $conCuenta = $esAlta || $usuario !== null;

    // Un cliente se identifica con DNI o con CUIT; el resto del catálogo
    // (pasaporte, CDI, etc.) es para otros usos y no se ofrece acá.
    $documentos = $tiposDocumento->whereIn('codigo_afip', $reglas::TIPOS_DOCUMENTO_CLIENTE);

    // Salvo que el cliente ya tenga uno de los que no se ofrecen, de cuando
    // se aceptaban. Si no estuviera en la lista, el navegador mostraría la
    // primera opción como si fuera la suya y guardar le cambiaría el
    // documento sin que nadie lo haya pedido.
    $suyo = $tiposDocumento->firstWhere('id', $cliente?->tipo_documento_id);

    if ($suyo && ! $documentos->contains('id', $suyo->id)) {
        $documentos = $documentos->push($suyo);
    }

    // Preselección: el caso común en el mostrador es consumidor final con DNI.
    $dniId             = $documentos->firstWhere('codigo_afip', $reglas::DOC_DNI)?->id;
    $consumidorFinalId = $condicionesIva->firstWhere('codigo_afip', $reglas::IVA_CONSUMIDOR_FINAL)?->id;
@endphp

<form
    action="{{ $accion }}"
    method="POST"
    autocomplete="off"
    class="p-4 rounded-4 bg-light shadow-sm border">

    @csrf

    @if (! $esAlta)
    @method('PUT')
    @endif


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


    {{-- ===================== Datos de la cuenta ===================== --}}

    @if ($conCuenta)

    <section class="mb-4">

        <h2 class="h6 fw-bold mb-1">Datos de la cuenta</h2>

        <p class="small text-muted">
            Con este nombre y este correo el cliente va a ingresar a la tienda.
        </p>

        <div class="row g-4">

            {{-- Nombres --}}
            <div class="col-md-6">

                <label for="nombres" class="form-label fw-semibold">
                    Nombres
                </label>

                <input
                    type="text"
                    class="form-control rounded-3 @error('nombres') is-invalid @enderror"
                    id="nombres"
                    name="nombres"
                    value="{{ old('nombres', $usuario?->nombres) }}"
                    maxlength="80"
                    placeholder="Ej: Juan Carlos"
                    autocomplete="off"
                    required
                    autofocus>

                @error('nombres')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
                @enderror

            </div>


            {{-- Apellido --}}
            <div class="col-md-6">

                <label for="apellido" class="form-label fw-semibold">
                    Apellido
                </label>

                <input
                    type="text"
                    class="form-control rounded-3 @error('apellido') is-invalid @enderror"
                    id="apellido"
                    name="apellido"
                    value="{{ old('apellido', $usuario?->apellido) }}"
                    maxlength="80"
                    placeholder="Ej: Pérez"
                    autocomplete="off"
                    required>

                @error('apellido')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
                @enderror

            </div>


            {{-- Correo electrónico --}}
            <div class="col-md-7">

                <label for="email" class="form-label fw-semibold">
                    Correo electrónico
                </label>

                <input
                    type="email"
                    class="form-control rounded-3 @error('email') is-invalid @enderror"
                    id="email"
                    name="email"
                    value="{{ old('email', $usuario?->email) }}"
                    maxlength="150"
                    placeholder="Ej: jperez@correo.com"
                    autocomplete="off"
                    aria-describedby="email-ayuda"
                    required>

                <div id="email-ayuda" class="form-text">
                    @if ($esAlta)
                        Verificalo con el cliente antes de guardar: es a donde le llega el
                        enlace para activar la cuenta.
                    @else
                        Si lo cambiás, el cliente va a tener que confirmar la casilla nueva.
                    @endif
                </div>

                @error('email')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
                @enderror

            </div>


            {{-- Teléfono --}}
            <div class="col-md-5">

                <label for="telefono" class="form-label fw-semibold">
                    Teléfono
                    <span class="fw-normal text-muted">(opcional)</span>
                </label>

                <input
                    type="tel"
                    class="form-control rounded-3 @error('telefono') is-invalid @enderror"
                    id="telefono"
                    name="telefono"
                    value="{{ old('telefono', $cliente?->telefono) }}"
                    maxlength="30"
                    placeholder="Ej: 297 400 0000"
                    autocomplete="off">

                @error('telefono')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
                @enderror

            </div>

        </div>

    </section>

    @else

    {{-- Cliente de mostrador sin cuenta: no hay nada de `users` que editar. --}}
    <div class="alert alert-secondary d-flex align-items-start gap-2">

        <svg xmlns="http://www.w3.org/2000/svg"
            width="18"
            height="18"
            fill="currentColor"
            viewBox="0 0 16 16"
            class="flex-shrink-0 mt-1">

            <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2" />
        </svg>

        <div>
            Este cliente no tiene cuenta en la tienda, así que solo se editan sus
            datos de facturación.
        </div>

    </div>

    @endif


    {{-- ===================== Datos para facturar ===================== --}}

    <section class="mb-2">

        <h2 class="h6 fw-bold mb-1">Datos para facturar</h2>

        <p class="small text-muted">
            El documento y la condición frente al IVA deciden si le corresponde
            factura A o B. Sin ellos no se le puede emitir el comprobante.
        </p>

        <div class="row g-4">

            {{-- Tipo de documento --}}
            <div class="col-md-4">

                <label for="tipo_documento_id" class="form-label fw-semibold">
                    Documento
                </label>

                <select
                    id="tipo_documento_id"
                    name="tipo_documento_id"
                    class="form-select rounded-3 @error('tipo_documento_id') is-invalid @enderror"
                    required>

                    @foreach ($documentos as $tipo)

                    <option
                        value="{{ $tipo->id }}"
                        @selected(old('tipo_documento_id', $cliente?->tipo_documento_id ?? $dniId) == $tipo->id)>

                        {{ $tipo->nombre }}

                    </option>

                    @endforeach

                </select>

                @error('tipo_documento_id')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
                @enderror

            </div>


            {{-- Número de documento --}}
            <div class="col-md-8">

                <label for="nro_doc" class="form-label fw-semibold">
                    Número
                </label>

                <input
                    type="text"
                    class="form-control rounded-3 @error('nro_doc') is-invalid @enderror"
                    id="nro_doc"
                    name="nro_doc"
                    value="{{ old('nro_doc', $cliente?->nro_doc) }}"
                    maxlength="20"
                    inputmode="numeric"
                    placeholder="Sin puntos ni guiones"
                    autocomplete="off"
                    required>

                <div class="form-text">
                    DNI de 7 u 8 dígitos, o CUIT de 11.
                </div>

                @error('nro_doc')
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
                    required>

                    @foreach ($condicionesIva as $condicion)

                    <option
                        value="{{ $condicion->id }}"
                        @selected(old('condicion_iva_id', $cliente?->condicion_iva_id ?? $consumidorFinalId) == $condicion->id)>

                        {{ $condicion->nombre }}

                    </option>

                    @endforeach

                </select>

                <div class="form-text">
                    Salvo consumidor final, todas exigen CUIT.
                </div>

                @error('condicion_iva_id')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
                @enderror

            </div>


            {{-- Razón social --}}
            <div class="col-md-6">

                <label for="razon_social" class="form-label fw-semibold">
                    Razón social
                    <span class="fw-normal text-muted">(opcional)</span>
                </label>

                <input
                    type="text"
                    class="form-control rounded-3 @error('razon_social') is-invalid @enderror"
                    id="razon_social"
                    name="razon_social"
                    value="{{ old('razon_social', $cliente?->razon_social) }}"
                    maxlength="150"
                    placeholder="Ej: Distribuidora del Sur S.R.L."
                    autocomplete="off"
                    aria-describedby="razon-social-ayuda">

                <div id="razon-social-ayuda" class="form-text">
                    Solo si factura a nombre de una empresa. Si queda vacía usamos
                    el apellido y los nombres.
                </div>

                @error('razon_social')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
                @enderror

            </div>

        </div>

    </section>


    {{-- Botones --}}
    <div class="mt-5 d-flex flex-wrap gap-3">

        <button type="submit" class="btn btn-success px-4 fw-semibold shadow-sm">
            {{ $esAlta ? 'Crear cuenta' : 'Guardar cambios' }}
        </button>

        <a
            href="{{ route('gestion.clientes.index') }}"
            class="btn btn-outline-secondary px-4 fw-semibold shadow-sm">
            Volver
        </a>

    </div>

</form>