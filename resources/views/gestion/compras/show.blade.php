@extends('layouts.gestion')

@section('title', 'PowerTech')

@section('content')

@php
// Un pedido mezclado no tiene destinatario propio: lo tienen sus órdenes. La
// vista pregunta esto una vez y después solo mira el booleano, para no repetir
// la misma comprobación en cada bloque.
$repartido = $orden->esMadre();
$destinatarios = $orden->proveedoresInvolucrados();
@endphp

{{-- Título --}}
<section class="mb-4">


    <h1 class="h3 fw-bold mb-2 mt-1">
        Pedido {{ $orden->numero }}
        <span class="badge text-bg-{{ $orden->estado->color() }} align-middle ms-1">
            {{ $orden->estado->etiqueta() }}
        </span>
    </h1>

    <p class="text-muted mb-0">
        @if ($repartido)
        {{ $destinatarios->count() }} {{ $destinatarios->count() === 1 ? 'proveedor' : 'proveedores' }}
        @else
        {{ $orden->proveedor->razon_social }}
        @endif
        · {{ $orden->origen->etiqueta() }}
    </p>
</section>

@include('gestion.partials.alertas')

{{--
    Las acciones disponibles, resueltas una sola vez.

    Se consulta la policy con Gate::allows en lugar de @can por cada boton, por
    dos motivos: permite saber si quedo ALGUNA accion habilitada —y explicar por
    que no, en vez de mostrar una barra vacia— y evita repetir la misma consulta
    cuando el boton y su modal preguntan lo mismo.

    Un @can que da false no distingue entre "te falta el permiso" y "el estado
    no lo permite". Esa diferencia es justo la que necesita ver el usuario.

    A la policy se le suma la FORMA del documento, que la policy no mira: un
    pedido repartido se aprueba, se envia y se cancela entero, pero no se recibe
    ni se cierra, porque la mercaderia la manda cada proveedor contra su propia
    orden y cargarla sobre el pedido no diria cual de los tres cumplio.
--}}
@php
$puede = [
'editar' => \Illuminate\Support\Facades\Gate::allows('update', $orden),
'aprobar' => \Illuminate\Support\Facades\Gate::allows('aprobar', $orden),
'enviar' => \Illuminate\Support\Facades\Gate::allows('enviar', $orden) && $orden->tieneAlgoQueEnviar(),
'recibir' => \Illuminate\Support\Facades\Gate::allows('recibir', $orden) && ! $repartido,
'cerrar' => \Illuminate\Support\Facades\Gate::allows('cerrar', $orden) && ! $repartido,
'cancelar' => \Illuminate\Support\Facades\Gate::allows('cancelar', $orden),
];

$hayAcciones = in_array(true, $puede, true);

// Separacion de funciones: la orden la cargo quien la esta mirando.
$esPropio = $orden->usuario_creo_id !== null
&& $orden->usuario_creo_id === auth()->id();

$motivoSinAcciones = match (true) {
$orden->estado->esCerrada()
=> 'Este pedido está cerrado y no admite más acciones.',

$orden->esBorrador() && $esPropio && ! auth()->user()->tienePermiso('compra.aprobar_propias')
=> 'Este pedido lo cargaste vos y no tenés permiso para aprobar tus propios pedidos: lo tiene que aprobar otro usuario.',

$repartido && $orden->estado === \App\Enums\EstadoOrdenCompra::Enviada
=> 'Ya salieron todos los remitos. La mercadería se recibe en la orden de cada proveedor, más abajo.',

$repartido
=> 'Este pedido se opera desde la orden de cada proveedor, más abajo.',

default
=> 'No tenés permisos para operar sobre este pedido.',
};
@endphp

{{--
    Navegación y acciones.

    Los botones que disparan un POST van dentro de un <form>, y el form es el
    flex item, no el boton. Sin w-100 en el boton, en pantalla angosta el form
    se estira y el boton queda del ancho de su texto: por eso "Aprobar" y
    "Enviar" se veian mas cortos que "Editar detalle" y "Recibir mercadería".
--}}
<section class="mb-4 d-flex flex-column flex-md-row align-items-stretch gap-2">

    @if ($orden->padre)
    {{--
        Una orden emitida desde un pedido mezclado. El enlace al pedido no es
        decorativo: es el único lugar donde se ven juntas las órdenes hermanas,
        y es lo que explica por qué este documento tiene un número con barra.
    --}}
    <a
        href="{{ route('gestion.compras.show', $orden->padre) }}"
        class="btn btn-outline-secondary d-flex align-items-center justify-content-center gap-2">

        <svg xmlns="http://www.w3.org/2000/svg"
            width="18"
            height="18"
            fill="currentColor"
            viewBox="0 0 16 16">
            <path fill-rule="evenodd"
                d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8" />
        </svg>

        Volver a {{ $orden->padre->numero }}
    </a>
    @else
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
    @endif

    <div class="d-flex flex-column flex-md-row gap-2 ms-md-auto">

        @if ($puede['editar'])
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
        @endif

        @if ($puede['aprobar'])
        <form method="POST" action="{{ route('gestion.compras.aprobar', $orden) }}">
            @csrf

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

                {{ $repartido ? 'Aprobar y emitir ' . $destinatarios->count() . ' órdenes' : 'Aprobar' }}
            </button>
        </form>
        @endif

        @if ($puede['enviar'])
        <form method="POST" action="{{ route('gestion.compras.enviar', $orden) }}">
            @csrf

            <button
                type="submit"
                class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2">

                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-envelope" viewBox="0 0 16 16">
                    <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1zm13 2.383-4.708 2.825L15 11.105zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741M1 11.105l4.708-2.897L1 5.383z" />
                </svg>

                {{ $repartido ? 'Enviar el remito a cada proveedor' : 'Enviar remito por correo' }}
            </button>
        </form>
        @endif

        {{-- La entrada de mercadería. Solo sobre un pedido ya enviado. --}}
        @if ($puede['recibir'])
        <a
            href="{{ route('gestion.compras.recibir', $orden) }}"
            class="btn btn-success d-flex align-items-center justify-content-center gap-2">

            <svg xmlns="http://www.w3.org/2000/svg"
                width="18"
                height="18"
                fill="currentColor"
                viewBox="0 0 16 16">
                <path fill-rule="evenodd"
                    d="M8 1a.5.5 0 0 1 .5.5v8.793l2.646-2.647a.5.5 0 0 1 .708.708l-3.5 3.5a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L7.5 10.293V1.5A.5.5 0 0 1 8 1" />
                <path d="M2 13.5a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5" />
            </svg>

            Recibir mercadería
        </a>
        @endif

        @if ($puede['cerrar'])
        <button
            type="button"
            class="btn btn-outline-warning d-flex align-items-center justify-content-center gap-2"
            data-bs-toggle="modal"
            data-bs-target="#cerrarPedido">

            <svg xmlns="http://www.w3.org/2000/svg"
                width="18"
                height="18"
                fill="currentColor"
                viewBox="0 0 16 16">
                <path d="M11 2a3 3 0 0 1 3 3v1h.5a1.5 1.5 0 0 1 1.5 1.5v6a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 13.5v-6A1.5 1.5 0 0 1 1.5 6H2V5a3 3 0 0 1 3-3zm0 1H5a2 2 0 0 0-2 2v1h10V5a2 2 0 0 0-2-2" />
            </svg>

            Cerrar con faltante
        </button>
        @endif

        @if ($puede['cancelar'])
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
        @endif

        {{--
            Una barra vacía parece un error de la pantalla. Decir por qué no hay
            acciones evita la consulta de "el botón no me aparece".
        --}}
        @unless ($hayAcciones)
        <p class="text-muted small mb-0 align-self-md-center text-md-end">
            {{ $motivoSinAcciones }}
        </p>
        @endunless

    </div>
</section>

{{--
    Aviso sobre el canal de pedido.

    En un pedido repartido se revisan todos los destinatarios, no uno: con tres
    proveedores, el que tiene el canal mal configurado es el único que no va a
    recibir nada, y el administrativo tiene que saber cuál antes de darle a
    enviar y no después.
--}}
@if ($orden->estado === \App\Enums\EstadoOrdenCompra::Aprobada)

@php
$malConfigurados = $destinatarios->filter(fn ($p) =>
$p->canal_pedido !== \App\Enums\CanalPedido::Email || blank($p->email)
);
@endphp

@foreach ($malConfigurados as $proveedor)
<div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
    @if ($proveedor->canal_pedido !== \App\Enums\CanalPedido::Email)
    <strong>{{ $proveedor->razon_social }} no está configurado para recibir pedidos por correo.</strong>
    Su canal es «{{ $proveedor->canal_pedido->etiqueta() }}».
    <a href="{{ route('gestion.proveedores.edit', $proveedor) }}" class="alert-link">
        Cambialo a email
    </a>
    para poder enviarle el remito.
    @else
    <strong>{{ $proveedor->razon_social }} no tiene correo cargado.</strong>
    <a href="{{ route('gestion.proveedores.edit', $proveedor) }}" class="alert-link">
        Cargalo en su ficha
    </a>
    para poder enviarle el remito.
    @endif

    @if ($repartido)
    <span class="d-block small mt-1">
        Los demás proveedores del pedido sí van a recibir el suyo: cada orden se envía por separado.
    </span>
    @endif

    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
</div>
@endforeach

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

{{-- Aviso de excedente --}}
@if ($orden->tieneExcedentes())
<div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
    <strong>Entró más de lo pedido en algún renglón.</strong>
    Está registrado así a propósito: la mercadería está en el depósito. Tené en cuenta que el
    total estimado se calculó sobre lo pedido, así que la factura del proveedor va a venir por
    más.

    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
</div>
@endif

{{-- Contenido --}}
<div class="row g-4">

    {{-- Columna izquierda --}}
    <div class="col-12 col-lg-8">

        {{--
            Órdenes emitidas.

            Es el bloque que convierte un pedido repartido en algo operable: el
            estado de cada proveedor por separado, y los botones de cada uno.
            Que un remito no salga es lo normal —un proveedor sin casilla
            cargada— y acá se ve exactamente cuál quedó pendiente, sin volver a
            molestar a los que ya recibieron el suyo.
        --}}
        @if ($repartido && $orden->hijas->isNotEmpty())
        <section class="p-4 rounded-4 bg-light shadow-sm border mb-4">

            <h2 class="h5 fw-bold mb-1">Órdenes emitidas</h2>

            <p class="text-muted small mb-3">
                Una por proveedor. Cada una se envía y se recibe por separado, y lleva su propio
                estado: el pedido de arriba resume el de la más atrasada.
            </p>

            <div class="table-responsive">

                <table class="table table-bordered align-middle text-center mb-0">

                    <thead class="table-dark">
                        <tr>
                            <th class="text-nowrap">Orden</th>
                            <th class="text-nowrap text-start">Proveedor</th>
                            <th class="text-nowrap">Estado</th>
                            <th class="text-nowrap">Renglones</th>
                            <th class="text-nowrap">Estimado</th>
                            <th class="text-nowrap">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($orden->hijas as $hija)
                        <tr>
                            <td class="text-nowrap fw-semibold">
                                <a href="{{ route('gestion.compras.show', $hija) }}" class="text-decoration-none">
                                    {{ $hija->numero }}
                                </a>
                            </td>

                            <td class="text-start">
                                <div class="fw-semibold">{{ $hija->proveedor->razon_social }}</div>
                                <div class="text-muted small">{{ $hija->proveedor->email ?? '— sin correo —' }}</div>
                            </td>

                            <td>
                                <span class="badge text-bg-{{ $hija->estado->color() }}">
                                    {{ $hija->estado->etiqueta() }}
                                </span>
                            </td>

                            <td class="text-nowrap">
                                {{ $hija->lineas->count() }}
                            </td>

                            <td class="text-nowrap">
                                $ {{ number_format((float) $hija->total_estimado, 2, ',', '.') }}
                            </td>

                            <td class="text-nowrap">

                                @if (\Illuminate\Support\Facades\Gate::allows('enviar', $hija))
                                <form method="POST" action="{{ route('gestion.compras.enviar', $hija) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary" title="Enviar el remito a este proveedor">
                                        Enviar
                                    </button>
                                </form>
                                @endif

                                @if (\Illuminate\Support\Facades\Gate::allows('recibir', $hija))
                                <a href="{{ route('gestion.compras.recibir', $hija) }}" class="btn btn-sm btn-success">
                                    Recibir
                                </a>
                                @endif

                                <a href="{{ route('gestion.compras.show', $hija) }}" class="btn btn-sm btn-outline-secondary">
                                    Ver
                                </a>

                            </td>
                        </tr>
                        @endforeach
                    </tbody>

                </table>

            </div>

        </section>
        @endif

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

                            {{-- Solo cuando hay más de uno: en una orden común
                                 la columna repetiría el encabezado. --}}
                            @if ($repartido)
                            <th class="text-nowrap">Proveedor</th>
                            @endif

                            <th class="text-nowrap">Pedido</th>
                            <th class="text-nowrap">Recibido</th>
                            <th class="text-nowrap">Costo unitario</th>
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

                            @if ($repartido)
                            <td class="small text-start">
                                {{ $linea->proveedor?->razon_social ?? '—' }}
                            </td>
                            @endif

                            <td class="text-nowrap fw-semibold">
                                {{ $linea->cantidad_pedida }}
                            </td>

                            <td class="text-nowrap">
                                {{ $linea->cantidad_recibida }}

                                @if ($linea->tieneExcedente())
                                <span class="badge text-bg-info ms-1">
                                    +{{ $linea->excedente }} de más
                                </span>
                                @elseif ($linea->pendiente > 0 && $orden->estado === \App\Enums\EstadoOrdenCompra::RecibidaParcial)
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
                            <td colspan="{{ $repartido ? 6 : 5 }}" class="text-end fw-semibold">
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

                @if ($repartido)
                <br>
                Este es el detalle completo del pedido. El mismo importe está repartido entre las
                órdenes de arriba, así que no se suman: son lo mismo visto de dos maneras.
                @endif
            </div>

        </section>

        {{-- Entregas recibidas --}}
        @if ($orden->recepciones->isNotEmpty())
        <section class="p-4 rounded-4 bg-light shadow-sm border mb-4">

            <h2 class="h5 fw-bold mb-1">Entregas recibidas</h2>

            <p class="text-muted small mb-3">
                Cada entrada quedó como un movimiento de stock contra este pedido. No hay tabla
                de recepciones aparte: el movimiento <em>es</em> el registro de la entrada.
            </p>

            <div class="table-responsive">

                <table class="table table-bordered align-middle text-center mb-0">

                    <thead class="table-dark">
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
                                {{ $movimiento->usuario?->nombre_completo ?? 'Sistema' }}
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
                    {{ $orden->usuarioCreo?->nombre_completo ?? 'Sistema (reposición)' }}
                </span>
            </div>

            @if ($orden->fecha_aprobacion)
            <div class="d-flex justify-content-between gap-3 mb-2">
                <span class="text-muted">Aprobado por</span>
                <span class="text-end">{{ $orden->usuarioAprobo?->nombre_completo ?? '—' }}</span>
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
            <div class="d-flex justify-content-between gap-3 mb-2">
                <span class="text-muted">
                    Entrega estimada
                    @if ($repartido)
                    <span class="d-block small">la del proveedor más lento</span>
                    @endif
                </span>
                <span class="text-end">{{ $orden->fecha_entrega_estimada->format('d/m/Y') }}</span>
            </div>
            @endif

            @if ($orden->unidadesRecibidas() > 0)
            <div class="d-flex justify-content-between gap-3">
                <span class="text-muted">Unidades recibidas</span>
                <span class="text-end">
                    {{ $orden->unidadesRecibidas() }}

                    @if ($orden->unidadesPendientes() > 0)
                    <span class="text-warning">
                        (faltan {{ $orden->unidadesPendientes() }})
                    </span>
                    @endif
                </span>
            </div>
            @endif

        </section>

        {{-- Proveedores --}}
        <section class="p-4 rounded-4 bg-light shadow-sm border mb-4">

            <h2 class="h6 fw-bold mb-3">
                {{ $destinatarios->count() === 1 ? 'Proveedor' : 'Proveedores' }}
            </h2>

            @forelse ($destinatarios as $proveedor)

            <div @class(['pb-3 mb-3 border-bottom'=> ! $loop->last])>

                <p class="fw-semibold mb-2">
                    {{ $proveedor->razon_social }}
                </p>

                @if ($proveedor->contacto)
                <p class="text-muted mb-2">
                    {{ $proveedor->contacto }}
                </p>
                @endif

                <p class="mb-2">
                    {{ $proveedor->email ?? '— sin correo —' }}
                </p>

                @if ($proveedor->telefono)
                <p class="text-muted mb-0">
                    {{ $proveedor->telefono }}
                </p>
                @endif

            </div>

            @empty

            <p class="text-muted mb-0">
                Este pedido todavía no tiene renglones, así que no hay a quién pedirle nada.
            </p>

            @endforelse

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

{{-- Modal de cierre con faltante --}}
@if ($puede['cerrar'])
<div class="modal fade" id="cerrarPedido" tabindex="-1" aria-hidden="true">

    <div class="modal-dialog">

        <form
            method="POST"
            action="{{ route('gestion.compras.cerrar', $orden) }}"
            class="modal-content">

            @csrf

            <div class="modal-header">
                <h3 class="modal-title h5">
                    Cerrar el pedido {{ $orden->numero }} con faltante
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
                    Es para cuando el proveedor avisa que el saldo no va a venir. El pedido queda
                    como recibido con lo que entró, y las
                    <strong>{{ $orden->unidadesPendientes() }} unidades que faltan</strong> dejan de
                    estar pedidas.
                </p>

                <p class="small text-muted">
                    Mientras el pedido siga recibido parcialmente, la reposición automática lo cuenta
                    como pedido vigente y <strong>no vuelve a pedir esos productos</strong>. Cerrarlo
                    es lo que destraba el faltante: si siguen bajo el punto de reposición, se va a
                    generar un pedido nuevo por lo que haga falta.
                </p>

                <p class="small text-muted">
                    Si el motivo es que el producto se discontinuó, apagale primero la reposición
                    automática en su ficha.
                </p>

                <label for="motivoCierre" class="form-label fw-semibold">
                    Motivo
                </label>

                <textarea
                    id="motivoCierre"
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
                    class="btn btn-outline-secondary"
                    data-bs-dismiss="modal">
                    Volver
                </button>

                <button
                    type="submit"
                    class="btn btn-warning">
                    Dar por cerrado el pedido
                </button>

            </div>

        </form>

    </div>

</div>
@endif

{{-- Modal de cancelación --}}
@if ($puede['cancelar'])
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

                @if ($repartido && $orden->hijas->isNotEmpty())
                <p class="small text-muted">
                    <strong>Se cancelan las {{ $orden->hijas->count() }} órdenes del pedido.</strong>
                    Si lo que querés dar de baja es la de un solo proveedor —porque avisó que no tiene
                    stock—, cancelala desde su propia orden y el resto del pedido sigue en pie.
                </p>
                @endif

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
                    class="btn btn-outline-secondary"
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
@endif

@endsection