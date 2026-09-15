<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Contador de las series internas: 'venta' y 'orden_compra'.
 *
 * No lo referencia nadie: es un contador, no una entidad del dominio. Es la
 * unica tabla del modelo disenada para escribirse bajo bloqueo explicito.
 */
class Numerador extends Model
{
    public const CREATED_AT = null;

    protected $table = 'numeradores';

    protected $fillable = ['serie', 'prefijo', 'ultimo_numero'];

    protected $casts = ['ultimo_numero' => 'integer'];

    /**
     * Toma el proximo numero de la serie bloqueando la fila del contador.
     *
     * El bloqueo serializa la asignacion: serie estrictamente correlativa, sin
     * huecos y sin reintentos. Un ROLLBACK devuelve tambien el numero.
     *
     * DEBE llamarse dentro de la transaccion que crea el documento: fuera de
     * ella el lock se libera al instante y el numero puede quedar tomado por
     * una operacion que despues falla.
     */
    public static function proximoNumero(string $serie): int
    {
        if (DB::transactionLevel() === 0) {
            throw new RuntimeException(
                "Numerador::proximoNumero('{$serie}') tiene que correr dentro de una transacción."
            );
        }

        $numerador = static::where('serie', $serie)->lockForUpdate()->first();

        if (! $numerador) {
            throw new RuntimeException("No existe la serie '{$serie}' en numeradores.");
        }

        $numerador->increment('ultimo_numero');

        return $numerador->ultimo_numero;
    }

    /** Presentacion: la columna guarda el entero, la pantalla arma V-0001204. */
    public function formatear(int $numero, int $digitos = 7): string
    {
        $relleno = str_pad((string) $numero, $digitos, '0', STR_PAD_LEFT);

        return $this->prefijo ? "{$this->prefijo}-{$relleno}" : $relleno;
    }
}
