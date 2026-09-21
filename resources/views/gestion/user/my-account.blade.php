@extends('layouts.app')

@section('title', 'PowerTech')

@section('content')


<main class="container my-4">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-body px-4 py-4">
                    <h2 class="fw-bold mb-1">Mi Cuenta</h2>
                    <p class="text-muted mb-4">Solo puedes cambiar tu contraseña.</p>


                    <form autocomplete="off">
                        <div class="mb-3 row">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label class="form-label fw-semibold">Nombres</label>
                                <input type="text" class="form-control rounded-3" [value]="session?.usuario" disabled>
                                <!-- <input type="text" class="form-control rounded-3"
                                    value="<?= htmlspecialchars($_SESSION['usuario'] ?? '') ?>" disabled> -->
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Apellido</label>
                                <input type="text" class="form-control rounded-3" [value]="session?.apellido" disabled>
                                <!-- <input type="text" class="form-control rounded-3"
                                    value="<?= htmlspecialchars($_SESSION['apellido'] ?? '') ?>" disabled> -->
                            </div>
                        </div>


                        <div class="mb-3 row">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label class="form-label fw-semibold">Cuenta</label>
                                <input type="text" class="form-control rounded-3" [value]="session?.cuenta" disabled>
                                <!-- <input type="text" class="form-control rounded-3"
                                    value="<?= htmlspecialchars($_SESSION['cuenta'] ?? '') ?>" disabled> -->
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Correo</label>
                                <input type="email" class="form-control rounded-3" [value]="session?.correo" disabled>
                                <!-- <input type="text" class="form-control rounded-3"
                                    value="<?= htmlspecialchars($_SESSION['correo'] ?? '') ?>" disabled> -->
                            </div>
                        </div>


                        <div class="mb-4 row">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label class="form-label fw-semibold">Perfil</label>
                                <input type="text" class="form-control rounded-3" [value]="session?.perfil" disabled>
                                <!-- <input type="text" class="form-control rounded-3"
                                    value="<?= htmlspecialchars($_SESSION['perfil'] ?? '') ?>" disabled> -->
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Fecha de alta</label>
                                <input type="text" class="form-control rounded-3" [value]="session?.fechaAlta" disabled>
                                <!-- <input type="text" class="form-control rounded-3"
                                    value="<?= htmlspecialchars($_SESSION['fechaAlta'] ?? '') ?>" disabled> -->
                            </div>
                        </div>


                        <div class="mb-4 row d-none">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label class="form-label fw-semibold">Id</label>
                                <input type="text" class="form-control rounded-3" id="usuarioActualId"
                                    [value]="session?.usuarioId" disabled>
                                <!-- <input type="text" class="form-control rounded-3" id="usuarioActualId"
                                    value="<?= htmlspecialchars($_SESSION['usuarioId'] ?? '') ?>" disabled> -->
                            </div>
                        </div>





                        <!-- Cambiar contraseña -->
                        <hr class="my-4">
                        <h5 class="fw-bold mb-3">Cambiar Contraseña</h5>


                        <div class="mb-3">
                            <label for="datoClaveActual" class="form-label fw-semibold">Contraseña actual</label>
                            <div class="input-group">
                                <input [type]="showPasswordOriginal ? 'text' : 'password'"
                                    class="form-control rounded-start-3" id="datoClaveActual" name="datoClaveActual"
                                    placeholder="Ingresa tu contraseña actual" required
                                    [(ngModel)]="passwordData.claveActual">
                                <button class="btn btn-outline-secondary rounded-end-3" type="button"
                                    id="switchContraseña" tabindex="-1" (click)="togglePasswordOriginal()">
                                    <svg xmlns="../../../../public/app/assets/icons/eye.svg" width="16" height="16"
                                        fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16">
                                        <path
                                            d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z" />
                                        <path
                                            d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0" />
                                    </svg></button>
                            </div>
                        </div>


                        <div class="mb-3">
                            <label for="datoClaveNueva" class="form-label fw-semibold">Nueva contraseña</label>
                            <div class="input-group">
                                <input [type]="showPasswordNew ? 'text' : 'password'"
                                    class="form-control rounded-start-3" id="datoClaveNueva" name="datoClaveNueva"
                                    placeholder="Ingresa la nueva contraseña" required
                                    [(ngModel)]="passwordData.claveNueva">
                                <button class="btn btn-outline-secondary rounded-end-3" type="button"
                                    id="switchContraseña" tabindex="-1" (click)="togglePasswordNew()">
                                    <svg xmlns="../../../../public/app/assets/icons/eye.svg" width="16" height="16"
                                        fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16">
                                        <path
                                            d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z" />
                                        <path
                                            d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0" />
                                    </svg></button>
                            </div>
                        </div>


                        <div class="mb-4">
                            <label for="datoClaveRepetir" class="form-label fw-semibold">Confirmar la nueva
                                contraseña</label>
                            <div class="input-group">
                                <input [type]="showPasswordConfirm ? 'text' : 'password'"
                                    class="form-control rounded-start-3" id="datoClaveRepetir" name="datoClaveRepetir"
                                    placeholder="Repite la nueva contraseña" required
                                    [(ngModel)]="passwordData.claveRepetir">
                                <button class="btn btn-outline-secondary rounded-end-3" type="button"
                                    id="switchContraseña" tabindex="-1" (click)="togglePasswordConfirm()">
                                    <svg xmlns="../../../../public/app/assets/icons/eye.svg" width="16" height="16"
                                        fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16">
                                        <path
                                            d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z" />
                                        <path
                                            d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0" />
                                    </svg></button>
                            </div>
                        </div>


                        <button type="button" class="btn btn-primary w-100 fw-semibold" id="btnGuardar"
                            (click)="updatePassword()">Guardar
                            cambios</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>


@endsection