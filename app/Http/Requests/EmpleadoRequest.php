<?php

namespace App\Http\Requests;

use App\Enums\AmbitoRol;
use App\Rules\Cuit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Alta y edicion de empleados.
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
            'cuil'  => preg_replace('/\D/', '', (string) $this->input('cuil')),

            'email' => Str::lower(trim((string) $this->input('email'))),
        ]);
    }

    public function rules(): array
    {
        $empleado = $this->route('empleado');
        $userId   = $empleado?->user_id;

        return [
            // --- Credenciales (tabla users) ---
            // Unico en TODA la tabla users: un empleado que ademas quiera
            // comprar en la tienda necesita otro correo para su cuenta de cliente.
            'email' => [
                'required',
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

            // Ni en el alta ni en la edicion: la elige el empleado.
            'password' => ['prohibited'],

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

    /** Columnas de users. */
    public function datosDelUsuario(): array
    {
        // 'activo' NO viaja en el formulario: dar de baja y reactivar son
        // acciones propias, con sus guardas. Si fuera un checkbox más, un
        // update podría desactivar al último administrador esquivándolas.
        return $this->safe()->only(['email', 'nombres', 'apellido', 'rol_id']);
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
            'email.required'     => 'El correo es obligatorio: el empleado ingresa con él y ahí recibe la invitación.',
            'email.unique'       => 'Ese correo ya está registrado en otra cuenta (puede ser de un cliente de la tienda).',
            'rol_id.required'    => 'Elegí el cargo del empleado.',
            'rol_id.exists'      => 'Ese cargo no existe o no habilita el acceso a la gestión.',
            'password.prohibited' => 'La contraseña la elige el empleado desde el enlace que recibe por correo.',
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
