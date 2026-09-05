@extends('layouts.app')

@section('title', 'PowerTech')

@section('content')

<div class="container py-4">


  <!-- Título -->
  <section class="mb-4">
    <h1 class="h3 fw-bold text-primary mb-2">Editar Categoría</h1>
    <p class="text-muted">Los campos están bloqueados por defecto.</p>
  </section>

  
  <!-- Formulario -->
  <section class="mb-4">
    <form id="editarCategoryForm" (ngSubmit)="update()" autocomplete="off" class="p-4 rounded-4 bg-light shadow-sm border">

      <div class="row g-4">
        <div class="col-12">
          <label for="datoNombre" class="form-label fw-semibold">Nombre</label>
          <input type="text" class="form-control rounded-3" [(ngModel)]="category.nombre" id="datoNombre" name="datoNombre" [disabled]="!isEditing" required
            maxlength="25">
        </div>
      </div>


      <!-- Botones -->
      <div class="mt-5 d-flex flex-wrap gap-3">
        <button type="button" class="btn btn-warning px-4 fw-semibold shadow-sm" id="btnEditar" [disabled]="isEditing" (click)="enableEdit()">
          Editar
        </button>
        <button type="submit" class="btn btn-success px-4 fw-semibold shadow-sm" id="btnActualizar" [disabled]="!isEditing" (click)="update()">
          Actualizar
        </button>
        <button type="button" class="btn btn-secondary px-4 fw-semibold shadow-sm" id="btnCancelar" [disabled]="!isEditing" (click)="cancelEdit()">
          Cancelar
        </button>
        <a routerLink="/categories" class="btn btn-outline-secondary px-4 fw-semibold shadow-sm">
          Volver al listado
        </a>
        <button type="button" class="btn btn-danger px-4 fw-semibold shadow-sm" id="btnEliminar" (click)="delete()">
          Eliminar
        </button>
        <button type="button" class="btn btn-outline-primary px-4 fw-semibold shadow-sm" id="btnExportar" (click)="exportPDF()">
          Exportar PDF
        </button>
      </div>


      <!-- Mensajes -->
      <div class="mt-4 d-none alert alert-success" id="mensajeActualizado">
        ¡Categoría actualizada correctamente!
      </div>
      <div class="mt-4 d-none alert alert-danger" id="mensajeEliminado">
        La categoría fue eliminada del sistema.
      </div>
    </form>
  </section>

</div>



@endsection