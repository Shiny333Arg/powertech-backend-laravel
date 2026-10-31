<?php

namespace App\Http\Requests;

use App\Enums\OrdenCatalogo;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Filtros de la vista de resultados.
 *
 * Los parametros llegan por querystring y los arma el usuario: un enlace
 * compartido, un banner viejo o alguien editando la URL a mano. Por eso el
 * criterio es SANEAR, no rechazar. Un Request que falla devolveria un redirect
 * con errores sobre una pantalla que deberia poder mostrarse siempre, aunque
 * sea vacia.
 *
 * Todo el trabajo sucio pasa en prepareForValidation: trim, recorte de largo,
 * normalizacion de precios y swap de min/max invertidos. Las reglas quedan
 * laxas a proposito; son la ultima red, no la primera.
 */
class BusquedaProductoRequest extends FormRequest
{
    /** La tienda es publica: un invitado busca igual que un cliente. */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $min = $this->aDecimal($this->input('precio_min'));
        $max = $this->aDecimal($this->input('precio_max'));

        // Min mayor que max no es un error del usuario que valga una pantalla
        // de validacion: es un dedo cambiado. Se invierten y listo.
        if ($min !== null && $max !== null && $min > $max) {
            [$min, $max] = [$max, $min];
        }

        $this->merge([
            'q'          => $this->aTexto($this->input('q'), 255),
            'categoria'  => $this->aEntero($this->input('categoria')),
            'marca'      => $this->aTexto($this->input('marca'), 80),
            'precio_min' => $min,
            'precio_max' => $max,
            'promocion'  => $this->aEntero($this->input('promocion')),
            'orden'      => OrdenCatalogo::desde($this->input('orden'))->value,
        ]);
    }

    public function rules(): array
    {
        return [
            'q'          => ['nullable', 'string', 'max:255'],
            'categoria'  => ['nullable', 'integer', 'min:1'],
            'marca'      => ['nullable', 'string', 'max:80'],
            'precio_min' => ['nullable', 'numeric', 'min:0'],
            'precio_max' => ['nullable', 'numeric', 'min:0'],
            'promocion'  => ['nullable', 'integer', 'min:1'],
            'orden'      => ['nullable', 'string'],
        ];
    }

    /**
     * Los filtros ya normalizados, listos para el controlador.
     *
     * @return array{q:?string, categoria:?int, marca:?string,
     *               precio_min:?float, precio_max:?float, promocion:?int,
     *               orden:OrdenCatalogo}
     */
    public function filtros(): array
    {
        return [
            'q'          => $this->input('q'),
            'categoria'  => $this->input('categoria'),
            'marca'      => $this->input('marca'),
            'precio_min' => $this->input('precio_min'),
            'precio_max' => $this->input('precio_max'),
            'promocion'  => $this->input('promocion'),
            'orden'      => OrdenCatalogo::desde($this->input('orden')),
        ];
    }

    /** '' y '   ' son lo mismo que no haber filtrado. */
    private function aTexto(mixed $valor, int $largo): ?string
    {
        if (! is_string($valor)) {
            return null;
        }

        $valor = mb_substr(trim($valor), 0, $largo);

        return $valor === '' ? null : $valor;
    }

    private function aEntero(mixed $valor): ?int
    {
        return is_numeric($valor) && (int) $valor > 0 ? (int) $valor : null;
    }

    /**
     * Acepta lo que escribe una persona: "15.000,50", "15000.50", "$ 15000".
     *
     * Criterio: se descarta todo lo que no sea digito, coma o punto; si quedan
     * ambos separadores, el ULTIMO es el decimal y el otro es de miles.
     */
    private function aDecimal(mixed $valor): ?float
    {
        if (is_float($valor) || is_int($valor)) {
            return $valor >= 0 ? (float) $valor : null;
        }

        if (! is_string($valor) || trim($valor) === '') {
            return null;
        }

        $limpio = preg_replace('/[^\d.,]/', '', $valor);

        if ($limpio === '' || $limpio === null) {
            return null;
        }

        $ultimaComa  = strrpos($limpio, ',');
        $ultimoPunto = strrpos($limpio, '.');

        if ($ultimaComa !== false && $ultimoPunto !== false) {
            $decimal = $ultimaComa > $ultimoPunto ? ',' : '.';
            $miles   = $decimal === ',' ? '.' : ',';
            $limpio  = str_replace($miles, '', $limpio);
            $limpio  = str_replace($decimal, '.', $limpio);
        } elseif ($ultimaComa !== false) {
            // Una coma sola: decimal si deja 1 o 2 cifras ("1500,50"),
            // separador de miles si deja 3 ("15,000").
            $decimales = strlen($limpio) - $ultimaComa - 1;
            $limpio = $decimales <= 2
                ? str_replace(',', '.', $limpio)
                : str_replace(',', '', $limpio);
        }

        return is_numeric($limpio) && (float) $limpio >= 0 ? (float) $limpio : null;
    }
}
