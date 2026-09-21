@extends('layouts.app')

@section('title', 'PowerTech')

@section('content')

<div class="container py-4">

    
    {{-- Título --}}
    <section class="mb-4">
        <h1 class="h3 fw-bold mb-2">Editar promoción</h1>
        <p class="text-muted">
            Los campos están bloqueados por defecto. Presione "Editar" para modificar los datos.
        </p>
    </section>

    {{-- Formulario de edición --}}
    <section class="mb-4">

        <form
            id="editarPromocionForm"
            action="{{ route('gestion.promociones.update', $promocion) }}"
            method="POST"
            enctype="multipart/form-data"
            autocomplete="off"
            class="p-4 rounded-4 bg-light shadow-sm border">

            @csrf
            @method('PUT')

            <div class="row g-4">

                {{-- Nombre --}}
                <div class="col-md-8">
                    <label for="nombre" class="form-label fw-semibold">
                        Nombre de la promoción
                    </label>

                    <input
                        type="text"
                        class="form-control rounded-3 @error('nombre') is-invalid @enderror"
                        id="nombre"
                        name="nombre"
                        value="{{ old('nombre', $promocion->nombre) }}"
                        maxlength="120"
                        required
                        disabled>

                    @error('nombre')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                {{-- Tipo --}}
                <div class="col-md-4">
                    <label for="tipo" class="form-label fw-semibold">
                        Tipo de promoción
                    </label>

                    <select
                        id="tipo"
                        name="tipo"
                        class="form-select rounded-3 @error('tipo') is-invalid @enderror"
                        required
                        disabled>

                        <option value="">Seleccione un tipo</option>

                        @foreach($tipos as $valor => $etiqueta)
                        <option
                            value="{{ $valor }}"
                            @selected(old('tipo', $promocion->tipo->value) === $valor)>
                            {{ $etiqueta }}
                        </option>
                        @endforeach

                    </select>

                    @error('tipo')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                {{-- Descripción --}}
                <div class="col-12">
                    <label for="descripcion" class="form-label fw-semibold">
                        Descripción
                    </label>

                    <textarea
                        class="form-control rounded-3 @error('descripcion') is-invalid @enderror"
                        id="descripcion"
                        name="descripcion"
                        rows="3"
                        maxlength="255"
                        style="resize: none"
                        disabled>{{ old('descripcion', $promocion->descripcion) }}</textarea>

                    @error('descripcion')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                {{-- Valor --}}
                <div class="col-md-4">
                    <label for="valor" class="form-label fw-semibold">
                        Valor
                    </label>

                    <input
                        type="number"
                        class="form-control rounded-3 @error('valor') is-invalid @enderror"
                        id="valor"
                        name="valor"
                        value="{{ old('valor', $promocion->valor) }}"
                        min="0.01"
                        max="9999999999.99"
                        step="0.01"
                        required
                        disabled>

                    @error('valor')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                {{-- Canal --}}
                <div class="col-md-4">
                    <label for="canal" class="form-label fw-semibold">
                        Canal de aplicación
                    </label>

                    <select
                        id="canal"
                        name="canal"
                        class="form-select rounded-3 @error('canal') is-invalid @enderror"
                        required
                        disabled>

                        <option value="">Seleccione un canal</option>
                        @foreach($canales as $valor => $etiqueta)
                        <option
                            value="{{ $valor }}"
                            @selected(old('canal', $promocion->canal->value) === $valor)>
                            {{ $etiqueta }}
                        </option>
                        @endforeach

                    </select>

                    @error('canal')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                {{-- Prioridad --}}
                <div class="col-md-4">
                    <label for="prioridad" class="form-label fw-semibold">
                        Prioridad
                    </label>

                    <input
                        type="number"
                        class="form-control rounded-3 @error('prioridad') is-invalid @enderror"
                        id="prioridad"
                        name="prioridad"
                        value="{{ old('prioridad', $promocion->prioridad) }}"
                        min="0"
                        max="9999"
                        required
                        disabled>

                    <div class="form-text">
                        Se utiliza para resolver conflictos entre promociones.
                    </div>

                    @error('prioridad')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                {{-- Vigencia desde --}}
                <div class="col-md-6">
                    <label for="vigencia_desde" class="form-label fw-semibold">
                        Vigencia desde
                    </label>

                    <input
                        type="datetime-local"
                        class="form-control rounded-3 @error('vigencia_desde') is-invalid @enderror"
                        id="vigencia_desde"
                        name="vigencia_desde"
                        value="{{ old('vigencia_desde', $promocion->vigencia_desde?->format('Y-m-d\TH:i')) }}"
                        required
                        disabled>

                    @error('vigencia_desde')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                {{-- Vigencia hasta --}}
                <div class="col-md-6">
                    <label for="vigencia_hasta" class="form-label fw-semibold">
                        Vigencia hasta
                    </label>

                    <input
                        type="datetime-local"
                        class="form-control rounded-3 @error('vigencia_hasta') is-invalid @enderror"
                        id="vigencia_hasta"
                        name="vigencia_hasta"
                        value="{{ old('vigencia_hasta', $promocion->vigencia_hasta?->format('Y-m-d\TH:i')) }}"
                        required
                        disabled>

                    @error('vigencia_hasta')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                {{-- Acumulable --}}
                <div class="col-md-6">
                    <div class="form-check form-switch mt-2">

                        <input
                            class="form-check-input"
                            type="checkbox"
                            role="switch"
                            id="acumulable"
                            name="acumulable"
                            value="1"
                            @checked(old('acumulable', $promocion->acumulable))
                        disabled>

                        <label
                            class="form-check-label fw-semibold"
                            for="acumulable">
                            Promoción acumulable
                        </label>

                    </div>

                    <div class="form-text">
                        Permite combinar esta promoción con otras promociones compatibles.
                    </div>

                    @error('acumulable')
                    <div class="text-danger small mt-2">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                {{-- Activo --}}
                <div class="col-md-6">
                    <div class="form-check form-switch mt-2">

                        <input
                            class="form-check-input"
                            type="checkbox"
                            role="switch"
                            id="activo"
                            name="activo"
                            value="1"
                            @checked(old('activo', $promocion->activo))
                        disabled>

                        <label
                            class="form-check-label fw-semibold"
                            for="activo">
                            Promoción activa
                        </label>

                    </div>

                    <div class="form-text">
                        Una promoción inactiva no se aplica a nuevas ventas.
                    </div>

                    @error('activo')
                    <div class="text-danger small mt-2">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                {{-- Productos --}}
                <div class="col-12">

                    <label for="productos" class="form-label fw-semibold">
                        Productos alcanzados
                    </label>

                    <select
                        id="productos"
                        name="productos[]"
                        class="form-select rounded-3 @error('productos') is-invalid @enderror"
                        multiple
                        required
                        disabled
                        size="8">

                        @foreach($productos as $producto)
                        <option
                            value="{{ $producto->id }}"
                            @selected(in_array(
                            $producto->id,
                            old('productos', $seleccionados)
                            ))>

                            {{ $producto->codigo }} - {{ $producto->nombre }}
                            @if($producto->categoria)
                            ({{ $producto->categoria->nombre }})
                            @endif

                        </option>
                        @endforeach

                    </select>

                    <div class="form-text">
                        Mantenga presionada la tecla Ctrl para seleccionar varios productos.
                    </div>

                    @error('productos')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror

                    @error('productos.*')
                    <div class="text-danger small mt-2">
                        {{ $message }}
                    </div>
                    @enderror

                </div>

                {{-- Banner actual --}}
                <div class="col-md-6">

                    <label class="form-label fw-semibold">
                        Banner actual
                    </label>

                    @if($promocion->banner)

                    <div class="border rounded-3 bg-white p-3">

                        <img
                            src="{{ asset('storage/' . $promocion->banner) }}"
                            alt="Banner de {{ $promocion->nombre }}"
                            class="img-fluid rounded-3"
                            style="max-height: 220px; object-fit: contain;">

                    </div>

                    @else

                    <div class="border rounded-3 bg-white p-3 text-muted">
                        La promoción no tiene un banner cargado.
                    </div>

                    @endif

                </div>

                {{-- Cambiar banner --}}
                <div class="col-md-6">

                    <label for="banner" class="form-label fw-semibold">
                        Cambiar banner
                    </label>

                    <input
                        type="file"
                        class="form-control rounded-3 @error('banner') is-invalid @enderror"
                        id="banner"
                        name="banner"
                        accept=".jpg,.jpeg,.png,.webp"
                        disabled>

                    <div class="form-text">
                        Imagen JPG, JPEG, PNG o WEBP. Tamaño máximo: 2 MB.
                    </div>

                    @error('banner')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror

                </div>

                {{-- Quitar banner --}}
                @if($promocion->banner)

                <div class="col-12">

                    <div class="form-check">

                        <input
                            class="form-check-input"
                            type="checkbox"
                            id="quitar_banner"
                            name="quitar_banner"
                            value="1"
                            disabled>

                        <label
                            class="form-check-label fw-semibold"
                            for="quitar_banner">
                            Quitar banner actual
                        </label>

                    </div>

                    <div class="form-text">
                        Si selecciona esta opción, el banner actual será eliminado.
                        Si selecciona un nuevo banner, se utilizará el nuevo archivo.
                    </div>

                </div>

                @endif

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
                form="editarPromocionForm"
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
                href="{{ route('gestion.promociones.index') }}"
                class="btn btn-outline-secondary px-4 fw-semibold shadow-sm">

                Volver al listado
            </a>

            {{-- Baja / Alta lógica --}}
            @if($promocion->activo)

            <form
                action="{{ route('gestion.promociones.darDeBaja', $promocion) }}"
                method="POST"
                class="d-inline">

                @csrf
                @method('PATCH')

                <button
                    type="submit"
                    class="btn btn-danger px-4 fw-semibold shadow-sm"
                    onclick="return confirm('¿Está seguro de que desea dar de baja esta promoción?')">

                    Dar de baja
                </button>

            </form>

            @else

            <form
                action="{{ route('promociones.reactivar', $promocion) }}"
                method="POST"
                class="d-inline">

                @csrf
                @method('PATCH')

                <button
                    type="submit"
                    class="btn btn-success px-4 fw-semibold shadow-sm"
                    onclick="return confirm('¿Está seguro de que desea reactivar esta promoción?')">

                    Reactivar
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

        const form = document.getElementById('editarPromocionForm');

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

                if (campo.multiple) {

                    valoresOriginales[campo.id] =
                        Array.from(campo.selectedOptions).map(option => option.value);

                } else {

                    valoresOriginales[campo.id] = campo.value;

                }

            }

        });

        function activarEdicion() {

            campos.forEach(campo => {

                if (campo.type !== 'hidden') {
                    campo.disabled = false;
                }

            });

            btnEditar.disabled = true;
            btnActualizar.disabled = false;
            btnCancelar.disabled = false;

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

                } else if (campo.multiple) {

                    const valores = valoresOriginales[campo.id] || [];

                    Array.from(campo.options).forEach(option => {
                        option.selected = valores.includes(option.value);
                    });

                } else {

                    campo.value = valoresOriginales[campo.id];

                }

                campo.disabled = true;

            });

            btnEditar.disabled = false;
            btnActualizar.disabled = true;
            btnCancelar.disabled = true;

        }

        btnEditar.addEventListener('click', activarEdicion);

        btnCancelar.addEventListener('click', cancelarEdicion);

    });
</script>

@endpush