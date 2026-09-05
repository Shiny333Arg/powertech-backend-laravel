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
        <form (ngSubmit)="save()" autocomplete="off" class="p-4 rounded-4 bg-light shadow-sm border">
            <div class="row g-4">
                <div class="col-12">
                    <label for="datoNombre" class="form-label fw-semibold">Nombre de la categoría</label>
                    <input type="text" class="form-control rounded-3" id="datoNombre" name="datoNombre"
                        [(ngModel)]="category.nombre" placeholder="Ej: Periféricos, Notebooks, Almacenamiento..."
                        required maxlength="100">
                </div>
            </div>


            <!-- Botones -->
            <div class="mt-5 d-flex flex-wrap gap-3">
                <button type="submit" class="btn btn-success px-4 fw-semibold shadow-sm">
                    Guardar
                </button>
                <a routerLink="/categories" class="btn btn-outline-secondary px-4 fw-semibold shadow-sm">
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