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

    /**
     * El proximo numero ya formateado, para las series que guardan texto.
     *
     * <b>Por que existe además de proximoNumero().</b> `ventas.numero` es un
     * INT y se guarda pelado; `ordenes_compra.numero` es un varchar(20) UK y lo
     * que va ahí es la cadena presentable. Sin este metodo, cada lugar que crea
     * una orden tiene que tomar el numero, volver a buscar el numerador para
     * leerle el prefijo y formatear: tres pasos para el mismo resultado, y tres
     * oportunidades de que uno arme "OC0000034" y otro "OC-0000034" y el indice
     * unico los acepte a los dos.
     *
     * Corre bajo la misma transaccion y el mismo lock que proximoNumero().
     */
    public static function proximoFormateado(string $serie, int $digitos = 7): string
    {
        $numero = static::proximoNumero($serie);

        // Ya esta en memoria y con la fila bloqueada por la llamada anterior:
        // esta consulta no vuelve a competir con nadie.
        $numerador = static::where('serie', $serie)->first();

        return $numerador->formatear($numero, $digitos);
    }

    /** Presentacion: la columna guarda el entero, la pantalla arma V-0001204. */
    public function formatear(int $numero, int $digitos = 7): string
    {
        $relleno = str_pad((string) $numero, $digitos, '0', STR_PAD_LEFT);

        return $this->prefijo ? "{$this->prefijo}-{$relleno}" : $relleno;
    }
}
