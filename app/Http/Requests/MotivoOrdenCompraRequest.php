<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * El motivo de las dos bajas del pedido a proveedor: la cancelación y el cierre
 * con faltante.
 *
 * <b>Un solo FormRequest para las dos.</b> La regla es idéntica —texto corto y
 * obligatorio, que termina anotado en las observaciones del documento con el
 * sello de fecha y el usuario— y tenerla dos veces es la forma de que dentro de
 * seis meses una acepte 255 caracteres y la otra 500. El controlador web valida
 * el motivo en línea, con `$request->validate()`, y no se toca: esta clase es
 * de la API.
 *
 * <b>El mensaje sí cambia según la operación</b>, y no es cosmético. «Escribí
 * por qué se cancela el pedido» sobre un cierre con faltante sería mentirle al
 * operador: cerrar no cancela, da por terminado lo que el proveedor ya no va a
 * mandar. Son los mismos dos textos que hoy muestran RecepcionController y
 * OrdenCompraController del panel, así que las dos interfaces le dicen lo mismo
 * a la misma persona.
 *
 * <b>La autorización no está acá.</b> La resuelve el `can:` de la ruta contra
 * OrdenCompraPolicy, igual que en el resto de la API de gestión: `cancelar`
 * exige 'compra.cancelar' y `cerrar` exige 'compra.recibir', y las dos miran
 * además el estado del pedido. Un FormRequest que devolviera false acá daría un
 * 403 sin pasar por la policy y partiría el criterio en dos lugares.
 */
class MotivoOrdenCompraRequest extends FormRequest
{
    /** La autorización la resuelve la policy desde la ruta. */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'motivo' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'motivo.required' => $this->esCierre()
                ? 'Escribí por qué se da por cerrado el pedido con faltante.'
                : 'Escribí por qué se cancela el pedido.',

            'motivo.max' => 'El motivo entra en 255 caracteres: es una anotación del documento, no un informe.',
        ];
    }

    /**
     * El motivo ya validado y sin espacios de sobra.
     *
     * Se expone como método y no se lee con validated('motivo') desde el
     * controlador por el mismo criterio que RecepcionRequest::remito(): la
     * normalización del dato de entrada es del FormRequest, no de quien lo usa.
     */
    public function motivo(): string
    {
        return trim((string) $this->validated('motivo'));
    }

    /**
     * ¿Esta petición es el cierre con faltante y no la cancelación?
     *
     * Por el nombre de la ruta y no por la URL: el prefijo lo pone
     * bootstrap/app.php y el grupo lo pone routes/api_gestion.php, así que la
     * ruta es lo único estable de los dos.
     */
    private function esCierre(): bool
    {
        return $this->routeIs('*.compras.cierre');
    }
}
