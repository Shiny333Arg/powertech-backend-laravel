<?php

namespace Database\Seeders\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Helpers de idempotencia para los seeders de datos maestros.
 *
 * Todos los seeders identifican cada fila por su clave natural (nombre, codigo_afip,
 * clave, serie, numero, usuario), nunca por id. Correr el seeder dos veces converge
 * al mismo estado en lugar de duplicar.
 *
 * Se usa el query builder y no Eloquent a propósito: los datos maestros no deben
 * depender de que exista el modelo, ni disparar observers, ni verse afectados por
 * global scopes de SoftDeletes.
 */
trait UpsertsMasterData
{
    /**
     * Inserta la fila si no existe; si existe, actualiza solo las columnas que cambiaron.
     * Pensado para catálogos: la definición del repositorio es la autoridad.
     *
     * @param  array<string, mixed>  $claves    Clave natural. Identifica la fila.
     * @param  array<string, mixed>  $valores   Resto de columnas, reconciliables.
     */
    protected function upsertPorClave(string $tabla, array $claves, array $valores = []): void
    {
        $existente = DB::table($tabla)->where($claves)->first();

        if ($existente === null) {
            $this->insertarFila($tabla, array_merge($claves, $valores));

            return;
        }

        $cambios = [];
        foreach ($valores as $columna => $valor) {
            $actual = $existente->{$columna} ?? null;

            // Comparación laxa: el driver devuelve "1"/"0" para BOOLEAN y "21.00" para DECIMAL.
            if ($actual != $valor) {
                $cambios[$columna] = $valor;
            }
        }

        if ($cambios === []) {
            return;
        }

        if (Schema::hasColumn($tabla, 'updated_at')) {
            $cambios['updated_at'] = now();
        }

        DB::table($tabla)->where($claves)->update($cambios);
    }

    /**
     * Inserta la fila solo si no existe. Si ya está, no la toca.
     *
     * Se usa donde reconciliar sería destructivo: `numeradores` (el contador ya avanzó)
     * y el usuario administrador (su contraseña ya fue cambiada).
     */
    protected function insertarSiFalta(string $tabla, array $claves, array $valores = []): bool
    {
        if (DB::table($tabla)->where($claves)->exists()) {
            return false;
        }

        $this->insertarFila($tabla, array_merge($claves, $valores));

        return true;
    }

    /**
     * Devuelve el id de una fila por su clave natural. Falla ruidosamente si no está:
     * un catálogo faltante en este punto es un seeder corrido fuera de orden.
     */
    protected function idPorClave(string $tabla, array $claves): int
    {
        $id = DB::table($tabla)->where($claves)->value('id');

        if ($id === null) {
            throw new \RuntimeException(sprintf(
                'No existe la fila %s en %s. ¿Se corrió el seeder fuera de orden?',
                json_encode($claves, JSON_UNESCAPED_UNICODE),
                $tabla
            ));
        }

        return (int) $id;
    }

    /**
     * Agrega created_at / updated_at solo si la tabla los tiene.
     * `numeradores` no tiene created_at y `movimientos_stock` no tiene updated_at.
     */
    private function insertarFila(string $tabla, array $fila): void
    {
        $ahora = now();

        if (Schema::hasColumn($tabla, 'created_at') && ! array_key_exists('created_at', $fila)) {
            $fila['created_at'] = $ahora;
        }

        if (Schema::hasColumn($tabla, 'updated_at') && ! array_key_exists('updated_at', $fila)) {
            $fila['updated_at'] = $ahora;
        }

        DB::table($tabla)->insert($fila);
    }
}
