<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * A.5 empleados. Datos personales y fiscales, separados de `users` porque no
 * son de autenticacion y un cliente no tiene legajo ni CUIL.
 */
class Empleado extends Model
{
    protected $table = 'empleados';

    protected $fillable = [
        'user_id', 'legajo', 'tipo_documento_id', 'nro_doc', 'cuil',
        'fecha_nacimiento', 'telefono', 'fecha_ingreso', 'fecha_baja',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'fecha_ingreso'    => 'date',
            'fecha_baja'       => 'date',
        ];
    }

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

    /** Conserva el legajo de quien ya no trabaja sin perder sus ventas. */
    public function estaDeBaja(): bool
    {
        return $this->fecha_baja !== null;
    }
}
