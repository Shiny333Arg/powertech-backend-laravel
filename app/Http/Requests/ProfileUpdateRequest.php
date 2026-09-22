<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ReglasDatosCliente;
use App\Models\Cliente;
use App\Models\Venta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Edicion del perfil del CLIENTE (users + clientes). Los empleados no editan
 * sus datos: solo su contraseña, con PasswordController.
 *
 * El documento queda fijo una vez que el cliente tiene compras: identifica a
 * quien se le facturo. Los comprobantes ya emitidos no cambiarian (copian los
 * datos al emitirse), pero el historial pasaria a mostrar compras de un
 * documento que ya no es el suyo. Ese cambio lo hace un empleado.
 */
class ProfileUpdateRequest extends FormRequest
{
    use ReglasDatosCliente;

    public function authorize(): bool
    {
        return $this->cliente() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizarDatosCliente();
    }

    public function rules(): array
    {
        return $this->reglasNombreYCorreo(ignorarUserId: $this->user()->id)
            + $this->reglasDatosFiscales($this->cliente());
    }

    public function after(): array
    {
        return [
            fn (Validator $validator) => $this->validarCoherenciaFiscal($validator),
            fn (Validator $validator) => $this->validarDocumentoFijo($validator),
        ];
    }

    public function messages(): array
    {
        return $this->mensajesDatosCliente();
    }

    public function cliente(): ?Cliente
    {
        return $this->user()?->cliente;
    }

    public function documentoBloqueado(): bool
    {
        return Venta::where('cliente_id', $this->cliente()->id)->exists();
    }

    private function validarDocumentoFijo(Validator $validator): void
    {
        $cliente = $this->cliente();

        $cambio = (int) $this->input('tipo_documento_id') !== (int) $cliente->tipo_documento_id
            || (string) $this->input('nro_doc') !== (string) $cliente->nro_doc;

        if ($cambio && $this->documentoBloqueado()) {
            $validator->errors()->add(
                'nro_doc',
                'Ya tenés compras registradas con este documento, así que no se puede cambiar desde acá. '
                .'Si hay un error, comunicate con PowerTech.',
            );
        }
    }
}
