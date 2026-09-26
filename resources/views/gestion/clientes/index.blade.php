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


@include('gestion.partials.alertas')


<!-- Botones -->

<section class="mb-4 d-flex flex-column flex-md-row justify-content-between align-items-stretch gap-3">

    @can('create', App\Models\Cliente::class)

    <a href="{{ route('gestion.clientes.create') }}"
        class="btn btn-success text-white d-flex align-items-center justify-content-center gap-2 flex-fill flex-md-grow-0">

        <svg xmlns="http://www.w3.org/2000/svg"
            width="18"
            height="18"
            fill="currentColor"
            class="bi bi-plus-circle"
            viewBox="0 0 16 16">

            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16" />

            <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4" />
        </svg>

        Registrar nuevo cliente
    </a>

    @endcan

</section>


<!-- Filtros -->

<section class="mb-4">

    <form
        action="{{ route('gestion.clientes.index') }}"
        method="GET"
        class="row g-3 align-items-end shadow-sm p-3 rounded bg-light"
        autocomplete="off">

        <div class="col-12 col-md-6">

            <label for="q" class="form-label fw-semibold">
                Buscar por razón social / documento
            </label>

            <input
                type="text"
                class="form-control"
                id="q"
                name="q"
                value="{{ $filtros['q'] ?? '' }}"
                placeholder="Ej: 20304050607"
                maxlength="100">

        </div>

        <div class="col-12 col-md-4">

            <label for="activo" class="form-label fw-semibold">Situación</label>

            <select id="activo" name="activo" class="form-select">

                <option value="">Todos</option>

                <option value="1" @selected(($filtros['activo'] ?? null) == '1' && isset($filtros['activo']))>
                    En operación
                </option>

                <option value="0" @selected(isset($filtros['activo']) && ! $filtros['activo'])>
                    Dados de baja
                </option>

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

            <tbody>

                @forelse ($clientes as $cliente)

                <tr>

                    <td class="text-start">
                        {{ $cliente->razon_social }}
                    </td>

                    <td class="text-nowrap">
                        {{ $cliente->tipoDocumento?->nombre }} {{ $cliente->nro_doc }}
                    </td>

                    <td>
                        {{ $cliente->condicionIva?->nombre }}
                    </td>

                    <td>
                        {{ $cliente->telefono ?: '—' }}
                    </td>

                    <td>

                        @if ($cliente->activo)

                        <span class="badge text-bg-success">Activo</span>

                        @else

                        <span class="badge text-bg-secondary">Inactivo</span>

                        @endif

                    </td>

                    <td class="text-nowrap">

                        <!-- Ver -->
                        <a
                            href="{{ route('gestion.clientes.show', $cliente) }}"
                            class="btn btn-sm btn-primary me-1"
                            title="Ver ficha">

                            <svg xmlns="http://www.w3.org/2000/svg"
                                width="16"
                                height="16"
                                fill="currentColor"
                                class="bi bi-eye"
                                viewBox="0 0 16 16">

                                <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z" />

                                <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0" />
                            </svg>

                        </a>


                        @can('update', $cliente)

                        <!-- Editar -->
                        <a
                            href="{{ route('gestion.clientes.edit', $cliente) }}"
                            class="btn btn-sm btn-warning me-1"
                            title="Editar">

                            <svg xmlns="http://www.w3.org/2000/svg"
                                width="16"
                                height="16"
                                fill="currentColor"
                                class="bi bi-pencil"
                                viewBox="0 0 16 16">

                                <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325" />
                            </svg>

                        </a>

                        @endcan


                        @can('baja', $cliente)

                            @if ($cliente->activo)

                            <!-- Dar de baja -->
                            <form
                                action="{{ route('gestion.clientes.baja', $cliente) }}"
                                method="POST"
                                class="d-inline">

                                @csrf
                                @method('PATCH')

                                <button
                                    type="submit"
                                    class="btn btn-sm btn-secondary"
                                    title="Dar de baja"
                                    onclick="return confirm('¿Está seguro de dar de baja este cliente?');">

                                    <svg xmlns="http://www.w3.org/2000/svg"
                                        width="16"
                                        height="16"
                                        fill="currentColor"
                                        viewBox="0 0 16 16">

                                        <path d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14zM4.5 7.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1 0-1z" />
                                    </svg>

                                </button>

                            </form>

                            @else

                            <!-- Reactivar -->
                            <form
                                action="{{ route('gestion.clientes.reactivar', $cliente) }}"
                                method="POST"
                                class="d-inline">

                                @csrf
                                @method('PATCH')

                                <button
                                    type="submit"
                                    class="btn btn-sm btn-success"
                                    title="Reactivar"
                                    onclick="return confirm('¿Está seguro de reactivar este cliente?');">

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

                        @endcan

                    </td>

                </tr>

                @empty

                <tr>

                    <td colspan="6" class="text-center py-4 text-muted">
                        No se encontraron clientes.
                    </td>

                </tr>

                @endforelse

            </tbody>

        </table>

    </div>


    <!-- Paginación Laravel -->
    @if ($clientes->hasPages())

    <div class="d-flex justify-content-center mt-3">

        {{ $clientes->onEachSide(2)->links() }}

    </div>

    @endif

</section>

@endsection
