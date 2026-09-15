<?php

namespace App\Models;

use App\Models\Concerns\TieneBajaLogica;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * La UNICA entidad del catalogo que admite borrado fisico, y por eso la unica
 * SIN guard de deleting (L.3).
 *
 * La regla la hace cumplir la FK productos.categoria_id ON DELETE RESTRICT, no
 * una validacion en PHP: el controlador intenta el DELETE y traduce el
 * QueryException 1451 a un mensaje entendible. No se antepone un count() de
 * productos, porque reimplementar en PHP una comprobacion que el motor ya hace
 * abre una ventana contra un alta concurrente.
 *
 * Tampoco puede usar SoftDeletes: con el trait, delete() escribiria una marca
 * y el RESTRICT nunca llegaria al motor.
 */
class Categoria extends Model
{
    use TieneBajaLogica;

    protected $table = 'categorias';

    protected $fillable = ['nombre', 'slug', 'orden', 'activo'];

    protected $casts = [
        'orden'  => 'integer',
        'activo' => 'boolean',
    ];

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'categoria_id');
    }
}
