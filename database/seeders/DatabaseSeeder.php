<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dos modos de siembra.
 *
 *  - Replica (por defecto fuera de produccion): deja la base igual que el
 *    entorno actual. Mismos ids, mismas fechas, mismo stock, mismas ventas.
 *    Es lo que necesita un clon del proyecto para arrancar a trabajar.
 *
 *  - Limpio (por defecto en produccion): solo los maestros y un usuario
 *    administrador tomado del .env. Nada de datos de prueba.
 *
 * Se puede forzar cualquiera de los dos con SEED_REPLICA:
 *
 *     php artisan migrate:fresh --seed          # replica en local
 *     SEED_REPLICA=false php artisan db:seed    # solo maestros + admin
 *     SEED_REPLICA=true  php artisan db:seed    # replica, incluso en prod
 *
 * Todos los seeders son idempotentes (upsert / insertOrIgnore), asi que se
 * pueden volver a correr sobre una base ya sembrada sin que explote.
 */
class DatabaseSeeder extends Seeder
{
    /** Van siempre: tablas de referencia y matriz de permisos. */
    private const MAESTROS = [
        Maestros\TipoDocumentoSeeder::class,
        Maestros\CondicionIvaSeeder::class,
        Maestros\AlicuotaIvaSeeder::class,
        Maestros\TipoComprobanteSeeder::class,
        Maestros\PuntoVentaSeeder::class,
        Maestros\RolSeeder::class,
        Maestros\PermisoSeeder::class,
        Maestros\NumeradorSeeder::class,
    ];

    /**
     * Datos actuales. El orden importa: cada tabla se siembra despues de
     * aquellas a las que apunta por clave foranea.
     */
    private const REPLICA = [
        Replica\UserSeeder::class,
        Replica\ProveedorSeeder::class,
        Replica\CategoriaSeeder::class,
        Replica\EmpleadoSeeder::class,
        Replica\ClienteSeeder::class,
        Replica\DireccionSeeder::class,
        Replica\ProductoSeeder::class,
        Replica\PromocionSeeder::class,
        Replica\VentaSeeder::class,
        Replica\PagoSeeder::class,
        Replica\ComprobanteSeeder::class,
        Replica\OrdenCompraSeeder::class,
        Replica\MovimientoStockSeeder::class,
        Replica\NumeradorSeeder::class,
    ];

    public function run(): void
    {
        $this->call(self::MAESTROS);

        if ($this->sembrarReplica()) {
            $this->call(self::REPLICA);
            $this->verificarStock();

            return;
        }

        $this->call(Maestros\AdminUserSeeder::class);
    }

    private function sembrarReplica(): bool
    {
        return filter_var(
            env('SEED_REPLICA', ! app()->isProduction()),
            FILTER_VALIDATE_BOOLEAN
        );
    }

    /**
     * El ultimo movimiento de cada producto tiene que coincidir con el stock
     * del catalogo. Si no coincide, el libro mayor quedo desfasado y conviene
     * enterarse ahora y no cuando falle una venta.
     */
    private function verificarStock(): void
    {
        if (! Schema::hasTable('movimientos_stock')) {
            return;
        }

        $descuadres = DB::table('productos as p')
            ->join('movimientos_stock as m', function ($join) {
                $join->on('m.producto_id', '=', 'p.id')
                    ->whereRaw('m.id = (
                        select max(m2.id) from movimientos_stock m2 where m2.producto_id = p.id
                    )');
            })
            ->whereColumn('m.stock_fisico_resultante', '!=', 'p.stock_fisico')
            ->orWhereColumn('m.stock_comprometido_resultante', '!=', 'p.stock_comprometido')
            ->pluck('p.codigo');

        if ($descuadres->isNotEmpty()) {
            $this->command->warn(
                'Stock desfasado contra movimientos_stock en: '.$descuadres->implode(', ')
            );

            return;
        }

        $this->command->info('Stock verificado contra movimientos_stock.');
    }
}
