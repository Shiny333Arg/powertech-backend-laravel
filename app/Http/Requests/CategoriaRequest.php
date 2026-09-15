<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Sirve al alta y a la edicion. La diferencia entre las dos es una sola:
 * al editar hay que ignorar la propia fila en las reglas de unicidad, o la
 * categoria chocaria contra si misma.
 */
class CategoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La autorizacion real vive en CategoriaPolicy. Se activa cuando exista
        // el login (ver nota en el controlador).
        return true;
    }

    /**
     * El slug es opcional en el formulario: si no lo escriben, sale del nombre.
     * Se resuelve antes de validar para que la regla unique lo vea ya armado.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            // 'slug'   => Str::slug($this->input('slug') ?: $this->input('nombre', '')),
            'slug' => Str::slug($this->input('nombre', '')),
            'activo' => $this->boolean('activo'),
        ]);
    }

    public function rules(): array
    {
        // null en el alta; la categoria que se esta editando en el update.
        $id = $this->route('categoria')?->id;

        return [
            'nombre' => [
                'required',
                'string',
                'max:100',
                Rule::unique('categorias', 'nombre')->ignore($id),
            ],
            'slug' => [
                'required',
                'string',
                'max:120',
                Rule::unique('categorias', 'slug')->ignore($id),
            ],
            // 'orden'  => ['required', 'integer', 'min:0', 'max:9999'],
            'activo' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'nombre' => 'nombre',
            'slug'   => 'slug',
            'orden'  => 'orden',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'Escribí un nombre para la categoría.',
            'nombre.unique'   => 'Ya hay una categoría con ese nombre.',
            'nombre.max'      => 'El nombre no puede pasar de 100 caracteres.',
            'slug.unique'     => 'Ese slug ya está en uso. Escribí uno distinto.',
            'orden.required'  => 'Indicá en qué posición va la categoría.',
            'orden.integer'   => 'El orden tiene que ser un número entero.',
        ];
    }
}
