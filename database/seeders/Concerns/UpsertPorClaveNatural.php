<?php

namespace Database\Seeders\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * Helpers de idempotencia para los seeders de datos maestros.
 *
 * Ninguna operacion identifica filas por id: siempre por clave natural.
 */
trait UpsertPorClaveNatural
{
    /**
     * Inserta la fila si falta; si existe, actualiza solo los valores no-clave.
     *
     * created_at se escribe unicamente en el insert: una segunda corrida no
     * reescribe la fecha de alta.
     *
     * @param  array<string,mixed>  $clave    clave natural (nombre, clave, codigo_afip, numero...)
     * @param  array<string,mixed>  $valores  columnas actualizables
     */
    protected function upsert(string $tabla, array $clave, array $valores = [], bool $conTimestamps = true): void
    {
        $fila = DB::table($tabla)->where($clave);

        if ($fila->exists()) {
            if ($valores !== []) {
                $fila->update($conTimestamps ? $valores + ['updated_at' => now()] : $valores);
            }

            return;
        }

        $nueva = $clave + $valores;

        if ($conTimestamps) {
            $nueva += ['created_at' => now(), 'updated_at' => now()];
        }

        DB::table($tabla)->insert($nueva);
    }

    /**
     * Inserta solo si falta. Nunca reescribe una fila existente.
     *
     * Es el modo que exigen numeradores (pisar ultimo_numero romperia la serie)
     * y el usuario administrador (reescribirlo restauraria la clave provisoria).
     *
     * @return bool true si inserto, false si ya estaba
     */
    protected function insertarSiFalta(string $tabla, array $clave, array $valores = []): bool
    {
        if (DB::table($tabla)->where($clave)->exists()) {
            return false;
        }

        DB::table($tabla)->insert($clave + $valores);

        return true;
    }
}
