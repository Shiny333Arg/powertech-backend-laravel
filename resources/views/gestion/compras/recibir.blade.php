@extends('layouts.gestion')

@section('title', 'Recibir mercadería')

@section('content')

{{--
    Título.

    El número del pedido, el proveedor y el estado los escribe
    resources/js/gestion/compras/recepcion.js con lo que devuelve
    GET /api/v1/gestion/compras/{orden}: son datos del documento y la página ya
    no los trae.
--}}
<section class="mb-4">
    <h1 class="h3 fw-bold mb-2">
        Recibir mercadería
        <span class="badge text-bg-secondary align-middle ms-1 d-none" id="estadoPedido"></span>
    </h1>

    <p class="text-muted mb-0" id="encabezadoPedido">
        Cargando el pedido…
    </p>
</section>

{{--
    Avisos de la pantalla.

    <b>Acá aparece el resultado de la entrega</b>, que antes se mostraba —o
    mejor dicho, no se mostraba— en el detalle: el POST web redirigía a
    gestion.compras.show con un `session('status')`. Con la pantalla contra la
    API no hay redirect que pueda arrastrar un flash, así que el resumen de lo
    que entró se escribe en esta misma página, que es además donde el depósito
    sigue trabajando si falta cargar otro remito.
--}}
<div id="avisosRecepcion" aria-live="polite"></div>

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

                <table class="table table-bordered table-hover align-middle text-center mb-0" id="tablaRecepcion">

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

                    {{-- Los renglones los dibuja el módulo con `data.lineas`. --}}
                    <tbody id="cuerpoRecepcion">
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                Cargando los renglones…
                            </td>
                        </tr>
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

        {{--
            Confirmación de excedente.

            Aparece cuando el backend contesta 422 con el error en
            'confirmar_excedente', igual que antes: la primera vez el formulario
            volvía con el aviso y la casilla sin tildar. El texto del aviso lo
            escribe RecepcionRequest y lo muestra el módulo, así que el operador
            lee exactamente lo mismo que leía.

            <b>No es un error que haya que corregir</b>: si el proveedor mandó de
            más, lo correcto es registrarlo, porque la mercadería está en el
            depósito. Lo que conviene es mirarlo una vez antes de guardar, porque
            el total estimado del pedido se calculó sobre lo pedido y la factura
            va a venir por más.
        --}}
        <section class="p-4 rounded-4 border border-warning bg-warning-subtle mb-4 d-none" id="bloqueExcedente">

            <h2 class="h6 fw-bold mb-2">Recibiste más de lo pedido</h2>

            <p class="small mb-3" id="detalleExcedente"></p>

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

        {{--
            Historial de entregas.

            Sale de `data.recepciones`: no hay tabla de recepciones, cada entrada
            ES un movimiento de stock con origen en este pedido y el número de
            remito en su 'motivo'. Se repinta con la respuesta del 201, así que
            cargar un segundo remito ya no obliga a recargar la página.
        --}}
        <section class="p-4 rounded-4 bg-light shadow-sm border mb-4 d-none" id="bloqueHistorial">

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

                    <tbody id="cuerpoHistorial"></tbody>

                </table>

            </div>

        </section>

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
                placeholder="0001-00123456"
                class="form-control">

            <div class="invalid-feedback" id="errorRemito"></div>

            <div class="form-text mt-2">
                Queda guardado en cada movimiento de stock de esta entrega, así que después se
                puede saber qué remito trajo qué unidades.
            </div>

            <hr class="my-4">

            <button
                type="button"
                id="btnRegistrar"
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

@endsection

@push('scripts')
<script type="module">
    import { iniciar } from '{{ Vite::asset('resources/js/gestion/compras/recepcion.js') }}';

    iniciar({
        id: {{ $orden->id }},
        colores: @json($colores),
    });
</script>
@endpush
