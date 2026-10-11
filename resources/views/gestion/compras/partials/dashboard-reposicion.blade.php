{{--
    Bloques de reposición del tablero del administrativo.

    Van en un partial y no dentro de gestion/dashboard.blade.php para poder
    pegarlos donde corresponda sin reescribir la vista entera:

        @include('gestion.compras.partials.dashboard-reposicion', ['gestion' => $gestion])

    Espera el array $gestion que arma DashboardController::gestion().
--}}

<div class="row g-3 mb-4">

    {{-- Órdenes pendientes de aprobación --}}
    <div class="col-12 col-md-6 col-xl-3">
        <a href="{{ route('gestion.compras.index', ['estado' => 'borrador']) }}"
           class="card h-100 text-decoration-none">
            <div class="card-body">
                <p class="text-secondary small text-uppercase mb-1">Pedidos por aprobar</p>
                <p class="display-6 mb-0 {{ $gestion['ordenesPendientes'] > 0 ? 'text-warning' : 'text-body' }}">
                    {{ $gestion['ordenesPendientes'] }}
                </p>
            </div>
        </a>
    </div>

    {{--
        El agujero silencioso: reposición automática activada y sin proveedor.
        No falla, no avisa, y el producto se queda sin stock. Por eso tiene
        tarjeta propia y no una línea perdida en una tabla.
    --}}
    @if (($gestion['sinProveedor'] ?? collect())->isNotEmpty())
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card h-100 border-danger">
                <div class="card-body">
                    <p class="text-secondary small text-uppercase mb-1">Sin proveedor</p>
                    <p class="display-6 mb-1 text-danger">{{ $gestion['sinProveedor']->count() }}</p>
                    <p class="small text-secondary mb-0">
                        Tienen reposición automática y no se les puede emitir pedido.
                    </p>
                </div>
            </div>
        </div>
    @endif

</div>

{{-- Productos bajo el punto de reposición --}}
<div class="card mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h2 class="h6 mb-0">Stock bajo el punto de reposición</h2>
        <a href="{{ route('gestion.compras.index') }}" class="small text-decoration-none">Ver pedidos</a>
    </div>

    @if ($gestion['bajoPuntoReposicion']->isEmpty())
        <div class="card-body text-secondary small">
            Ningún producto activo está por debajo de su punto de reposición.
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr class="text-secondary small text-uppercase">
                        <th scope="col">Código</th>
                        <th scope="col">Producto</th>
                        <th scope="col" class="text-end">Físico</th>
                        <th scope="col" class="text-end">Comprom.</th>
                        <th scope="col" class="text-end">Disponible</th>
                        <th scope="col" class="text-end">Punto rep.</th>
                        <th scope="col">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($gestion['bajoPuntoReposicion'] as $producto)
                        <tr>
                            <td class="text-nowrap">{{ $producto->codigo }}</td>
                            <td>{{ $producto->nombre }}</td>
                            <td class="text-end">{{ $producto->stock_fisico }}</td>
                            <td class="text-end">{{ $producto->stock_comprometido }}</td>

                            {{--
                                El disponible en negativo es preventa: hay
                                unidades vendidas y cobradas que todavía no
                                están en el depósito. Se marca distinto porque
                                no es lo mismo "queda poco" que "se debe".
                            --}}
                            <td class="text-end fw-semibold {{ $producto->stock_disponible < 0 ? 'text-danger' : 'text-warning' }}">
                                {{ $producto->stock_disponible }}
                            </td>

                            <td class="text-end text-secondary">{{ $producto->punto_reposicion }}</td>

                            <td>
                                @php($orden = $producto->ordenAbierta())

                                @if ($orden)
                                    <a href="{{ route('gestion.compras.show', $orden) }}"
                                       class="badge text-bg-{{ $orden->estado->color() }} text-decoration-none">
                                        {{ $orden->numero }}
                                    </a>
                                @elseif (! $producto->reposicion_automatica)
                                    <span class="badge text-bg-light text-secondary">Reposición manual</span>
                                @elseif ($producto->proveedor_id === null)
                                    <span class="badge text-bg-danger">Sin proveedor</span>
                                @else
                                    <span class="badge text-bg-secondary">Sin pedir</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- El detalle de los que no se pueden pedir --}}
@if (($gestion['sinProveedor'] ?? collect())->isNotEmpty())
    <div class="card mb-4 border-danger">
        <div class="card-header bg-white">
            <h2 class="h6 mb-0 text-danger">Reposición automática sin proveedor</h2>
        </div>

        <div class="card-body small text-secondary pb-0">
            Estos productos tienen la reposición automática activada pero no tienen a quién pedirle.
            No generan ningún pedido y su faltante pasa desapercibido hasta que alguien lo note.
        </div>

        <ul class="list-group list-group-flush mt-2">
            @foreach ($gestion['sinProveedor'] as $producto)
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span>
                        <span class="text-secondary small">{{ $producto->codigo }}</span>
                        {{ $producto->nombre }}
                    </span>

                    <a href="{{ route('gestion.items.edit', $producto) }}" class="btn btn-sm btn-outline-danger">
                        Asignar proveedor
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
@endif
