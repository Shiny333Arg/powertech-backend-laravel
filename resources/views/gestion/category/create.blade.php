@extends('layouts.gestion')

@section('title', 'PowerTech')

@section('content')

{{--
    Alta de categoria.

    La pantalla es la misma; lo que cambio es que el formulario ya no postea a
    una ruta web: lo manda resources/js/gestion/categorias/formulario.js a
    POST /api/v1/gestion/categorias. Por eso no hay @csrf ni action, y los
    errores de validacion ya no llegan en $errors sino en el 422 de la API,
    que los escribe el MISMO CategoriaRequest de antes.
--}}

<!-- Contenido principal -->
<div class="container py-4">

    <!-- Título -->
    <section class="mb-4">
        <h1 class="h3 fw-bold mb-2">Registrar nueva categoría</h1>
        <p class="text-muted">Complete los datos para guardar una nueva categoría de productos.</p>
    </section>

    <!-- Formulario -->
    <section class="mb-4">

        {{-- Fallas de la API que no son de validacion: sin permiso, sin red. --}}
        <div id="avisosCategoria" aria-live="polite"></div>

        <form
            autocomplete="off"
            class="p-4 rounded-4 bg-light shadow-sm border"
            id="formularioCategoria">

            {{-- Errores de validación que devuelve la API. --}}
            <div id="erroresCategoria"></div>

            <div class="row g-4">

                <!-- Nombre -->
                <div class="col-12">
                    <label for="nombre" class="form-label fw-semibold">
                        Nombre de la categoría
                    </label>

                    <input
                        type="text"
                        class="form-control rounded-3"
                        id="nombre"
                        name="nombre"
                        placeholder="Ej: Periféricos, Notebooks, Almacenamiento..."
                        required
                        maxlength="100"
                        autofocus>

                    <div class="invalid-feedback" id="errorNombre"></div>
                </div>


                <!-- Estado -->
                <div class="col-12">
                    <div class="form-check">

                        <input
                            type="checkbox"
                            class="form-check-input"
                            id="activo"
                            name="activo"
                            value="1"
                            checked>

                        <label for="activo" class="form-check-label fw-semibold">
                            En operación
                        </label>

                    </div>

                    <div class="form-text">
                        Las categorías fuera de operación no se ofrecen al cargar un producto.
                    </div>
                </div>

            </div>

            <!-- Botones -->
            <div class="mt-5 d-flex flex-wrap gap-3">

                <button
                    type="submit"
                    id="botonGuardar"
                    class="btn btn-success px-4 fw-semibold shadow-sm">
                    Guardar
                </button>

                <a
                    href="{{ route('gestion.categorias.index') }}"
                    class="btn btn-outline-secondary px-4 fw-semibold shadow-sm">
                    Volver al listado
                </a>

            </div>

        </form>
    </section>

</div>

@endsection

@push('scripts')
<script type="module">
    import { iniciar } from '{{ Vite::asset('resources/js/gestion/categorias/formulario.js') }}';

    iniciar({
        urlListado: '{{ route('gestion.categorias.index') }}',
    });
</script>
@endpush
