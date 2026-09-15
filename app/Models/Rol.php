<?php

namespace App\Models;

use App\Enums\AmbitoRol;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rol extends Model
{
    protected $table = 'roles';

    protected $fillable = ['nombre', 'descripcion', 'ambito', 'es_sistema'];

    protected $casts = [
        'ambito'     => AmbitoRol::class,
        'es_sistema' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'rol_id');
    }

    public function permisos(): BelongsToMany
    {
        // Pivot sin timestamps: no lleva withTimestamps().
        return $this->belongsToMany(Permiso::class, 'rol_permiso', 'rol_id', 'permiso_id');
    }

    public function esDeGestion(): bool
    {
        return $this->ambito === AmbitoRol::Gestion;
    }
}
