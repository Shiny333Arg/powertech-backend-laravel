<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\Concerns\ReglasDatosCliente;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

/**
 * Registro de un cliente en la tienda: credenciales + datos para facturarle.
 *
 * Si el documento ya pertenece a un cliente (tipicamente uno de mostrador que
 * nunca tuvo cuenta), el registro se rechaza en lugar de vincularlo solo:
 * vincular por documento le mostraria el historial de compras de otra persona
 * a cualquiera que escriba su DNI. La vinculacion la hace un empleado.
 */
class RegistroClienteRequest extends FormRequest
{
    use ReglasDatosCliente;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizarDatosCliente();
    }

    public function rules(): array
    {
        return $this->reglasNombreYCorreo(ignorarUserId: null)
            + ['password' => ['required', 'confirmed', Password::defaults()]]
            + $this->reglasDatosFiscales(cliente: null);
    }

    public function after(): array
    {
        return [
            fn (Validator $validator) => $this->validarCoherenciaFiscal($validator),
        ];
    }

    public function messages(): array
    {
        return $this->mensajesDatosCliente();
    }
}
