<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Sirve al alta y a la edicion, y a los DOS clientes: el formulario Blade del
 * panel y el PUT/POST de /api/v1/gestion/categorias.
 *
 * <b>Es la misma validacion para los dos, y eso es el punto.</b> Duplicarla en
 * un request "api" seria tener dos definiciones de que es una categoria valida,
 * y cuando se separan el sintoma es un dato que el panel rechaza y la API
 * acepta.
 *
 * Tres detalles que hacen que sirva a los dos sin romper al primero:
 *
 *   - <b>'activo' solo se normaliza si vino.</b> Con un boolean()
 *     incondicional, un PUT sin 'activo' daria de baja la categoria sin que
 *     nadie lo pida. Ademas el estado tiene su propio endpoint
 *     (PATCH .../estado) y su propio permiso, asi que el update lo ignora
 *     aunque viaje. Desde que el panel manda el alta y la edicion por la API,
 *     el unico que manda 'activo' es el alta —donde si lo fija—; cuando el
 *     formulario era un POST tradicional lo mandaba siempre, por el input
 *     hidden en 0 que acompanhaba al checkbox.
 *   - <b>'slug' se respeta si vino, y si no sale del nombre.</b> El formulario
 *     no tiene campo de slug, asi que sigue derivandose igual que antes.
 *   - <b>'orden' es 'sometimes'.</b> El formulario tampoco lo manda y queda con
 *     el default 0 de la tabla, como hasta ahora; la API puede fijarlo.
 */
class CategoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La autorizacion real vive en CategoriaPolicy: el panel la pide con
        // authorizeResource() y la API con 'can:' en cada ruta.
        return true;
    }

    /**
     * El slug es opcional: si no lo escriben, sale del nombre. Se resuelve
     * antes de validar para que la regla unique lo vea ya armado.
     */
    protected function prepareForValidation(): void
    {
        $datos = [
            'slug' => Str::slug($this->input('slug') ?: $this->input('nombre', '')),
        ];

        if ($this->has('activo')) {
            $datos['activo'] = $this->boolean('activo');
        }

        $this->merge($datos);
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
            'orden'  => ['sometimes', 'integer', 'min:0', 'max:9999'],
            'activo' => ['sometimes', 'boolean'],
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
            'orden.integer'   => 'El orden tiene que ser un número entero.',
        ];
    }
}
