@extends('layouts.gestion')

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

        {{-- Fallas de la API que no son de validacion: sin permiso, sin red. --}}
        <div id="avisosProducto" aria-live="polite"></div>

        <form
            id="formularioProducto"
            enctype="multipart/form-data"
            autocomplete="off"
            class="p-4 rounded-4 bg-light shadow-sm border">

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
                        maxlength="30"
                        required
                        disabled>

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
                        maxlength="150"
                        required
                        disabled>

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
                        maxlength="80"
                        disabled>

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
                        required
                        disabled>

                        <option value="">Seleccione una categoría</option>

                        @foreach($categorias as $categoria)
                        <option
                            value="{{ $categoria->id }}"
 == $categoria->id)>
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
                        style="resize: none"
                        disabled></textarea>

                    <div class="invalid-feedback" id="error-descripcion"></div>
                </div>

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
                        required
                        disabled>

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
                        disabled>

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
                        required
                        disabled>

                        <option value="">Seleccione IVA</option>

                        @foreach($alicuotas as $alicuota)
                        <option
                            value="{{ $alicuota->id }}"
 == $alicuota->id)>
                            {{ $alicuota->nombre }} ({{ $alicuota->porcentaje }}%)
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
                        class="form-select rounded-3"
                        disabled>

                        <option value="">Sin proveedor</option>

                        @foreach($proveedores as $proveedor)
                        <option
                            value="{{ $proveedor->id }}"
 == $proveedor->id)>
                            {{ $proveedor->razon_social }}
                        </option>
                        @endforeach

                    </select>

                    <div class="invalid-feedback" id="error-proveedor_id"></div>
                </div>

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
                        disabled>

                    <div class="invalid-feedback" id="error-peso_gramos"></div>
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
                        min="0"
                        required
                        disabled>

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
                        min="0"
                        required
                        disabled>

                    <div class="invalid-feedback" id="error-cantidad_reposicion"></div>
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
                        disabled>

                        <label
                            class="form-check-label fw-semibold"
                            for="publicado_app">
                            Publicado en la aplicación
                        </label>

                    </div>

                    <div class="form-text">
                        @can('publicar', $producto)
                        El producto podrá estar disponible para los clientes.
                        @else
                        Tu rol no puede publicar ni despublicar productos.
                        @endcan
                    </div>

                    <div class="text-danger small mt-2" id="error-publicado_app"></div>
                </div>

                {{-- Imagen --}}
                <div class="col-12">
                    <label for="imagen" class="form-label fw-semibold">
                        Imagen del producto
                    </label>

                    {{--
                        Imagen actual.

                        La direccion la trae el JavaScript en `imagen_url`, el
                        mismo campo que arma el resource con UrlPublica, asi
                        que la pagina tampoco entrega esto.
                    --}}
                    <div class="mb-3">
                        <p class="form-text mb-2">Imagen actual:</p>

                        <img
                            id="imagenActual"
                            src=""
                            alt="Imagen actual del producto"
                            class="img-thumbnail rounded-3 d-none"
                            style="max-width: 220px; max-height: 220px; object-fit: contain;">

                        <div
                            id="sinImagen"
                            class="text-muted border rounded-3 p-4 text-center d-none"
                            style="max-width: 220px;">
                            Este producto no tiene una imagen cargada.
                        </div>
                    </div>

                    {{-- Nueva imagen --}}
                    <label for="imagen" class="form-text mb-2">
                        Seleccionar nueva imagen:
                    </label>

                    <input
                        type="file"
                        class="form-control rounded-3"
                        id="imagen"
                        name="imagen"
                        accept=".jpg,.jpeg,.png,.webp"
                        disabled>

                    <div class="invalid-feedback" id="error-imagen"></div>

                    {{-- Previsualización de la nueva imagen --}}
                    <div id="previewContainer" class="mt-3 d-none">
                        <p class="form-text mb-2">Nueva imagen:</p>

                        <img
                            id="imagenPreview"
                            src="#"
                            alt="Vista previa de la nueva imagen"
                            class="img-thumbnail rounded-3"
                            style="max-width: 220px; max-height: 220px; object-fit: contain;">
                    </div>
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
                form="formularioProducto"
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
                href="{{ route('gestion.items.index') }}"
                class="btn btn-outline-secondary px-4 fw-semibold shadow-sm">
                Volver al listado
            </a>

            {{-- Historial --}}
            <a
                href="{{ route('gestion.stock.show', $producto) }}"
                class="btn btn-outline-primary px-4 fw-semibold shadow-sm">
                Historial de stock
            </a>

            {{--
                Baja y reactivacion.

                Los dos botones se dibujan siempre y el JavaScript muestra el
                que corresponde cuando la API le dice si el producto esta
                activo: la pagina ya no sabe en que estado esta. Van contra
                PATCH /api/v1/gestion/productos/{id}/estado, que exige
                'producto.baja'.
            --}}
            @can('cambiarEstado', $producto)

            <button
                type="button"
                id="botonBaja"
                class="btn btn-danger px-4 fw-semibold shadow-sm d-none">
                Dar de baja
            </button>

            <button
                type="button"
                id="botonReactivar"
                class="btn btn-success px-4 fw-semibold shadow-sm d-none">
                Dar de alta
            </button>

            @endcan

        </div>

    </section>


</div>

@endsection

@push('scripts')
<script type="module">
    import { iniciar } from '{{ Vite::asset('resources/js/gestion/productos/formulario.js') }}';

    iniciar({
        id: {{ $producto->id }},
        urlListado: '{{ route('gestion.items.index') }}',

        // Publicar tiene permiso propio y endpoint propio: sin el permiso, el
        // interruptor queda deshabilitado y la pantalla no intenta el PATCH.
        puedePublicar: @json($puedePublicar),
    });
</script>
@endpush
