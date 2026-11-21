@extends('layouts.gestion')

@section('title', 'Pedidos a proveedores')

@section('content')

{{--
    Avisos de la pantalla.

    Antes se imprimía acá el partial de alertas, que mostraba el `session(...)`
    que dejaba el redirect del controlador web. El listado ya no recibe ningún
    redirect —no opera: no aprueba, no envía, no cancela y no recibe—, así que
    el único aviso posible es el error de la llamada a la API, y lo pinta
    resources/js/gestion/avisos.js con el mismo marcado de Bootstrap.
--}}
<div id="avisosCompras" aria-live="polite"></div>

<!-- Título principal -->
<section class="mb-4 text-center">
    <h1 class="display-6 fw-bold">Pedidos a proveedores</h1>
    <p class="text-muted mb-0">
        Gestione los pedidos de compra realizados a los proveedores.
    </p>
</section>

<!-- Botones -->
<section class="mb-4 d-flex
    @if ($permisos['crear'])
        justify-content-between
    @else
        justify-content-end
    @endif">

    @if ($permisos['crear'])

    <a href="{{ route('gestion.compras.create') }}"
        class="btn btn-success text-white d-flex align-items-center justify-content-center gap-2">

        <svg xmlns="http://www.w3.org/2000/svg"
            width="18"
            height="18"
            fill="currentColor"
            viewBox="0 0 16 16">

            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16" />

            <path
                d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4" />
        </svg>

        Nuevo pedido
    </a>

    @endif

    <button
        class="btn btn-outline-primary d-flex align-items-center justify-content-center gap-2 flex-fill flex-md-grow-0"
        id="btnExportarPDF"
        type="button">

        <svg xmlns="http://www.w3.org/2000/svg"
            width="18"
            height="18"
            fill="currentColor"
            class="bi bi-file-earmark-pdf"
            viewBox="0 0 16 16">

            <path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2M9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5z" />

            <path d="M4.603 14.087a.8.8 0 0 1-.438-.42c-.195-.388-.13-.776.08-1.102.198-.307.526-.568.897-.787a7.7 7.7 0 0 1 1.482-.645 20 20 0 0 0 1.062-2.227 7.3 7.3 0 0 1-.43-1.295c-.086-.4-.119-.796-.046-1.136.075-.354.274-.672.65-.823.192-.077.4-.12.602-.077a.7.7 0 0 1 .477.365c.088.164.12.356.127.538.007.188-.012.396-.047.614-.084.51-.27 1.134-.52 1.794a11 11 0 0 0 .98 1.686 5.8 5.8 0 0 1 1.334.05c.364.066.734.195.96.465.12.144.193.32.2.518.007.192-.047.382-.138.563a1.04 1.04 0 0 1-.354.416.86.86 0 0 1-.51.138c-.331-.014-.654-.196-.933-.417a5.7 5.7 0 0 1-.911-.95 11.7 11.7 0 0 0-1.997.406 11.3 11.3 0 0 0-1.02 1.51c-.292.35-.609.656-.927.787a.8.8 0 0 1-.58.029m1.379-1.901q-.25.115-.459.238c-.328.194-.541.383-.647.547-.094.145-.096.25-.04.361q.016.032.026.044l.035-.012c.137-.056.355-.235.635-.572a8 8 0 0 0 .45-.606m1.64-1.33a13 13 0 0 1 1.01-.193 12 12 0 0 1-.51-.858 21 21 0 0 1-.5 1.05zm2.446.45q.226.245.435.41c.24.19.407.253.498.256a.1.1 0 0 0 .07-.015.3.3 0 0 0 .094-.125.44.44 0 0 0 .059-.2.1.1 0 0 0-.026-.063c-.052-.062-.2-.152-.518-.209a4 4 0 0 0-.612-.053zM8.078 7.8a7 7 0 0 0 .2-.828q.046-.282.038-.465a.6.6 0 0 0-.032-.198.5.5 0 0 0-.145.04c-.087.035-.158.106-.196.283-.04.192-.03.469.046.822q.036.167.09.346z" />
        </svg>

        Exportar listado a PDF
    </button>
</section>

{{--
    Filtros.

    Sigue siendo un <form method="GET"> con su action: así la pantalla funciona
    igual si el módulo no cargó, y el listado filtrado se puede guardar y
    compartir como antes. Lo que cambió es quién atiende el submit: el JS lo
    intercepta, escribe los filtros en la URL con history.pushState y le pide
    la página al endpoint.
--}}
<section class="mb-4">
    <form
        method="GET"
        action="{{ route('gestion.compras.index') }}"
        id="formFiltros"
        class="row g-3 align-items-end shadow-sm p-3 rounded bg-light"
        autocomplete="off">

        <!-- Búsqueda -->
        <div class="col-12 col-md-5 col-lg-5">
            <label for="q" class="form-label fw-semibold">
                Buscar por número o proveedor
            </label>

            <input
                type="search"
                id="q"
                name="q"
                class="form-control"
                placeholder="Ej: OC-0001 o proveedor"
                maxlength="150">
        </div>

        <!-- Estado -->
        <div class="col-12 col-md-3 col-lg-3">
            <label for="estado" class="form-label fw-semibold">
                Estado
            </label>

            {{--
                Los dos desplegables siguen viniendo del servidor: son los enums
                EstadoOrdenCompra y OrigenOrdenCompra —el catálogo de opciones
                del filtro—, no datos de ningún pedido. El valor seleccionado lo
                pone el JS desde la URL.
            --}}
            <select id="estado" name="estado" class="form-select">
                <option value="">Todos</option>

                @foreach ($estados as $estado)
                <option value="{{ $estado->value }}">
                    {{ $estado->etiqueta() }}
                </option>
                @endforeach
            </select>
        </div>

        <!-- Origen -->
        <div class="col-12 col-md-3 col-lg-3">
            <label for="origen" class="form-label fw-semibold">
                Origen
            </label>

            <select id="origen" name="origen" class="form-select">
                <option value="">Todos</option>

                @foreach ($origenes as $origen)
                <option value="{{ $origen->value }}">
                    {{ $origen->etiqueta() }}
                </option>
                @endforeach
            </select>
        </div>

        <!-- Botón filtrar -->
        <div class="col-12 col-md-1 d-flex">
            <button
                type="submit"
                class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2 fw-semibold">

                Filtrar

                <svg xmlns="http://www.w3.org/2000/svg"
                    width="16"
                    height="16"
                    fill="currentColor"
                    viewBox="0 0 16 16">

                    <path
                        d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.128.334L10 8.692V13.5a.5.5 0 0 1-.342.474l-3 1A.5.5 0 0 1 6 13.5V8.692L1.628 3.834A.5.5 0 0 1 1.5 3.5zm1 .5v1.308l4.372 4.858A.5.5 0 0 1 7 8.5v5.306l2-.666V8.5a.5.5 0 0 1 .128-.334L13.5 3.308V2z" />
                </svg>
            </button>
        </div>

    </form>
</section>

<!-- Tabla de pedidos -->
<section>

    {{--
        El listado muestra pedidos, no repartos: un pedido a tres proveedores
        emite tres órdenes, pero acá es una fila sola. Las órdenes de cada
        proveedor se ven y se operan desde el detalle del pedido, que es donde
        tiene sentido mirarlas juntas. Si cada una fuera una fila, el
        administrativo no sabría cuál tiene que aprobar.

        Ese criterio ahora vive en el endpoint, que devuelve sólo órdenes raíz.
    --}}
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle text-center" id="tablaCompras">

            <thead class="table-dark">
                <tr>
                    <th class="text-nowrap">Número</th>
                    <th class="text-nowrap">Proveedor</th>
                    <th class="text-nowrap">Estado</th>
                    <th class="text-nowrap">Origen</th>
                    <th class="text-nowrap">Renglones</th>
                    <th class="text-nowrap">Estimado</th>
                    <th class="text-nowrap">Emitido</th>
                    <th class="text-nowrap">Opciones</th>
                </tr>
            </thead>

            {{--
                Las filas las dibuja el JavaScript con lo que devuelve
                GET /api/v1/gestion/compras. La página ya no trae datos de
                negocio: entrega la estructura y nada más.
            --}}
            <tbody id="contenidoTablaCompras">
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">
                        Cargando pedidos…
                    </td>
                </tr>
            </tbody>

        </table>
    </div>

</section>

{{-- Mismo paginado de Bootstrap 5, armado con el `meta` de la respuesta. --}}
<div id="paginadoCompras" class="mt-3 d-flex justify-content-center"></div>

@endsection

@push('scripts')
<script type="module">
    import { iniciar } from '{{ Vite::asset('resources/js/gestion/compras/index.js') }}';

    iniciar({
        // El color del badge de cada estado, tal como lo define
        // EstadoOrdenCompra::color(). Baja del servidor para que el listado
        // migrado y el detalle —que sigue siendo Blade— no tengan dos paletas.
        colores: @json($colores),

        // La ruta del detalle con un hueco en lugar del id, para no escribir
        // URLs del panel dentro del JavaScript.
        urlDetalle: '{{ route('gestion.compras.show', ['orden' => '__ID__']) }}',
    });
</script>
@endpush
