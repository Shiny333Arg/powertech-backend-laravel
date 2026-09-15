<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Baja logica = desactivacion. Una sola columna: activo.
 *
 * Los scopes son NOMBRADOS a proposito, nunca globales (L.3): las pantallas
 * de gestion necesitan ver lo dado de baja para poder reactivarlo, y un
 * global scope obliga a acordarse de desactivarlo justo donde importa.
 * El filtro se aplica donde corresponde y queda visible en la consulta.
 */
trait TieneBajaLogica
{
    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public function scopeInactivos(Builder $query): Builder
    {
        return $query->where('activo', false);
    }

    public function darDeBaja(): bool
    {
        return $this->update(['activo' => false]);
    }

    public function reactivar(): bool
    {
        return $this->update(['activo' => true]);
    }
}
