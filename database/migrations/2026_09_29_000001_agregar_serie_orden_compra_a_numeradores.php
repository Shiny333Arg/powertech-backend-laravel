<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * La serie 'orden_compra' del numerador.
 *
 * Va como migracion y no como seeder porque sin esta fila el restock
 * automatico revienta en produccion la primera vez que un producto cruza su
 * umbral: Numerador::proximoNumero() lanza "No existe la serie 'orden_compra'".
 * Un seeder se corre a mano y se olvida; una migracion viaja con el deploy.
 *
 * Es idempotente: si la fila ya existe —porque el seeder de datos base ya la
 * creo— no la toca ni le pisa el contador.
 */
return new class extends Migration
{
    public function up(): void
    {
        $existe = DB::table('numeradores')->where('serie', 'orden_compra')->exists();

        if ($existe) {
            return;
        }

        DB::table('numeradores')->insert([
            'serie'         => 'orden_compra',
            'prefijo'       => 'OC',
            'ultimo_numero' => 0,
            'updated_at'    => now(),
        ]);
    }

    /**
     * El down NO borra la serie.
     *
     * Si hay ordenes emitidas, borrar el contador y volver a crearlo
     * reiniciaria la numeracion en cero y la proxima orden chocaria contra el
     * indice unico de 'numero'. Revertir esta migracion con datos cargados no
     * es una operacion reversible, y fingir que lo es es peor que no hacer
     * nada.
     */
    public function down(): void
    {
        if (DB::table('ordenes_compra')->exists()) {
            return;
        }

        DB::table('numeradores')->where('serie', 'orden_compra')->delete();
    }
};
