@extends('layouts.gestion')

@section('title', 'PowerTech')

@section('content')

<div class="container py-4">


    {{-- Título --}}
    <section class="mb-4">
        <h1 class="h3 fw-bold mb-2">Alta de nuevo producto</h1>
        <p class="text-muted">
            Complete los datos para registrar un nuevo producto en el sistema.
        </p>
    </section>

    {{-- Formulario --}}
    <section class="mb-4">

        {{-- Fallas de la API que no son de validacion: sin permiso, sin red. --}}
        <div id="avisosProducto" aria-live="polite"></div>

        <form
            autocomplete="off"
            class="p-4 rounded-4 bg-light shadow-sm border"
            id="formularioProducto">

            {{-- Errores de validación que devuelve la API. --}}
            <div id="erroresProducto"></div>

            <div class="row g-4">

                {{-- Código --}}
                <div class="col-md-4">
                    <label for="codigo" class="form-label fw-semibold">
                        Código
                    </label>

                    <input
                        type="text"
                        class="form-control rounded-3"
                        id="codigo"
                        name="codigo"
                        maxlength="50"
                        placeholder="Ej: PRD-001"
                        required>

                    <div class="invalid-feedback" id="error-codigo"></div>
                </div>

                {{-- Nombre --}}
                <div class="col-md-8">
                    <label for="nombre" class="form-label fw-semibold">
                        Nombre del producto
                    </label>

                    <input
                        type="text"
                        class="form-control rounded-3"
                        id="nombre"
                        name="nombre"
                        maxlength="100"
                        placeholder="Ej: Mouse Gamer RGB"
                        required>

                    <div class="invalid-feedback" id="error-nombre"></div>
                </div>

                {{-- Marca --}}
                <div class="col-md-6">
                    <label for="marca" class="form-label fw-semibold">
                        Marca
                    </label>

                    <input
                        type="text"
                        class="form-control rounded-3"
                        id="marca"
                        name="marca"
                        maxlength="100"
                        placeholder="Ej: Logitech">

                    <div class="invalid-feedback" id="error-marca"></div>
                </div>

                {{-- Categoría --}}
                <div class="col-md-6">
                    <label for="categoria_id" class="form-label fw-semibold">
                        Categoría
                    </label>

                    <select
                        id="categoria_id"
                        name="categoria_id"
                        class="form-select rounded-3"
                        required>
                        <option value="">Seleccione una categoría</option>

                        @foreach($categorias as $categoria)
                        <option
                            value="{{ $categoria->id }}"
 == $categoria->id)
                            >
                            {{ $categoria->nombre }}
                        </option>
                        @endforeach
                    </select>

                    <div class="invalid-feedback" id="error-categoria_id"></div>
                </div>

                {{-- Descripción --}}
                <div class="col-12">
                    <label for="descripcion" class="form-label fw-semibold">
                        Descripción
                    </label>

                    <textarea
                        class="form-control rounded-3"
                        id="descripcion"
                        name="descripcion"
                        rows="4"
                        placeholder="Breve descripción del producto."
                        style="resize: none"></textarea>

                    <div class="invalid-feedback" id="error-descripcion"></div>
                </div>

                <hr class="my-4">

                {{-- Precio --}}
                <div class="col-md-4">
                    <label for="precio" class="form-label fw-semibold">
                        Precio
                    </label>

                    <input
                        type="number"
                        class="form-control rounded-3"
                        id="precio"
                        name="precio"
                        min="0"
                        step="0.01"
                        placeholder="Ej: 35000"
                        required>

                    <div class="invalid-feedback" id="error-precio"></div>
                </div>

                {{-- Costo --}}
                <div class="col-md-4">
                    <label for="costo" class="form-label fw-semibold">
                        Costo
                    </label>

                    <input
                        type="number"
                        class="form-control rounded-3"
                        id="costo"
                        name="costo"
                        min="0"
                        step="0.01"
                        placeholder="Ej: 25000">

                    <div class="invalid-feedback" id="error-costo"></div>
                </div>

                {{-- IVA --}}
                <div class="col-md-4">
                    <label for="alicuota_iva_id" class="form-label fw-semibold">
                        Alícuota de IVA
                    </label>

                    <select
                        id="alicuota_iva_id"
                        name="alicuota_iva_id"
                        class="form-select rounded-3"
                        required>
                        <option value="">Seleccione IVA</option>

                        @foreach($alicuotas as $alicuota)
                        <option
                            value="{{ $alicuota->id }}"
 == $alicuota->id)
                            >
                            {{ $alicuota->nombre ?? $alicuota->porcentaje . '%' }}
                        </option>
                        @endforeach
                    </select>

                    <div class="invalid-feedback" id="error-alicuota_iva_id"></div>
                </div>

                {{-- Proveedor --}}
                <div class="col-md-6">
                    <label for="proveedor_id" class="form-label fw-semibold">
                        Proveedor
                    </label>

                    <select
                        id="proveedor_id"
                        name="proveedor_id"
                        class="form-select rounded-3">
                        <option value="">Sin proveedor</option>

                        @foreach($proveedores as $proveedor)
                        <option
                            value="{{ $proveedor->id }}"
 == $proveedor->id)
                            >
                            {{ $proveedor->razon_social }}
                        </option>
                        @endforeach
                    </select>

                    <div class="invalid-feedback" id="error-proveedor_id"></div>
                </div>

                {{-- Stock inicial --}}
                <div class="col-md-6">
                    <label for="stock_inicial" class="form-label fw-semibold">
                        Stock inicial
                    </label>

                    <input
                        type="number"
                        class="form-control rounded-3"
                        id="stock_inicial"
                        name="stock_inicial"
                        value="0"
                        min="0"
                        placeholder="Ej: 20">

                    <div class="form-text">
                        Se registrará como movimiento inicial de stock.
                    </div>

                    <div class="invalid-feedback" id="error-stock_inicial"></div>
                </div>

                {{-- Punto de reposición --}}
                <div class="col-md-6">
                    <label for="punto_reposicion" class="form-label fw-semibold">
                        Punto de reposición
                    </label>

                    <input
                        type="number"
                        class="form-control rounded-3"
                        id="punto_reposicion"
                        name="punto_reposicion"
                        value="0"
                        min="0"
                        placeholder="Ej: 5"
                        required>

                    <div class="invalid-feedback" id="error-punto_reposicion"></div>
                </div>

                {{-- Cantidad de reposición --}}
                <div class="col-md-6">
                    <label for="cantidad_reposicion" class="form-label fw-semibold">
                        Cantidad de reposición
                    </label>

                    <input
                        type="number"
                        class="form-control rounded-3"
                        id="cantidad_reposicion"
                        name="cantidad_reposicion"
                        value="0"
                        min="0"
                        placeholder="Ej: 10"
                        required>

                    <div class="invalid-feedback" id="error-cantidad_reposicion"></div>
                </div>

                <hr class="my-4">

                {{-- Peso --}}
                <div class="col-md-6">
                    <label for="peso_gramos" class="form-label fw-semibold">
                        Peso (gramos)
                    </label>

                    <input
                        type="number"
                        class="form-control rounded-3"
                        id="peso_gramos"
                        name="peso_gramos"
                        min="0"
                        placeholder="Ej: 500">

                    <div class="invalid-feedback" id="error-peso_gramos"></div>
                </div>

                {{-- Imagen --}}
                <div class="col-md-6">
                    <label for="imagen" class="form-label fw-semibold">
                        Imagen del producto
                    </label>

                    <input
                        type="file"
                        class="form-control rounded-3"
                        id="imagen"
                        name="imagen"
                        accept=".jpg,.jpeg,.png,.webp">

                    <div class="form-text">
                        Formatos permitidos: JPG, JPEG, PNG o WEBP. Máximo 2 MB.
                    </div>

                    <div class="invalid-feedback" id="error-imagen"></div>
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
                        >

                        <label
                            class="form-check-label fw-semibold"
                            for="reposicion_automatica">
                            Reposición automática
                        </label>

                    </div>

                    <div class="form-text">
                        Requiere seleccionar un proveedor.
                    </div>

                    <div class="text-danger small mt-2" id="error-reposicion_automatica"></div>
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
                        >

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

            </div>

            {{-- Botones --}}
            <div class="mt-5 d-flex flex-wrap gap-3">

                <button
                    type="submit"
                    id="botonGuardar"
                    class="btn btn-success px-4 fw-semibold shadow-sm">
                    Guardar
                </button>

                <a
                    href="{{ route('gestion.items.index') }}"
                    class="btn btn-outline-secondary px-4 fw-semibold shadow-sm">
                    Volver al listado
                </a>

            </div>

        </form>

    </section>


</div>

@endsection

@push('scripts')
<script type="module">
    import { iniciar } from '{{ Vite::asset('resources/js/gestion/productos/formulario.js') }}';

    iniciar({
        urlListado: '{{ route('gestion.items.index') }}',
    });
</script>
@endpush
