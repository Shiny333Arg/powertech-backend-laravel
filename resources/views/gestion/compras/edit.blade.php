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

    {{--
        El destinatario ya no se muestra como un dato fijo del pedido, porque
        dejó de serlo: sale de los productos que tenga el detalle. Agregar uno
        de otro proveedor convierte este pedido en uno repartido, y sacarle
        todos los renglones de un proveedor lo devuelve a ser una orden común
        dirigida al que queda.

        No es una excepción a la vieja regla de «el destinatario no se cambia»:
        lo que se edita sigue siendo el detalle. El destinatario es la
        consecuencia.
    --}}
    @php
        $destinatarios = $orden->proveedoresInvolucrados();
    @endphp

    <div class="alert alert-light border">
        @if ($destinatarios->count() === 1)
            <span class="text-secondary small d-block">Por ahora va a un solo proveedor</span>
            <strong>{{ $destinatarios->first()->razon_social }}</strong>
        @elseif ($destinatarios->count() > 1)
            <span class="text-secondary small d-block">Al aprobarlo se van a emitir {{ $destinatarios->count() }} órdenes, una para</span>
            <strong>{{ $destinatarios->pluck('razon_social')->implode(' · ') }}</strong>
        @else
            <strong>Este pedido no tiene renglones.</strong>
        @endif

        <span class="text-secondary small d-block mt-1">
            El destinatario sale de los productos: cada proveedor recibe por correo solamente los
            renglones que le corresponden.
        </span>
    </div>

    @if ($orden->origen === \App\Enums\OrigenOrdenCompra::Automatica)
        <div class="alert alert-info small">
            Este pedido lo generó la reposición automática. Editarlo es normal —para ajustar cantidades
            antes de aprobarlo—, pero tené en cuenta que las cantidades sugeridas cubren el faltante
            hasta el punto de reposición más el lote de compra de cada producto.

            @if ($destinatarios->count() === 1)
                <br>
                Mientras siga siendo de un solo proveedor, la reposición lo puede seguir ampliando sola.
                Si le agregás productos de otro, deja de ser el borrador abierto de ese proveedor y el
                automático va a abrir uno nuevo en lugar de tocar este.
            @endif
        </div>
    @endif

    @include('gestion.compras.partials.formulario', [
        'accion' => route('gestion.compras.update', $orden),
        'metodo' => 'PUT',
    ])

@endsection
