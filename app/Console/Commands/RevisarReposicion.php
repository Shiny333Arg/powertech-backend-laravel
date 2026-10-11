<?php

namespace App\Console\Commands;

use App\Services\ReposicionService;
use Illuminate\Console\Command;

/**
 * Barrido completo del catalogo. Corre todos los dias a primera hora.
 *
 * <b>Por que existe si ya hay un listener.</b> El camino reactivo solo ve lo
 * que pasa por StockService. Hay tres faltantes que nunca emiten un movimiento
 * y que sin este comando quedarian invisibles:
 *
 *   1. Alguien SUBE el punto de reposicion de un producto que ya estaba por
 *      debajo del nuevo umbral. El stock no se movio; el umbral se movio.
 *   2. Un producto se carga o se reactiva ya en falta.
 *   3. Se le asigna proveedor a un producto que tenia la reposicion activada
 *      sin poder disparar nada.
 *
 * Y cubre un cuarto caso que no es de dominio: eventos perdidos porque el
 * worker de colas estaba caido cuando se registro el movimiento.
 *
 * Es idempotente. Correrlo diez veces seguidas genera los mismos pedidos que
 * correrlo una: lo que impide duplicar es la verificacion de orden abierta,
 * no la frecuencia.
 */
class RevisarReposicion extends Command
{
    protected $signature = 'compras:revisar-reposicion
                            {--dry-run : Informa qué pediría, sin crear ninguna orden}';

    protected $description = 'Recorre el catálogo y genera las órdenes de compra en borrador de los productos bajo su punto de reposición.';

    public function handle(ReposicionService $reposicion): int
    {
        $simular = (bool) $this->option('dry-run');

        if ($simular) {
            $this->comment('Modo simulación: no se va a crear ninguna orden.');
        }

        $resumen = $reposicion->evaluarTodos($simular);

        $this->newLine();
        $this->table(
            ['Evaluados', $simular ? 'Pediría' : 'Pedidos', 'Sin proveedor'],
            [[$resumen['evaluados'], $resumen['pedidos'], $resumen['sin_proveedor']]],
        );

        foreach ($resumen['ordenes'] as $linea) {
            $this->line("  · {$linea}");
        }

        // El caso que no se ve solo: reposicion automatica activada y sin
        // proveedor. No es un error del comando, es un dato del catalogo que
        // hay que corregir, asi que se avisa en vez de fallar callado.
        if ($resumen['sin_proveedor'] > 0) {
            $this->newLine();
            $this->warn(
                "{$resumen['sin_proveedor']} producto(s) tienen reposición automática y no tienen proveedor: "
                .'no se les puede emitir orden. Revisalos en el tablero de gestión.'
            );
        }

        return self::SUCCESS;
    }
}
