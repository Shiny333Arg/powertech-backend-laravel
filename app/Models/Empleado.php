<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Empleado extends Model
{
    protected $table = 'empleados';

    protected $fillable = [
        'user_id', 'legajo', 'tipo_documento_id', 'nro_doc', 'cuil',
        'fecha_nacimiento', 'telefono', 'fecha_ingreso', 'fecha_baja',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'fecha_ingreso'    => 'date',
        'fecha_baja'       => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tipoDocumento(): BelongsTo
    {
        return $this->belongsTo(TipoDocumento::class, 'tipo_documento_id');
    }

    public function direcciones(): MorphMany
    {
        return $this->morphMany(Direccion::class, 'direccionable');
    }
}
