<?php

namespace App\Providers;

use App\Models\OrdenCompra;
use App\Policies\OrdenCompraPolicy;
use App\Services\AutorizadorSimulado;
use App\Services\Facturacion\AutorizadorComprobante;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            AutorizadorComprobante::class,
            AutorizadorSimulado::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // ------------------------------------------------------------
        // Reposición automática
        // ------------------------------------------------------------
        //
        // Los tres enganches del restock con el resto del sistema. Ni
        // StockService ni OrdenCompraService saben que existe la reposición:
        // emiten hechos, y estos listeners los escuchan.
        //
        //   MovimientoStockRegistrado -> EvaluarReposicion
        //       Bajó el disponible de un producto. El camino reactivo.
        //
        //   OrdenCompraCancelada -> ReponerPorCancelacion
        //       Cancelar vuelve a descubrir el faltante que el pedido cubría,
        //       y no mueve stock: sin esto, nadie se entera hasta el barrido.
        //
        //   OrdenCompraCerrada -> ReponerPorCierre
        //       Cerrada la orden, lo que quedó sin cubrir vuelve a estar
        //       descubierto.
        //
        // <b>No se registran acá con Event::listen().</b> Laravel descubre y
        // registra solo los listeners de app/Listeners, por el tipo del
        // parámetro de handle(). Agregar además el registro manual engancha el
        // mismo listener DOS veces y cada evento despacha dos jobs idénticos.
        // Eso es lo que pasaba hasta el 17/10/2026.
        //
        // Verificable con: php artisan event:list
        // (tiene que haber una sola entrada por evento, la que termina en @handle)

        // La orden de compra no se elimina: la policy no tiene delete(), y la
        // ruta tampoco existe.
        Gate::policy(OrdenCompra::class, OrdenCompraPolicy::class);
    }
}
