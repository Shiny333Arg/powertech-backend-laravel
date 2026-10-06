@extends('layouts.gestion')

@section('title', 'Nuevo pedido')

@section('content')

    @include('gestion.partials.alertas')

    {{-- Título principal --}}
    <section class="mb-4">
        <h1 class="h3 fw-bold mb-2">Nuevo pedido</h1>
        <p class="text-muted mb-0">
            Un pedido va dirigido a un solo proveedor: primero se elige a quién, y después se cargan sus productos.
        </p>
    </section>

    @if ($proveedor === null)

        {{--
            Paso 1. El desplegable de productos depende del proveedor, así que
            se elige antes y la pantalla se recarga. Es un GET a la misma ruta:
            mantiene el botón «atrás» del navegador y permite entrar directo con
            ?proveedor=N desde la ficha del proveedor.
        --}}
        <section class="mb-4">
            <div class="p-4 rounded-4 bg-light shadow-sm border" style="max-width: 560px;">
                <form method="GET" action="{{ route('gestion.compras.create') }}">
                    <div class="mb-4">
                        <label for="proveedor" class="form-label fw-semibold">Proveedor</label>

                        <select id="proveedor"
                                name="proveedor"
                                class="form-select rounded-3"
                                required>
                            <option value="">Elegí un proveedor…</option>

                            @foreach ($proveedores as $opcion)
                                <option value="{{ $opcion->id }}">{{ $opcion->razon_social }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        Continuar
                    </button>
                </form>
            </div>
        </section>

    @else

        {{-- Proveedor seleccionado --}}
        <section class="mb-4">
            <div class="p-3 rounded-4 bg-light shadow-sm border d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <span class="text-muted small d-block mb-1">Pedido dirigido a</span>

                    <div class="fw-semibold">
                        {{ $proveedor->razon_social }}

                        @if ($proveedor->email)
                            <span class="text-muted fw-normal">· {{ $proveedor->email }}</span>
                        @endif
                    </div>
                </div>

                <a href="{{ route('gestion.compras.create') }}"
                   class="btn btn-outline-secondary btn-sm">
                    Cambiar proveedor
                </a>
            </div>
        </section>

        @if ($productos->isEmpty())

            <div class="alert alert-warning shadow-sm">
                <strong>{{ $proveedor->razon_social }} no tiene productos activos asignados.</strong>
                Asignale el proveedor a los productos que le compramos desde la ficha de cada producto.
            </div>

        @else

            @include('gestion.compras.partials.formulario', [
                'accion' => route('gestion.compras.store'),
                'metodo' => 'POST',
            ])

        @endif

    @endif

@endsection
