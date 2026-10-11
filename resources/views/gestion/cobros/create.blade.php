@extends('layouts.gestion')

@section('title', 'PowerTech')

@section('content')

<!-- Título -->

<section class="mb-4">

    <h1 class="h3 fw-bold mb-2">
        Cobrar venta {{ $venta->numeroFormateado() }}
    </h1>

    <p class="text-muted mb-0">
        {{ $venta->cliente?->razon_social ?? 'Consumidor final' }}
        @if ($venta->cliente)
        · {{ $venta->cliente->condicionIva?->nombre }}
        @endif
    </p>

</section>


@include('gestion.partials.alertas')


<form action="{{ route('gestion.cobros.store', $venta) }}" method="POST" autocomplete="off" id="formCobro">

    @csrf


    {{-- Errores generales de validación --}}
    @if ($errors->any())

    <div class="alert alert-danger">

        <strong>Revisá estos campos:</strong>

        <ul class="mb-0 mt-2">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>

    </div>

    @endif


    <div class="row g-4">

        <!-- Columna izquierda: medios de pago -->
        <div class="col-12 col-lg-8">

            <section class="p-4 rounded-4 bg-light shadow-sm border mb-4">

                <h2 class="h5 fw-bold mb-1">Medios de pago</h2>

                <p class="text-muted small">
                    Se puede cobrar en partes: efectivo y tarjeta en la misma venta.
                </p>

                <div id="filasPago"></div>

                <button
                    type="button"
                    class="btn btn-outline-primary mt-3 d-flex align-items-center gap-2"
                    id="btnAgregarPago">

                    <svg xmlns="http://www.w3.org/2000/svg"
                        width="16"
                        height="16"
                        fill="currentColor"
                        class="bi bi-plus-circle"
                        viewBox="0 0 16 16">

                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16" />

                        <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4" />
                    </svg>

                    Agregar otro medio
                </button>

                @error('pagos')
                <div class="text-danger small mt-2">
                    {{ $message }}
                </div>
                @enderror

            </section>


            @if ($venta->modo_entrega->value === 'retiro')

            <section class="p-4 rounded-4 bg-light shadow-sm border">

                <h2 class="h5 fw-bold mb-3">Entrega</h2>

                <div class="form-check form-switch">

                    <input
                        class="form-check-input"
                        type="checkbox"
                        role="switch"
                        id="entregar_inmediato"
                        name="entregar_inmediato"
                        value="1"
                        @checked(old('entregar_inmediato', true))>

                    <label class="form-check-label fw-semibold" for="entregar_inmediato">
                        El cliente se lleva la mercadería ahora
                    </label>

                </div>

                <div class="form-text">
                    Descuenta el stock físico en el mismo acto. Si no hay unidades
                    en depósito, la venta queda pendiente de stock aunque tildes esto.
                </div>

            </section>

            @endif

        </div>


        <!-- Columna derecha: totales -->
        <div class="col-12 col-lg-4">

            <section class="p-4 rounded-4 bg-white shadow-sm border mb-4">

                <div class="d-flex justify-content-between">
                    <span class="text-muted">Total de la venta</span>
                    <span class="text-nowrap">$ {{ number_format($venta->total, 2, ',', '.') }}</span>
                </div>

                @php
                    $yaCobrado = $venta->pagos
                        ->filter(fn ($p) => $p->estado->value === 'acreditado')
                        ->sum('monto');
                @endphp

                @if ($yaCobrado > 0)
                <div class="d-flex justify-content-between mt-2">
                    <span class="text-muted">Ya cobrado</span>
                    <span class="text-nowrap">$ {{ number_format($yaCobrado, 2, ',', '.') }}</span>
                </div>
                @endif

                <hr>

                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">A cobrar</span>
                    <span class="h3 fw-bold mb-0 text-nowrap" id="montoSaldo">
                        $ {{ number_format($saldo, 2, ',', '.') }}
                    </span>
                </div>

                <div class="d-flex justify-content-between mt-3">
                    <span class="text-muted">Ingresado</span>
                    <span class="text-nowrap" id="montoIngresado">$ 0,00</span>
                </div>

                <div class="d-flex justify-content-between">
                    <span class="text-muted">Falta</span>
                    <span class="fw-semibold text-nowrap" id="montoFalta">$ 0,00</span>
                </div>

                <div class="alert alert-danger small mt-3 d-none" id="avisoExcedido">
                    Estás ingresando más que el saldo. El vuelto no se registra
                    como pago: ajustá el importe.
                </div>

                <div class="alert alert-warning small mt-3 d-none" id="avisoParcial">
                    Pago parcial. La venta queda confirmada con saldo y todavía no se factura.
                </div>

                <div class="form-text mt-3">
                    Al completarse el total se emite la factura automáticamente.
                </div>

                <div class="mt-4 d-flex flex-wrap gap-2">

                    <button
                        type="submit"
                        class="btn btn-success px-4 fw-semibold shadow-sm"
                        id="btnCobrar">
                        Registrar el cobro
                    </button>

                    <a
                        href="{{ route('gestion.ventas.show', $venta) }}"
                        class="btn btn-outline-secondary px-4 fw-semibold shadow-sm">
                        Volver
                    </a>

                </div>

            </section>


            <!-- Qué se está cobrando -->
            <section class="p-4 rounded-4 bg-light shadow-sm border">

                <h2 class="h6 fw-bold mb-3">Qué se está cobrando</h2>

                <ul class="list-group list-group-flush">

                    @foreach ($venta->lineas as $linea)

                    <li class="list-group-item bg-transparent d-flex justify-content-between gap-3 px-0">

                        <span>{{ $linea->cantidad }} × {{ $linea->descripcion }}</span>

                        <span class="text-nowrap">
                            $ {{ number_format($linea->total, 2, ',', '.') }}
                        </span>

                    </li>

                    @endforeach

                </ul>

            </section>

        </div>

    </div>

</form>


<script>
    (function () {
        'use strict';

        var saldo = Number(@json($saldo));

        var contenedor   = document.getElementById('filasPago');
        var btnAgregar   = document.getElementById('btnAgregarPago');
        var ingresado    = document.getElementById('montoIngresado');
        var falta        = document.getElementById('montoFalta');
        var avisoExceso  = document.getElementById('avisoExcedido');
        var avisoParcial = document.getElementById('avisoParcial');
        var btnCobrar    = document.getElementById('btnCobrar');

        // La primera fila arranca con el saldo completo: el caso normal del
        // mostrador es un solo medio de pago por el total.
        var pagos = [{ metodo: 'efectivo', monto: saldo, cuotas: '' }];

        function pesos(n) {
            return '$ ' + Number(n).toLocaleString('es-AR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function dibujar() {
            contenedor.innerHTML = '';

            pagos.forEach(function (pago, i) {
                var fila = document.createElement('div');
                fila.className = 'row g-3 align-items-end mb-3';

                fila.innerHTML =
                    '<div class="col-12 col-md-4">' +
                        '<label class="form-label fw-semibold" for="metodo' + i + '">Medio</label>' +
                        '<select class="form-select rounded-3" id="metodo' + i + '" ' +
                            'name="pagos[' + i + '][metodo]" data-campo="metodo" data-indice="' + i + '">' +
                            '<option value="efectivo">Efectivo</option>' +
                            '<option value="tarjeta">Tarjeta</option>' +
                            '<option value="transferencia">Transferencia</option>' +
                        '</select>' +
                    '</div>' +
                    '<div class="col-8 col-md-4">' +
                        '<label class="form-label fw-semibold" for="monto' + i + '">Importe</label>' +
                        '<input type="number" class="form-control rounded-3" id="monto' + i + '" ' +
                            'name="pagos[' + i + '][monto]" value="' + pago.monto + '" ' +
                            'min="0.01" step="0.01" required data-campo="monto" data-indice="' + i + '">' +
                    '</div>' +
                    '<div class="col-4 col-md-3' + (pago.metodo === 'tarjeta' ? '' : ' d-none') + '">' +
                        '<label class="form-label fw-semibold" for="cuotas' + i + '">Cuotas</label>' +
                        '<input type="number" class="form-control rounded-3" id="cuotas' + i + '" ' +
                            (pago.metodo === 'tarjeta' ? 'name="pagos[' + i + '][cuotas]" ' : '') +
                            'value="' + (pago.cuotas || 1) + '" min="1" max="24" ' +
                            'data-campo="cuotas" data-indice="' + i + '">' +
                    '</div>' +
                    '<div class="col-12 col-md-1 d-grid">' +
                        (pagos.length > 1
                            ? '<button type="button" class="btn btn-outline-danger" data-quitar="' + i + '" ' +
                              'title="Quitar medio de pago">&times;</button>'
                            : '') +
                    '</div>';

                contenedor.appendChild(fila);

                fila.querySelector('[data-campo="metodo"]').value = pago.metodo;
            });

            actualizarTotales();
        }

        function actualizarTotales() {
            var cobrado = pagos.reduce(function (a, p) {
                var n = parseFloat(p.monto);
                return a + (isNaN(n) ? 0 : n);
            }, 0);

            var restante = Math.round((saldo - cobrado) * 100) / 100;

            ingresado.textContent = pesos(cobrado);
            falta.textContent = pesos(restante);
            falta.classList.toggle('text-danger', restante < 0);

            avisoExceso.classList.toggle('d-none', restante >= 0);
            avisoParcial.classList.toggle('d-none', !(restante > 0 && cobrado > 0));

            btnCobrar.disabled = cobrado <= 0 || restante < 0;
        }

        contenedor.addEventListener('input', function (e) {
            var campo = e.target.dataset.campo;
            if (!campo) return;

            pagos[Number(e.target.dataset.indice)][campo] = e.target.value;
            actualizarTotales();
        });

        contenedor.addEventListener('change', function (e) {
            if (e.target.dataset.campo !== 'metodo') return;

            pagos[Number(e.target.dataset.indice)].metodo = e.target.value;
            dibujar();
        });

        contenedor.addEventListener('click', function (e) {
            var boton = e.target.closest('[data-quitar]');
            if (!boton) return;

            pagos.splice(Number(boton.dataset.quitar), 1);
            dibujar();
        });

        btnAgregar.addEventListener('click', function () {
            var cobrado = pagos.reduce(function (a, p) {
                var n = parseFloat(p.monto);
                return a + (isNaN(n) ? 0 : n);
            }, 0);

            pagos.push({
                metodo: 'efectivo',
                monto: Math.max(0, Math.round((saldo - cobrado) * 100) / 100),
                cuotas: ''
            });

            dibujar();
        });

        dibujar();
    })();
</script>

@endsection
