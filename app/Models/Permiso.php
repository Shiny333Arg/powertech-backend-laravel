<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A.3 permisos. Claves nombradas (venta.anular, compra.aprobar,
 * stock.ajustar), no banderas CRUD: lo que no se asigna explicitamente,
 * no se puede hacer.
 */
class Permiso extends Model
{
    protected $table = 'permisos';

    public $timestamps = false;

    protected $fillable = ['clave', 'modulo', 'descripcion'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'rol_permiso', 'permiso_id', 'rol_id');
    }
}
