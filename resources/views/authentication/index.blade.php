@extends('layouts.auth')

@section('title', 'Iniciar Sesión')

@section('content')



<div class="bg-primary bg-gradient d-flex align-items-center justify-content-center min-vh-100">
    <div class="card shadow-sm p-4" style="width: 100%; max-width: 400px;">
        <div class="text-center mb-4">
            <div class="fs-1 text-primary">
                <img src="{{ asset('images/Logo_Primary.png') }}" alt="Logo" style="height: 80px;">
            </div>
            <div class="fs-1 text-primary">
                <span class="fw-bold">POWERTECH</span>
            </div>
            <h2 class="h4 fw-bold">Iniciar Sesión</h2>
        </div>

        <form (ngSubmit)="login()" autocomplete="off">

            <div class="mb-3">
                <label for="datoUsuario" class="form-label">Nombre de usuario</label>
                <input type="text" class="form-control rounded-3" id="datoUsuario" name="datoUsuario" required
                    [(ngModel)]="loginData.cuenta" />
            </div>
            <div class="mb-4">
                <label for="datoContraseña" class="form-label">Contraseña</label>
                <div class="input-group">
                    <input [type]="showPassword ? 'text' : 'password'" class="form-control rounded-start-3"
                        id="datoContraseña" name="datoContraseña" required [(ngModel)]="loginData.clave" />
                    <button class="btn btn-outline-secondary rounded-end-3" type="button" id="switchContraseña"
                        tabindex="-1" (click)="togglePassword()">
                        <svg xmlns="../../../../public/app/assets/icons/eye.svg" width="16" height="16"
                            fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16">
                            <path
                                d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z" />
                            <path
                                d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0" />
                        </svg></button>
                </div>
                <div class="mt-3">
                    <p>Usuario: PedroC</p>
                </div>
                <div>
                    <p>Clave: Contraseña123</p>
                </div>
            </div>
            <button type="submit" id="btn-login" class="btn btn-primary w-100 rounded-3">Ingresar</button>

            <div>
                <a href="/authentication/reset-password" class="btn btn-outline-secondary w-100 rounded-3 fw-semibold shadow-sm mt-3">
                    Restablecer contraseña
                </a>
            </div>

        </form>
    </div>
</div>


@endsection