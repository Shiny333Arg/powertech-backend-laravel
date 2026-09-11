<?php

namespace App\Models;

use App\Enums\AmbitoRol;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A.2 roles. Cinco roles fijos: administrador, administrativo, cajero,
 * vendedor y cliente. Es tabla y no ENUM para que agregar o renombrar un rol
 * no exija un ALTER TABLE sobre users.
 */
class Role extends Model
{
    // El inflector de Laravel es ingles: en este proyecto el nombre de tabla
    // se declara siempre a mano, aunque a veces coincida.
    protected $table = 'roles';

    public $timestamps = false;

    protected $fillable = ['nombre', 'descripcion', 'ambito', 'es_sistema'];

    protected function casts(): array
    {
        return [
            'ambito'     => AmbitoRol::class,
            'es_sistema' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'rol_id');
    }

    public function permisos(): BelongsToMany
    {
        return $this->belongsToMany(Permiso::class, 'rol_permiso', 'rol_id', 'permiso_id');
    }

    /** Decide a que aplicacion entra el usuario al autenticarse. */
    public function esDeGestion(): bool
    {
        return $this->ambito === AmbitoRol::GESTION;
    }
}
