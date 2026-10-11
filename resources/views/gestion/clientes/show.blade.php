@extends('layouts.gestion')

@section('title', 'PowerTech')

@section('content')

<!-- Título -->

<section class="mb-4">

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">

        <div>

            <h1 class="h3 fw-bold mb-2 d-flex flex-wrap align-items-center gap-2">

                {{ $cliente->razon_social }}

                @unless ($cliente->activo)
                <span class="badge text-bg-secondary">Dado de baja</span>
                @endunless

            </h1>

            <p class="text-muted mb-0">
                {{ $cliente->tipoDocumento?->nombre }} {{ $cliente->nro_doc }}
                · {{ $cliente->condicionIva?->nombre }}
            </p>

        </div>


        <div class="d-flex flex-wrap gap-2">

            @can('update', $cliente)

            <a href="{{ route('gestion.clientes.edit', $cliente) }}" class="btn btn-warning fw-semibold">
                Editar
            </a>

            @endcan


            @can('baja', $cliente)

            <form
                action="{{ $cliente->activo ? route('gestion.clientes.baja', $cliente) : route('gestion.clientes.reactivar', $cliente) }}"
                method="POST">

                @csrf
                @method('PATCH')

                <button
                    type="submit"
                    class="btn {{ $cliente->activo ? 'btn-outline-danger' : 'btn-success' }} fw-semibold"
                    onclick="return confirm('{{ $cliente->activo ? '¿Está seguro de dar de baja este cliente?' : '¿Está seguro de reactivar este cliente?' }}');">
                    {{ $cliente->activo ? 'Dar de baja' : 'Reactivar' }}
                </button>

            </form>

            @endcan


            <a href="{{ route('gestion.clientes.index') }}" class="btn btn-outline-secondary">
                Volver al listado
            </a>

        </div>

    </div>

</section>


@include('gestion.partials.alertas')


@unless ($cliente->activo)

<div class="alert alert-warning" role="alert">
    Este cliente está fuera de operación: no se ofrece al cargar una venta nueva.
    Sus compras anteriores lo siguen nombrando y la baja se puede revertir.
</div>

@endunless


<div class="row g-4">

    <!-- Columna izquierda -->
    <div class="col-12 col-lg-8">

        <!-- Últimas compras -->
        <section class="mb-4">

            <h2 class="h5 fw-bold mb-3">Últimas compras</h2>

            <div class="table-responsive">

                <table class="table table-bordered table-hover align-middle text-center">

                    <thead class="table-dark">

                        <tr>
                            <th class="text-nowrap">Número</th>
                            <th class="text-nowrap">Fecha</th>
                            <th class="text-nowrap">Estado</th>
                            <th class="text-nowrap">Total</th>
                        </tr>

                    </thead>

                    <tbody>

                        @forelse ($ventas as $venta)

                        <tr>

                            <td class="text-nowrap fw-semibold">
                                <a href="{{ route('gestion.ventas.show', $venta) }}">
                                    {{ $venta->numeroFormateado() }}
                                </a>
                            </td>

                            <td class="text-nowrap">
                                {{ $venta->fecha?->format('d/m/Y') }}
                            </td>

                            <td>
                                @include('gestion.partials.estado', ['estado' => $venta->estado])
                            </td>

                            <td class="text-nowrap fw-semibold">
                                $ {{ number_format($venta->total, 2, ',', '.') }}
                            </td>

                        </tr>

                        @empty

                        <tr>

                            <td colspan="4" class="text-center py-4 text-muted">
                                Todavía no compró nada.
                            </td>

                        </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </section>


        <!-- Direcciones -->
        <section>

            <h2 class="h5 fw-bold mb-1">Direcciones</h2>

            <p class="text-muted small">
                La fiscal va en la factura; la de envío, en el remito. Un envío ya
                despachado no cambia de destino si se edita esto.
            </p>

            @if ($cliente->direcciones->isEmpty())

            <p class="text-muted">Sin direcciones cargadas.</p>

            @else

            <ul class="list-group mb-3">

                @foreach ($cliente->direcciones as $direccion)

                <li class="list-group-item d-flex flex-wrap justify-content-between align-items-start gap-3">

                    <div>

                        <span class="badge text-bg-light border">
                            {{ ucfirst($direccion->tipo->value) }}
                        </span>

                        @if ($direccion->es_predeterminada)
                        <span class="text-muted small">Predeterminada</span>
                        @endif

                        <address class="mb-0 mt-1">
                            {{ $direccion->calle }} {{ $direccion->numero }}
                            @if ($direccion->piso_depto) ({{ $direccion->piso_depto }}) @endif<br>
                            {{ $direccion->localidad }}, {{ $direccion->provincia }}
                            ({{ $direccion->codigo_postal }})
                        </address>

                    </div>

                    @can('update', $cliente)

                    <form
                        action="{{ route('gestion.direcciones.destroy', ['tipo' => 'clientes', 'id' => $cliente->id, 'direccion' => $direccion->id]) }}"
                        method="POST">

                        @csrf
                        @method('DELETE')

                        <button
                            type="submit"
                            class="btn btn-sm btn-outline-danger"
                            title="Eliminar dirección"
                            onclick="return confirm('¿Está seguro de eliminar esta dirección?');">
                            Eliminar
                        </button>

                    </form>

                    @endcan

                </li>

                @endforeach

            </ul>

            @endif


            @can('update', $cliente)

            <!-- Alta de dirección -->
            <div class="accordion" id="acordeonDireccion">

                <div class="accordion-item">

                    <h3 class="accordion-header" id="encabezadoNuevaDireccion">

                        <button
                            class="accordion-button collapsed"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#nuevaDireccion"
                            aria-expanded="false"
                            aria-controls="nuevaDireccion">
                            Agregar una dirección
                        </button>

                    </h3>

                    <div
                        id="nuevaDireccion"
                        class="accordion-collapse collapse"
                        aria-labelledby="encabezadoNuevaDireccion"
                        data-bs-parent="#acordeonDireccion">

                        <div class="accordion-body">

                            <form
                                action="{{ route('gestion.direcciones.store', ['tipo' => 'clientes', 'id' => $cliente->id]) }}"
                                method="POST"
                                class="row g-3"
                                autocomplete="off">

                                @csrf

                                <div class="col-12 col-md-4">

                                    <label for="tipo" class="form-label fw-semibold">Tipo</label>

                                    <select id="tipo" name="tipo" class="form-select rounded-3" required>
                                        <option value="fiscal">Fiscal</option>
                                        <option value="envio">Envío</option>
                                        <option value="particular">Particular</option>
                                    </select>

                                </div>

                                <div class="col-12 col-md-6">

                                    <label for="calle" class="form-label fw-semibold">Calle</label>

                                    <input type="text" class="form-control rounded-3" id="calle" name="calle"
                                        value="{{ old('calle') }}" maxlength="120" required>

                                </div>

                                <div class="col-6 col-md-2">

                                    <label for="numero" class="form-label fw-semibold">Número</label>

                                    <input type="text" class="form-control rounded-3" id="numero" name="numero"
                                        value="{{ old('numero') }}" maxlength="10">

                                </div>

                                <div class="col-6 col-md-3">

                                    <label for="piso_depto" class="form-label fw-semibold">Piso / depto</label>

                                    <input type="text" class="form-control rounded-3" id="piso_depto" name="piso_depto"
                                        value="{{ old('piso_depto') }}" maxlength="20">

                                </div>

                                <div class="col-12 col-md-3">

                                    <label for="localidad" class="form-label fw-semibold">Localidad</label>

                                    <input type="text" class="form-control rounded-3" id="localidad" name="localidad"
                                        value="{{ old('localidad') }}" maxlength="80" required>

                                </div>

                                <div class="col-12 col-md-3">

                                    <label for="provincia" class="form-label fw-semibold">Provincia</label>

                                    <input type="text" class="form-control rounded-3" id="provincia" name="provincia"
                                        value="{{ old('provincia') }}" maxlength="60" required>

                                </div>

                                <div class="col-6 col-md-3">

                                    <label for="codigo_postal" class="form-label fw-semibold">Código postal</label>

                                    <input type="text" class="form-control rounded-3" id="codigo_postal" name="codigo_postal"
                                        value="{{ old('codigo_postal') }}" maxlength="10" required>

                                </div>

                                <div class="col-12">

                                    <div class="form-check form-switch">

                                        <input class="form-check-input" type="checkbox" role="switch"
                                            id="es_predeterminada" name="es_predeterminada" value="1">

                                        <label class="form-check-label fw-semibold" for="es_predeterminada">
                                            Usar como predeterminada de su tipo
                                        </label>

                                    </div>

                                </div>

                                <div class="col-12">

                                    <button type="submit" class="btn btn-success px-4 fw-semibold">
                                        Guardar dirección
                                    </button>

                                </div>

                            </form>

                        </div>

                    </div>

                </div>

            </div>

            @endcan

        </section>

    </div>


    <!-- Columna derecha -->
    <div class="col-12 col-lg-4">

        <section class="p-4 rounded-4 bg-light shadow-sm border mb-4">

            <h2 class="h6 fw-bold mb-2">Contacto</h2>

            <p class="mb-0">{{ $cliente->telefono ?: 'Sin teléfono cargado' }}</p>

        </section>


        <section class="p-4 rounded-4 bg-light shadow-sm border">

            <h2 class="h6 fw-bold mb-2">Cuenta en la tienda</h2>

            @if ($cliente->user)

            <p class="mb-1">{{ $cliente->user->email }}</p>

            <p class="text-muted small mb-0">
                {{ $cliente->user->activo ? 'Puede iniciar sesión en la tienda.' : 'Acceso bloqueado.' }}
            </p>

            @else

            <p class="mb-1">Cliente de mostrador, sin cuenta.</p>

            <p class="text-muted small mb-0">
                Si más adelante se registra, la cuenta se vincula a esta ficha
                en lugar de duplicarla.
            </p>

            @endif

        </section>

    </div>

</div>

@endsection
