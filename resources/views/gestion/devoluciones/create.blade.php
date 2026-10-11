@extends('layouts.gestion')

@section('title', 'PowerTech')

@section('content')

<!-- Título -->

<section class="mb-4">

    <h1 class="h3 fw-bold mb-2">
        Devolución de la venta {{ $venta->numeroFormateado() }}
    </h1>

    <p class="text-muted mb-0">
        Se emite una nota de crédito asociada a la factura y las unidades
        vuelven al stock físico.
    </p>

</section>


@include('gestion.partials.alertas')


<form action="{{ route('gestion.devoluciones.store', $venta) }}" method="POST" autocomplete="off">

    @csrf


    {{-- Errores generales de validación --}}
    @if ($errors->any())

    <div class="alert alert-danger">

        <strong>Revisá estos campos:</strong>

        <ul class="mb-0 mt-2">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>

    </div>

    @endif


    <!-- Líneas -->
    <section class="mb-4">

        <h2 class="h5 fw-bold mb-1">Qué se devuelve</h2>

        <p class="text-muted small">
            Dejá en cero las líneas que el cliente se queda.
        </p>

        <div class="table-responsive">

            <table class="table table-bordered align-middle text-center">

                <thead class="table-dark">

                    <tr>
                        <th class="text-nowrap">Producto</th>
                        <th class="text-nowrap">Vendidas</th>
                        <th class="text-nowrap">Ya devueltas</th>
                        <th class="text-nowrap" style="width: 10rem;">Devolver ahora</th>
                    </tr>

                </thead>

                <tbody>

                    @foreach ($venta->lineas as $linea)

                        @php $tope = $linea->pendienteDeDevolucion(); @endphp

                        <tr class="{{ $tope === 0 ? 'text-muted' : '' }}">

                            <td class="text-start">

                                <div class="fw-semibold">{{ $linea->descripcion }}</div>

                                @if ($linea->producto)
                                <div class="text-muted small">{{ $linea->producto->codigo }}</div>
                                @endif

                            </td>

                            <td>{{ $linea->cantidad }}</td>

                            <td>{{ $linea->cantidad_devuelta }}</td>

                            <td>

                                <input
                                    type="number"
                                    class="form-control form-control-sm text-center"
                                    name="cantidades[{{ $linea->id }}]"
                                    value="{{ old('cantidades.'.$linea->id, 0) }}"
                                    min="0"
                                    max="{{ $tope }}"
                                    @disabled($tope === 0)>

                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

        @error('cantidades')
        <div class="text-danger small mt-2">
            {{ $message }}
        </div>
        @enderror

    </section>


    <!-- Motivo -->
    <section class="mb-4 p-4 rounded-4 bg-light shadow-sm border">

        <label for="motivo" class="form-label fw-semibold">
            Motivo
        </label>

        <textarea
            class="form-control rounded-3 @error('motivo') is-invalid @enderror"
            id="motivo"
            name="motivo"
            rows="3"
            minlength="5"
            maxlength="255"
            style="resize: none"
            placeholder="Ej: producto fallado."
            required>{{ old('motivo') }}</textarea>

        <div class="form-text">
            Queda asentado en la nota de crédito y en el historial de la venta.
        </div>

        @error('motivo')
        <div class="invalid-feedback">
            {{ $message }}
        </div>
        @enderror

    </section>


    <!-- Botones -->
    <section class="d-flex flex-wrap gap-3">

        <button
            type="submit"
            class="btn btn-success px-4 fw-semibold shadow-sm"
            onclick="return confirm('Se va a emitir una nota de crédito. ¿Continuar?');">
            Emitir la nota de crédito
        </button>

        <a
            href="{{ route('gestion.ventas.show', $venta) }}"
            class="btn btn-outline-secondary px-4 fw-semibold shadow-sm">
            Volver a la venta
        </a>

    </section>


    <p class="text-muted small mt-3">
        La venta conserva su número. Queda como devuelta o devuelta parcialmente
        según cuánto se devuelva.
    </p>

</form>

@endsection
