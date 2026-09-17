@extends('layouts.app')

@section('title', 'PowerTech')

@section('content')

<!-- Contenido principal -->
<div class="container py-4">

    <!-- Título -->
    <section class="mb-4">
        <h1 class="h3 fw-bold mb-2">Registrar nueva categoría</h1>
        <p class="text-muted">Complete los datos para guardar una nueva categoría de productos.</p>
    </section>

    <!-- Formulario -->
    <section class="mb-4">
        <form
            action="{{ route('categorias.store') }}"
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

                <!-- Nombre -->
                <div class="col-12">
                    <label for="nombre" class="form-label fw-semibold">
                        Nombre de la categoría
                    </label>

                    <input
                        type="text"
                        class="form-control rounded-3 @error('nombre') is-invalid @enderror"
                        id="nombre"
                        name="nombre"
                        value="{{ old('nombre', $categoria->nombre) }}"
                        placeholder="Ej: Periféricos, Notebooks, Almacenamiento..."
                        required
                        maxlength="100"
                        autofocus>

                    @error('nombre')
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
                            {{ old('activo', $categoria->activo ?? true) ? 'checked' : '' }}>

                        <label for="activo" class="form-check-label fw-semibold">
                            En operación
                        </label>

                    </div>

                    <div class="form-text">
                        Las categorías fuera de operación no se ofrecen al cargar un producto.
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
                    href="{{ route('categorias.index') }}"
                    class="btn btn-outline-secondary px-4 fw-semibold shadow-sm">
                    Volver al listado
                </a>

            </div>

        </form>
    </section>

</div>

@endsection