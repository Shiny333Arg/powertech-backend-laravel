@extends('layouts.gestion')

@section('title', 'PowerTech')

@section('content')

@php
    // Ids ya elegidos: los de la edición, o los que vuelven de un intento fallido.
    $idsSeleccionados = collect(old('productos', $seleccionados ?? []))
        ->map(fn ($id) => (int) $id)
        ->all();

    // Se hidratan contra $productos (los activos que ya manda el controlador)
    // solo para dibujar la tabla al abrir. El resto de las filas las agrega
    // el buscador, igual que en la pantalla de venta.
    $productosIniciales = $productos
        ->whereIn('id', $idsSeleccionados)
        ->map(fn ($p) => [
            'id'        => $p->id,
            'codigo'    => $p->codigo,
            'nombre'    => $p->nombre,
            'precio'    => (float) $p->precio,
            'categoria' => $p->categoria?->nombre,
        ])
        ->values();
@endphp

<div class="container py-4">


    {{-- Título --}}
    <section class="mb-4">
        <h1 class="h3 fw-bold mb-2">Alta de nueva promoción</h1>
        <p class="text-muted">
            Complete los datos para registrar una nueva promoción en el sistema.
        </p>
    </section>

    {{-- Formulario --}}
    <section class="mb-4">

        <form
            action="{{ route('gestion.promociones.store') }}"
            method="POST"
            enctype="multipart/form-data"
            autocomplete="off"
            class="p-4 rounded-4 bg-light shadow-sm border"
            id="formPromocion">

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
                        placeholder="Ej: 20% de descuento en periféricos"
                        required>

                    @error('nombre')
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
                        placeholder="Ej: 10"
                        required>

                    <div class="form-text">
                        Define qué promoción tiene prioridad cuando se superponen.
                    </div>

                    @error('prioridad')
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
                        maxlength="255"
                        placeholder="Breve descripción de la promoción."
                        style="resize: none">{{ old('descripcion', $promocion->descripcion) }}</textarea>

                    @error('descripcion')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <hr class="my-4">

                {{-- Tipo --}}
                <div class="col-md-6">
                    <label for="tipo" class="form-label fw-semibold">
                        Tipo de promoción
                    </label>

                    <select
                        id="tipo"
                        name="tipo"
                        class="form-select rounded-3 @error('tipo') is-invalid @enderror"
                        required>

                        <option value="" hidden>
                            Seleccione un tipo
                        </option>

                        @foreach($tipos as $valor => $nombre)
                        <option
                            value="{{ $valor }}"
                            @selected(old('tipo', $promocion->tipo?->value ?? $promocion->tipo) == $valor)>
                            {{ $nombre }}
                        </option>
                        @endforeach

                    </select>

                    <div class="form-text">
                        Puede ser porcentaje, monto fijo o precio especial.
                    </div>

                    @error('tipo')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                {{-- Valor --}}
                <div class="col-md-6">
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
                        placeholder="Ej: 20"
                        required>

                    <div class="form-text">
                        Si el tipo es porcentaje, el valor máximo permitido es 100.
                    </div>

                    @error('valor')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                {{-- Canal --}}
                <div class="col-md-6">
                    <label for="canal" class="form-label fw-semibold">
                        Canal de aplicación
                    </label>

                    <select
                        id="canal"
                        name="canal"
                        class="form-select rounded-3 @error('canal') is-invalid @enderror"
                        required>

                        <option value="">Seleccione un canal</option>

                        @foreach($canales as $valor => $nombre)
                        <option
                            value="{{ $valor }}"
                            @selected(old('canal', $promocion->canal?->value ?? $promocion->canal) == $valor)>
                            {{ $nombre }}
                        </option>
                        @endforeach

                    </select>

                    <div class="form-text">
                        Indica dónde puede aplicarse la promoción.
                    </div>

                    @error('canal')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                {{-- Banner --}}
                <div class="col-md-6">
                    <label for="banner" class="form-label fw-semibold">
                        Banner de la promoción
                    </label>

                    <input
                        type="file"
                        class="form-control rounded-3 @error('banner') is-invalid @enderror"
                        id="banner"
                        name="banner"
                        accept=".jpg,.jpeg,.png,.webp">

                    <div class="form-text">
                        Formatos permitidos: JPG, JPEG, PNG o WEBP. Máximo 2 MB.
                    </div>

                    @error('banner')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <hr class="my-4">

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
                        required>

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
                        required>

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
                        >

                        <label
                            class="form-check-label fw-semibold"
                            for="acumulable">
                            Promoción acumulable
                        </label>

                    </div>

                    <div class="form-text">
                        Permite combinar esta promoción con otras promociones aplicables.
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
                        >

                        <label
                            class="form-check-label fw-semibold"
                            for="activo">
                            Promoción activa
                        </label>

                    </div>

                    <div class="form-text">
                        Si está activa, podrá aplicarse cuando se encuentre dentro de su vigencia.
                    </div>

                    @error('activo')
                    <div class="text-danger small mt-2">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <hr class="my-4">

                {{-- Productos alcanzados --}}
                <div class="col-12">

                    <label for="buscadorProducto" class="form-label fw-semibold">
                        Productos alcanzados
                    </label>

                    <div class="form-text mb-3">
                        Buscá por código, nombre o marca y agregá al menos un producto
                        al que se aplicará la promoción.
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

                    @if($productos->isEmpty())

                    <div class="alert alert-warning mb-0">
                        No hay productos activos disponibles para asociar a la promoción.
                    </div>

                    @else

                    {{-- Buscador --}}
                    <div class="position-relative mb-3">

                        <input
                            type="text"
                            class="form-control rounded-3"
                            id="buscadorProducto"
                            placeholder="Ej: RTX 3070"
                            maxlength="150">

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

                    @endif

                </div>

            </div>

            {{-- Botones --}}
            <div class="mt-5 d-flex flex-wrap gap-3">

                <button
                    type="submit"
                    class="btn btn-success px-4 fw-semibold shadow-sm">
                    Guardar
                </button>

                <a
                    href="{{ route('gestion.promociones.index') }}"
                    class="btn btn-outline-secondary px-4 fw-semibold shadow-sm">
                    Volver al listado
                </a>

            </div>

        </form>

    </section>


</div>


@unless($productos->isEmpty())

{{-- Datos iniciales para el script, sin ensuciar atributos HTML --}}
<script type="application/json" id="datosProductos">
    @json($productosIniciales)
</script>

<script>
    (function() {
        'use strict';

        var urlProductos = @json(route('gestion.buscar.productos'));

        var elegidos = JSON.parse(document.getElementById('datosProductos').textContent) || [];

        var cuerpo = document.getElementById('cuerpoProductos');
        var buscador = document.getElementById('buscadorProducto');
        var resultados = document.getElementById('resultadosProducto');
        var total = document.getElementById('totalProductos');

        // ----------------------------------------------------------
        // Utilidades
        // ----------------------------------------------------------

        function pesos(n) {
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

        // ----------------------------------------------------------
        // Tabla de productos alcanzados
        // ----------------------------------------------------------

        function dibujar() {
            cuerpo.innerHTML = '';

            if (!elegidos.length) {
                cuerpo.innerHTML =
                    '<tr><td colspan="4" class="text-center py-4 text-muted">' +
                    'Todavía no agregaste productos. Buscá uno arriba para empezar.' +
                    '</td></tr>';
            }

            elegidos.forEach(function(p, i) {
                var fila = document.createElement('tr');

                fila.innerHTML =
                    '<td class="text-start">' +
                    '<div class="fw-semibold">' + escapar(p.nombre) + '</div>' +
                    '<div class="text-muted small">Código: ' + escapar(p.codigo) + '</div>' +
                    '<input type="hidden" name="productos[]" value="' + p.id + '">' +
                    '</td>' +
                    '<td class="text-muted small">' + (p.categoria ? escapar(p.categoria) : '—') + '</td>' +
                    '<td class="text-nowrap">' + pesos(p.precio) + '</td>' +
                    '<td>' +
                    '<button type="button" class="btn btn-sm btn-outline-danger" ' +
                    'data-quitar="' + i + '" title="Quitar">&times;</button>' +
                    '</td>';

                cuerpo.appendChild(fila);
            });

            total.textContent = elegidos.length;
        }

        function agregarProducto(p) {
            var yaEsta = elegidos.some(function(e) {
                return e.id === p.id;
            });

            if (!yaEsta) {
                elegidos.push({
                    id: p.id,
                    codigo: p.codigo,
                    nombre: p.nombre,
                    precio: p.precio,
                    categoria: p.categoria || null
                });
            }

            buscador.value = '';
            ocultar(resultados);
            dibujar();
        }

        cuerpo.addEventListener('click', function(e) {
            var boton = e.target.closest('[data-quitar]');
            if (!boton) return;

            elegidos.splice(Number(boton.dataset.quitar), 1);
            dibujar();
        });

        // ----------------------------------------------------------
        // Buscador
        // ----------------------------------------------------------

        function buscar(url, texto, alRecibir) {
            if (texto.trim().length < 2) return alRecibir([]);

            fetch(url + '?q=' + encodeURIComponent(texto), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                })
                .then(function(r) {
                    if (!r.ok) {
                        return r.text().then(function(cuerpo) {
                            console.error('Búsqueda ' + url + ' → HTTP ' + r.status, cuerpo.slice(0, 800));
                            return [];
                        });
                    }

                    var tipo = r.headers.get('content-type') || '';

                    if (tipo.indexOf('application/json') === -1) {
                        return r.text().then(function(cuerpo) {
                            console.error('Búsqueda ' + url + ' → no devolvió JSON (¿redirect de middleware?)', cuerpo.slice(0, 800));
                            return [];
                        });
                    }

                    return r.json();
                })
                .then(alRecibir)
                .catch(function(e) {
                    console.error('Búsqueda ' + url + ' falló', e);
                    alRecibir([]);
                });
        }

        buscador.addEventListener('input', debounce(function() {
            buscar(urlProductos, buscador.value, function(items) {
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
                        agregarProducto(p);
                    });

                    resultados.appendChild(boton);
                });

                resultados.classList.remove('d-none');
            });
        }, 300));

        document.addEventListener('click', function(e) {
            if (!e.target.closest('#resultadosProducto') && e.target !== buscador) ocultar(resultados);
        });

        // El buscador no es parte del form: Enter no debe enviarlo.
        buscador.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') e.preventDefault();
        });

        // ----------------------------------------------------------
        // Arranque
        // ----------------------------------------------------------

        dibujar();
    })();
</script>

@endunless

@endsection