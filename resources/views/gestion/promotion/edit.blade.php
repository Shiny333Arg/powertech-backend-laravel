@extends('layouts.gestion')

@section('title', 'PowerTech')

@section('content')

@php
    // Catalogo de referencia para dibujar las filas ya elegidas al abrir.
    // $productos solo trae activos: si la promocion alcanza un producto que
    // despues se dio de baja, no esta aca. Ese caso se dibuja igual, marcado,
    // para que conserve su input oculto —si la fila desapareciera, el sync()
    // del update lo sacaria de la promocion sin que nadie lo decida.
    $catalogo = $productos->keyBy('id');

    $idsSeleccionados = collect(old('productos', $seleccionados))
        ->map(fn ($id) => (int) $id)
        ->all();

    $productosIniciales = collect($idsSeleccionados)
        ->map(function (int $id) use ($catalogo) {
            $producto = $catalogo->get($id);

            if (! $producto) {
                return [
                    'id'        => $id,
                    'codigo'    => '—',
                    'nombre'    => "Producto #{$id} (dado de baja)",
                    'precio'    => null,
                    'categoria' => null,
                    'inactivo'  => true,
                ];
            }

            return [
                'id'        => $producto->id,
                'codigo'    => $producto->codigo,
                'nombre'    => $producto->nombre,
                'precio'    => (float) $producto->precio,
                'categoria' => $producto->categoria?->nombre,
                'inactivo'  => false,
            ];
        })
        ->values();
@endphp

<div class="container py-4">


    {{-- Título --}}
    <section class="mb-4">
        <h1 class="h3 fw-bold mb-2">Editar promoción</h1>
        <p class="text-muted">
            Los campos están bloqueados por defecto. Presione "Editar" para modificar los datos.
        </p>
    </section>

    {{-- Formulario de edición --}}
    <section class="mb-4">

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

        <form
            id="editarPromocionForm"
            action="{{ route('gestion.promociones.update', $promocion) }}"
            method="POST"
            enctype="multipart/form-data"
            autocomplete="off"
            class="p-4 rounded-4 bg-light shadow-sm border">

            @csrf
            @method('PUT')

            <div class="row g-4">

                {{-- Nombre --}}
                <div class="col-md-8">
                    <label for="nombre" class="form-label fw-semibold">
                        Nombre de la promoción
                    </label>

                    <input
                        type="text"
                        class="form-control rounded-3 @error('nombre') is-invalid @enderror"
                        id="nombre"
                        name="nombre"
                        value="{{ old('nombre', $promocion->nombre) }}"
                        maxlength="120"
                        required
                        disabled>

                    @error('nombre')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                {{-- Tipo --}}
                <div class="col-md-4">
                    <label for="tipo" class="form-label fw-semibold">
                        Tipo de promoción
                    </label>

                    <select
                        id="tipo"
                        name="tipo"
                        class="form-select rounded-3 @error('tipo') is-invalid @enderror"
                        required
                        disabled>

                        <option value="">Seleccione un tipo</option>

                        @foreach($tipos as $valor => $etiqueta)
                        <option
                            value="{{ $valor }}"
                            @selected(old('tipo', $promocion->tipo->value) === $valor)>
                            {{ $etiqueta }}
                        </option>
                        @endforeach

                    </select>

                    @error('tipo')
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
                        rows="3"
                        maxlength="255"
                        style="resize: none"
                        disabled>{{ old('descripcion', $promocion->descripcion) }}</textarea>

                    @error('descripcion')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                {{-- Valor --}}
                <div class="col-md-4">
                    <label for="valor" class="form-label fw-semibold">
                        Valor
                    </label>

                    <input
                        type="number"
                        class="form-control rounded-3 @error('valor') is-invalid @enderror"
                        id="valor"
                        name="valor"
                        value="{{ old('valor', $promocion->valor) }}"
                        min="0.01"
                        max="9999999999.99"
                        step="0.01"
                        required
                        disabled>

                    @error('valor')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                {{-- Canal --}}
                <div class="col-md-4">
                    <label for="canal" class="form-label fw-semibold">
                        Canal de aplicación
                    </label>

                    <select
                        id="canal"
                        name="canal"
                        class="form-select rounded-3 @error('canal') is-invalid @enderror"
                        required
                        disabled>

                        <option value="">Seleccione un canal</option>
                        @foreach($canales as $valor => $etiqueta)
                        <option
                            value="{{ $valor }}"
                            @selected(old('canal', $promocion->canal->value) === $valor)>
                            {{ $etiqueta }}
                        </option>
                        @endforeach

                    </select>

                    @error('canal')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                {{-- Prioridad --}}
                <div class="col-md-4">
                    <label for="prioridad" class="form-label fw-semibold">
                        Prioridad
                    </label>

                    <input
                        type="number"
                        class="form-control rounded-3 @error('prioridad') is-invalid @enderror"
                        id="prioridad"
                        name="prioridad"
                        value="{{ old('prioridad', $promocion->prioridad) }}"
                        min="0"
                        max="9999"
                        required
                        disabled>

                    <div class="form-text">
                        Se utiliza para resolver conflictos entre promociones.
                    </div>

                    @error('prioridad')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                {{-- Vigencia desde --}}
                <div class="col-md-6">
                    <label for="vigencia_desde" class="form-label fw-semibold">
                        Vigencia desde
                    </label>

                    <input
                        type="datetime-local"
                        class="form-control rounded-3 @error('vigencia_desde') is-invalid @enderror"
                        id="vigencia_desde"
                        name="vigencia_desde"
                        value="{{ old('vigencia_desde', $promocion->vigencia_desde?->format('Y-m-d\TH:i')) }}"
                        required
                        disabled>

                    @error('vigencia_desde')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                {{-- Vigencia hasta --}}
                <div class="col-md-6">
                    <label for="vigencia_hasta" class="form-label fw-semibold">
                        Vigencia hasta
                    </label>

                    <input
                        type="datetime-local"
                        class="form-control rounded-3 @error('vigencia_hasta') is-invalid @enderror"
                        id="vigencia_hasta"
                        name="vigencia_hasta"
                        value="{{ old('vigencia_hasta', $promocion->vigencia_hasta?->format('Y-m-d\TH:i')) }}"
                        required
                        disabled>

                    @error('vigencia_hasta')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                {{-- Acumulable --}}
                <div class="col-md-6">
                    <div class="form-check form-switch mt-2">

                        <input
                            class="form-check-input"
                            type="checkbox"
                            role="switch"
                            id="acumulable"
                            name="acumulable"
                            value="1"
                            @checked(old('acumulable', $promocion->acumulable))
                        disabled>

                        <label
                            class="form-check-label fw-semibold"
                            for="acumulable">
                            Promoción acumulable
                        </label>

                    </div>

                    <div class="form-text">
                        Permite combinar esta promoción con otras promociones compatibles.
                    </div>

                    @error('acumulable')
                    <div class="text-danger small mt-2">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                {{-- Activo --}}
                <div class="col-md-6">
                    <div class="form-check form-switch mt-2">

                        <input
                            class="form-check-input"
                            type="checkbox"
                            role="switch"
                            id="activo"
                            name="activo"
                            value="1"
                            @checked(old('activo', $promocion->activo))
                        disabled>

                        <label
                            class="form-check-label fw-semibold"
                            for="activo">
                            Promoción activa
                        </label>

                    </div>

                    <div class="form-text">
                        Una promoción inactiva no se aplica a nuevas ventas.
                    </div>

                    @error('activo')
                    <div class="text-danger small mt-2">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                {{-- Productos alcanzados --}}
                <div class="col-12">

                    <label for="buscadorProducto" class="form-label fw-semibold">
                        Productos alcanzados
                    </label>

                    <div class="form-text mb-3">
                        Buscá por código, nombre o marca para agregar productos a la promoción.
                    </div>

                    @error('productos')
                    <div class="text-danger small mb-2">
                        {{ $message }}
                    </div>
                    @enderror

                    @error('productos.*')
                    <div class="text-danger small mb-2">
                        {{ $message }}
                    </div>
                    @enderror

                    {{-- Buscador. data-no-bloquear: lo habilita y deshabilita el
                         modulo de productos, no el bloqueo generico del form. --}}
                    <div class="position-relative mb-3">

                        <input
                            type="text"
                            class="form-control rounded-3"
                            id="buscadorProducto"
                            placeholder="Ej: RTX 3070"
                            maxlength="150"
                            data-no-bloquear
                            disabled>

                        <div
                            class="list-group position-absolute w-100 shadow d-none"
                            id="resultadosProducto"
                            style="z-index: 1050; max-height: 18rem; overflow-y: auto;">
                        </div>

                    </div>

                    {{-- Seleccionados --}}
                    <div class="table-responsive">

                        <table class="table table-bordered align-middle text-center mb-0" id="tablaProductos">

                            <thead class="table-dark">

                                <tr>
                                    <th class="text-nowrap">Producto</th>
                                    <th class="text-nowrap" style="width: 12rem;">Categoría</th>
                                    <th class="text-nowrap" style="width: 10rem;">Precio</th>
                                    <th class="text-nowrap" style="width: 5rem;">Quitar</th>
                                </tr>

                            </thead>

                            <tbody id="cuerpoProductos">
                                {{-- Lo dibuja el script del pie --}}
                            </tbody>

                        </table>

                    </div>

                    <div class="form-text mt-2">
                        Productos seleccionados: <span class="fw-semibold" id="totalProductos">0</span>
                    </div>

                </div>

                {{-- Banner actual --}}
                <div class="col-md-6">

                    <label class="form-label fw-semibold">
                        Banner actual
                    </label>

                    @if($promocion->banner)

                    <div class="border rounded-3 bg-white p-3">

                        <img
                            src="{{ asset('storage/' . $promocion->banner) }}"
                            alt="Banner de {{ $promocion->nombre }}"
                            class="img-fluid rounded-3"
                            style="max-height: 220px; object-fit: contain;">

                    </div>

                    @else

                    <div class="border rounded-3 bg-white p-3 text-muted">
                        La promoción no tiene un banner cargado.
                    </div>

                    @endif

                </div>

                {{-- Cambiar banner --}}
                <div class="col-md-6">

                    <label for="banner" class="form-label fw-semibold">
                        Cambiar banner
                    </label>

                    <input
                        type="file"
                        class="form-control rounded-3 @error('banner') is-invalid @enderror"
                        id="banner"
                        name="banner"
                        accept=".jpg,.jpeg,.png,.webp"
                        disabled>

                    <div class="form-text">
                        Imagen JPG, JPEG, PNG o WEBP. Tamaño máximo: 2 MB.
                    </div>

                    @error('banner')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror

                </div>

                {{-- Quitar banner --}}
                @if($promocion->banner)

                <div class="col-12">

                    <div class="form-check">

                        <input
                            class="form-check-input"
                            type="checkbox"
                            id="quitar_banner"
                            name="quitar_banner"
                            value="1"
                            disabled>

                        <label
                            class="form-check-label fw-semibold"
                            for="quitar_banner">
                            Quitar banner actual
                        </label>

                    </div>

                    <div class="form-text">
                        Si selecciona esta opción, el banner actual será eliminado.
                        Si selecciona un nuevo banner, se utilizará el nuevo archivo.
                    </div>

                </div>

                @endif

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
                form="editarPromocionForm"
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
                href="{{ route('gestion.promociones.index') }}"
                class="btn btn-outline-secondary px-4 fw-semibold shadow-sm">

                Volver al listado
            </a>

            {{-- Baja / Alta lógica --}}
            @if($promocion->activo)

            <form
                action="{{ route('gestion.promociones.darDeBaja', $promocion) }}"
                method="POST"
                class="d-inline">

                @csrf
                @method('PATCH')

                <button
                    type="submit"
                    class="btn btn-danger px-4 fw-semibold shadow-sm"
                    onclick="return confirm('¿Está seguro de que desea dar de baja esta promoción?')">

                    Dar de baja
                </button>

            </form>

            @else

            <form
                action="{{ route('gestion.promociones.reactivar', $promocion) }}"
                method="POST"
                class="d-inline">

                @csrf
                @method('PATCH')

                <button
                    type="submit"
                    class="btn btn-success px-4 fw-semibold shadow-sm"
                    onclick="return confirm('¿Está seguro de que desea reactivar esta promoción?')">

                    Reactivar
                </button>

            </form>

            @endif

        </div>

    </section>


</div>

{{-- Datos iniciales para el script, sin ensuciar atributos HTML --}}
<script type="application/json" id="datosProductos">
    @json($productosIniciales)
</script>

@endsection

@push('scripts')

<script>
    document.addEventListener('DOMContentLoaded', function() {

        // ==========================================================
        // PRODUCTOS ALCANZADOS
        // ==========================================================
        // Expone bloquear/desbloquear/restaurar para que el bloqueo
        // generico del formulario lo maneje como a un campo mas.

        var productos = (function() {
            'use strict';

            var urlProductos = @json(route('gestion.buscar.productos'));

            var iniciales = JSON.parse(document.getElementById('datosProductos').textContent) || [];
            var elegidos = iniciales.slice();
            var bloqueado = true;

            var cuerpo = document.getElementById('cuerpoProductos');
            var buscador = document.getElementById('buscadorProducto');
            var resultados = document.getElementById('resultadosProducto');
            var total = document.getElementById('totalProductos');

            function pesos(n) {
                if (n === null || n === undefined) return '—';

                return '$ ' + Number(n).toLocaleString('es-AR', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            function debounce(fn, ms) {
                var t;
                return function() {
                    var args = arguments;
                    clearTimeout(t);
                    t = setTimeout(function() {
                        fn.apply(null, args);
                    }, ms);
                };
            }

            function escapar(texto) {
                var div = document.createElement('div');
                div.textContent = texto == null ? '' : texto;
                return div.innerHTML;
            }

            function ocultar(caja) {
                caja.classList.add('d-none');
                caja.innerHTML = '';
            }

            function dibujar() {
                cuerpo.innerHTML = '';

                if (!elegidos.length) {
                    cuerpo.innerHTML =
                        '<tr><td colspan="4" class="text-center py-4 text-muted">' +
                        'La promoción no tiene productos. Buscá uno arriba para agregarlo.' +
                        '</td></tr>';
                }

                elegidos.forEach(function(p, i) {
                    var fila = document.createElement('tr');

                    fila.innerHTML =
                        '<td class="text-start">' +
                        '<div class="fw-semibold">' + escapar(p.nombre) + '</div>' +
                        '<div class="text-muted small">Código: ' + escapar(p.codigo) + '</div>' +
                        (p.inactivo ?
                            '<div class="text-danger small">Este producto está dado de baja. ' +
                            'Si lo quitás, sale de la promoción.</div>' :
                            '') +
                        '<input type="hidden" name="productos[]" value="' + p.id + '">' +
                        '</td>' +
                        '<td class="text-muted small">' + (p.categoria ? escapar(p.categoria) : '—') + '</td>' +
                        '<td class="text-nowrap">' + pesos(p.precio) + '</td>' +
                        '<td>' +
                        '<button type="button" class="btn btn-sm btn-outline-danger" ' +
                        'data-quitar="' + i + '" title="Quitar"' +
                        (bloqueado ? ' disabled' : '') + '>&times;</button>' +
                        '</td>';

                    cuerpo.appendChild(fila);
                });

                total.textContent = elegidos.length;
            }

            function agregar(p) {
                var yaEsta = elegidos.some(function(e) {
                    return e.id === p.id;
                });

                if (!yaEsta) {
                    elegidos.push({
                        id: p.id,
                        codigo: p.codigo,
                        nombre: p.nombre,
                        precio: p.precio,
                        categoria: p.categoria || null,
                        inactivo: false
                    });
                }

                buscador.value = '';
                ocultar(resultados);
                dibujar();
            }

            cuerpo.addEventListener('click', function(e) {
                if (bloqueado) return;

                var boton = e.target.closest('[data-quitar]');
                if (!boton) return;

                elegidos.splice(Number(boton.dataset.quitar), 1);
                dibujar();
            });

            function buscar(texto, alRecibir) {
                if (texto.trim().length < 2) return alRecibir([]);

                fetch(urlProductos + '?q=' + encodeURIComponent(texto), {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin'
                    })
                    .then(function(r) {
                        if (!r.ok) {
                            return r.text().then(function(cuerpo) {
                                console.error('Búsqueda de productos → HTTP ' + r.status, cuerpo.slice(0, 800));
                                return [];
                            });
                        }

                        var tipo = r.headers.get('content-type') || '';

                        if (tipo.indexOf('application/json') === -1) {
                            return r.text().then(function(cuerpo) {
                                console.error('Búsqueda de productos → no devolvió JSON (¿redirect de middleware?)', cuerpo.slice(0, 800));
                                return [];
                            });
                        }

                        return r.json();
                    })
                    .then(alRecibir)
                    .catch(function(e) {
                        console.error('Búsqueda de productos falló', e);
                        alRecibir([]);
                    });
            }

            buscador.addEventListener('input', debounce(function() {
                if (bloqueado) return;

                buscar(buscador.value, function(items) {
                    if (!items.length) return ocultar(resultados);

                    resultados.innerHTML = '';

                    items.forEach(function(p) {
                        var yaEsta = elegidos.some(function(e) {
                            return e.id === p.id;
                        });

                        var boton = document.createElement('button');
                        boton.type = 'button';
                        boton.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-3';
                        boton.disabled = yaEsta;

                        boton.innerHTML =
                            '<span class="text-start">' +
                            '<span class="fw-semibold">' + escapar(p.nombre) + '</span>' +
                            '<span class="text-muted small ms-2">' + escapar(p.codigo) + '</span>' +
                            '</span>' +
                            '<span class="text-nowrap">' +
                            (yaEsta ? '<span class="badge text-bg-secondary me-2">ya agregado</span>' : '') +
                            pesos(p.precio) +
                            '</span>';

                        boton.addEventListener('click', function() {
                            agregar(p);
                        });

                        resultados.appendChild(boton);
                    });

                    resultados.classList.remove('d-none');
                });
            }, 300));

            document.addEventListener('click', function(e) {
                if (!e.target.closest('#resultadosProducto') && e.target !== buscador) {
                    ocultar(resultados);
                }
            });

            // El buscador no es un campo del form: Enter no debe enviarlo.
            buscador.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') e.preventDefault();
            });

            dibujar();

            return {
                bloquear: function() {
                    bloqueado = true;
                    buscador.disabled = true;
                    buscador.value = '';
                    ocultar(resultados);
                    dibujar();
                },
                desbloquear: function() {
                    bloqueado = false;
                    buscador.disabled = false;
                    dibujar();
                },
                restaurar: function() {
                    elegidos = iniciales.slice();
                    dibujar();
                }
            };
        })();


        // ==========================================================
        // BLOQUEO DEL FORMULARIO
        // ==========================================================

        const form = document.getElementById('editarPromocionForm');

        const btnEditar = document.getElementById('btnEditar');
        const btnActualizar = document.getElementById('btnActualizar');
        const btnCancelar = document.getElementById('btnCancelar');

        const campos = form.querySelectorAll(
            'input:not([type="hidden"]):not([data-no-bloquear]), select, textarea'
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

            productos.desbloquear();

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

            productos.restaurar();
            productos.bloquear();

            btnEditar.disabled = false;
            btnActualizar.disabled = true;
            btnCancelar.disabled = true;

        }

        btnEditar.addEventListener('click', activarEdicion);

        btnCancelar.addEventListener('click', cancelarEdicion);

        // Si la validación rechazó el envío, los datos que se ven son los que
        // el operador tipeó. Dejar el formulario bloqueado lo obligaría a
        // apretar "Editar" para corregir algo que ya estaba corrigiendo.
        @if ($errors->any())
        activarEdicion();
        @endif

    });
</script>

@endpush