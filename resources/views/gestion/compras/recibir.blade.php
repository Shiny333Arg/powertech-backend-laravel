@extends('layouts.gestion')

@section('title', 'PowerTech')

@section('content')

{{-- Título --}}
<section class="mb-4">
    <h1 class="h3 fw-bold mb-2">
        Recibir mercadería
        <span class="badge text-bg-{{ $orden->estado->color() }} align-middle ms-1">
            {{ $orden->estado->etiqueta() }}
        </span>
    </h1>

    <p class="text-muted mb-0">
        Pedido {{ $orden->numero }} · {{ $orden->proveedor->razon_social }}
    </p>
</section>

@include('gestion.partials.alertas')

{{-- Navegación --}}
<section class="mb-4">
    <a
        href="{{ route('gestion.compras.show', $orden) }}"
        class="btn btn-outline-secondary d-flex align-items-center justify-content-center gap-2 d-md-inline-flex">

        <svg xmlns="http://www.w3.org/2000/svg"
            width="18"
            height="18"
            fill="currentColor"
            viewBox="0 0 16 16">
            <path fill-rule="evenodd"
                d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8" />
        </svg>

        Volver al pedido
    </a>
</section>

<form method="POST" action="{{ route('gestion.compras.recibir.store', $orden) }}">
    @csrf

    <div class="row g-4">

        {{-- Columna izquierda --}}
        <div class="col-12 col-lg-8">

            {{-- Qué entró --}}
            <section class="p-4 rounded-4 bg-light shadow-sm border mb-4">

                <h2 class="h5 fw-bold mb-1">Qué entró</h2>

                <p class="text-muted small mb-3">
                    Cargá solo las unidades de <strong>esta</strong> entrega. Lo que no vino va en
                    cero: el pedido queda abierto por el saldo y se puede seguir recibiendo después.
                </p>

                <div class="table-responsive">

                    <table class="table table-bordered table-hover align-middle text-center mb-0">

                        <thead class="table-dark">
                            <tr>
                                <th class="text-nowrap">Código</th>
                                <th class="text-nowrap">Producto</th>
                                <th class="text-nowrap">Pedido</th>
                                <th class="text-nowrap">Recibido</th>
                                <th class="text-nowrap">Falta</th>
                                <th class="text-nowrap" style="min-width: 8rem;">Entra ahora</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($orden->lineas as $linea)
                                <tr>
                                    <td class="text-nowrap">
                                        {{ $linea->producto->codigo }}
                                    </td>

                                    <td class="text-start">
                                        <div class="fw-semibold">
                                            {{ $linea->producto->nombre }}
                                        </div>

                                        @if ($linea->tieneExcedente())
                                            <div class="text-muted small mt-1">
                                                <span class="badge text-bg-info">De más</span>
                                                Ya entraron {{ $linea->excedente }} unidades por encima de lo pedido.
                                            </div>
                                        @endif
                                    </td>

                                    <td class="text-nowrap fw-semibold">
                                        {{ $linea->cantidad_pedida }}
                                    </td>

                                    <td class="text-nowrap">
                                        {{ $linea->cantidad_recibida }}
                                    </td>

                                    <td class="text-nowrap">
                                        @if ($linea->pendiente > 0)
                                            <span class="badge text-bg-warning">
                                                {{ $linea->pendiente }}
                                            </span>
                                        @else
                                            <span class="text-success">—</span>
                                        @endif
                                    </td>

                                    <td>
                                        <input
                                            type="number"
                                            name="cantidades[{{ $linea->id }}]"
                                            class="form-control text-center js-cantidad"
                                            min="0"
                                            step="1"
                                            inputmode="numeric"
                                            data-pendiente="{{ $linea->pendiente }}"
                                            value="{{ old('cantidades.' . $linea->id, 0) }}"
                                            aria-label="Unidades recibidas de {{ $linea->producto->nombre }}">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>

                    </table>

                </div>

                <div class="d-flex flex-column flex-md-row gap-2 mt-3">
                    <button
                        type="button"
                        id="completarPendiente"
                        class="btn btn-outline-secondary btn-sm">
                        Llenar con lo que falta
                    </button>

                    <button
                        type="button"
                        id="limpiarCantidades"
                        class="btn btn-outline-secondary btn-sm">
                        Poner todo en cero
                    </button>
                </div>

            </section>

            {{-- Confirmación de excedente --}}
            <!-- '@'e'r'r'or('cantidades') -->
            @error('confirmar_excedente')
                <section class="p-4 rounded-4 border border-warning bg-warning-subtle mb-4">

                    <h2 class="h6 fw-bold mb-2">Recibiste más de lo pedido</h2>

                    <p class="small mb-3">
                        No es un error que haya que corregir: si el proveedor mandó de más, lo
                        correcto es registrarlo, porque la mercadería está en el depósito. Lo que sí
                        conviene es mirarlo una vez antes de guardar, porque el total estimado del
                        pedido se calculó sobre lo pedido y la factura del proveedor va a venir por
                        más.
                    </p>

                    <div class="form-check">
                        <input
                            type="checkbox"
                            name="confirmar_excedente"
                            id="confirmar_excedente"
                            value="1"
                            class="form-check-input">

                        <label for="confirmar_excedente" class="form-check-label">
                            Confirmo que entró más de lo pedido y quiero registrarlo así.
                        </label>
                    </div>

                </section>
            @enderror

            {{-- Historial de entregas --}}
            @if ($orden->recepciones->isNotEmpty())
                <section class="p-4 rounded-4 bg-light shadow-sm border mb-4">

                    <h2 class="h6 fw-bold mb-1">Entregas anteriores</h2>

                    <p class="text-muted small mb-3">
                        Cada entrada quedó registrada como un movimiento de stock contra este pedido.
                    </p>

                    <div class="table-responsive">

                        <table class="table table-sm align-middle mb-0">

                            <thead class="table-light">
                                <tr>
                                    <th class="text-nowrap">Fecha</th>
                                    <th class="text-nowrap">Producto</th>
                                    <th class="text-nowrap text-center">Unidades</th>
                                    <th class="text-nowrap">Remito</th>
                                    <th class="text-nowrap">Cargó</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($orden->recepciones as $movimiento)
                                    <tr>
                                        <td class="text-nowrap">
                                            {{ $movimiento->created_at->format('d/m/Y H:i') }}
                                        </td>

                                        <td>
                                            {{ $movimiento->producto?->nombre ?? '—' }}
                                        </td>

                                        <td class="text-center fw-semibold">
                                            +{{ $movimiento->cantidad_fisico }}
                                        </td>

                                        <td class="text-nowrap">
                                            {{ $movimiento->motivo ?? '—' }}
                                        </td>

                                        <td class="text-nowrap">
                                            {{ $movimiento->usuario?->usuario ?? 'Sistema' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>

                        </table>

                    </div>

                </section>
            @endif

        </div>

        {{-- Columna derecha --}}
        <div class="col-12 col-lg-4">

            {{-- Remito y confirmación --}}
            <section class="p-4 rounded-4 bg-light shadow-sm border mb-4">

                <h2 class="h6 fw-bold mb-3">Remito del proveedor</h2>

                <label for="remito" class="form-label">
                    Número
                </label>

                <input
                    type="text"
                    id="remito"
                    name="remito"
                    maxlength="60"
                    value="{{ old('remito') }}"
                    placeholder="0001-00123456"
                    class="form-control @error('remito') is-invalid @enderror">

                @error('remito')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror

                <div class="form-text mt-2">
                    Queda guardado en cada movimiento de stock de esta entrega, así que después se
                    puede saber qué remito trajo qué unidades.
                </div>

                <hr class="my-4">

                <button
                    type="submit"
                    class="btn btn-success w-100 d-flex align-items-center justify-content-center gap-2">

                    <svg xmlns="http://www.w3.org/2000/svg"
                        width="18"
                        height="18"
                        fill="currentColor"
                        viewBox="0 0 16 16">
                        <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.992 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.093 2.093z" />
                    </svg>

                    Registrar la entrega
                </button>

            </section>

            {{-- Qué va a pasar --}}
            <section class="p-4 rounded-4 bg-light shadow-sm border">

                <h2 class="h6 fw-bold mb-3">Qué va a pasar al guardar</h2>

                <ul class="small text-muted mb-0 ps-3">
                    <li class="mb-2">
                        Las unidades suman al <strong>stock físico</strong>. El comprometido no se
                        toca: el disponible sube solo.
                    </li>

                    <li class="mb-2">
                        Si quedan renglones sin completar, el pedido sigue abierto y se puede recibir
                        de nuevo.
                    </li>

                    <li class="mb-0">
                        Las <strong>preventas que esperaban esta mercadería</strong> pasan a
                        preparación, de la más vieja a la más nueva: quien pagó primero se entrega
                        primero.
                    </li>
                </ul>

            </section>

        </div>
    </div>

</form>

@push('scripts')
<script>
    (function () {
        const campos = Array.from(document.querySelectorAll('.js-cantidad'));

        const asignar = (valor) => campos.forEach((campo) => {
            campo.value = valor === 'pendiente' ? campo.dataset.pendiente : 0;
        });

        document.getElementById('completarPendiente')
            ?.addEventListener('click', () => asignar('pendiente'));

        document.getElementById('limpiarCantidades')
            ?.addEventListener('click', () => asignar(0));
    })();
</script>
@endpush

@endsection
