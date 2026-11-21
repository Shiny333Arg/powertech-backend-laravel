@extends('layouts.gestion')

@section('title', 'PowerTech')

@section('content')

{{--
    Edicion de categoria.

    La pagina ya no trae los datos de la categoria: los pide
    resources/js/gestion/categorias/formulario.js a
    GET /api/v1/gestion/categorias/{id} y los guarda con PUT. Lo unico que
    baja del servidor es el id y lo que la policy deja hacer.

    El casillero de estado va aparte: el PUT no lo toca —tiene su endpoint,
    PATCH .../estado, y su propio permiso (categoria.baja)—, asi que viene
    deshabilitado si el rol no puede cambiarlo.
--}}

<div class="container py-4">

  <!-- Título -->
  <section class="mb-4">
    <h1 class="h3 fw-bold text-dark mb-2">Editar Categoría</h1>
    <p class="text-muted">Modifique los datos de la categoría seleccionada.</p>
  </section>

  <!-- Formulario -->
  <section class="mb-4">

    {{-- Fallas de la API que no son de validacion: 409, sin permiso, sin red. --}}
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
            Nombre
          </label>

          <input
            type="text"
            class="form-control rounded-3"
            id="nombre"
            name="nombre"
            maxlength="100"
            required
            autofocus>

          <div class="invalid-feedback" id="errorNombre"></div>
        </div>

        <div class="col-12">
          <div class="form-check">

            <input
              type="checkbox"
              class="form-check-input"
              id="activo"
              name="activo"
              value="1"
              @cannot('cambiarEstado', $categoria) disabled @endcannot>

            <label for="activo" class="form-check-label fw-semibold">
              En operación
            </label>

          </div>

          <div class="form-text">
            @can('cambiarEstado', $categoria)
            Si la categoría está fuera de operación, no estará disponible para nuevos productos.
            @else
            Tu rol no puede cambiar el estado de una categoría.
            @endcan
          </div>
        </div>

      </div>



    </form>

    <div class="mt-5 d-flex flex-wrap gap-3">

      <button
        type="submit"
        form="formularioCategoria"
        id="botonGuardar"
        class="btn btn-success px-4 fw-semibold shadow-sm">
        Actualizar
      </button>

      <a
        href="{{ route('gestion.categorias.index') }}"
        class="btn btn-outline-secondary px-4 fw-semibold shadow-sm">
        Volver al listado
      </a>

      @can('delete', $categoria)
      <button
        type="button"
        id="botonEliminar"
        class="btn btn-danger px-4 fw-semibold shadow-sm">
        Eliminar
      </button>
      @endcan

    </div>

  </section>

</div>

@endsection

@push('scripts')
<script type="module">
    import { iniciar } from '{{ Vite::asset('resources/js/gestion/categorias/formulario.js') }}';

    iniciar({
        id: {{ $categoria->id }},
        urlListado: '{{ route('gestion.categorias.index') }}',
        puedeCambiarEstado: @json($puedeCambiarEstado),
    });
</script>
@endpush
