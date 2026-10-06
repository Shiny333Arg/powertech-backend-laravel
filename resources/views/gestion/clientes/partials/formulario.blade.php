{{--
    Ficha del cliente. La comparten el alta y la edición.

    Variables que recibe:
      $accion, $metodo ('POST'|'PUT'), $cliente (o null), $tiposDocumento, $condicionesIva
--}}

<form
    action="{{ $accion }}"
    method="POST"
    autocomplete="off"
    class="p-4 rounded-4 bg-light shadow-sm border">

    @csrf

    @if ($metodo === 'PUT')
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


    <div class="row g-4">

        {{-- Razón social --}}
        <div class="col-12">

            <label for="razon_social" class="form-label fw-semibold">
                Razón social o nombre
            </label>

            <input
                type="text"
                class="form-control rounded-3 @error('razon_social') is-invalid @enderror"
                id="razon_social"
                name="razon_social"
                value="{{ old('razon_social', $cliente?->razon_social) }}"
                maxlength="150"
                placeholder="Ej: Distribuidora del Sur S.R.L."
                required>

            @error('razon_social')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
            @enderror

        </div>


        {{-- Tipo de documento --}}
        <div class="col-md-6">

            <label for="tipo_documento_id" class="form-label fw-semibold">
                Tipo de documento
            </label>

            <select
                id="tipo_documento_id"
                name="tipo_documento_id"
                class="form-select rounded-3 @error('tipo_documento_id') is-invalid @enderror"
                required>

                <option value="">Seleccione un tipo</option>

                @foreach ($tiposDocumento as $tipo)

                <option
                    value="{{ $tipo->id }}"
                    @selected(old('tipo_documento_id', $cliente?->tipo_documento_id) == $tipo->id)>

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
        <div class="col-md-6">

            <label for="nro_doc" class="form-label fw-semibold">
                Número de documento
            </label>

            <input
                type="text"
                class="form-control rounded-3 @error('nro_doc') is-invalid @enderror @error('nro_doc_unico') is-invalid @enderror"
                id="nro_doc"
                name="nro_doc"
                value="{{ old('nro_doc', $cliente?->nro_doc) }}"
                maxlength="20"
                inputmode="numeric"
                placeholder="Ej: 20304050607"
                required>

            <div class="form-text">
                Sin puntos ni guiones.
            </div>

            @error('nro_doc')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
            @enderror

            @error('nro_doc_unico')
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

                <option value="">Seleccione una condición</option>

                @foreach ($condicionesIva as $condicion)

                <option
                    value="{{ $condicion->id }}"
                    @selected(old('condicion_iva_id', $cliente?->condicion_iva_id) == $condicion->id)>

                    {{ $condicion->nombre }}

                </option>

                @endforeach

            </select>

            <div class="form-text">
                Responsable inscripto recibe factura A; el resto, B.
            </div>

            @error('condicion_iva_id')
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
                value="{{ old('telefono', $cliente?->telefono) }}"
                maxlength="30"
                placeholder="Ej: 297 400 0000">

            @error('telefono')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
            @enderror

        </div>

    </div>


    {{-- Botones --}}
    <div class="mt-5 d-flex flex-wrap gap-3">

        <button type="submit" class="btn btn-success px-4 fw-semibold shadow-sm">
            {{ $metodo === 'PUT' ? 'Guardar cambios' : 'Guardar' }}
        </button>

        <a
            href="{{ url()->previous() }}"
            class="btn btn-outline-secondary px-4 fw-semibold shadow-sm">
            Volver
        </a>

    </div>

</form>
