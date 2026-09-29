@extends('layouts.gestion')

@section('title', "Pedido {$orden->numero}")

@section('content')

    @include('gestion.partials.alertas')

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <a href="{{ route('gestion.compras.index') }}" class="small text-secondary text-decoration-none">
                &larr; Pedidos a proveedores
            </a>

            <h1 class="h3 mt-1 mb-2">
                {{ $orden->numero }}
                <span class="badge text-bg-{{ $orden->estado->color() }} align-middle ms-1">
                    {{ $orden->estado->etiqueta() }}
                </span>
            </h1>

            <p class="text-secondary mb-0">
                {{ $orden->proveedor->razon_social }} · {{ $orden->origen->etiqueta() }}
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            @can('update', $orden)
                <a href="{{ route('gestion.compras.edit', $orden) }}" class="btn btn-outline-secondary">
                    Editar detalle
                </a>
            @endcan

            @can('aprobar', $orden)
                <form method="POST" action="{{ route('gestion.compras.aprobar', $orden) }}">
                    @csrf
                    <button type="submit" class="btn btn-success">Aprobar</button>
                </form>
            @endcan

            @can('enviar', $orden)
                <form method="POST" action="{{ route('gestion.compras.enviar', $orden) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        Enviar remito por correo
                    </button>
                </form>
            @endcan

            @can('cancelar', $orden)
                <button type="button" class="btn btn-outline-danger"
                        data-bs-toggle="modal" data-bs-target="#cancelarPedido">
                    Cancelar
                </button>
            @endcan
        </div>
    </div>

    {{--
        El aviso que evita el error mas comun del circuito: la orden esta
        aprobada pero el proveedor no tiene correo o tiene otro canal, asi que
        el boton de enviar va a fallar. Decirlo antes de que lo aprieten.
    --}}
    @if ($orden->estado === \App\Enums\EstadoOrdenCompra::Aprobada)
        @if ($orden->proveedor->canal_pedido !== \App\Enums\CanalPedido::Email)
            <div class="alert alert-warning">
                <strong>Este proveedor no está configurado para recibir pedidos por correo.</strong>
                Su canal es «{{ $orden->proveedor->canal_pedido->etiqueta() }}».
                <a href="{{ route('gestion.proveedores.edit', $orden->proveedor) }}" class="alert-link">
                    Cambialo a email
                </a>
                para poder enviarle el remito.
            </div>
        @elseif (blank($orden->proveedor->email))
            <div class="alert alert-warning">
                <strong>{{ $orden->proveedor->razon_social }} no tiene correo cargado.</strong>
                <a href="{{ route('gestion.proveedores.edit', $orden->proveedor) }}" class="alert-link">
                    Cargalo en su ficha
                </a>
                para poder enviarle el remito.
            </div>
        @endif
    @endif

    <div class="row g-4">

        {{-- Detalle --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header bg-white">
                    <h2 class="h6 mb-0">Detalle del pedido</h2>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr class="text-secondary small text-uppercase">
                                <th scope="col">Código</th>
                                <th scope="col">Producto</th>
                                <th scope="col" class="text-end">Pedido</th>
                                <th scope="col" class="text-end">Recibido</th>
                                <th scope="col" class="text-end">Costo est.</th>
                                <th scope="col" class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($orden->lineas as $linea)
                                <tr>
                                    <td class="text-nowrap">{{ $linea->producto->codigo }}</td>
                                    <td>{{ $linea->producto->nombre }}</td>
                                    <td class="text-end fw-semibold">{{ $linea->cantidad_pedida }}</td>
                                    <td class="text-end">
                                        {{ $linea->cantidad_recibida }}
                                        @if ($linea->pendiente > 0 && $orden->estado === \App\Enums\EstadoOrdenCompra::RecibidaParcial)
                                            <span class="badge text-bg-warning ms-1">faltan {{ $linea->pendiente }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end text-nowrap">
                                        $ {{ number_format((float) $linea->costo_unitario, 2, ',', '.') }}
                                    </td>
                                    <td class="text-end text-nowrap">
                                        $ {{ number_format($linea->subtotal_estimado, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5" class="text-end text-secondary">Total estimado</td>
                                <td class="text-end fw-bold text-nowrap">
                                    $ {{ number_format((float) $orden->total_estimado, 2, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="card-footer bg-white small text-secondary">
                    Los importes son una estimación tomada del costo del catálogo, para dimensionar el
                    pedido. No se le informan al proveedor: el precio real lo fija su factura.
                </div>
            </div>
        </div>

        {{-- Ficha --}}
        <div class="col-12 col-lg-4">
            <div class="card mb-3">
                <div class="card-header bg-white">
                    <h2 class="h6 mb-0">Seguimiento</h2>
                </div>

                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-secondary">Emitido</span>
                        <span>{{ $orden->created_at->format('d/m/Y H:i') }}</span>
                    </li>

                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-secondary">Creado por</span>
                        <span>
                            {{-- Las automaticas no tienen autor: las creo el sistema. --}}
                            {{ $orden->usuarioCreo?->usuario ?? 'Sistema (reposición)' }}
                        </span>
                    </li>

                    @if ($orden->fecha_aprobacion)
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-secondary">Aprobado por</span>
                            <span>{{ $orden->usuarioAprobo?->usuario ?? '—' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-secondary">Fecha de aprobación</span>
                            <span>{{ $orden->fecha_aprobacion->format('d/m/Y H:i') }}</span>
                        </li>
                    @endif

                    @if ($orden->fecha_envio)
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-secondary">Enviado</span>
                            <span>{{ $orden->fecha_envio->format('d/m/Y H:i') }}</span>
                        </li>
                    @endif

                    @if ($orden->fecha_entrega_estimada)
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-secondary">Entrega estimada</span>
                            <span>{{ $orden->fecha_entrega_estimada->format('d/m/Y') }}</span>
                        </li>
                    @endif
                </ul>
            </div>

            <div class="card mb-3">
                <div class="card-header bg-white">
                    <h2 class="h6 mb-0">Proveedor</h2>
                </div>

                <div class="card-body small">
                    <p class="fw-semibold mb-1">{{ $orden->proveedor->razon_social }}</p>

                    @if ($orden->proveedor->contacto)
                        <p class="mb-1 text-secondary">{{ $orden->proveedor->contacto }}</p>
                    @endif

                    <p class="mb-1">{{ $orden->proveedor->email ?? '— sin correo —' }}</p>

                    @if ($orden->proveedor->telefono)
                        <p class="mb-0 text-secondary">{{ $orden->proveedor->telefono }}</p>
                    @endif
                </div>
            </div>

            @if (filled($orden->observaciones))
                <div class="card">
                    <div class="card-header bg-white">
                        <h2 class="h6 mb-0">Observaciones</h2>
                    </div>
                    <div class="card-body small" style="white-space: pre-line;">{{ $orden->observaciones }}</div>
                </div>
            @endif
        </div>

    </div>

    {{-- Cancelar: exige motivo, como el resto de las bajas del sistema. --}}
    @can('cancelar', $orden)
        <div class="modal fade" id="cancelarPedido" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('gestion.compras.cancelar', $orden) }}" class="modal-content">
                    @csrf

                    <div class="modal-header">
                        <h3 class="modal-title h6">Cancelar el pedido {{ $orden->numero }}</h3>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>

                    <div class="modal-body">
                        <p class="small text-secondary">
                            El pedido no se elimina: queda cancelado y con su historial. Sus productos
                            vuelven a entrar en la reposición automática, así que si siguen bajo el punto
                            de reposición se va a generar un pedido nuevo.
                        </p>

                        <label for="motivo" class="form-label">Motivo</label>
                        <textarea id="motivo" name="motivo" rows="3" maxlength="255"
                                  class="form-control @error('motivo') is-invalid @enderror" required></textarea>

                        @error('motivo')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Volver</button>
                        <button type="submit" class="btn btn-danger">Cancelar el pedido</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan

@endsection
