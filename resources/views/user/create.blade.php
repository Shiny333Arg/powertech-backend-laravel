@extends('layouts.app')

@section('title', 'PowerTech')

@section('content')




<!-- Contenido principal -->
<div class="container py-4">


    <!-- Título -->
    <section class="mb-4">
        <h1 class="h3 fw-bold mb-2">Alta de nueva cuenta de usuario</h1>
        <p class="text-muted">Complete los datos para crear una nueva cuenta.</p>
    </section>


    <!-- Formulario -->
    <section class="mb-4">
        <form id="altaUsuarioForm" (ngSubmit)="save()" autocomplete="off" class="p-4 rounded-4 bg-light shadow-sm border">
            <div class="row g-4">
                <div class="col-md-6">
                    <label for="datoApellido" class="form-label fw-semibold">Apellido</label>
                    <input type="text" class="form-control rounded-3" id="datoApellido" name="datoApellido"
                        placeholder="Ej: Spinetta" required minlength="3" maxlength="20"
                        pattern="^[A-Za-zÁÉÍÓÚáéíóúÑñ]+(?: [A-Za-zÁÉÍÓÚáéíóúÑñ]+)*$" [(ngModel)]="user.apellido">
                </div>

                <div class="col-md-6">
                    <label for="datoNombres" class="form-label fw-semibold">Nombres</label>
                    <input type="text" class="form-control rounded-3" id="datoNombres" name="datoNombres"
                        placeholder="Ej: Luis Alberto" required minlength="3" maxlength="32"
                        pattern="^[A-Za-zÁÉÍÓÚáéíóúÑñ]+(?: [A-Za-zÁÉÍÓÚáéíóúÑñ]+)*$" [(ngModel)]="user.nombres">
                </div>

                <div class="col-md-6">
                    <label for="datoCuenta" class="form-label fw-semibold">Cuenta</label>
                    <input type="text" class="form-control rounded-3" id="datoCuenta" name="datoCuenta"
                        placeholder="Ej: Flaco" required minlength="3" maxlength="20"
                        pattern="^[A-Za-zÁÉÍÓÚáéíóúÑñ0-9]+(?: [A-Za-zÁÉÍÓÚáéíóúÑñ0-9]+)*$" [(ngModel)]="user.cuenta">
                </div>

                <div class="col-md-6">
                    <label for="datoPerfil" class="form-label fw-semibold">Perfil</label>
                    <select id="datoPerfil" name="datoPerfil" class="form-select rounded-3" required [(ngModel)]="user.perfil">
                        <option value="" selected hidden>Seleccione perfil</option>
                        <option value="Operador">Operador</option>
                        <option value="Administrador">Administrador</option>
                        <option value="Vendedor">Vendedor</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="datoCorreo" class="form-label fw-semibold">Correo</label>
                    <input type="email" class="form-control rounded-3" id="datoCorreo" name="datoCorreo"
                        placeholder="Ej: usuario@empresa.com" required minlength="8" maxlength="64" [(ngModel)]="user.correo">
                </div>
                
            </div>


            <!-- Botones -->
            <div class="mt-5 d-flex flex-wrap gap-3">
                <button id="btnSubmit" type="submit" class="btn btn-success px-4 fw-semibold shadow-sm">
                    Guardar
                </button>
                <a routerLink="/users" class="btn btn-outline-secondary px-4 fw-semibold shadow-sm">
                    Volver al listado
                </a>
            </div>


            <!-- Mensaje de confirmación/error -->
            <div class="mt-4 d-none alert alert-success" id="mensajeExito">
                <strong>¡Registro creado correctamente!</strong>
            </div>
            <div class="mt-4 d-none alert alert-danger" id="mensajeError">
                <strong>Ocurrió un error al registrar el usuario.</strong>
            </div>
        </form>
    </section>
</div>




@endsection