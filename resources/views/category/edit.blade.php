@extends('layouts.app')

@section('title', 'PowerTech')

@section('content')

<div class="container py-4">

  <!-- Título -->
  <section class="mb-4">
    <h1 class="h3 fw-bold text-dark mb-2">Editar Categoría</h1>
    <p class="text-muted">Modifique los datos de la categoría seleccionada.</p>
  </section>

  <!-- Formulario -->
  <section class="mb-4">
    <form
      action="{{ route('categorias.update', $categoria) }}"
      method="POST"
      autocomplete="off"
      class="p-4 rounded-4 bg-light shadow-sm border"
      id="editarCategoriaForm">

      @csrf
      @method('PUT')

      {{-- Errores de validación --}}
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
            Nombre
          </label>

          <input
            type="text"
            class="form-control rounded-3 @error('nombre') is-invalid @enderror"
            id="nombre"
            name="nombre"
            value="{{ old('nombre', $categoria->nombre) }}"
            maxlength="100"
            required
            autofocus>

          @error('nombre')
          <div class="invalid-feedback">
            {{ $message }}
          </div>
          @enderror
        </div>

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
              {{ old('activo', $categoria->activo) ? 'checked' : '' }}>

            <label for="activo" class="form-check-label fw-semibold">
              En operación
            </label>

          </div>

          <div class="form-text">
            Si la categoría está fuera de operación, no estará disponible para nuevos productos.
          </div>

          @error('activo')
          <div class="text-danger small mt-1">
            {{ $message }}
          </div>
          @enderror
        </div>

      </div>



    </form>

    <div class="mt-5 d-flex flex-wrap gap-3">

      <button
        type="submit"
        form="editarCategoriaForm"
        class="btn btn-success px-4 fw-semibold shadow-sm">
        Actualizar
      </button>

      <a
        href="{{ route('categorias.index') }}"
        class="btn btn-outline-secondary px-4 fw-semibold shadow-sm">
        Volver al listado
      </a>

      <form
        action="{{ route('categorias.destroy', $categoria) }}"
        method="POST"
        class="d-inline">

        @csrf
        @method('DELETE')

        <button
          type="submit"
          class="btn btn-danger px-4 fw-semibold shadow-sm"
          onclick="return confirm('¿Está seguro de eliminar permanentemente esta categoría? Esta acción no se puede deshacer.');">
          Eliminar
        </button>

      </form>

    </div>

  </section>

</div>

@endsection