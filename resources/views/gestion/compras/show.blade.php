@extends('layouts.gestion')

@section('title', 'PowerTech')

@section('content')

{{-- Título --}}
<section class="mb-4">
    <h1 class="h3 fw-bold mb-2">
        Pedido {{ $orden->numero }}
        <span class="badge text-bg-{{ $orden->estado->color() }} align-middle ms-1">
            {{ $orden->estado->etiqueta() }}
        </span>
    </h1>

    <p class="text-muted mb-0">
        {{ $orden->proveedor->razon_social }} · {{ $orden->origen->etiqueta() }}
    </p>
</section>

@include('gestion.partials.alertas')

{{-- Navegación y acciones --}}
<section class="mb-4 d-flex flex-column flex-md-row align-items-stretch gap-2">

    <a
        href="{{ route('gestion.compras.index') }}"
        class="btn btn-outline-secondary d-flex align-items-center justify-content-center gap-2">

        <svg xmlns="http://www.w3.org/2000/svg"
            width="18"
            height="18"
            fill="currentColor"
            viewBox="0 0 16 16">
            <path fill-rule="evenodd"
                d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8" />
        </svg>

        Volver a pedidos
    </a>

    <div class="d-flex flex-column flex-md-row gap-2 ms-md-auto">

        @can('update', $orden)
        <a
            href="{{ route('gestion.compras.edit', $orden) }}"
            class="btn btn-outline-secondary d-flex align-items-center justify-content-center gap-2">

            <svg xmlns="http://www.w3.org/2000/svg"
                width="18"
                height="18"
                fill="currentColor"
                viewBox="0 0 16 16">
                <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325" />
            </svg>

            Editar detalle
        </a>
        @endcan

        @can('aprobar', $orden)
        <form method="POST" action="{{ route('gestion.compras.aprobar', $orden) }}" class="d-flex">
            @csrf

            <button
                type="submit"
                class="btn btn-success d-flex align-items-center justify-content-center gap-2">

                <svg xmlns="http://www.w3.org/2000/svg"
                    width="18"
                    height="18"
                    fill="currentColor"
                    viewBox="0 0 16 16">
                    <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.992 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.093 2.093z" />
                </svg>

                Aprobar
            </button>
        </form>
        @endcan

        @can('enviar', $orden)
        <form method="POST" action="{{ route('gestion.compras.enviar', $orden) }}" class="d-flex">
            @csrf

            <button
                type="submit"
                class="btn btn-primary d-flex align-items-center justify-content-center gap-2">

                <svg xmlns="http://www.w3.org/2000/svg"
                    width="18"
                    height="18"
                    fill="currentColor"
                    viewBox="0 0 16 16">
                    <path d="M15.854.146a.5.5 0 0 0-.708 0L.146 15.146a.5.5 0 0 0 .708.708L6.5 10.207l3.293 3.293a.5.5 0 0 0 .707-.707L7.207 9.5 15.854.854a.5.5 0 0 0 0-.708" />
                    <path d="M6.5 10.207 2.707 6.414 15.146.146a.5.5 0 0 1 .708.708z" />
                </svg>

                Enviar remito por correo
            </button>
        </form>
        @endcan

        @can('cancelar', $orden)
        <button
            type="button"
            class="btn btn-outline-danger d-flex align-items-center justify-content-center gap-2"
            data-bs-toggle="modal"
            data-bs-target="#cancelarPedido">

            <svg xmlns="http://www.w3.org/2000/svg"
                width="18"
                height="18"
                fill="currentColor"
                viewBox="0 0 16 16">
                <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708" />
                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0-1A6 6 0 1 0 8 2a6 6 0 0 0 0 12" />
            </svg>

            Cancelar
        </button>
        @endcan

    </div>
</section>

{{-- Aviso sobre el canal de pedido --}}
@if ($orden->estado === \App\Enums\EstadoOrdenCompra::Aprobada)
    @if ($orden->proveedor->canal_pedido !== \App\Enums\CanalPedido::Email)
        <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
            <strong>Este proveedor no está configurado para recibir pedidos por correo.</strong>
            Su canal es «{{ $orden->proveedor->canal_pedido->etiqueta() }}».
            <a href="{{ route('gestion.proveedores.edit', $orden->proveedor) }}" class="alert-link">
                Cambialo a email
            </a>
            para poder enviarle el remito.

            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @elseif (blank($orden->proveedor->email))
        <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
            <strong>{{ $orden->proveedor->razon_social }} no tiene correo cargado.</strong>
            <a href="{{ route('gestion.proveedores.edit', $orden->proveedor) }}" class="alert-link">
                Cargalo en su ficha
            </a>
            para poder enviarle el remito.

            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif
@endif

@php
    $insuficientes = $orden->estaAbierta() ? $orden->lineasInsuficientes() : collect();
@endphp

{{-- Aviso de faltante --}}
@if ($insuficientes->isNotEmpty())
    <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
        <p class="mb-2">
            <strong>Este pedido ya no cubre el faltante.</strong>
            Se siguió vendiendo después de emitirlo, así que
            {{ $insuficientes->count() === 1 ? 'un producto va a quedar' : 'algunos productos van a quedar' }}
            en negativo incluso recibiendo todo.
        </p>

        <ul class="mb-2 small">
            @foreach ($insuficientes as $linea)
                <li>
                    <strong>{{ $linea->producto->nombre }}</strong> —
                    pendiente de recibir {{ $linea->pendiente }},
                    faltan {{ $linea->faltanteDescubierto() }} más
                    (disponible {{ $linea->producto->stock_disponible }},
                    punto de reposición {{ $linea->producto->punto_reposicion }})
                </li>
            @endforeach
        </ul>

        <p class="mb-0 small">
            @if ($orden->esBorrador())
                Podés ajustar las cantidades desde «Editar detalle» antes de aprobar.
            @else
                El detalle de un pedido aprobado no se modifica. El saldo se va a pedir
                solo en cuanto este pedido se cierre, o podés emitir otro a mano ahora.
            @endif
        </p>

        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
@endif

{{-- Contenido --}}
<div class="row g-4">

    {{-- Columna izquierda --}}
    <div class="col-12 col-lg-8">

        {{-- Detalle del pedido --}}
        <section class="p-4 rounded-4 bg-light shadow-sm border mb-4">

            <h2 class="h5 fw-bold mb-1">Detalle del pedido</h2>

            <p class="text-muted small mb-3">
                Productos, cantidades y costos estimados incluidos en este pedido.
            </p>

            <div class="table-responsive">

                <table class="table table-bordered table-hover align-middle text-center mb-0">

                    <thead class="table-dark">
                        <tr>
                            <th class="text-nowrap">Código</th>
                            <th class="text-nowrap">Producto</th>
                            <th class="text-nowrap">Pedido</th>
                            <th class="text-nowrap">Recibido</th>
                            <th class="text-nowrap">Costo est.</th>
                            <th class="text-nowrap">Subtotal</th>
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

                                    @if ($orden->estaAbierta() && ! $linea->cubreElFaltante())
                                        <div class="text-warning small mt-1">
                                            <span class="badge text-bg-warning">
                                                No alcanza
                                            </span>
                                            Faltan {{ $linea->faltanteDescubierto() }} unidades para cubrir el faltante actual.
                                        </div>
                                    @endif
                                </td>

                                <td class="text-nowrap fw-semibold">
                                    {{ $linea->cantidad_pedida }}
                                </td>

                                <td class="text-nowrap">
                                    {{ $linea->cantidad_recibida }}

                                    @if ($linea->pendiente > 0 && $orden->estado === \App\Enums\EstadoOrdenCompra::RecibidaParcial)
                                        <span class="badge text-bg-warning ms-1">
                                            faltan {{ $linea->pendiente }}
                                        </span>
                                    @endif
                                </td>

                                <td class="text-nowrap">
                                    $ {{ number_format((float) $linea->costo_unitario, 2, ',', '.') }}
                                </td>

                                <td class="text-nowrap fw-semibold">
                                    $ {{ number_format($linea->subtotal_estimado, 2, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                    <tfoot class="table-light">
                        <tr>
                            <td colspan="5" class="text-end fw-semibold">
                                Total estimado
                            </td>

                            <td class="text-end fw-bold text-nowrap">
                                $ {{ number_format((float) $orden->total_estimado, 2, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>

                </table>

            </div>

            <div class="form-text mt-2">
                Los importes son una estimación tomada del costo del catálogo, para dimensionar el
                pedido. No se le informan al proveedor: el precio real lo fija su factura.
            </div>

        </section>

    </div>

    {{-- Columna derecha --}}
    <div class="col-12 col-lg-4">

        {{-- Seguimiento --}}
        <section class="p-4 rounded-4 bg-light shadow-sm border mb-4">

            <h2 class="h6 fw-bold mb-3">
                Seguimiento
            </h2>

            <div class="d-flex justify-content-between gap-3 mb-2">
                <span class="text-muted">Emitido</span>
                <span class="text-end">{{ $orden->created_at->format('d/m/Y H:i') }}</span>
            </div>

            <div class="d-flex justify-content-between gap-3 mb-2">
                <span class="text-muted">Creado por</span>
                <span class="text-end">
                    {{ $orden->usuarioCreo?->usuario ?? 'Sistema (reposición)' }}
                </span>
            </div>

            @if ($orden->fecha_aprobacion)
                <div class="d-flex justify-content-between gap-3 mb-2">
                    <span class="text-muted">Aprobado por</span>
                    <span class="text-end">{{ $orden->usuarioAprobo?->usuario ?? '—' }}</span>
                </div>

                <div class="d-flex justify-content-between gap-3 mb-2">
                    <span class="text-muted">Fecha de aprobación</span>
                    <span class="text-end">{{ $orden->fecha_aprobacion->format('d/m/Y H:i') }}</span>
                </div>
            @endif

            @if ($orden->fecha_envio)
                <div class="d-flex justify-content-between gap-3 mb-2">
                    <span class="text-muted">Enviado</span>
                    <span class="text-end">{{ $orden->fecha_envio->format('d/m/Y H:i') }}</span>
                </div>
            @endif

            @if ($orden->fecha_entrega_estimada)
                <div class="d-flex justify-content-between gap-3">
                    <span class="text-muted">Entrega estimada</span>
                    <span class="text-end">{{ $orden->fecha_entrega_estimada->format('d/m/Y') }}</span>
                </div>
            @endif

        </section>

        {{-- Proveedor --}}
        <section class="p-4 rounded-4 bg-light shadow-sm border mb-4">

            <h2 class="h6 fw-bold mb-3">
                Proveedor
            </h2>

            <p class="fw-semibold mb-2">
                {{ $orden->proveedor->razon_social }}
            </p>

            @if ($orden->proveedor->contacto)
                <p class="text-muted mb-2">
                    {{ $orden->proveedor->contacto }}
                </p>
            @endif

            <p class="mb-2">
                {{ $orden->proveedor->email ?? '— sin correo —' }}
            </p>

            @if ($orden->proveedor->telefono)
                <p class="text-muted mb-0">
                    {{ $orden->proveedor->telefono }}
                </p>
            @endif

        </section>

        {{-- Observaciones --}}
        @if (filled($orden->observaciones))
            <section class="p-4 rounded-4 bg-light shadow-sm border">

                <h2 class="h6 fw-bold mb-3">
                    Observaciones
                </h2>

                <div style="white-space: pre-line;">
                    {{ $orden->observaciones }}
                </div>

            </section>
        @endif

    </div>
</div>

{{-- Modal de cancelación --}}
@can('cancelar', $orden)
<div class="modal fade" id="cancelarPedido" tabindex="-1" aria-hidden="true">

    <div class="modal-dialog">

        <form
            method="POST"
            action="{{ route('gestion.compras.cancelar', $orden) }}"
            class="modal-content">

            @csrf

            <div class="modal-header">
                <h3 class="modal-title h5">
                    Cancelar el pedido {{ $orden->numero }}
                </h3>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Cerrar">
                </button>
            </div>

            <div class="modal-body">

                <p class="small text-muted">
                    El pedido no se elimina: queda cancelado y con su historial. Sus productos
                    vuelven a entrar en la reposición automática, así que si siguen bajo el punto
                    de reposición <strong>se va a generar un pedido nuevo de inmediato</strong>,
                    por la cantidad que haga falta en ese momento.
                </p>

                <p class="small text-muted">
                    Si estás cancelando porque el proveedor discontinuó el producto, apagale
                    primero la reposición automática en su ficha: si no, el sistema lo va a
                    volver a pedir.
                </p>

                <label for="motivo" class="form-label fw-semibold">
                    Motivo
                </label>

                <textarea
                    id="motivo"
                    name="motivo"
                    rows="3"
                    maxlength="255"
                    class="form-control @error('motivo') is-invalid @enderror"
                    required></textarea>

                @error('motivo')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror

            </div>

            <div class="modal-footer">

                <button
                    type="button"
                    class="btn-outline-secondary btn"
                    data-bs-dismiss="modal">
                    Volver
                </button>

                <button
                    type="submit"
                    class="btn btn-danger">
                    Cancelar el pedido
                </button>

            </div>

        </form>

    </div>

</div>
@endcan

@endsection