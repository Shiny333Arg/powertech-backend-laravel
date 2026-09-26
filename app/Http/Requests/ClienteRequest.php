<?php

namespace App\Http\Requests;

use App\Models\CondicionIva;
use App\Models\TipoDocumento;
use App\Rules\Cuit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * El documento y la condicion frente al IVA NO son opcionales: son requisito
 * de la facturacion electronica. Sin ellos el sistema no puede determinar si
 * corresponde factura A o B.
 *
 * Si `App\Http\Requests\Concerns\ReglasDatosCliente` ya define estas reglas,
 * reemplazar el cuerpo de rules() por el trait y dejar solo la unicidad.
 */
class ClienteRequest extends FormRequest
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
        $cliente = $this->route('cliente');

        return [
            'razon_social'      => ['required', 'string', 'max:150'],
            'tipo_documento_id' => ['required', 'integer', Rule::exists('tipos_documento', 'id')],
            // La regla Cuit valida el digito verificador. Solo se aplica
            // cuando el documento elegido es un CUIT o un CUIL.
            'nro_doc'           => ['required', 'string', 'max:20', ...($this->exigeCuit() ? [new Cuit] : [])],
            'condicion_iva_id'  => ['required', 'integer', Rule::exists('condiciones_iva', 'id')],
            'telefono'          => ['nullable', 'string', 'max:30'],
            'activo'            => ['boolean'],

            // Unicidad compuesta: el mismo documento no puede pertenecer a dos
            // clientes. Es la regla que impide el duplicado por tipeo.
            'nro_doc_unico' => [
                Rule::unique('clientes', 'nro_doc')
                    ->where('tipo_documento_id', $this->integer('tipo_documento_id'))
                    ->ignore($cliente?->id),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $codigos = config('facturacion.codigos');
            $tipoDoc = TipoDocumento::find($this->integer('tipo_documento_id'));
            $condicion = CondicionIva::find($this->integer('condicion_iva_id'));

            if (! $tipoDoc || ! $condicion) {
                return;
            }

            // Un responsable inscripto sin CUIT no puede recibir factura A, y
            // la factura A es justamente lo que lo define. Atajarlo en el alta
            // evita descubrirlo recien al intentar cobrar.
            if ($condicion->codigo_afip === $codigos['condicion_iva']['responsable_inscripto']
                && $tipoDoc->codigo_afip !== $codigos['documento']['cuit']) {
                $validator->errors()->add(
                    'tipo_documento_id',
                    'Un responsable inscripto tiene que identificarse con CUIT.'
                );
            }

        });
    }

    /** El documento elegido exige digito verificador. */
    private function exigeCuit(): bool
    {
        $codigos = config('facturacion.codigos.documento');
        $codigo = TipoDocumento::whereKey($this->integer('tipo_documento_id'))->value('codigo_afip');

        return in_array((int) $codigo, [$codigos['cuit'], $codigos['cuil']], true);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nro_doc'       => preg_replace('/\D/', '', (string) $this->input('nro_doc')),
            'nro_doc_unico' => $this->input('nro_doc'),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'razon_social'      => 'razón social',
            'tipo_documento_id' => 'tipo de documento',
            'nro_doc'           => 'número de documento',
            'nro_doc_unico'     => 'número de documento',
            'condicion_iva_id'  => 'condición frente al IVA',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nro_doc_unico.unique' => 'Ya existe un cliente con ese tipo y número de documento.',
        ];
    }

    /**
     * Solo las columnas del modelo: `nro_doc_unico` es un campo auxiliar de
     * validacion y no tiene que llegar al fill().
     *
     * @return array<string, mixed>
     */
    public function datos(): array
    {
        return collect($this->validated())->except('nro_doc_unico')->all();
    }
}
