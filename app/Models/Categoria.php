<?php

namespace App\Models;

use App\Exceptions\BorradoNoPermitidoException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\QueryException;

/**
 * C.1 categorias. Planas por requerimiento: sin parent_id.
 *
 * UNICA ENTIDAD DEL CATALOGO QUE ADMITE BORRADO FISICO, y solo si ningun
 * producto la referencia. La regla no esta programada: la hace cumplir la FK
 * productos.categoria_id con ON DELETE RESTRICT.
 *
 * Su alcance real conviene tenerlo presente: como los productos nunca se
 * borran fisicamente, la restriccion tambien ve a los dados de baja, asi que
 * una categoria que alguna vez tuvo un producto ya no se puede eliminar. En
 * la practica el borrado fisico sirve para deshacer un alta equivocada.
 */
class Categoria extends Model
{
    use SoftDeletes;

    protected $table = 'categorias';

    protected $fillable = ['nombre', 'slug', 'orden', 'activo'];

    protected function casts(): array
    {
        return [
            'orden'  => 'integer',
            'activo' => 'boolean',
        ];
    }

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'categoria_id');
    }

    /**
     * Cuenta tambien los productos dados de baja, que es lo que hace la FK.
     * Sirve para que la pantalla habilite o no el boton, no para autorizar
     * el borrado: la autoridad es el motor.
     */
    public function puedeEliminarseFisicamente(): bool
    {
        return ! $this->productos()->withTrashed()->exists();
    }

    /**
     * Traduce el rechazo del motor a un mensaje del dominio. No reimplementa
     * la comprobacion: la intenta y deja que la FK decida, que es lo unico
     * libre de condiciones de carrera.
     */
    public function eliminarDefinitivamente(): void
    {
        try {
            $this->forceDelete();
        } catch (QueryException $e) {
            throw BorradoNoPermitidoException::para(
                "la categoria '{$this->nombre}'",
                'tiene productos asociados, incluidos los dados de baja. '
                . 'Se puede dar de baja en su lugar.'
            );
        }
    }
}
