@extends('layouts.gestion')

@section('title', 'PowerTech')

@section('content')

<div class="container py-4">


    {{-- Título --}}
    <section class="mb-4">
        <h1 class="h3 fw-bold mb-2">Alta de nueva promoción</h1>
        <p class="text-muted">
            Complete los datos para registrar una nueva promoción en el sistema.
        </p>
    </section>

    {{-- Formulario --}}
    <section class="mb-4">

        <form
            action="{{ route('gestion.promociones.store') }}"
            method="POST"
            enctype="multipart/form-data"
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
                        placeholder="Ej: 20% de descuento en periféricos"
                        required>

                    @error('nombre')
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
                        placeholder="Ej: 10"
                        required>

                    <div class="form-text">
                        Define qué promoción tiene prioridad cuando se superponen.
                    </div>

                    @error('prioridad')
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
                        rows="4"
                        maxlength="255"
                        placeholder="Breve descripción de la promoción."
                        style="resize: none">{{ old('descripcion', $promocion->descripcion) }}</textarea>

                    @error('descripcion')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <hr class="my-4">

                {{-- Tipo --}}
                <div class="col-md-6">
                    <label for="tipo" class="form-label fw-semibold">
                        Tipo de promoción
                    </label>

                    <select
                        id="tipo"
                        name="tipo"
                        class="form-select rounded-3 @error('tipo') is-invalid @enderror"
                        required>

                        <option value="" hidden>
                            Seleccione un tipo
                        </option>

                        @foreach($tipos as $valor => $nombre)
                        <option
                            value="{{ $valor }}"
                            @selected(old('tipo', $promocion->tipo?->value ?? $promocion->tipo) == $valor)>
                            {{ $nombre }}
                        </option>
                        @endforeach

                    </select>

                    <div class="form-text">
                        Puede ser porcentaje, monto fijo o precio especial.
                    </div>

                    @error('tipo')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                {{-- Valor --}}
                <div class="col-md-6">
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
                        placeholder="Ej: 20"
                        required>

                    <div class="form-text">
                        Si el tipo es porcentaje, el valor máximo permitido es 100.
                    </div>

                    @error('valor')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                {{-- Canal --}}
                <div class="col-md-6">
                    <label for="canal" class="form-label fw-semibold">
                        Canal de aplicación
                    </label>

                    <select
                        id="canal"
                        name="canal"
                        class="form-select rounded-3 @error('canal') is-invalid @enderror"
                        required>

                        <option value="">Seleccione un canal</option>

                        @foreach($canales as $valor => $nombre)
                        <option
                            value="{{ $valor }}"
                            @selected(old('canal', $promocion->canal?->value ?? $promocion->canal) == $valor)>
                            {{ $nombre }}
                        </option>
                        @endforeach

                    </select>

                    <div class="form-text">
                        Indica dónde puede aplicarse la promoción.
                    </div>

                    @error('canal')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                {{-- Banner --}}
                <div class="col-md-6">
                    <label for="banner" class="form-label fw-semibold">
                        Banner de la promoción
                    </label>

                    <input
                        type="file"
                        class="form-control rounded-3 @error('banner') is-invalid @enderror"
                        id="banner"
                        name="banner"
                        accept=".jpg,.jpeg,.png,.webp">

                    <div class="form-text">
                        Formatos permitidos: JPG, JPEG, PNG o WEBP. Máximo 2 MB.
                    </div>

                    @error('banner')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <hr class="my-4">

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
                        required>

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
                        required>

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
                        >

                        <label
                            class="form-check-label fw-semibold"
                            for="acumulable">
                            Promoción acumulable
                        </label>

                    </div>

                    <div class="form-text">
                        Permite combinar esta promoción con otras promociones aplicables.
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
                        >

                        <label
                            class="form-check-label fw-semibold"
                            for="activo">
                            Promoción activa
                        </label>

                    </div>

                    <div class="form-text">
                        Si está activa, podrá aplicarse cuando se encuentre dentro de su vigencia.
                    </div>

                    @error('activo')
                    <div class="text-danger small mt-2">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <hr class="my-4">

                {{-- Productos --}}
                <div class="col-12">

                    <label class="form-label fw-semibold">
                        Productos alcanzados
                    </label>

                    <div class="form-text mb-3">
                        Seleccione al menos un producto al que se aplicará la promoción.
                    </div>

                    @error('productos')
                    <div class="text-danger small mb-2">
                        {{ $message }}
                    </div>
                    @enderror

                    @error('productos.*')
                    <div class="text-danger small mb-2">
                        {{ $message }}
                    </div>
                    @enderror

                    @if($productos->isEmpty())

                    <div class="alert alert-warning mb-0">
                        No hay productos activos disponibles para asociar a la promoción.
                    </div>

                    @else

                    <div class="row g-3">

                        @foreach($productos as $producto)

                        <div class="col-md-6 col-lg-4">

                            <div class="form-check border rounded-3 p-3 h-100 bg-white">

                                <input
                                    class="form-check-input ms-0 me-2 @error('productos') is-invalid @enderror"
                                    type="checkbox"
                                    name="productos[]"
                                    value="{{ $producto->id }}"
                                    id="producto_{{ $producto->id }}"
                                    @checked(in_array(
                                        $producto->id,
                                        old('productos', $seleccionados ?? [])
                                    ))>

                                <label
                                    class="form-check-label"
                                    for="producto_{{ $producto->id }}">

                                    <span class="fw-semibold d-block">
                                        {{ $producto->nombre }}
                                    </span>

                                    <span class="text-muted small d-block">
                                        Código: {{ $producto->codigo }}
                                    </span>

                                    @if($producto->categoria)
                                    <span class="text-muted small d-block">
                                        Categoría: {{ $producto->categoria->nombre }}
                                    </span>
                                    @endif

                                    <span class="small d-block mt-1">
                                        Precio: ${{ number_format($producto->precio, 2, ',', '.') }}
                                    </span>

                                </label>

                            </div>

                        </div>

                        @endforeach

                    </div>

                    @endif

                </div>

            </div>

            {{-- Botones --}}
            <div class="mt-5 d-flex flex-wrap gap-3">

                <button
                    type="submit"
                    class="btn btn-success px-4 fw-semibold shadow-sm">
                    Guardar
                </button>

                <a
                    href="{{ route('gestion.promociones.index') }}"
                    class="btn btn-outline-secondary px-4 fw-semibold shadow-sm">
                    Volver al listado
                </a>

            </div>

        </form>

    </section>


</div>

@endsection