<?php

namespace App\Http\Requests\Concerns;

use App\Models\Cliente;
use App\Models\CondicionIva;
use App\Models\TipoDocumento;
use App\Rules\Cuit;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Reglas compartidas por el registro del cliente y la edicion de su perfil.
 * Son los datos que ARCA exige para facturarle: documento y condicion frente
 * al IVA, que deciden si recibe factura A o B.
 *
 * Los codigos son los de ARCA y tienen que coincidir con la columna
 * codigo_afip de los seeders de tipos_documento y condiciones_iva.
 */
trait ReglasDatosCliente
{
    public const DOC_CUIT = 80;

    public const DOC_DNI = 96;

    public const IVA_CONSUMIDOR_FINAL = 5;

    /** El cliente se identifica con DNI o con CUIT. */
    public const TIPOS_DOCUMENTO_CLIENTE = [self::DOC_CUIT, self::DOC_DNI];

    private ?int $codigoTipoDocumentoMemo = null;

    /** Llamar desde prepareForValidation(). */
    protected function normalizarDatosCliente(): void
    {
        $this->merge([
            'email'   => Str::lower(trim((string) $this->input('email'))),
            // "20-12345678-3" y "20123456783" son el mismo documento.
            'nro_doc' => preg_replace('/\D/', '', (string) $this->input('nro_doc')),
        ]);
    }

    /** @return array<string, mixed> */
    protected function reglasNombreYCorreo(?int $ignorarUserId): array
    {
        return [
            'nombres'  => ['required', 'string', 'max:80'],
            'apellido' => ['required', 'string', 'max:80'],
            'email'    => [
                'required',
                'string',
                'email',
                'max:150',
                Rule::unique('users', 'email')->ignore($ignorarUserId),
            ],
        ];
    }

    /** @return array<string, mixed> */
    protected function reglasDatosFiscales(?Cliente $cliente): array
    {
        return [
            // Opcional: si queda vacia se usa "Apellido Nombres".
            'razon_social' => ['nullable', 'string', 'max:150'],
            'telefono'     => ['nullable', 'string', 'max:30'],

            'tipo_documento_id' => [
                'required',
                'integer',
                Rule::exists('tipos_documento', 'id')->whereIn('codigo_afip', self::TIPOS_DOCUMENTO_CLIENTE),
            ],
            'nro_doc' => [
                'required',
                'string',
                'max:20',
                Rule::when(fn () => $this->codigoTipoDocumento() === self::DOC_CUIT, ['digits:11', new Cuit('CUIT')]),
                Rule::when(fn () => $this->codigoTipoDocumento() === self::DOC_DNI, ['digits_between:7,8']),
                Rule::unique('clientes', 'nro_doc')
                    ->where('tipo_documento_id', $this->integer('tipo_documento_id'))
                    ->ignore($cliente?->id),
            ],
            'condicion_iva_id' => ['required', 'integer', Rule::exists('condiciones_iva', 'id')],
        ];
    }

    /**
     * Responsable inscripto, monotributista y exento tienen CUIT, y ARCA lo
     * exige para facturarles. Solo el consumidor final puede usar DNI.
     */
    protected function validarCoherenciaFiscal(Validator $validator): void
    {
        if ($validator->errors()->hasAny(['tipo_documento_id', 'condicion_iva_id'])) {
            return;
        }

        $codigoIva = (int) CondicionIva::whereKey($this->integer('condicion_iva_id'))->value('codigo_afip');

        if ($codigoIva !== self::IVA_CONSUMIDOR_FINAL && $this->codigoTipoDocumento() !== self::DOC_CUIT) {
            $validator->errors()->add(
                'tipo_documento_id',
                'Con esa condición frente al IVA tenés que identificarte con tu CUIT.',
            );
        }
    }

    /** Columnas de users. */
    public function datosDelUsuario(): array
    {
        return $this->safe()->only(['email', 'nombres', 'apellido']);
    }

    /** Columnas de clientes. */
    public function datosDelCliente(): array
    {
        $datos = $this->safe()->only(['razon_social', 'telefono', 'tipo_documento_id', 'nro_doc', 'condicion_iva_id']);

        if (blank($datos['razon_social'] ?? null)) {
            $datos['razon_social'] = trim($this->validated('apellido').' '.$this->validated('nombres'));
        }

        return $datos;
    }

    protected function codigoTipoDocumento(): ?int
    {
        return $this->codigoTipoDocumentoMemo ??= (int) TipoDocumento::whereKey($this->integer('tipo_documento_id'))
            ->value('codigo_afip') ?: null;
    }

    /** @return array<string, string> */
    protected function mensajesDatosCliente(): array
    {
        return [
            'email.unique'            => 'Ese correo ya está registrado. Si es tuyo, ingresá o usá "¿Olvidaste tu contraseña?".',
            'tipo_documento_id.exists' => 'Elegí DNI o CUIT.',
            'nro_doc.digits'          => 'El CUIT tiene que tener 11 dígitos.',
            'nro_doc.digits_between'  => 'El DNI tiene que tener 7 u 8 dígitos.',
            'nro_doc.unique'          => 'Ya hay un cliente registrado con ese documento. Si alguna vez compraste en el local, '
                .'acercate con tu DNI y te vinculamos la cuenta.',
        ];
    }
}
