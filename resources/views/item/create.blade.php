@extends('layouts.app')

@section('title', 'PowerTech')

@section('content')


<!-- Contenido principal -->
<div class="container py-4">


    <!-- Título -->
    <section class="mb-4">
        <h1 class="h3 fw-bold mb-2">Alta de nuevo producto</h1>
        <p class="text-muted">Complete los datos para registrar un nuevo producto en el sistema.</p>
    </section>


    <!-- Formulario -->
    <section class="mb-4">
        <form id="altaProductoForm" (ngSubmit)="save()" autocomplete="off" class="p-4 rounded-4 bg-light shadow-sm border">
            <div class="row g-4">
                <div class="col-md-6">
                    <label for="datoNombre" class="form-label fw-semibold">Nombre del producto</label>
                    <input type="text" class="form-control rounded-3" id="datoNombre" name="datoNombre" required
                        maxlength="100" placeholder="Ej: Mouse Gamer RGB" [(ngModel)]="item.nombre">
                </div>
                <div class="col-md-6">
                    <label for="datoCodigo" class="form-label fw-semibold">Código</label>
                    <input type="text" class="form-control rounded-3" id="datoCodigo" name="datoCodigo" required
                        maxlength="25" placeholder="Ej: PRD-001" [(ngModel)]="item.codigo">
                </div>
                <div class="col-12">
                    <label for="datoDescripcion" class="form-label fw-semibold">Descripción</label>
                    <textarea class="form-control rounded-3" id="datoDescripcion" name="datoDescripcion" rows="3"
                        required placeholder="Breve descripción del producto." style="resize:none" [(ngModel)]="item.descripcion"></textarea>
                </div>
                <div class="col-md-5">
                    <label for="datoCategoria" class="form-label fw-semibold">Categoría</label>
                    <select id="datoCategoria" name="datoCategoria" class="form-select rounded-3" required [(ngModel)]="item.categoriaId">
                        <option [ngValue]="0">Seleccione una categoría</option>
                        <option *ngFor="let category of categories" [value]="category.id">
                            {'{ category.nombre }}
                        </option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="datoPrecio" class="form-label fw-semibold">Precio</label>
                    <input type="number" class="form-control rounded-3" id="datoPrecio" name="datoPrecio" min="0"
                        required placeholder="Ej: 35000" [(ngModel)]="item.precio">
                </div>
                <div class="col-md-4">
                    <label for="datoStock" class="form-label fw-semibold">Stock</label>
                    <input type="number" class="form-control rounded-3" id="datoStock" name="datoStock" min="0" required
                        placeholder="Ej: 20" [(ngModel)]="item.stock">
                </div>
            </div>


            <!-- Botones -->
            <div class="mt-5 d-flex flex-wrap gap-3">
                <button type="submit" class="btn btn-success px-4 fw-semibold shadow-sm">
                    Guardar
                </button>
                <a routerLink="/items" class="btn btn-outline-secondary px-4 fw-semibold shadow-sm">
                    Volver al listado
                </a>
            </div>


            <!-- Mensaje de confirmación -->
            <div class="mt-4 d-none alert alert-success" id="mensajeExito">
                <strong>¡Categoría registrada correctamente!</strong>
            </div>
            <div class="mt-4 d-none alert alert-danger" id="mensajeError">
                <strong>Ocurrió un error al registrar la categoría.</strong>
            </div>
        </form>
    </section>

</div>


@endsection