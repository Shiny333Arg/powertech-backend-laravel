<?php

namespace App\Providers;

use App\Events\MovimientoStockRegistrado;
use App\Events\OrdenCompraCancelada;
use App\Listeners\EvaluarReposicion;
use App\Listeners\ReponerPorCancelacion;
use App\Models\OrdenCompra;
use App\Policies\OrdenCompraPolicy;
use App\Services\AutorizadorSimulado;
use App\Services\Facturacion\AutorizadorComprobante;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
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
        // Los dos enganches del restock con el resto del sistema. Ni
        // StockService ni OrdenCompraService saben que existe la reposición:
        // emiten hechos, y acá se decide quién los escucha.
        //
        // Se registran explícitamente y no por convención de nombres para que
        // el enganche sea visible: alguien que lee este archivo ve que mover
        // stock, o cancelar un pedido, puede terminar generando una orden de
        // compra. Con el descubrimiento automático eso solo se descubre
        // leyendo los listeners.

        // Camino reactivo: bajó el disponible de un producto.
        Event::listen(MovimientoStockRegistrado::class, EvaluarReposicion::class);

        // Cancelar un pedido vuelve a descubrir el faltante que cubría, y no
        // mueve stock: sin este listener, nadie se entera hasta el barrido
        // diario.
        Event::listen(OrdenCompraCancelada::class, ReponerPorCancelacion::class);

        // La orden de compra no se elimina: la policy no tiene delete(), y la
        // ruta tampoco existe.
        Gate::policy(OrdenCompra::class, OrdenCompraPolicy::class);
    }
}
