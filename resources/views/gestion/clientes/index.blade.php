@extends('layouts.gestion')

@section('title', 'PowerTech')

@section('content')

<!-- Título principal -->

<section class="mb-4 text-center">
    <h1 class="display-6 fw-bold">Gestión de clientes</h1>
    <p class="text-muted mb-0">
        El documento y la condición frente al IVA son lo que decide si corresponde factura A o B.
    </p>
</section>


{{--
    Avisos de la pantalla.

    Antes se imprimía acá `@include('gestion.partials.alertas')`, que mostraba el
    `session('status')` que dejaba el redirect del controlador web. Ahora las
    operaciones las hace el JavaScript contra la API, no hay redirect, y el mismo
    cartel lo pinta resources/js/gestion/avisos.js con este marcado.
--}}
<div id="avisosClientes" aria-live="polite"></div>


<!-- Botones -->
<section class="mb-4 d-flex flex-column flex-md-row align-items-stretch gap-3
    @if ($permisos['crear'])
        justify-content-between
    @else
        justify-content-end
    @endif">

    @if ($permisos['crear'])

    <a href="{{ route('gestion.clientes.create') }}"
        class="btn btn-success d-flex align-items-center justify-content-center gap-2 flex-fill flex-md-grow-0">

        <svg
            xmlns="http://www.w3.org/2000/svg"
            width="18"
            height="18"
            fill="currentColor"
            viewBox="0 0 16 16">
            <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6M8 3a2 2 0 1 1 0 4 2 2 0 0 1 0-4" />
            <path d="M8 9c-2.67 0-5 1.34-5 4v1h1v-1c0-2 1.64-3 4-3s4 1 4 3v1h1v-1c0-2.66-2.33-4-5-4" />
            <path d="M13 9V7h-1v2h-2v1h2v2h1v-2h2V9z" />
        </svg>

        Registrar nuevo cliente
    </a>

    @endif

    <!-- PDF -->
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

            <path
                d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2M9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5z" />

            <path
                d="M4.603 14.087a.8.8 0 0 1-.438-.42c-.195-.388-.13-.776.08-1.102.198-.307.526-.568.897-.787a7.7 7.7 0 0 1 1.482-.645 20 20 0 0 0 1.062-2.227 7.3 7.3 0 0 1-.43-1.295c-.086-.4-.119-.796-.046-1.136.075-.354.274-.672.65-.823.192-.077.4-.12.602-.077a.7.7 0 0 1 .477.365c.088.164.12.356.127.538.007.188-.012.396-.047.614-.084.51-.27 1.134-.52 1.794a11 11 0 0 0 .98 1.686 5.8 5.8 0 0 1 1.334.05c.364.066.734.195.96.465.12.144.193.32.2.518.007.192-.047.382-.138.563a1.04 1.04 0 0 1-.354.416.86.86 0 0 1-.51.138c-.331-.014-.654-.196-.933-.417a5.7 5.7 0 0 1-.911-.95 11.7 11.7 0 0 0-1.997.406 11.3 11.3 0 0 0-1.02 1.51c-.292.35-.609.656-.927.787a.8.8 0 0 1-.58.029m1.379-1.901q-.25.115-.459.238c-.328-.194-.541-.383-.647-.547-.094-.145-.096-.25-.04-.361q.016-.032.026-.044l.035-.012c.137-.056.355-.235.635-.572a8 8 0 0 0 .45-.606m1.64-1.33a13 13 0 0 1 1.01-.193 12 12 0 0 1-.51-.858 21 21 0 0 1-.5 1.05zm2.446.45q.226.245.435.41c.24.19.407.253.498.256a.1.1 0 0 0 .07-.015.3.3 0 0 0 .094-.125.44.44 0 0 0 .059-.2.1.1 0 0 0-.026-.063c-.052-.062-.2-.152-.518-.209a4 4 0 0 0-.612-.053zM8.078 7.8a7 7 0 0 0 .2-.828q.046-.282.038-.465a.6.6 0 0 0-.032-.198.5.5 0 0 0-.145.04c-.087.035-.158.106-.196.283-.04.192-.03.469.046.822q.036.167.09.346z" />
        </svg>

        Exportar listado a PDF
    </button>

</section>


<!-- Filtros -->

<section class="mb-4">

    {{--
        Sigue siendo un GET contra la propia página y con los mismos nombres de
        parámetro —`q` y `activo`—, así que una URL filtrada que alguien haya
        guardado abre el mismo listado. Lo que cambió es quién lo atiende: el
        `submit` lo intercepta el JavaScript, que escribe la URL y le pide los
        datos a la API.
    --}}
    <form
        action="{{ route('gestion.clientes.index') }}"
        method="GET"
        class="row g-3 align-items-end shadow-sm p-3 rounded bg-light"
        autocomplete="off"
        id="formFiltros">

        <div class="col-12 col-md-6">

            <label for="q" class="form-label fw-semibold">
                Buscar por razón social / documento
            </label>

            {{-- El valor lo pone el JS con lo que diga la URL. --}}
            <input
                type="text"
                class="form-control"
                id="q"
                name="q"
                placeholder="Ej: 20304050607"
                maxlength="100">

        </div>

        <div class="col-12 col-md-4">

            <label for="activo" class="form-label fw-semibold">Situación</label>

            <select id="activo" name="activo" class="form-select">

                <option value="">Todos</option>

                <option value="1">En operación</option>

                <option value="0">Dados de baja</option>

            </select>

        </div>

        <div class="col-12 col-md-2 d-flex">

            <button type="submit" class="btn btn-primary w-100 fw-semibold">
                Filtrar
            </button>

        </div>

    </form>

</section>


<!-- Tabla -->

<section>

    <div class="table-responsive">

        <table class="table table-bordered table-hover align-middle text-center" id="tablaClientes">

            <thead class="table-dark">

                <tr>
                    <th class="text-nowrap">Razón social</th>
                    <th class="text-nowrap">Documento</th>
                    <th class="text-nowrap">Condición IVA</th>
                    <th class="text-nowrap">Teléfono</th>
                    <th class="text-nowrap">Estado</th>
                    <th class="text-nowrap">Opciones</th>
                </tr>

            </thead>

            {{--
                Las filas las dibuja el JavaScript con lo que devuelve
                GET /api/v1/gestion/clientes. La página ya no trae datos de
                negocio: entrega la estructura y nada más.

                La columna de opciones queda siempre: llegar hasta acá ya exige
                'cliente.ver', que es el mismo permiso de la ficha, así que
                "Ver" se dibuja para todos. Los de editar y baja los decide el
                permiso del rol.
            --}}
            <tbody id="contenidoTablaClientes">

                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">
                        Cargando clientes…
                    </td>
                </tr>

            </tbody>

        </table>

    </div>


    {{-- Mismo paginado de Bootstrap 5, armado con el `meta` de la respuesta. --}}
    <div id="paginadoClientes" class="mt-3 d-flex justify-content-center"></div>

</section>

@endsection

@push('scripts')
<script type="module">
    import { iniciar } from '{{ Vite::asset('resources/js/gestion/clientes/index.js') }}';

    iniciar({
        // Los decide ClientePolicy en el servidor (ver el controlador): acá
        // sólo dicen qué botones se dibujan. El permiso de verdad lo exige la
        // API en cada llamada.
        permisos: @json($permisos),

        // Las rutas del panel con un hueco en lugar del id, para no escribir
        // URLs del panel dentro del JavaScript.
        urlFicha: '{{ route('gestion.clientes.show', ['cliente' => '__ID__']) }}',
        urlEdicion: '{{ route('gestion.clientes.edit', ['cliente' => '__ID__']) }}',
    });
</script>
@endpush
