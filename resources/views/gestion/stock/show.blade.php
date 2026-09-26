@extends('layouts.gestion')

@section('title', 'PowerTech')

@section('content')

<!-- Título -->

<section class="mb-4">

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">

        <div>
            <h1 class="h3 fw-bold mb-2">{{ $producto->nombre }}</h1>
            <p class="text-muted mb-0">
                {{ $producto->codigo }} · {{ $producto->marca ?: 'Sin marca' }}
            </p>
        </div>

        <a href="{{ route('gestion.stock.index') }}" class="btn btn-outline-secondary">
            Ver todos los movimientos
        </a>

    </div>

</section>


@include('gestion.partials.alertas')


<!-- Los tres valores de stock -->

<section class="mb-4">

    <div class="row g-3">

        <div class="col-12 col-md-4">

            <div class="p-4 rounded-4 bg-white shadow-sm border h-100">

                <div class="text-muted">En depósito</div>

                <div class="display-6 fw-bold mb-0">{{ $producto->stock_fisico }}</div>

                <div class="text-muted small">Unidades que están físicamente.</div>

            </div>

        </div>

        <div class="col-12 col-md-4">

            <div class="p-4 rounded-4 bg-white shadow-sm border h-100">

                <div class="text-muted">Comprometidas</div>

                <div class="display-6 fw-bold mb-0">{{ $producto->stock_comprometido }}</div>

                <div class="text-muted small">Vendidas y pendientes de entrega.</div>

            </div>

        </div>

        <div class="col-12 col-md-4">

            <div class="p-4 rounded-4 bg-white shadow-sm border h-100 {{ $producto->stock_disponible < 0 ? 'border-danger' : '' }}">

                <div class="text-muted">Disponible</div>

                <div class="display-6 fw-bold mb-0 {{ $producto->stock_disponible < 0 ? 'text-danger' : '' }}">
                    {{ $producto->stock_disponible }}
                </div>

                <div class="small {{ $producto->stock_disponible < 0 ? 'text-danger' : 'text-muted' }}">
                    @if ($producto->stock_disponible < 0)
                    Se deben {{ abs($producto->stock_disponible) }} unidades a clientes.
                    @else
                    Lo que queda para vender.
                    @endif
                </div>

            </div>

        </div>

    </div>

</section>


@if ($producto->stock_disponible < $producto->punto_reposicion)

<div class="alert alert-warning" role="alert">
    El disponible está por debajo del punto de reposición ({{ $producto->punto_reposicion }}).
    @if ($producto->proveedor)
    Corresponde pedirle a {{ $producto->proveedor->razon_social }}.
    @endif
</div>

@endif


<div class="row g-4">

    <!-- Columna izquierda: historial -->
    <div class="col-12 col-lg-8">

        <section>

            <h2 class="h5 fw-bold mb-3">Historial</h2>

            <div class="table-responsive">

                <table class="table table-bordered table-hover align-middle text-center">

                    <thead class="table-dark">

                        <tr>
                            <th class="text-nowrap">Fecha</th>
                            <th class="text-nowrap">Tipo</th>
                            <th class="text-nowrap">Δ físico</th>
                            <th class="text-nowrap">Δ comprometido</th>
                            <th class="text-nowrap">Saldo</th>
                            <th class="text-nowrap">Responsable</th>
                            <th class="text-nowrap">Motivo</th>
                        </tr>

                    </thead>

                    <tbody>

                        @forelse ($movimientos as $movimiento)

                        <tr>

                            <td class="text-nowrap">
                                {{ $movimiento->created_at?->format('d/m/Y H:i') }}
                            </td>

                            <td>
                                <span class="badge text-bg-light border">
                                    {{ ucfirst(str_replace('_', ' ', $movimiento->tipo->value)) }}
                                </span>
                            </td>

                            <td class="fw-semibold {{ $movimiento->cantidad_fisico < 0 ? 'text-danger' : ($movimiento->cantidad_fisico > 0 ? 'text-success' : 'text-muted') }}">
                                {{ $movimiento->cantidad_fisico > 0 ? '+' : '' }}{{ $movimiento->cantidad_fisico }}
                            </td>

                            <td class="fw-semibold {{ $movimiento->cantidad_comprometido < 0 ? 'text-danger' : ($movimiento->cantidad_comprometido > 0 ? 'text-warning-emphasis' : 'text-muted') }}">
                                {{ $movimiento->cantidad_comprometido > 0 ? '+' : '' }}{{ $movimiento->cantidad_comprometido }}
                            </td>

                            <td class="text-nowrap">
                                {{ $movimiento->stock_fisico_resultante }}
                                <span class="text-muted">/</span>
                                {{ $movimiento->stock_comprometido_resultante }}
                            </td>

                            <td>
                                {{ $movimiento->usuario?->nombre_completo ?? 'Sistema' }}
                            </td>

                            <td class="text-start">
                                {{ $movimiento->motivo ?: '—' }}
                            </td>

                        </tr>

                        @empty

                        <tr>

                            <td colspan="7" class="text-center py-4 text-muted">
                                Este producto no tiene movimientos. Cargá el conteo inicial
                                para que el saldo quede respaldado por el histórico desde
                                el primer día.
                            </td>

                        </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>


            <!-- Paginación Laravel -->
            @if ($movimientos->hasPages())

            <div class="d-flex justify-content-center mt-3">

                {{ $movimientos->onEachSide(2)->links() }}

            </div>

            @endif

        </section>

    </div>


    <!-- Columna derecha: acciones -->
    <div class="col-12 col-lg-4">

        <!-- Conteo inicial -->
        @if ($movimientos->isEmpty())

        <section class="p-4 rounded-4 bg-light shadow-sm border mb-4">

            <h2 class="h6 fw-bold mb-1">Conteo inicial</h2>

            <p class="text-muted small">
                Solo se puede cargar mientras el producto no tenga ningún movimiento.
            </p>

            <form action="{{ route('gestion.stock.carga-inicial') }}" method="POST" autocomplete="off">

                @csrf

                <input type="hidden" name="conteos[0][producto_id]" value="{{ $producto->id }}">

                <label for="cantidadInicial" class="form-label fw-semibold">
                    Unidades contadas
                </label>

                <input
                    type="number"
                    class="form-control rounded-3"
                    id="cantidadInicial"
                    name="conteos[0][cantidad]"
                    value="0"
                    min="0"
                    max="999999"
                    required>

                <button type="submit" class="btn btn-success w-100 mt-3 fw-semibold">
                    Cargar el conteo
                </button>

            </form>

        </section>

        @endif


        <!-- Ajuste de inventario -->
        <section class="p-4 rounded-4 bg-light shadow-sm border mb-4">

            <h2 class="h6 fw-bold mb-1">Ajuste de inventario</h2>

            <p class="text-muted small">
                Diferencias de conteo, roturas, mermas. Siempre con motivo.
            </p>

            <form action="{{ route('gestion.stock.ajustar') }}" method="POST" autocomplete="off">

                @csrf

                <input type="hidden" name="producto_id" value="{{ $producto->id }}">

                <div class="mb-3">

                    <label for="cantidad" class="form-label fw-semibold">Unidades</label>

                    <input
                        type="number"
                        class="form-control rounded-3 @error('cantidad') is-invalid @enderror"
                        id="cantidad"
                        name="cantidad"
                        value="{{ old('cantidad') }}"
                        placeholder="Ej: -2"
                        required>

                    <div class="form-text">
                        Positivo suma, negativo resta. No puede dejar el físico bajo cero.
                    </div>

                    @error('cantidad')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror

                </div>

                <div class="mb-3">

                    <label for="motivo" class="form-label fw-semibold">Motivo</label>

                    <textarea
                        class="form-control rounded-3 @error('motivo') is-invalid @enderror"
                        id="motivo"
                        name="motivo"
                        rows="3"
                        maxlength="255"
                        style="resize: none"
                        placeholder="Ej: rotura en depósito."
                        required>{{ old('motivo') }}</textarea>

                    @error('motivo')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror

                </div>

                <button
                    type="submit"
                    class="btn btn-primary w-100 fw-semibold"
                    onclick="return confirm('¿Confirma registrar este ajuste de inventario?');">
                    Registrar el ajuste
                </button>

            </form>

        </section>


        <!-- Reposición -->
        <section class="p-4 rounded-4 bg-light shadow-sm border">

            <h2 class="h6 fw-bold mb-3">Reposición</h2>

            <div class="d-flex justify-content-between">
                <span class="text-muted">Punto de reposición</span>
                <span class="fw-semibold">{{ $producto->punto_reposicion }}</span>
            </div>

            <div class="d-flex justify-content-between mt-2">
                <span class="text-muted">Cantidad a pedir</span>
                <span class="fw-semibold">{{ $producto->cantidad_reposicion }}</span>
            </div>

            <div class="d-flex justify-content-between mt-2">
                <span class="text-muted">Proveedor</span>
                <span class="fw-semibold text-end">
                    {{ $producto->proveedor?->razon_social ?? '—' }}
                </span>
            </div>

        </section>

    </div>

</div>

@endsection
