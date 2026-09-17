<?php

namespace App\Http\Requests;

use App\Enums\CanalPedido;
use App\Rules\Cuit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Alta y edicion de proveedores.
 *
 * El domicilio viaja en el mismo formulario bajo la clave 'direccion', pero
 * vive en la tabla polimorfica 'direcciones'. Es opcional: si no escriben la
 * calle, no se crea ninguna fila.
 */
class ProveedorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ProveedorPolicy
    }

    /**
     * El CUIT se normaliza a 11 digitos ANTES de validar.
     *
     * Es lo que hace util el indice unico: sin esto, "30-71234567-0" y
     * "30712345670" son dos proveedores distintos para la base y el mismo
     * proveedor para la empresa. El formato se arma al mostrarlo.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'cuit'   => preg_replace('/\D/', '', (string) $this->input('cuit')),
            'activo' => $this->boolean('activo'),
        ]);
    }

    public function rules(): array
    {
        $id = $this->route('proveedor')?->id;

        return [
            'razon_social' => ['required', 'string', 'max:150'],

            'cuit' => [
                'required', 'digits:11', new Cuit,
                Rule::unique('proveedores', 'cuit')->ignore($id),
            ],

            // Requisito de la operacion: sin condicion frente al IVA no se sabe
            // que comprobante espera recibir de este proveedor.
            'condicion_iva_id' => ['required', 'integer', Rule::exists('condiciones_iva', 'id')],

            'email'    => ['nullable', 'email', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'contacto' => ['nullable', 'string', 'max:100'],

            'canal_pedido' => ['required', Rule::in(CanalPedido::valores())],
            'portal_url'   => [
                'nullable', 'url', 'max:255',
                Rule::requiredIf(fn () => $this->input('canal_pedido') === CanalPedido::PortalExterno->value),
            ],

            // Alimenta la fecha estimada de entrega de la orden de compra.
            'plazo_entrega_dias' => ['nullable', 'integer', 'min:0', 'max:365'],

            'activo' => ['boolean'],

            // Domicilio opcional. La calle es la que decide si hay direccion:
            // si viene, el resto de los campos pasa a ser obligatorio.
            'direccion.calle'         => ['nullable', 'string', 'max:120'],
            'direccion.numero'        => ['nullable', 'string', 'max:10'],
            'direccion.piso_depto'    => ['nullable', 'string', 'max:20'],
            'direccion.codigo_postal' => ['nullable', 'required_with:direccion.calle', 'string', 'max:10'],
            'direccion.localidad'     => ['nullable', 'required_with:direccion.calle', 'string', 'max:80'],
            'direccion.provincia'     => ['nullable', 'required_with:direccion.calle', 'string', 'max:60'],
        ];
    }

    /** Solo las columnas de proveedores. El domicilio se guarda aparte. */
    public function datosDelProveedor(): array
    {
        $datos = $this->safe()->except('direccion');

        // Un canal que no es portal no tiene URL que guardar: si quedo cargada
        // de un canal anterior, se limpia en vez de arrastrar un dato muerto.
        if ($datos['canal_pedido'] !== CanalPedido::PortalExterno->value) {
            $datos['portal_url'] = null;
        }

        return $datos;
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
            'razon_social.required'     => 'Escribí la razón social del proveedor.',
            'cuit.required'             => 'El CUIT es obligatorio.',
            'cuit.digits'               => 'El CUIT tiene que tener 11 dígitos.',
            'cuit.unique'               => 'Ya hay un proveedor cargado con ese CUIT.',
            'condicion_iva_id.required' => 'Elegí la condición del proveedor frente al IVA.',
            'canal_pedido.required'     => 'Indicá cómo se le hacen los pedidos a este proveedor.',
            'portal_url.required'       => 'Escribí la dirección del portal donde se cargan los pedidos.',
            'portal_url.url'            => 'La dirección del portal tiene que ser una URL completa, con https://',
            'plazo_entrega_dias.integer'=> 'El plazo de entrega se expresa en días, con un número entero.',
            'direccion.codigo_postal.required_with' => 'Completá el código postal del domicilio.',
            'direccion.localidad.required_with'     => 'Completá la localidad del domicilio.',
            'direccion.provincia.required_with'     => 'Completá la provincia del domicilio.',
        ];
    }
}
