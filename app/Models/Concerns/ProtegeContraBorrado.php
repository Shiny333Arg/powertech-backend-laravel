<?php

namespace App\Models\Concerns;

use App\Exceptions\EntidadNoEliminableException;
use Illuminate\Database\Eloquent\Model;

/**
 * Guard de borrado fisico (Modelo de datos L.3).
 *
 * Sin SoftDeletes, delete() es un DELETE real. Las FK RESTRICT cubren casi
 * todo, pero no la fila todavia no referenciada: un producto recien creado
 * que nadie vendio se borraria sin que ninguna restriccion se oponga.
 * Este trait cierra ese hueco.
 *
 * La baja es siempre update(['activo' => false]).
 */
trait ProtegeContraBorrado
{
    public static function bootProtegeContraBorrado(): void
    {
        static::deleting(function (Model $modelo): void {
            throw new EntidadNoEliminableException($modelo->mensajeNoEliminable());
        });
    }

    protected function mensajeNoEliminable(): string
    {
        $entidad = class_basename($this);

        return "{$entidad} no se elimina. Usá la baja lógica (activo = false).";
    }
}
