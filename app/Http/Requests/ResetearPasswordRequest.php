<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * El circuito de contrasena olvidada, que por requerimiento NO es autogestion:
 * el empleado avisa al administrativo y el administrativo le asigna una
 * provisoria, con cambio forzado en el proximo ingreso.
 */
class ResetearPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // EmpleadoPolicy::resetearPassword
    }

    public function rules(): array
    {
        return [
            // Opcional: en blanco, el sistema genera una y la muestra una vez.
            'password' => ['nullable', 'string', 'min:8', 'max:72'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.min' => 'La contraseña provisoria necesita al menos 8 caracteres.',
        ];
    }
}
