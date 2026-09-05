@extends('layouts.app')

@section('title', 'PowerTech')

@section('content')

<!-- Contenido principal -->
<div class="container py-4">


    <!-- Título -->
    <section class="mb-4">
        <h1 class="h3 fw-bold mb-2">Editar Cuenta de Usuario</h1>
        <p class="text-muted">Los campos están bloqueados por defecto.</p>
    </section>


    <!-- Formulario -->
    <section class="mb-4">
        <form id="editarUsuarioForm" (ngSubmit)="update()" autocomplete="off"
            class="p-4 rounded-4 bg-light shadow-sm border">

            <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Estado de la cuenta</label>
                    <select id="datoEstado" name="datoEstado" class="form-select rounded-3" [(ngModel)]="user.estado"
                        [disabled]="!isEditing" required>
                        <option [ngValue]="1">Activo</option>
                        <option [ngValue]="0">Inactivo</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Fecha de creación</label>
                    <input type="text" class="form-control-plaintext" id="datoCreacion" name="datoCreacion"
                        [value]="user.fechaAlta" [disabled]="!isEditing">
                </div>

                <div class="col-md-6">
                    <label for="datoApellido" class="form-label fw-semibold">Apellido</label>
                    <input type="text" class="form-control rounded-3" id="datoApellido" name="datoApellido"
                        [(ngModel)]="user.apellido" [disabled]="!isEditing" required minlength="3" maxlength="20"
                        pattern="^[A-Za-zÁÉÍÓÚáéíóúÑñ]+(?: [A-Za-zÁÉÍÓÚáéíóúÑñ]+)*$">
                </div>

                <div class="col-md-6">
                    <label for="datoNombres" class="form-label fw-semibold">Nombres</label>
                    <input type="text" class="form-control rounded-3" id="datoNombres" name="datoNombres"
                        [(ngModel)]="user.nombres" [disabled]="!isEditing" required minlength="3" maxlength="32"
                        pattern="^[A-Za-zÁÉÍÓÚáéíóúÑñ]+(?: [A-Za-zÁÉÍÓÚáéíóúÑñ]+)*$">
                </div>

                <div class="col-md-6">
                    <label for="datoCuenta" class="form-label fw-semibold">Cuenta</label>
                    <input type="text" class="form-control rounded-3" id="datoCuenta" name="datoCuenta"
                        [(ngModel)]="user.cuenta" [disabled]="!isEditing" required minlength="3" maxlength="20"
                        pattern="^[A-Za-zÁÉÍÓÚáéíóúÑñ0-9]+(?: [A-Za-zÁÉÍÓÚáéíóúÑñ0-9]+)*$">
                </div>

                <div class="col-md-6">
                    <label for="datoPerfil" class="form-label fw-semibold">Perfil</label>
                    <select id="datoPerfil" name="datoPerfil" class="form-select rounded-3" [(ngModel)]="user.perfil"
                        [disabled]="!isEditing" required>
                        <option value="Administrador">Administrador</option>
                        <option value="Operador">Operador</option>
                        <option value="Vendedor">Vendedor</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="datoCorreo" class="form-label fw-semibold">Correo</label>
                    <input type="email" class="form-control rounded-3" id="datoCorreo" name="datoCorreo"
                        [(ngModel)]="user.correo" [disabled]="!isEditing" required minlength="8" maxlength="64">
                </div>

                <div class="col-md-6">
                    <label for="datoCorreo" class="form-label fw-semibold">Contraseña</label>
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" id="datoPass" name="datoPass"
                            [(ngModel)]="user.resetPass" [disabled]="!isEditing">

                        <label class="form-label fw-semibold" for="datoPass">
                            Cambiar contraseña
                        </label>
                    </div>
                </div>

            </div>


            <!-- Botones -->
            <div class="mt-5 d-flex flex-wrap gap-3">
                <button type="button" class="btn btn-warning px-4 fw-semibold shadow-sm" id="btnEditar"
                    [disabled]="isEditing" (click)="enableEdit()">
                    Editar
                </button>
                <button type="submit" class="btn btn-success px-4 fw-semibold shadow-sm" id="btnActualizar"
                    [disabled]="!isEditing" (click)="update()">
                    Actualizar
                </button>
                <button type="button" class="btn btn-secondary px-4 fw-semibold shadow-sm" id="btnCancelar"
                    [disabled]="!isEditing" (click)="cancelEdit()">
                    Cancelar
                </button>
                <a routerLink="/users" class="btn btn-outline-secondary px-4 fw-semibold shadow-sm">
                    Volver al listado
                </a>
                <button type="button" class="btn btn-danger px-4 fw-semibold shadow-sm" id="btnEliminar"
                    (click)="delete()">
                    Eliminar
                </button>
                <button type="button" class="btn btn-outline-primary px-4 fw-semibold shadow-sm" id="btnExportar"
                    (click)="exportPDF()">
                    Exportar PDF
                </button>
            </div>


            <!-- Mensajes -->
            <div class="mt-4 d-none alert alert-success" id="mensajeActualizado">
                Registro actualizado.
            </div>
            <div class="mt-4 d-none alert alert-danger" id="mensajeEliminado">
                Registro eliminado.
            </div>
        </form>
    </section>

</div>

@endsection