@extends('layouts.gestion')

@section('title', "Editar {$orden->numero}")

@section('content')

    @include('gestion.partials.alertas')

    <div class="mb-4">
        <a href="{{ route('gestion.compras.show', $orden) }}" class="small text-secondary text-decoration-none">
            &larr; {{ $orden->numero }}
        </a>
        <h1 class="h3 mt-1 mb-1">Editar el detalle de {{ $orden->numero }}</h1>
        <p class="text-secondary mb-0 small">
            Solo se edita mientras está en borrador. Una vez aprobada, el detalle queda fijo: si hay que
            cambiar algo, se cancela y se emite otro pedido.
        </p>
    </div>

    <div class="alert alert-light border">
        <span class="text-secondary small d-block">Pedido dirigido a</span>
        <strong>{{ $proveedor->razon_social }}</strong>
        <span class="text-secondary small d-block mt-1">
            El proveedor de un pedido no se cambia: cambiar de destinatario es otro pedido.
        </span>
    </div>

    @if ($orden->origen === \App\Enums\OrigenOrdenCompra::Automatica)
        <div class="alert alert-info small">
            Este pedido lo generó la reposición automática. Editarlo es normal —para ajustar cantidades
            antes de aprobarlo—, pero tené en cuenta que las cantidades sugeridas cubren el faltante
            hasta el punto de reposición más el lote de compra de cada producto.
        </div>
    @endif

    @include('gestion.compras.partials.formulario', [
        'accion' => route('gestion.compras.update', $orden),
        'metodo' => 'PUT',
    ])

@endsection
