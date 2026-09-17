<?php

namespace App\Http\Requests;

use App\Enums\AmbitoRol;
use App\Rules\Cuit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Alta y edicion de empleados.
 *
 * Un empleado son TRES filas: credenciales en users, datos personales y
 * fiscales en empleados, y domicilio en direcciones. El formulario es uno solo
 * y el controlador las escribe en una transaccion.
 *
 * La contrasena NO se edita desde acá. Por requerimiento, el empleado es el
 * unico que puede cambiar la suya; lo que el administrativo puede hacer es
 * asignar una provisoria y forzar el cambio (ver EmpleadoController::resetearPassword).
 */
class EmpleadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // EmpleadoPolicy
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            // Igual que el CUIT del proveedor: se guarda en 11 digitos para que
            // el mismo CUIL escrito de dos formas no pase dos veces.
            'cuil' => preg_replace('/\D/', '', (string) $this->input('cuil')),
        ]);
    }

    public function rules(): array
    {
        $empleado = $this->route('empleado');
        $userId   = $empleado?->user_id;

        return [
            // --- Credenciales (tabla users) ---
            'usuario' => [
                'required',
                'string',
                'max:30',
                'alpha_dash',
                Rule::unique('users', 'usuario')->ignore($userId),
            ],
            'email' => [
                'nullable',
                'email',
                'max:150',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'nombres'  => ['required', 'string', 'max:80'],
            'apellido' => ['required', 'string', 'max:80'],

            // Un empleado tiene UN solo cargo, y tiene que ser de gestion: el
            // rol cliente pertenece al ambito tienda y no habilita esta app.
            'rol_id' => [
                'required',
                'integer',
                // Rule::exists('roles', 'id')->where('ambito', AmbitoRol::Gestion->value),
                Rule::exists('roles', 'id')->where(function ($query) {
                    $query
                        ->where('ambito', AmbitoRol::Gestion->value)
                        ->where('nombre', '!=', 'administrador');
                }),
            ],

            // Opcional en el alta: si no la escriben, se genera una provisoria.
            // En la edicion no se acepta nunca.
            'password' => [$this->esAlta() ? 'nullable' : 'prohibited', 'string', 'min:8', 'max:72'],

            // --- Datos personales y fiscales (tabla empleados) ---
            'legajo' => [
                'required',
                'string',
                'max:20',
                Rule::unique('empleados', 'legajo')->ignore($empleado?->id),
            ],
            'tipo_documento_id' => ['required', 'integer', Rule::exists('tipos_documento', 'id')],
            'nro_doc' => [
                'required',
                'string',
                'max:20',
                // La base no tiene indice unico acá (sí lo tiene clientes), asi
                // que esto atrapa el tipeo repetido pero no es una garantia.
                Rule::unique('empleados', 'nro_doc')
                    ->where('tipo_documento_id', $this->integer('tipo_documento_id'))
                    ->ignore($empleado?->id),
            ],
            'cuil'             => ['required', 'digits:11', new Cuit('CUIL')],
            'fecha_nacimiento' => ['nullable', 'date', 'before:today'],
            'telefono'         => ['nullable', 'string', 'max:30'],
            'fecha_ingreso'    => ['required', 'date', 'before_or_equal:today'],

            // --- Domicilio (tabla direcciones, opcional) ---
            'direccion.calle'         => ['nullable', 'string', 'max:120'],
            'direccion.numero'        => ['nullable', 'string', 'max:10'],
            'direccion.piso_depto'    => ['nullable', 'string', 'max:20'],
            'direccion.codigo_postal' => ['nullable', 'required_with:direccion.calle', 'string', 'max:10'],
            'direccion.localidad'     => ['nullable', 'required_with:direccion.calle', 'string', 'max:80'],
            'direccion.provincia'     => ['nullable', 'required_with:direccion.calle', 'string', 'max:60'],
        ];
    }

    public function esAlta(): bool
    {
        return $this->route('empleado') === null;
    }

    /** Columnas de users. La contrasena la resuelve el controlador. */
    public function datosDelUsuario(): array
    {
        // 'activo' NO viaja en el formulario: dar de baja y reactivar son
        // acciones propias, con sus guardas. Si fuera un checkbox más, un
        // update podría desactivar al último administrador esquivándolas.
        return $this->safe()->only(['usuario', 'email', 'nombres', 'apellido', 'rol_id']);
    }

    /** Columnas de empleados. */
    public function datosDelEmpleado(): array
    {
        return $this->safe()->only([
            'legajo',
            'tipo_documento_id',
            'nro_doc',
            'cuil',
            'fecha_nacimiento',
            'telefono',
            'fecha_ingreso',
        ]);
    }

    /** @return array<string,mixed>|null null = no cargaron domicilio */
    public function datosDeLaDireccion(): ?array
    {
        $direccion = $this->safe()->collect()->get('direccion', []);

        return filled($direccion['calle'] ?? null) ? $direccion : null;
    }

    public function messages(): array
    {
        return [
            'usuario.required'   => 'Elegí un nombre de usuario. Es con lo que el empleado va a entrar al sistema.',
            'usuario.alpha_dash' => 'El nombre de usuario admite letras, números, guiones y guiones bajos.',
            'usuario.unique'     => 'Ese nombre de usuario ya está tomado.',
            'email.unique'       => 'Ese correo ya está registrado en otra cuenta.',
            'rol_id.required'    => 'Elegí el cargo del empleado.',
            'rol_id.exists'      => 'Ese cargo no existe o no habilita el acceso a la gestión.',
            'password.prohibited' => 'La contraseña no se edita desde acá: usá "Restablecer contraseña".',
            'password.min'       => 'La contraseña provisoria necesita al menos 8 caracteres.',
            'legajo.required'    => 'Asigná un legajo.',
            'legajo.unique'      => 'Ese legajo ya está asignado a otro empleado.',
            'nro_doc.unique'     => 'Ya hay un empleado cargado con ese documento.',
            'cuil.required'      => 'El CUIL es obligatorio.',
            'cuil.digits'        => 'El CUIL tiene que tener 11 dígitos.',
            'fecha_ingreso.required'        => 'Indicá la fecha de ingreso.',
            'fecha_ingreso.before_or_equal' => 'La fecha de ingreso no puede ser futura.',
            'fecha_nacimiento.before'       => 'Revisá la fecha de nacimiento.',
            'direccion.codigo_postal.required_with' => 'Completá el código postal del domicilio.',
            'direccion.localidad.required_with'     => 'Completá la localidad del domicilio.',
            'direccion.provincia.required_with'     => 'Completá la provincia del domicilio.',
        ];
    }
}
