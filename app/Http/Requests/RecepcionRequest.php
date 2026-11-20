<?php

namespace App\Http\Requests;

use App\Models\OrdenCompra;
use App\Models\OrdenCompraLinea;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * El formulario de recepcion de mercaderia.
 *
 * Valida dos cosas que la pantalla sola no puede garantizar: que haya algo que
 * recibir, y que recibir de mas sea una decision tomada y no un tipeo.
 *
 * <b>Sobre el excedente.</b> Recibir mas de lo pedido esta permitido: la
 * mercaderia esta en el deposito y negarse a registrarla obliga al operador a
 * cargar lo pedido y despues un ajuste por la diferencia, que es peor para la
 * trazabilidad —ese ajuste no tiene origen apuntando a la orden—. Pero sin
 * ninguna friccion, un 120 donde iba 12 mete 120 unidades al fisico y el unico
 * arreglo es un ajuste negativo. Por eso el excedente no se rechaza: se
 * confirma. La primera vez el formulario vuelve con el aviso y la casilla sin
 * tildar; si el operador la tilda, pasa.
 */
class RecepcionRequest extends FormRequest
{
    /** La autorizacion la resuelve la policy desde el controlador. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'remito' => ['nullable', 'string', 'max:60'],

            'cantidades'   => ['required', 'array'],
            'cantidades.*' => ['nullable', 'integer', 'min:0', 'max:999999'],

            'confirmar_excedente' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'cantidades.required' => 'No llegó ninguna cantidad del formulario.',
            'cantidades.*.integer' => 'Las cantidades se cargan en unidades enteras.',
            'cantidades.*.min'     => 'Una recepción no puede ser negativa.',
        ];
    }

    public function attributes(): array
    {
        return ['remito' => 'número de remito'];
    }

    /**
     * Las dos validaciones que dependen del estado de la orden.
     *
     * Van acá y no en rules() porque necesitan leer cuanto se recibio hasta
     * ahora de cada renglon, que es un dato de la base y no del formulario.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->cantidades() === []) {
                $validator->errors()->add(
                    'cantidades',
                    'Cargá al menos una cantidad para registrar la entrega.'
                );

                return;
            }

            $aviso = $this->avisoDeExcedente();

            if ($aviso !== null) {
                $validator->errors()->add('confirmar_excedente', $aviso);
            }
        });
    }

    /**
     * El aviso de que esta entrega se pasa de lo pedido, o null si no hay nada
     * que confirmar.
     *
     * <b>Es publico y no parte de withValidator() porque se consulta dos
     * veces.</b> La validacion del FormRequest corre ANTES del cuerpo del
     * controlador, es decir FUERA del candado que serializa las operaciones
     * sobre el pedido: lee el acumulado de cada renglon en un momento en que
     * otra entrega del mismo pedido todavia puede estar por aplicarse. El
     * controlador de la API lo vuelve a preguntar ya con el candado tomado, que
     * es cuando el dato es firme.
     *
     * Devuelve el mensaje en lugar de un booleano para que las dos llamadas
     * digan exactamente lo mismo: el texto que ve el operador del panel y el que
     * recibe el cliente de la API salen de esta unica linea.
     *
     * El panel no cambia: withValidator() hace la misma pregunta que hacia
     * antes y agrega el mismo error sobre el mismo campo.
     */
    public function avisoDeExcedente(): ?string
    {
        if ($this->boolean('confirmar_excedente')) {
            return null;
        }

        $cantidades = $this->cantidades();

        if ($cantidades === []) {
            return null;
        }

        $excedidos = $this->lineasExcedidas($cantidades);

        if ($excedidos->isEmpty()) {
            return null;
        }

        return 'Estás recibiendo más de lo pedido en '
            .$excedidos->implode(', ')
            .'. Si el proveedor mandó de más está bien registrarlo: confirmalo abajo y volvé a guardar.';
    }

    /**
     * Las cantidades que efectivamente entran, en unidades enteras y sin los
     * renglones vacios.
     *
     * Un renglon en cero o en blanco no es un error: es el producto que no vino
     * en esta entrega.
     *
     * @return array<int, int>
     */
    public function cantidades(): array
    {
        $cantidades = [];

        foreach ((array) $this->input('cantidades', []) as $lineaId => $cantidad) {
            $cantidad = (int) $cantidad;

            if ($cantidad > 0) {
                $cantidades[(int) $lineaId] = $cantidad;
            }
        }

        return $cantidades;
    }

    public function remito(): ?string
    {
        $remito = trim((string) $this->input('remito', ''));

        return $remito === '' ? null : $remito;
    }

    /**
     * Los renglones donde el acumulado quedaria por encima de lo pedido,
     * descritos para el mensaje.
     *
     * @param  array<int, int>  $cantidades
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function lineasExcedidas(array $cantidades)
    {
        $orden = $this->route('orden');

        if (! $orden instanceof OrdenCompra) {
            return collect();
        }

        return $orden->lineas()
            ->with('producto:id,codigo,nombre')
            ->whereIn('id', array_keys($cantidades))
            ->get()
            ->filter(function (OrdenCompraLinea $linea) use ($cantidades) {
                return $linea->cantidad_recibida + $cantidades[$linea->id] > $linea->cantidad_pedida;
            })
            ->map(function (OrdenCompraLinea $linea) use ($cantidades) {
                $total    = $linea->cantidad_recibida + $cantidades[$linea->id];
                $exceso   = $total - $linea->cantidad_pedida;
                $producto = $linea->producto?->nombre ?? "renglón #{$linea->id}";

                return "«{$producto}» ({$exceso} de más)";
            })
            ->values();
    }
}
