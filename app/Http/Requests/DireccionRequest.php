<?php

namespace App\Http\Requests;

use App\Enums\TipoDireccion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Una sola tabla de direcciones para empleados, clientes y proveedores, asi
 * que un solo request. El dueño lo pone la ruta, nunca el formulario: si
 * llegara por el body, cualquiera podria colgarle una direccion a otro.
 */
class DireccionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tipo'              => ['required', Rule::enum(TipoDireccion::class)],
            'calle'             => ['required', 'string', 'max:120'],
            'numero'            => ['nullable', 'string', 'max:10'],
            'piso_depto'        => ['nullable', 'string', 'max:20'],
            'codigo_postal'     => ['required', 'string', 'max:10'],
            'localidad'         => ['required', 'string', 'max:80'],
            'provincia'         => ['required', 'string', 'max:60'],
            'es_predeterminada' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['es_predeterminada' => $this->boolean('es_predeterminada')]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'codigo_postal'     => 'código postal',
            'es_predeterminada' => 'dirección predeterminada',
        ];
    }
}
