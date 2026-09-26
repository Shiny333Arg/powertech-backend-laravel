<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold text-zinc-900">Para cobrar</h1>
                <p class="text-sm text-zinc-500">Ventas confirmadas y validadas, de la más antigua a la más reciente.</p>
            </div>
            <x-ui.boton variante="plano" :href="route('gestion.cobros.index')">Ver mis cobros</x-ui.boton>
        </div>
    </x-slot>

    <div class="mx-auto max-w-5xl space-y-4 px-4 py-6 sm:px-6 lg:px-8">
        <x-ui.avisos />

        <x-ui.panel :padding="false">
            @if ($ventas->isEmpty())
                <x-ui.vacio
                    titulo="No hay nada esperando cobro"
                    detalle="Acá van a aparecer las ventas apenas un vendedor las confirme." />
            @else
                <ul class="divide-y divide-zinc-100">
                    @foreach ($ventas as $venta)
                        <li class="flex flex-wrap items-center justify-between gap-4 px-5 py-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('gestion.ventas.show', $venta) }}"
                                       class="font-medium tabular-nums text-zinc-900 underline decoration-zinc-300 underline-offset-4">
                                        {{ $venta->numeroFormateado() }}
                                    </a>
                                    <span class="text-sm text-zinc-500">{{ $venta->lineas_count }} ítems</span>
                                </div>
                                <p class="text-sm text-zinc-600">
                                    {{ $venta->cliente?->razon_social ?? 'Consumidor final' }}
                                    <span class="text-zinc-400">·</span>
                                    {{ $venta->fecha?->format('d/m/Y H:i') }}
                                    @if ($venta->usuario)
                                        <span class="text-zinc-400">·</span> {{ $venta->usuario->nombre_completo }}
                                    @endif
                                </p>
                            </div>

                            <div class="flex items-center gap-4">
                                <x-ui.moneda :valor="$venta->total" destacar class="text-lg" />
                                <x-ui.boton variante="primario" :href="route('gestion.cobros.create', $venta)">Cobrar</x-ui.boton>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <div class="border-t border-zinc-100 px-5 py-3">{{ $ventas->links() }}</div>
            @endif
        </x-ui.panel>
    </div>
</x-app-layout>
