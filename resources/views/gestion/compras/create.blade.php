@extends('layouts.gestion')

@section('title', 'Nuevo pedido')

@section('content')

    @include('gestion.partials.alertas')

    <div class="mb-4">
        <a href="{{ route('gestion.compras.index') }}" class="small text-secondary text-decoration-none">
            &larr; Pedidos a proveedores
        </a>
        <h1 class="h3 mt-1 mb-1">Nuevo pedido</h1>
        <p class="text-secondary mb-0 small">
            Un pedido va dirigido a un solo proveedor: primero se elige a quién, y después se cargan
            sus productos.
        </p>
    </div>

    @if ($proveedor === null)

        {{--
            Paso 1. El desplegable de productos depende del proveedor, así que
            se elige antes y la pantalla se recarga. Es un GET a la misma ruta:
            mantiene el botón «atrás» del navegador y permite entrar directo con
            ?proveedor=N desde la ficha del proveedor.
        --}}
        <div class="card" style="max-width: 560px;">
            <div class="card-body">
                <form method="GET" action="{{ route('gestion.compras.create') }}">
                    <label for="proveedor" class="form-label">Proveedor</label>

                    <select id="proveedor" name="proveedor" class="form-select mb-3" required>
                        <option value="">Elegí un proveedor…</option>
                        @foreach ($proveedores as $opcion)
                            <option value="{{ $opcion->id }}">{{ $opcion->razon_social }}</option>
                        @endforeach
                    </select>

                    <button type="submit" class="btn btn-primary">Continuar</button>
                </form>
            </div>
        </div>

    @else

        <div class="alert alert-light border d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <span class="text-secondary small d-block">Pedido dirigido a</span>
                <strong>{{ $proveedor->razon_social }}</strong>
                @if ($proveedor->email)
                    <span class="text-secondary small"> · {{ $proveedor->email }}</span>
                @endif
            </div>

            <a href="{{ route('gestion.compras.create') }}" class="btn btn-sm btn-outline-secondary">
                Cambiar proveedor
            </a>
        </div>

        @if ($productos->isEmpty())
            <div class="alert alert-warning">
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
