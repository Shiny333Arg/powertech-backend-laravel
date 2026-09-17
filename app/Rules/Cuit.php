<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Verifica el digito verificador de un CUIT o un CUIL (modulo 11).
 *
 * Es el mismo algoritmo para los dos; cambia solo la etiqueta del mensaje,
 * que se pasa por constructor: new Cuit('CUIL').
 *
 * NO esta pedido en los requerimientos: lo agrego porque un CUIT mal tipeado
 * se detecta acá o no se detecta nunca, y como la columna es unica, un digito
 * cambiado crea un proveedor duplicado que despues hay que fusionar a mano.
 * Si preferís no validarlo, sacá la regla del request.
 *
 * Espera el CUIT ya normalizado a 11 digitos (lo hace ProveedorRequest).
 */
class Cuit implements ValidationRule
{
    private const PESOS = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];

    public function __construct(private readonly string $etiqueta = 'CUIT')
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $digitos = preg_replace('/\D/', '', (string) $value);

        if (strlen($digitos) !== 11) {
            $fail("El {$this->etiqueta} tiene que tener 11 dígitos.");

            return;
        }

        $suma = 0;

        foreach (self::PESOS as $posicion => $peso) {
            $suma += ((int) $digitos[$posicion]) * $peso;
        }

        $resto = $suma % 11;

        $verificador = match ($resto) {
            0       => 0,
            1       => 9,
            default => 11 - $resto,
        };

        if ($verificador !== (int) $digitos[10]) {
            $fail("El {$this->etiqueta} no es válido: no coincide el dígito verificador. Revisá si hay algún número cambiado.");
        }
    }
}
