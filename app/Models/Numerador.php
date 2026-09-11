<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * K.1 numeradores. Contador de los correlativos INTERNOS del sistema: la
 * venta y la orden de compra. Nada que ver con la numeracion fiscal, que la
 * autoriza ARCA y vive en `comprobantes`.
 *
 * Una fila por serie. No tiene FK ni la referencia nadie: es un contador,
 * no una entidad del dominio.
 */
class Numerador extends Model
{
    protected $table = 'numeradores';

    public const CREATED_AT = null;

    public const SERIE_VENTA        = 'venta';
    public const SERIE_ORDEN_COMPRA = 'orden_compra';

    protected $fillable = ['serie', 'prefijo', 'ultimo_numero'];

    protected function casts(): array
    {
        return ['ultimo_numero' => 'integer'];
    }

    /**
     * Entrega el siguiente numero de la serie.
     *
     * Tiene que llamarse DENTRO de la transaccion que crea el documento. El
     * FOR UPDATE bloquea la fila del contador hasta el COMMIT, que es lo que
     * serializa la asignacion: sin huecos, sin duplicados y sin reintentos.
     * Si la operacion falla, el ROLLBACK devuelve tambien el numero.
     *
     * Las dos alternativas se descartaron a proposito:
     *  - MAX(numero)+1 hace que dos cajas simultaneas calculen el mismo
     *    numero; el indice unico evita el duplicado pero a costa de una
     *    operacion fallida que hay que reintentar, y el reintento compite
     *    otra vez.
     *  - Derivar del id ata un dato que el negocio dice por telefono e
     *    imprime en un presupuesto a una clave tecnica que salta ante
     *    cualquier INSERT fallido.
     */
    public static function siguiente(string $serie): int
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException(
                "Numerador::siguiente('{$serie}') debe llamarse dentro de la "
                . 'transaccion que crea el documento. Fuera de ella, el bloqueo '
                . 'se libera antes del INSERT y la serie deja de ser confiable.'
            );
        }

        $numerador = static::where('serie', $serie)->lockForUpdate()->first();

        if ($numerador === null) {
            throw new LogicException(
                "No existe la serie '{$serie}' en numeradores. Se carga por seeder."
            );
        }

        $numerador->increment('ultimo_numero');

        return $numerador->ultimo_numero;
    }

    /**
     * Presentacion: la columna del documento guarda el entero (1204) y la
     * pantalla arma V-0001204. Guardar el numero ya formateado rompe el
     * orden, la comparacion y el indice.
     */
    public function formatear(int $numero, int $digitos = 7): string
    {
        return $this->prefijo
            ? sprintf('%s-%0' . $digitos . 'd', $this->prefijo, $numero)
            : (string) $numero;
    }
}
