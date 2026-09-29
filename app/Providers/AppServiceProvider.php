<?php

namespace App\Providers;

use App\Events\MovimientoStockRegistrado;
use App\Listeners\EvaluarReposicion;
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
        // El único enganche entre el stock y las compras. StockService emite
        // el evento sin saber que existe; acá se decide quién lo escucha.
        //
        // Se registra explícitamente y no por convención de nombres para que
        // el enganche sea visible: alguien que lee este archivo ve que mover
        // stock puede terminar generando una orden de compra. Con el
        // descubrimiento automático, eso solo se descubre leyendo el listener.
        Event::listen(MovimientoStockRegistrado::class, EvaluarReposicion::class);

        // La orden de compra no se elimina: la policy no tiene delete(), y la
        // ruta tampoco existe.
        Gate::policy(OrdenCompra::class, OrdenCompraPolicy::class);
    }
}
