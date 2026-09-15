<?php

namespace App\Enums\Concerns;

/**
 * Etiqueta legible y helpers para armar selects en Blade.
 *
 * El value de cada case es EXACTAMENTE el literal de la columna ENUM en la
 * migracion: si los dos dejan de coincidir, el insert falla en el motor.
 */
trait ConEtiqueta
{
    abstract public function etiqueta(): string;

    /** @return array<string,string> value => etiqueta */
    public static function opciones(): array
    {
        $opciones = [];

        foreach (static::cases() as $caso) {
            $opciones[$caso->value] = $caso->etiqueta();
        }

        return $opciones;
    }

    /** @return list<string> */
    public static function valores(): array
    {
        return array_column(static::cases(), 'value');
    }

    public function es(self ...$casos): bool
    {
        return in_array($this, $casos, true);
    }
}
