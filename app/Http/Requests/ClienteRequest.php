<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ReglasDatosCliente;
use App\Models\Cliente;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Alta y edicion de un cliente desde gestion.
 *
 * Las reglas son LAS MISMAS que las del registro en la tienda: el cliente que
 * carga un empleado no es una entidad distinta, es la misma cuenta dada de
 * alta por otra puerta. Por eso todo sale de ReglasDatosCliente y no se
 * reescribe aca: si manana cambia que un monotributista necesita CUIT, cambia
 * en un solo lugar y vale para las dos puertas.
 *
 * Lo unico que NO comparte con el registro es la contraseña. El cliente nace
 * sin ella y la elige el mismo desde el enlace que recibe por correo
 * (InvitacionCliente), igual que el empleado invitado.
 *
 * <b>La edicion valida lo fiscal solo si lo fiscal cambio.</b> Ver
 * tocaronLosDatosFiscales(): es lo que permite corregirle el telefono a un
 * cliente cargado antes de que existieran estas reglas.
 *
 * La autorizacion vive en ClientePolicy y la aplica el controlador con
 * Gate::authorize(), no este request.
 */
class ClienteRequest extends FormRequest
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

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $cliente = $this->cliente();

        // Si no tocaron el documento ni la condicion frente al IVA, esas tres
        // columnas no se validan y tampoco se escriben: datosDelCliente() usa
        // safe()->only(), y lo que no se valido no esta ahi. La fila fiscal
        // queda exactamente como estaba.
        $reglas = $this->tocaronLosDatosFiscales()
            ? $this->reglasDatosFiscales($cliente)
            : $this->reglasDeContacto();

        if ($this->tieneCuenta()) {
            // En la edicion se ignora al propio usuario: si no, su correo
            // actual chocaria contra la regla de unicidad.
            return $reglas + $this->reglasNombreYCorreo(ignorarUserId: $cliente?->user_id);
        }

        // Cliente de mostrador sin cuenta: no hay nombre ni apellido de los
        // que derivar la razon social, asi que deja de ser opcional.
        $reglas['razon_social'] = ['required', 'string', 'max:150'];

        return $reglas;
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        if (! $this->tocaronLosDatosFiscales()) {
            return [];
        }

        return [
            fn (Validator $validator) => $this->validarCoherenciaFiscal($validator),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->mensajesDatosCliente() + [
            // El mensaje del trait esta escrito para el cliente que se registra
            // solo. Aca lo lee un empleado, que necesita saber que hacer.
            'email.unique' => 'Ese correo ya tiene una cuenta. Buscá al cliente en el listado '
                .'en lugar de crearlo de nuevo.',
            'nro_doc.unique' => 'Ya existe un cliente con ese tipo y número de documento.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nombres'           => 'nombres',
            'apellido'          => 'apellido',
            'email'             => 'correo electrónico',
            'razon_social'      => 'razón social',
            'tipo_documento_id' => 'tipo de documento',
            'nro_doc'           => 'número de documento',
            'condicion_iva_id'  => 'condición frente al IVA',
            'telefono'          => 'teléfono',
        ];
    }

    /** Lo unico del cliente que no es fiscal. */
    private function reglasDeContacto(): array
    {
        return [
            'razon_social' => ['nullable', 'string', 'max:150'],
            'telefono'     => ['nullable', 'string', 'max:30'],
        ];
    }

    /**
     * En el alta, siempre. En la edicion, solo si el formulario trae un
     * documento o una condicion distintos de los guardados.
     *
     * Sin esto, endurecer una regla congela las fichas que no la cumplen: un
     * cliente cargado como monotributista con DNI, de cuando eso se aceptaba,
     * no se podria ni corregirle el telefono. Y el empleado no tendria forma
     * de arreglarlo, porque el error aparece sobre un campo que no toco.
     *
     * No es un permiso para dejar datos incoherentes: en cuanto alguien
     * cambia uno de los tres, se validan los tres juntos.
     *
     * La comparacion corre despues de prepareForValidation(), asi que
     * `nro_doc` ya viene sin puntos ni guiones y se compara contra la columna,
     * que tambien se guardo normalizada.
     */
    private function tocaronLosDatosFiscales(): bool
    {
        $cliente = $this->cliente();

        if ($cliente === null) {
            return true;
        }

        return (int) $this->input('tipo_documento_id') !== (int) $cliente->tipo_documento_id
            || (string) $this->input('nro_doc') !== (string) $cliente->nro_doc
            || (int) $this->input('condicion_iva_id') !== (int) $cliente->condicion_iva_id;
    }

    /**
     * Si hay que validar los datos de `users`.
     *
     * En el alta siempre: toda cuenta nueva nace con su fila en users. En la
     * edicion, solo si el cliente ya tiene una; los de mostrador viejos
     * (user_id nulo) no la tienen y el formulario no muestra ese bloque.
     */
    public function tieneCuenta(): bool
    {
        return $this->isMethod('POST') || $this->cliente()?->user_id !== null;
    }

    /** El cliente que viene de la URL, en la edicion. */
    public function cliente(): ?Cliente
    {
        $cliente = $this->route('cliente');

        return $cliente instanceof Cliente ? $cliente : null;
    }
}