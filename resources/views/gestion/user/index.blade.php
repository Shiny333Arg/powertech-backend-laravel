@extends('layouts.gestion')

@section('title', 'PowerTech')

@section('content')

<!-- Contenido principal -->




<!-- Título -->
<section class="mb-4 text-center">
    <h1 class="display-6 fw-bold">Gestión de empleados</h1>
    <p class="text-muted mb-0">
        Administre los empleados y sus accesos al sistema.
    </p>
</section>

<!-- Mensajes -->
@if (session('exito'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('exito') }}

    <button
        type="button"
        class="btn-close"
        data-bs-dismiss="alert">
    </button>
</div>
@endif

@if (session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    {{ session('error') }}

    <button
        type="button"
        class="btn-close"
        data-bs-dismiss="alert">
    </button>
</div>
@endif

<!-- Contraseña provisoria -->
@if (session('password_provisoria'))
<div class="alert alert-warning alert-dismissible fade show" role="alert">
    <strong>Contraseña provisoria:</strong>
    <span class="font-monospace">
        {{ session('password_provisoria') }}
    </span>

    <br>

    <small>
        Mostrá esta contraseña al empleado. Deberá cambiarla en su próximo ingreso.
    </small>

    <button
        type="button"
        class="btn-close"
        data-bs-dismiss="alert">
    </button>
</div>
@endif

<!-- Errores generales -->
@if ($errors->any())
<div class="alert alert-danger">
    <strong>Se encontraron errores:</strong>

    <ul class="mb-0 mt-2">
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<!-- Botones -->
<section class="mb-4 d-flex flex-column flex-md-row justify-content-between align-items-stretch gap-3">

    <a
        href="{{ route('gestion.empleados.create') }}"
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

        Registrar nuevo empleado
    </a>

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
    <form
        class="row g-3 align-items-end shadow-sm p-3 rounded bg-light"
        method="GET"
        action="{{ route('gestion.empleados.index') }}"
        autocomplete="off"
        id="formFiltros">

        <!-- Búsqueda -->
        <div class="col-md-5">
            <label
                for="q"
                class="form-label fw-semibold">
                Buscar
            </label>

            <input
                type="text"
                class="form-control rounded-3"
                id="q"
                name="q"
                value="{{ $filtros['q'] ?? '' }}"
                placeholder="Nombre, apellido, usuario, legajo o documento">
        </div>

        <!-- Rol -->
        <div class="col-md-3">
            <label
                for="rol_id"
                class="form-label fw-semibold">
                Cargo
            </label>

            <select
                class="form-select rounded-3"
                id="rol_id"
                name="rol_id">

                <option value="">Todos los cargos</option>

                @foreach ($roles as $rol)
                <option
                    value="{{ $rol->id }}"
                    {{ ($filtros['rol_id'] ?? '') == $rol->id ? 'selected' : '' }}>
                    {{ ucfirst($rol->nombre) }}
                </option>
                @endforeach

            </select>
        </div>

        <!-- Estado -->
        <div class="col-md-2">
            <label
                for="estado"
                class="form-label fw-semibold">
                Estado
            </label>

            <select
                class="form-select rounded-3"
                id="estado"
                name="estado">

                <option
                    value=""
                    {{ ($filtros['estado'] ?? '') === '' ? 'selected' : '' }}>
                    Todos
                </option>

                <option
                    value="activos"
                    {{ ($filtros['estado'] ?? '') === 'activos' ? 'selected' : '' }}>
                    Activos
                </option>

                <option
                    value="inactivos"
                    {{ ($filtros['estado'] ?? '') === 'inactivos' ? 'selected' : '' }}>
                    Inactivos
                </option>

            </select>
        </div>

        <!-- Botón filtrar -->
        <div class="col-md-2">
            <button
                type="submit"
                class="btn btn-primary w-100">
                Filtrar
            </button>
        </div>

    </form>
</section>

<!-- Tabla -->
<section>
    <div class="table-responsive">

        <table
            class="table table-bordered table-hover align-middle text-center"
            id="tablaEmpleados">

            <thead class="table-dark">
                <tr>
                    <th>Legajo</th>
                    <th>Empleado</th>
                    <th>Usuario</th>
                    <th>Cargo</th>
                    <th>Documento</th>
                    <th>CUIL</th>
                    <th>Estado</th>
                    <th>Opciones</th>
                </tr>
            </thead>

            <tbody>

                @forelse ($empleados as $empleado)

                <tr>

                    <!-- Legajo -->
                    <td>
                        {{ $empleado->legajo }}
                    </td>

                    <!-- Nombre completo -->
                    <td class="text-start">
                        <strong>
                            {{ $empleado->user->apellido }},
                            {{ $empleado->user->nombres }}
                        </strong>

                        @if ($empleado->user->email)
                        <br>
                        <small class="text-muted">
                            {{ $empleado->user->email }}
                        </small>
                        @endif
                    </td>

                    <!-- Cargo -->
                    <td>
                        {{ ucfirst($empleado->user->rol?->nombre ?? 'Sin cargo') }}
                    </td>

                    <!-- Documento -->
                    <td>
                        {{ $empleado->tipoDocumento?->nombre ?? '—' }}
                        <br>
                        <small class="text-muted">
                            {{ $empleado->nro_doc }}
                        </small>
                    </td>

                    <!-- CUIL -->
                    <td>
                        {{ $empleado->cuilFormateado() }}
                    </td>

                    <!-- Estado -->
                    <td>
                        @if ($empleado->user->activo)
                        <span class="badge text-bg-success">
                            Activo
                        </span>
                        @else
                        <span class="badge text-bg-secondary">
                            Inactivo
                        </span>
                        @endif
                    </td>

                    <!-- Opciones -->
                    <td class="text-nowrap">

                        <!-- Editar -->
                        <a
                            href="{{ route('gestion.empleados.edit', $empleado) }}"
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

                        <!-- Dar de baja -->
                        @if ($empleado->user->activo)

                        <form
                            action="{{ route('gestion.empleados.darDeBaja', $empleado) }}"
                            method="POST"
                            class="d-inline">

                            @csrf
                            @method('PATCH')

                            <button
                                type="submit"
                                class="btn btn-sm btn-secondary me-1"
                                title="Dar de baja"
                                onclick="return confirm('¿Está seguro de dar de baja este empleado?');">

                                <svg xmlns="http://www.w3.org/2000/svg"
                                    width="16"
                                    height="16"
                                    fill="currentColor"
                                    viewBox="0 0 16 16">

                                    <path d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14zM4.5 7.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1 0-1z" />

                                </svg>
                            </button>

                        </form>

                        <!-- Reactivar -->
                        @else

                        <form
                            action="{{ route('user.reactivar', $empleado) }}"
                            method="POST"
                            class="d-inline">

                            @csrf
                            @method('PATCH')

                            <button
                                type="submit"
                                class="btn btn-sm btn-success"
                                title="Reactivar"
                                onclick="return confirm('¿Está seguro de reactivar este empleado?');">

                                <svg xmlns="http://www.w3.org/2000/svg"
                                    width="16"
                                    height="16"
                                    fill="currentColor"
                                    viewBox="0 0 16 16">

                                    <path d="M8 3a5 5 0 1 1-4.546 2.917.5.5 0 0 1 .91-.414A4 4 0 1 0 8 4z" />

                                    <path d="M8 1v4H4" />
                                </svg>
                            </button>

                        </form>

                        @endif

                    </td>

                </tr>

                @empty

                <tr>
                    <td
                        colspan="8"
                        class="text-center py-4 text-muted">
                        No se encontraron empleados.
                    </td>
                </tr>

                @endforelse

            </tbody>

        </table>

    </div>
</section>

<!-- Paginación -->
@if ($empleados->hasPages())
<section class="mt-4 d-flex justify-content-center">
    {{ $empleados->links() }}
</section>
@endif




@endsection