@extends('layouts.app')

@section('title', 'PowerTech')

@section('content')

<div class="container py-4">


{{-- Título --}}
<section class="mb-4">
    <h1 class="h3 fw-bold mb-2">Editar producto</h1>
    <p class="text-muted">
        Los campos están bloqueados por defecto. Presione "Editar" para modificar los datos.
    </p>
</section>

{{-- Formulario de edición --}}
<section class="mb-4">

    <form
        id="editarProductoForm"
        action="{{ route('items.update', $producto) }}"
        method="POST"
        enctype="multipart/form-data"
        autocomplete="off"
        class="p-4 rounded-4 bg-light shadow-sm border">

        @csrf
        @method('PUT')

        <div class="row g-4">

            {{-- Código --}}
            <div class="col-md-4">
                <label for="codigo" class="form-label fw-semibold">
                    Código
                </label>

                <input
                    type="text"
                    class="form-control rounded-3 @error('codigo') is-invalid @enderror"
                    id="codigo"
                    name="codigo"
                    value="{{ old('codigo', $producto->codigo) }}"
                    maxlength="30"
                    required
                    disabled>

                @error('codigo')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            {{-- Nombre --}}
            <div class="col-md-8">
                <label for="nombre" class="form-label fw-semibold">
                    Nombre del producto
                </label>

                <input
                    type="text"
                    class="form-control rounded-3 @error('nombre') is-invalid @enderror"
                    id="nombre"
                    name="nombre"
                    value="{{ old('nombre', $producto->nombre) }}"
                    maxlength="150"
                    required
                    disabled>

                @error('nombre')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            {{-- Marca --}}
            <div class="col-md-6">
                <label for="marca" class="form-label fw-semibold">
                    Marca
                </label>

                <input
                    type="text"
                    class="form-control rounded-3 @error('marca') is-invalid @enderror"
                    id="marca"
                    name="marca"
                    value="{{ old('marca', $producto->marca) }}"
                    maxlength="80"
                    disabled>

                @error('marca')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            {{-- Categoría --}}
            <div class="col-md-6">
                <label for="categoria_id" class="form-label fw-semibold">
                    Categoría
                </label>

                <select
                    id="categoria_id"
                    name="categoria_id"
                    class="form-select rounded-3 @error('categoria_id') is-invalid @enderror"
                    required
                    disabled>

                    <option value="">Seleccione una categoría</option>

                    @foreach($categorias as $categoria)
                        <option
                            value="{{ $categoria->id }}"
                            @selected(old('categoria_id', $producto->categoria_id) == $categoria->id)>
                            {{ $categoria->nombre }}
                        </option>
                    @endforeach

                </select>

                @error('categoria_id')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            {{-- Descripción --}}
            <div class="col-12">
                <label for="descripcion" class="form-label fw-semibold">
                    Descripción
                </label>

                <textarea
                    class="form-control rounded-3 @error('descripcion') is-invalid @enderror"
                    id="descripcion"
                    name="descripcion"
                    rows="4"
                    style="resize: none"
                    disabled>{{ old('descripcion', $producto->descripcion) }}</textarea>

                @error('descripcion')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            {{-- Precio --}}
            <div class="col-md-4">
                <label for="precio" class="form-label fw-semibold">
                    Precio
                </label>

                <input
                    type="number"
                    class="form-control rounded-3 @error('precio') is-invalid @enderror"
                    id="precio"
                    name="precio"
                    value="{{ old('precio', $producto->precio) }}"
                    min="0"
                    step="0.01"
                    required
                    disabled>

                @error('precio')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            {{-- Costo --}}
            <div class="col-md-4">
                <label for="costo" class="form-label fw-semibold">
                    Costo
                </label>

                <input
                    type="number"
                    class="form-control rounded-3 @error('costo') is-invalid @enderror"
                    id="costo"
                    name="costo"
                    value="{{ old('costo', $producto->costo) }}"
                    min="0"
                    step="0.01"
                    disabled>

                @error('costo')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            {{-- IVA --}}
            <div class="col-md-4">
                <label for="alicuota_iva_id" class="form-label fw-semibold">
                    Alícuota de IVA
                </label>

                <select
                    id="alicuota_iva_id"
                    name="alicuota_iva_id"
                    class="form-select rounded-3 @error('alicuota_iva_id') is-invalid @enderror"
                    required
                    disabled>

                    <option value="">Seleccione IVA</option>

                    @foreach($alicuotas as $alicuota)
                        <option
                            value="{{ $alicuota->id }}"
                            @selected(old('alicuota_iva_id', $producto->alicuota_iva_id) == $alicuota->id)>
                            {{ $alicuota->nombre }} ({{ $alicuota->porcentaje }}%)
                        </option>
                    @endforeach

                </select>

                @error('alicuota_iva_id')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            {{-- Proveedor --}}
            <div class="col-md-6">
                <label for="proveedor_id" class="form-label fw-semibold">
                    Proveedor
                </label>

                <select
                    id="proveedor_id"
                    name="proveedor_id"
                    class="form-select rounded-3 @error('proveedor_id') is-invalid @enderror"
                    disabled>

                    <option value="">Sin proveedor</option>

                    @foreach($proveedores as $proveedor)
                        <option
                            value="{{ $proveedor->id }}"
                            @selected(old('proveedor_id', $producto->proveedor_id) == $proveedor->id)>
                            {{ $proveedor->razon_social }}
                        </option>
                    @endforeach

                </select>

                @error('proveedor_id')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            {{-- Peso --}}
            <div class="col-md-6">
                <label for="peso_gramos" class="form-label fw-semibold">
                    Peso (gramos)
                </label>

                <input
                    type="number"
                    class="form-control rounded-3 @error('peso_gramos') is-invalid @enderror"
                    id="peso_gramos"
                    name="peso_gramos"
                    value="{{ old('peso_gramos', $producto->peso_gramos) }}"
                    min="0"
                    disabled>

                @error('peso_gramos')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            {{-- Punto de reposición --}}
            <div class="col-md-6">
                <label for="punto_reposicion" class="form-label fw-semibold">
                    Punto de reposición
                </label>

                <input
                    type="number"
                    class="form-control rounded-3 @error('punto_reposicion') is-invalid @enderror"
                    id="punto_reposicion"
                    name="punto_reposicion"
                    value="{{ old('punto_reposicion', $producto->punto_reposicion) }}"
                    min="0"
                    required
                    disabled>

                @error('punto_reposicion')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            {{-- Cantidad de reposición --}}
            <div class="col-md-6">
                <label for="cantidad_reposicion" class="form-label fw-semibold">
                    Cantidad de reposición
                </label>

                <input
                    type="number"
                    class="form-control rounded-3 @error('cantidad_reposicion') is-invalid @enderror"
                    id="cantidad_reposicion"
                    name="cantidad_reposicion"
                    value="{{ old('cantidad_reposicion', $producto->cantidad_reposicion) }}"
                    min="0"
                    required
                    disabled>

                @error('cantidad_reposicion')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            {{-- Reposición automática --}}
            <div class="col-md-6">
                <div class="form-check form-switch mt-2">

                    <input
                        class="form-check-input"
                        type="checkbox"
                        role="switch"
                        id="reposicion_automatica"
                        name="reposicion_automatica"
                        value="1"
                        @checked(old('reposicion_automatica', $producto->reposicion_automatica))
                        disabled>

                    <label
                        class="form-check-label fw-semibold"
                        for="reposicion_automatica">
                        Reposición automática
                    </label>

                </div>

                <div class="form-text">
                    Requiere seleccionar un proveedor.
                </div>

                @error('reposicion_automatica')
                    <div class="text-danger small mt-2">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            {{-- Publicado en la aplicación --}}
            <div class="col-md-6">
                <div class="form-check form-switch mt-2">

                    <input
                        class="form-check-input"
                        type="checkbox"
                        role="switch"
                        id="publicado_app"
                        name="publicado_app"
                        value="1"
                        @checked(old('publicado_app', $producto->publicado_app))
                        disabled>

                    <label
                        class="form-check-label fw-semibold"
                        for="publicado_app">
                        Publicado en la aplicación
                    </label>

                </div>

                <div class="form-text">
                    El producto podrá estar disponible para los clientes.
                </div>
            </div>

            {{-- Imagen --}}
            <div class="col-12">
                <label for="imagen" class="form-label fw-semibold">
                    Cambiar imagen
                </label>

                <input
                    type="file"
                    class="form-control rounded-3 @error('imagen') is-invalid @enderror"
                    id="imagen"
                    name="imagen"
                    accept=".jpg,.jpeg,.png,.webp"
                    disabled>

                @error('imagen')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

        </div>

    </form>

    {{-- ========================================================= --}}
    {{-- BOTONES: TODOS FUERA DEL FORMULARIO --}}
    {{-- ========================================================= --}}

    <div class="mt-4 d-flex flex-wrap gap-3">

        {{-- Editar --}}
        <button
            type="button"
            class="btn btn-warning px-4 fw-semibold shadow-sm"
            id="btnEditar">
            Editar
        </button>

        {{-- Actualizar --}}
        <button
            type="submit"
            form="editarProductoForm"
            class="btn btn-success px-4 fw-semibold shadow-sm"
            id="btnActualizar"
            disabled>
            Actualizar
        </button>

        {{-- Cancelar --}}
        <button
            type="button"
            class="btn btn-secondary px-4 fw-semibold shadow-sm"
            id="btnCancelar"
            disabled>
            Cancelar
        </button>

        {{-- Volver --}}
        <a
            href="{{ route('items.index') }}"
            class="btn btn-outline-secondary px-4 fw-semibold shadow-sm">
            Volver al listado
        </a>

        {{-- Historial --}}
        <a
            href="{{ route('items.movimientos', $producto) }}"
            class="btn btn-outline-primary px-4 fw-semibold shadow-sm">
            Historial de stock
        </a>

        @if($producto->activo)

            <form
                action="{{ route('items.darDeBaja', ['producto' => $producto->id]) }}"
                method="POST"
                class="d-inline">

                @csrf
                @method('PATCH')

                <button
                    type="submit"
                    class="btn btn-danger px-4 fw-semibold shadow-sm"
                    onclick="return confirm('¿Está seguro de que desea dar de baja este producto?')">
                    Dar de baja
                </button>

            </form>

        @else

            <form
                action="{{ route('items.reactivar', ['producto' => $producto->id]) }}"
                method="POST"
                class="d-inline">

                @csrf
                @method('PATCH')

                <button
                    type="submit"
                    class="btn btn-success px-4 fw-semibold shadow-sm"
                    onclick="return confirm('¿Está seguro de que desea dar de alta este producto?')">
                    Dar de alta
                </button>

            </form>

        @endif

    </div>

</section>


</div>

@endsection

@push('scripts')

<script>

    document.addEventListener('DOMContentLoaded', function() {

        const form = document.getElementById('editarProductoForm');
        const btnEditar = document.getElementById('btnEditar');
        const btnActualizar = document.getElementById('btnActualizar');
        const btnCancelar = document.getElementById('btnCancelar');

        const campos = form.querySelectorAll(
            'input:not([type="hidden"]), select, textarea'
        );

        // Guardamos los valores originales para poder cancelar.
        const valoresOriginales = {};

        campos.forEach(campo => {

            if (campo.type === 'checkbox') {

                valoresOriginales[campo.id] = campo.checked;

            } else if (campo.type !== 'file') {

                valoresOriginales[campo.id] = campo.value;

            }

        });

        function activarEdicion() {

            campos.forEach(campo => {

                if (campo.type !== 'hidden') {
                    campo.disabled = false;
                }

            });

            btnEditar.disabled = true;
            btnActualizar.disabled = false;
            btnCancelar.disabled = false;
        }

        function cancelarEdicion() {

            campos.forEach(campo => {

                if (campo.type === 'file') {

                    campo.value = '';
                    campo.disabled = true;
                    return;

                }

                if (campo.type === 'checkbox') {

                    campo.checked = valoresOriginales[campo.id];

                } else {

                    campo.value = valoresOriginales[campo.id];

                }

                campo.disabled = true;

            });

            btnEditar.disabled = false;
            btnActualizar.disabled = true;
            btnCancelar.disabled = true;
        }

        btnEditar.addEventListener('click', activarEdicion);
        btnCancelar.addEventListener('click', cancelarEdicion);

    });

</script>

@endpush
