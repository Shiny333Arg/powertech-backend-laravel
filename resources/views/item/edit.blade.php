@extends('layouts.app')

@section('title', 'PowerTech')

@section('content')


<!-- Contenido principal -->
<div class="container py-4">


    <!-- Título -->
    <section class="mb-4">
        <h1 class="h3 fw-bold mb-2">Editar Producto</h1>
        <p class="text-muted">Los campos están bloqueados por defecto.</p>
    </section>


    <!-- Formulario -->
    <section class="mb-4">
        <form id="editarProductoForm" (ngSubmit)="update()" autocomplete="off"
            class="p-4 rounded-4 bg-light shadow-sm border">

            <div class="row g-4">
                <div class="col-md-6">
                    <label for="datoCodigo" class="form-label fw-semibold">Código</label>
                    <input type="text" class="form-control rounded-3" id="datoCodigo" name="datoCodigo"
                        [(ngModel)]="item.codigo" [disabled]="!isEditing" required maxlength="25">
                </div>

                <div class="col-md-6">
                    <label for="datoNombre" class="form-label fw-semibold">Nombre</label>
                    <input type="text" class="form-control rounded-3" id="datoNombre" name="datoNombre"
                        [(ngModel)]="item.nombre" [disabled]="!isEditing" required maxlength="25">
                </div>

                <div class="col-md-12">
                    <label for="datoDescripcion" class="form-label fw-semibold">Descripción</label>
                    <textarea class="form-control rounded-3" id="datoDescripcion" name="datoDescripcion" rows="3"
                        [(ngModel)]="item.descripcion" [disabled]="!isEditing" required>
                    </textarea>
                </div>

                <div class="col-md-6">
                    <label for="datoCategoria" class="form-label fw-semibold">Categoría</label>
                    <select id="datoCategoria" name="datoCategoria" class="form-select rounded-3"
                        [(ngModel)]="item.categoriaId" [disabled]="!isEditing" required>
                        <option *ngFor="let category of categories" [value]="category.id">
                            {'{ category.nombre }}
                        </option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="datoPrecio" class="form-label fw-semibold">Precio</label>
                    <input type="number" class="form-control rounded-3" id="datoPrecio" name="datoPrecio"
                        [(ngModel)]="item.precio" [disabled]="!isEditing" required min="0">
                </div>

                <div class="col-md-3">
                    <label for="datoStock" class="form-label fw-semibold">Stock</label>
                    <input type="number" class="form-control rounded-3" id="datoStock" name="datoStock"
                        [(ngModel)]="item.stock" [disabled]="!isEditing" required min="0">
                </div>
            </div>


            <!-- Botones -->
            <div class="mt-5 d-flex flex-wrap gap-3">
                <button type="button" class="btn btn-warning px-4 fw-semibold shadow-sm" id="btnEditar"
                    [disabled]="isEditing" (click)="enableEdit()">
                    Editar
                </button>
                <button type="submit" class="btn btn-success px-4 fw-semibold shadow-sm" id="btnActualizar" [disabled]="!isEditing" (click)="update()">
                    Actualizar
                </button>
                <button type="button" class="btn btn-secondary px-4 fw-semibold shadow-sm" id="btnCancelar" [disabled]="!isEditing" (click)="cancelEdit()">
                    Cancelar
                </button>
                <a routerLink="/items" class="btn btn-outline-secondary px-4 fw-semibold shadow-sm">
                    Volver al listado
                </a>
                <button type="button" class="btn btn-danger px-4 fw-semibold shadow-sm" id="btnEliminar" (click)="delete()">
                    Eliminar
                </button>
                <button type="button" class="btn btn-outline-primary px-4 fw-semibold shadow-sm" id="btnExportar" (click)="exportPDF()"s>
                    Exportar PDF
                </button>
            </div>


            <!-- Mensajes -->
            <div class="mt-4 d-none alert alert-success" id="mensajeActualizado">
                ¡Producto actualizado correctamente!
            </div>
            <div class="mt-4 d-none alert alert-danger" id="mensajeEliminado">
                El producto fue eliminado del sistema.
            </div>
        </form>
    </section>

</div>


@endsection