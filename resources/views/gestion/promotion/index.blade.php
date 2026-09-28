@extends('layouts.gestion')

@section('title', 'PowerTech')

@vite('resources/css/gestion/promotion/index.css')

@section('content')

<!-- Título principal -->

<section class="mb-4 text-center">

    <h1 class="display-6 fw-bold">Gestión de promociones</h1>

    <p class="text-muted mb-0">
        Administre las promociones y descuentos aplicables a los productos.
    </p>

</section>


<!-- Botones -->

<section class="mb-4 d-flex flex-column flex-md-row align-items-stretch gap-3
    @can('create', App\Models\Promocion::class)
        justify-content-between
    @else
        justify-content-end
    @endcan">

    @can('create', App\Models\Promocion::class)

    <a
        href="{{ route('gestion.promociones.create') }}"
        class="btn btn-success text-white d-flex align-items-center justify-content-center gap-2 flex-fill flex-md-grow-0">

        <svg
            xmlns="http://www.w3.org/2000/svg"
            width="18"
            height="18"
            fill="currentColor"
            class="bi bi-plus-circle"
            viewBox="0 0 16 16">

            <path
                d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16" />

            <path
                d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4" />

        </svg>

        Registrar nueva promoción

    </a>

    @endcan

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


<!-- Mensajes -->

@if (session('exito'))

<div
    class="alert alert-success alert-dismissible fade show"
    role="alert">

    {{ session('exito') }}

    <button
        type="button"
        class="btn-close"
        data-bs-dismiss="alert"
        aria-label="Cerrar">
    </button>

</div>

@endif


@if (session('advertencia'))

<div
    class="alert alert-warning alert-dismissible fade show"
    role="alert">

    {{ session('advertencia') }}

    <button
        type="button"
        class="btn-close"
        data-bs-dismiss="alert"
        aria-label="Cerrar">
    </button>

</div>

@endif


<!-- Filtros -->

<section class="mb-4">

    <form
        action="{{ route('gestion.promociones.index') }}"
        method="GET"
        class="row g-3 align-items-end shadow-sm p-3 rounded bg-light"
        autocomplete="off"
        id="formFiltros">

        <!-- Búsqueda -->

        <div class="col-12 col-md-6 col-lg-4">

            <label
                for="q"
                class="form-label fw-semibold">

                Buscar promoción

            </label>

            <input
                type="text"
                class="form-control"
                id="q"
                name="q"
                value="{{ $filtros['q'] ?? '' }}"
                placeholder="Ej: Oferta primavera"
                maxlength="120">

        </div>


        <!-- Canal -->

        <div class="col-12 col-md-6 col-lg-2">

            <label
                for="canal"
                class="form-label fw-semibold">

                Canal

            </label>

            <select
                id="canal"
                name="canal"
                class="form-select">

                <option value="">Todos</option>

                @foreach ($canales as $valor => $nombre)

                <option
                    value="{{ $valor }}"
                    @selected(($filtros['canal'] ?? null)===$valor)>

                    {{ $nombre }}

                </option>

                @endforeach

            </select>

        </div>


        <!-- Estado -->

        <div class="col-12 col-md-6 col-lg-2">

            <label
                for="estado"
                class="form-label fw-semibold">

                Estado

            </label>

            <select
                id="estado"
                name="estado"
                class="form-select">

                <option value="">Todos</option>

                <option
                    value="activas"
                    @selected(($filtros['estado'] ?? null)==='activas' )>

                    Activas

                </option>

                <option
                    value="inactivas"
                    @selected(($filtros['estado'] ?? null)==='inactivas' )>

                    Inactivas

                </option>

            </select>

        </div>


        <!-- Vigencia -->

        <div class="col-12 col-md-6 col-lg-2">

            <label
                for="vigencia"
                class="form-label fw-semibold">

                Vigencia

            </label>

            <select
                id="vigencia"
                name="vigencia"
                class="form-select">

                <option value="">Todas</option>

                <option
                    value="vigentes"
                    @selected(($filtros['vigencia'] ?? null)==='vigentes' )>

                    Vigentes

                </option>

                <option
                    value="programadas"
                    @selected(($filtros['vigencia'] ?? null)==='programadas' )>

                    Programadas

                </option>

                <option
                    value="vencidas"
                    @selected(($filtros['vigencia'] ?? null)==='vencidas' )>

                    Vencidas

                </option>

            </select>

        </div>


        <!-- Botón filtrar -->

        <div class="col-12 col-md-6 col-lg-2 d-flex">

            <button
                type="submit"
                class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2 fw-semibold">

                Filtrar

                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="16"
                    height="16"
                    fill="currentColor"
                    class="bi bi-funnel"
                    viewBox="0 0 16 16">

                    <path
                        d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.128.334L10 8.692V13.5a.5.5 0 0 1-.342.474l-3 1A.5.5 0 0 1 6 13.5V8.692L1.628 3.834A.5.5 0 0 1 1.5 3.5z" />

                </svg>

            </button>

        </div>

    </form>

</section>


<!-- Tabla de promociones -->

<section>

    <div class="table-responsive">

        <table
            class="table table-bordered table-hover align-middle text-center"
            id="tablaPromociones">

            <thead class="table-dark">

                <tr>

                    <th class="text-nowrap">
                        Promoción
                    </th>

                    <th class="text-nowrap">
                        Descuento
                    </th>

                    <th class="text-nowrap">
                        Canal
                    </th>

                    <th class="text-nowrap">
                        Vigencia
                    </th>

                    <th class="text-nowrap">
                        Prioridad
                    </th>

                    <th class="text-nowrap">
                        Productos
                    </th>

                    <th class="text-nowrap">
                        Aplicaciones
                    </th>

                    <th class="text-nowrap">
                        Estado
                    </th>

                    @can('create', App\Models\Promocion::class)
                    <th class="text-nowrap">
                        Opciones
                    </th>
                    @endcan

                </tr>

            </thead>


            <tbody>

                @forelse ($promociones as $promocion)

                @php

                $vigenciaActual = now()->lt($promocion->vigencia_desde)
                ? 'programada'
                : (
                now()->gt($promocion->vigencia_hasta)
                ? 'vencida'
                : 'vigente'
                );

                $tipo = $promocion->tipo->value;

                $valorDescuento = match ($tipo) {

                'porcentaje' =>
                number_format(
                (float) $promocion->valor,
                2,
                ',',
                '.'
                ) . '%',

                'monto_fijo' =>
                '$ ' . number_format(
                (float) $promocion->valor,
                2,
                ',',
                '.'
                ),

                'precio_especial' =>
                '$ ' . number_format(
                (float) $promocion->valor,
                2,
                ',',
                '.'
                ),

                default => $promocion->valor,

                };

                @endphp


                <tr>

                    <!-- Promoción -->

                    <td class="text-start">

                        <div class="fw-semibold">
                            {{ $promocion->nombre }}
                        </div>

                        @if ($promocion->descripcion)

                        <small class="text-muted">
                            {{ $promocion->descripcion }}
                        </small>

                        @endif

                        @if ($promocion->creadoPor)

                        <div class="mt-1">

                            <small class="text-muted">

                                Creada por:
                                {{ $promocion->creadoPor->apellido }},
                                {{ $promocion->creadoPor->nombres }}

                            </small>

                        </div>

                        @endif

                    </td>


                    <!-- Descuento -->

                    <td>

                        <span class="fw-semibold">
                            {{ $valorDescuento }}
                        </span>

                        <small class="text-muted d-block">

                            {{ ucfirst(str_replace('_', ' ', $tipo)) }}

                        </small>

                    </td>


                    <!-- Canal -->

                    <td>
                        {{ $promocion->canal->value }}
                    </td>


                    <!-- Vigencia -->

                    <td>

                        <div>
                            {{ $promocion->vigencia_desde->format('d/m/Y H:i') }}
                        </div>

                        <div class="text-muted">
                            {{ $promocion->vigencia_hasta->format('d/m/Y H:i') }}
                        </div>


                        @if ($vigenciaActual === 'vigente')

                        <span class="badge text-bg-success mt-1">
                            Vigente
                        </span>

                        @elseif ($vigenciaActual === 'programada')

                        <span class="badge text-bg-info mt-1">
                            Programada
                        </span>

                        @else

                        <span class="badge text-bg-secondary mt-1">
                            Vencida
                        </span>

                        @endif

                    </td>


                    <!-- Prioridad -->

                    <td>

                        <span class="badge text-bg-dark">
                            {{ $promocion->prioridad }}
                        </span>

                        @if ($promocion->acumulable)

                        <small class="text-success d-block mt-1">
                            Acumulable
                        </small>

                        @else

                        <small class="text-muted d-block mt-1">
                            No acumulable
                        </small>

                        @endif

                    </td>


                    <!-- Productos -->

                    <td>

                        <span class="fw-semibold">
                            {{ $promocion->productos_count }}
                        </span>

                        <small class="text-muted d-block">

                            {{ $promocion->productos_count == 1
                                    ? 'producto'
                                    : 'productos' }}

                        </small>

                    </td>


                    <!-- Aplicaciones -->

                    <td>

                        <span class="fw-semibold">
                            {{ $promocion->aplicaciones_count }}
                        </span>

                        <small class="text-muted d-block">
                            aplicaciones
                        </small>

                        @if ($promocion->descuento_total !== null)

                        <small class="text-success">

                            $ {{ number_format(
                                        (float) $promocion->descuento_total,
                                        2,
                                        ',',
                                        '.'
                                    ) }}

                        </small>

                        @endif

                    </td>


                    <!-- Estado -->

                    <td>

                        @if ($promocion->activo)

                        <span class="badge text-bg-success">
                            Activa
                        </span>

                        @else

                        <span class="badge text-bg-secondary">
                            Inactiva
                        </span>

                        @endif

                    </td>


                    @can('create', App\Models\Promocion::class)
                    <!-- Opciones -->

                    <td>

                        <!-- Editar -->

                        <a
                            href="{{ route('gestion.promociones.edit', $promocion) }}"
                            class="btn btn-sm btn-warning me-1"
                            title="Editar">

                            <svg xmlns="http://www.w3.org/2000/svg"
                                width="16"
                                height="16"
                                fill="currentColor"
                                class="bi bi-pencil"
                                viewBox="0 0 16 16">

                                <path
                                    d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325" />

                            </svg>

                        </a>


                        @if ($promocion->activo)

                        <!-- Dar de baja -->

                        <form
                            action="{{ route('gestion.promociones.darDeBaja', $promocion) }}"
                            method="POST"
                            class="d-inline">

                            @csrf

                            @method('PATCH')

                            <button
                                type="submit"
                                class="btn btn-sm btn-secondary me-1"
                                title="Dar de baja"
                                onclick="return confirm('¿Está seguro de dar de baja esta promoción?');">

                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="16"
                                    height="16"
                                    fill="currentColor"
                                    viewBox="0 0 16 16">

                                    <path
                                        d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14zM4.5 7.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1 0-1z" />

                                </svg>

                            </button>

                        </form>

                        @else

                        <!-- Reactivar -->

                        <form
                            action="{{ route('gestion.promociones.reactivar', $promocion) }}"
                            method="POST"
                            class="d-inline">

                            @csrf

                            @method('PATCH')

                            <button
                                type="submit"
                                class="btn btn-sm btn-success"
                                title="Reactivar"
                                onclick="return confirm('¿Está seguro de reactivar esta promoción?');">

                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="16"
                                    height="16"
                                    fill="currentColor"
                                    viewBox="0 0 16 16">

                                    <path
                                        d="M8 3a5 5 0 1 1-4.546 2.917.5.5 0 0 1 .91-.414A4 4 0 1 0 8 4z" />

                                    <path
                                        d="M8 1v4H4" />

                                </svg>

                            </button>

                        </form>

                        @endif

                    </td>
                    @endcan

                </tr>

                @empty

                <tr>

                    <td
                        colspan="9"
                        class="text-center py-4 text-muted">

                        No se encontraron promociones.

                    </td>

                </tr>

                @endforelse

            </tbody>

        </table>

    </div>


    <!-- Paginación Laravel -->

    @if ($promociones->hasPages())

    <div class="d-flex justify-content-center mt-3">

        {{ $promociones->links() }}

    </div>

    @endif

</section>

@endsection