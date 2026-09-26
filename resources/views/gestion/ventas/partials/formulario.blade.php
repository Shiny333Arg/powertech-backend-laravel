{{--
    Editor de venta. Lo comparten el alta y la edición.

    Variables que recibe:
      $accion  url del form
      $metodo  'POST' | 'PUT'
      $venta   la venta en edición, o null en el alta
      $modosEntrega

    Los totales que se ven acá son ORIENTATIVOS: el precio y las promociones
    los vuelve a tomar el servidor del catálogo al guardar. Un precio que
    llega del formulario es un precio que el operador puede editar.
--}}

@php
$lineasIniciales = collect(old('lineas', $venta?->lineas?->map(fn ($l) => [
'producto_id' => $l->producto_id,
'codigo' => $l->producto?->codigo,
'nombre' => $l->descripcion,
'precio' => (float) $l->precio_unitario,
'disponible' => (int) ($l->producto?->stock_disponible ?? 0),
'cantidad' => (int) $l->cantidad,
])->all() ?? []))->values();

$clienteInicial = $venta?->cliente ? [
'id' => $venta->cliente->id,
'razon_social' => $venta->cliente->razon_social,
'nro_doc' => $venta->cliente->nro_doc,
] : null;

$modoInicial = old('modo_entrega', $venta?->modo_entrega?->value ?? 'retiro');
@endphp


<form
    action="{{ $accion }}"
    method="POST"
    autocomplete="off"
    id="formVenta">

    @csrf

    @if ($metodo === 'PUT')
    @method('PUT')
    @endif


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

        <!-- Columna izquierda: detalle -->
        <div class="col-12 col-lg-8">

            <!-- Buscador y líneas -->
            <section class="p-4 rounded-4 bg-light shadow-sm border mb-4">

                <h2 class="h5 fw-bold mb-1">Productos</h2>

                <p class="text-muted small">
                    Buscá por código, nombre o marca. Se puede vender sin stock:
                    la preventa queda comprometida y se entrega cuando llegue la reposición.
                </p>

                <div class="position-relative mb-3">

                    <label for="buscadorProducto" class="form-label fw-semibold">
                        Agregar producto
                    </label>

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


                <div class="table-responsive">

                    <table class="table table-bordered align-middle text-center mb-0" id="tablaLineas">

                        <thead class="table-dark">

                            <tr>
                                <th class="text-nowrap">Producto</th>
                                <th class="text-nowrap" style="width: 9rem;">Cantidad</th>
                                <th class="text-nowrap" style="width: 9rem;">Precio</th>
                                <th class="text-nowrap" style="width: 9rem;">Importe</th>
                                <th class="text-nowrap" style="width: 4rem;">Quitar</th>
                            </tr>

                        </thead>

                        <tbody id="cuerpoLineas">
                            {{-- Lo dibuja el script del pie --}}
                        </tbody>

                    </table>

                </div>

                @error('lineas')
                <div class="text-danger small mt-2">
                    {{ $message }}
                </div>
                @enderror

            </section>


            <!-- Observaciones -->
            <section class="p-4 rounded-4 bg-light shadow-sm border">

                <label for="observaciones" class="form-label fw-semibold">
                    Observaciones
                </label>

                <textarea
                    class="form-control rounded-3 @error('observaciones') is-invalid @enderror"
                    id="observaciones"
                    name="observaciones"
                    rows="3"
                    maxlength="2000"
                    style="resize: none"
                    placeholder="Notas internas sobre esta operación.">{{ old('observaciones', $venta?->observaciones) }}</textarea>

                @error('observaciones')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
                @enderror

            </section>

        </div>


        <!-- Columna derecha: datos de la operación -->
        <div class="col-12 col-lg-4">

            <!-- Cliente -->
            <section class="p-4 rounded-4 bg-light shadow-sm border mb-4">

                <h2 class="h6 fw-bold mb-1">Cliente</h2>

                <p class="text-muted small">
                    Sin cliente identificado se factura B a consumidor final.
                </p>

                <input type="hidden" name="cliente_id" id="clienteId" value="{{ old('cliente_id', $clienteInicial['id'] ?? '') }}">

                <!-- Cliente elegido -->
                <div class="border rounded-3 bg-white p-3 d-none" id="clienteElegido">

                    <div class="d-flex justify-content-between align-items-start gap-2">

                        <div>
                            <div class="fw-semibold" id="clienteNombre"></div>
                            <div class="text-muted small" id="clienteDoc"></div>
                        </div>

                        <button
                            type="button"
                            class="btn btn-sm btn-outline-secondary"
                            id="btnQuitarCliente">
                            Quitar
                        </button>

                    </div>

                </div>


                <!-- Buscador de cliente -->
                <div class="position-relative" id="buscadorClienteBloque">

                    <input
                        type="text"
                        class="form-control rounded-3"
                        id="buscadorCliente"
                        placeholder="Nombre o documento"
                        maxlength="150">

                    <div
                        class="list-group position-absolute w-100 shadow d-none"
                        id="resultadosCliente"
                        style="z-index: 1050; max-height: 15rem; overflow-y: auto;">
                    </div>

                    <div class="form-text">
                        ¿No está cargado?
                        <a href="{{ route('gestion.clientes.create') }}" target="_blank">Darlo de alta</a>.
                    </div>

                </div>

                @error('cliente_id')
                <div class="text-danger small mt-2">
                    {{ $message }}
                </div>
                @enderror

            </section>


            <!-- Entrega -->
            <section class="p-4 rounded-4 bg-light shadow-sm border mb-4">

                <h2 class="h6 fw-bold mb-3">Entrega</h2>

                <div class="mb-3">

                    <label for="modo_entrega" class="form-label fw-semibold">
                        Modo de entrega
                    </label>

                    <select
                        id="modo_entrega"
                        name="modo_entrega"
                        class="form-select rounded-3 @error('modo_entrega') is-invalid @enderror"
                        required>

                        @foreach ($modosEntrega as $opcion)

                        <option
                            value="{{ $opcion->value }}"
                            @selected($modoInicial===$opcion->value)>

                            {{ $opcion->value === 'retiro' ? 'Retiro en el local' : 'Envío a domicilio' }}

                        </option>

                        @endforeach

                    </select>

                    @error('modo_entrega')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror

                </div>


                <div id="bloqueEnvio" class="d-none">

                    <label for="costo_envio" class="form-label fw-semibold">
                        Costo de envío
                    </label>

                    <input
                        type="number"
                        class="form-control rounded-3 @error('costo_envio') is-invalid @enderror"
                        id="costo_envio"
                        name="costo_envio"
                        value="{{ old('costo_envio', $venta?->costo_envio) }}"
                        min="0"
                        step="0.01"
                        placeholder="Ej: 8500">

                    <div class="form-text">
                        Un envío necesita un cliente identificado.
                    </div>

                    @error('costo_envio')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror

                </div>

            </section>


            <!-- Cómo se guarda -->
            <section class="p-4 rounded-4 bg-light shadow-sm border mb-4">

                <h2 class="h6 fw-bold mb-3">Cómo se guarda</h2>

                @if ($metodo === 'POST')

                <div class="form-check form-switch mb-3">

                    <input
                        class="form-check-input"
                        type="checkbox"
                        role="switch"
                        id="confirmar"
                        name="confirmar"
                        value="1"
                        @checked(old('confirmar'))>

                    <label class="form-check-label fw-semibold" for="confirmar">
                        Confirmar la venta ahora
                    </label>

                    <div class="form-text">
                        Compromete el stock y la habilita para cobro.
                        Sin tildar, se guarda como presupuesto.
                    </div>

                </div>

                @endif


                <div id="bloqueValidez">

                    <label for="validez_hasta" class="form-label fw-semibold">
                        Validez del presupuesto
                    </label>

                    <input
                        type="date"
                        class="form-control rounded-3 @error('validez_hasta') is-invalid @enderror"
                        id="validez_hasta"
                        name="validez_hasta"
                        value="{{ old('validez_hasta', $venta?->validez_hasta?->format('Y-m-d') ?? now()->addDays(7)->format('Y-m-d')) }}"
                        min="{{ now()->format('Y-m-d') }}">

                    <div class="form-text">
                        Pasada esa fecha el presupuesto vence y caduca el precio ofertado.
                    </div>

                    @error('validez_hasta')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror

                </div>

            </section>


            <!-- Totales -->
            <section class="p-4 rounded-4 bg-white shadow-sm border">

                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted">Ítems</span>
                    <span class="fw-semibold" id="totalItems">0</span>
                </div>

                <hr>

                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted">Subtotal estimado</span>
                    <span class="h4 fw-bold mb-0" id="totalSubtotal">$ 0,00</span>
                </div>

                <div class="form-text">
                    Las promociones vigentes se aplican al guardar y pueden bajar este importe.
                </div>

                <div class="alert alert-danger small mt-3 d-none" id="avisoPreventa">
                    Hay líneas sin stock suficiente. La venta se puede confirmar igual:
                    las unidades quedan comprometidas y se entregan cuando llegue la reposición.
                </div>

                <div class="mt-4 d-flex flex-wrap gap-2">

                    <button
                        type="submit"
                        class="btn btn-success px-4 fw-semibold shadow-sm"
                        id="btnGuardar"
                        disabled>
                        {{ $metodo === 'PUT' ? 'Guardar cambios' : 'Guardar' }}
                    </button>

                    <a
                        href="{{ $venta ? route('gestion.ventas.show', $venta) : route('gestion.ventas.index') }}"
                        class="btn btn-outline-secondary px-4 fw-semibold shadow-sm">
                        Volver
                    </a>

                </div>

            </section>

        </div>

    </div>

</form>


{{-- Datos iniciales para el script, sin ensuciar atributos HTML --}}
<script type="application/json" id="datosLineas">
    @json($lineasIniciales)
</script>
<script type="application/json" id="datosCliente">
    @json($clienteInicial)
</script>


<script>
    (function() {
        'use strict';

        var urlProductos = @json(route('gestion.buscar.productos'));
        var urlClientes = @json(route('gestion.buscar.clientes'));

        var lineas = JSON.parse(document.getElementById('datosLineas').textContent) || [];
        var cliente = JSON.parse(document.getElementById('datosCliente').textContent);

        var cuerpo = document.getElementById('cuerpoLineas');
        var buscador = document.getElementById('buscadorProducto');
        var resultados = document.getElementById('resultadosProducto');
        var totalItems = document.getElementById('totalItems');
        var totalSubtotal = document.getElementById('totalSubtotal');
        var avisoPreventa = document.getElementById('avisoPreventa');
        var btnGuardar = document.getElementById('btnGuardar');

        var modoEntrega = document.getElementById('modo_entrega');
        var bloqueEnvio = document.getElementById('bloqueEnvio');
        var confirmar = document.getElementById('confirmar');
        var bloqueValidez = document.getElementById('bloqueValidez');
        var campoValidez = document.getElementById('validez_hasta');

        var buscadorCliente = document.getElementById('buscadorCliente');
        var resultadosCliente = document.getElementById('resultadosCliente');
        var bloqueBuscador = document.getElementById('buscadorClienteBloque');
        var clienteElegido = document.getElementById('clienteElegido');
        var clienteNombre = document.getElementById('clienteNombre');
        var clienteDoc = document.getElementById('clienteDoc');
        var clienteId = document.getElementById('clienteId');
        var btnQuitarCliente = document.getElementById('btnQuitarCliente');

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

        // ----------------------------------------------------------
        // Líneas de la venta
        // ----------------------------------------------------------

        function dibujarLineas() {
            cuerpo.innerHTML = '';

            if (!lineas.length) {
                cuerpo.innerHTML =
                    '<tr><td colspan="5" class="text-center py-4 text-muted">' +
                    'Todavía no cargaste productos. Buscá uno arriba para empezar.' +
                    '</td></tr>';
            }

            lineas.forEach(function(linea, i) {
                var preventa = linea.cantidad > linea.disponible;

                var fila = document.createElement('tr');

                fila.innerHTML =
                    '<td class="text-start">' +
                    '<div class="fw-semibold">' + escapar(linea.nombre) + '</div>' +
                    '<div class="text-muted small">' + escapar(linea.codigo) + '</div>' +
                    (preventa ?
                        '<div class="text-danger small">Preventa: hay ' + linea.disponible +
                        ' disponibles.</div>' :
                        '') +
                    '<input type="hidden" name="lineas[' + i + '][producto_id]" value="' + linea.producto_id + '">' +
                    '</td>' +
                    '<td>' +
                    '<input type="number" class="form-control form-control-sm text-center" ' +
                    'name="lineas[' + i + '][cantidad]" value="' + linea.cantidad + '" ' +
                    'min="1" max="9999" data-indice="' + i + '">' +
                    '</td>' +
                    '<td class="text-nowrap">' + pesos(linea.precio) + '</td>' +
                    '<td class="text-nowrap fw-semibold">' + pesos(linea.precio * linea.cantidad) + '</td>' +
                    '<td>' +
                    '<button type="button" class="btn btn-sm btn-outline-danger" ' +
                    'data-quitar="' + i + '" title="Quitar">&times;</button>' +
                    '</td>';

                cuerpo.appendChild(fila);
            });

            actualizarTotales();
        }

        function actualizarTotales() {
            var items = lineas.reduce(function(a, l) {
                return a + Number(l.cantidad);
            }, 0);
            var subtotal = lineas.reduce(function(a, l) {
                return a + l.precio * l.cantidad;
            }, 0);
            var hayPreventa = lineas.some(function(l) {
                return l.cantidad > l.disponible;
            });

            totalItems.textContent = items;
            totalSubtotal.textContent = pesos(subtotal);
            avisoPreventa.classList.toggle('d-none', !hayPreventa);
            btnGuardar.disabled = lineas.length === 0;
        }

        function agregarProducto(p) {
            var existente = lineas.filter(function(l) {
                return l.producto_id === p.id;
            })[0];

            if (existente) {
                existente.cantidad++;
            } else {
                lineas.push({
                    producto_id: p.id,
                    codigo: p.codigo,
                    nombre: p.nombre,
                    precio: p.precio,
                    disponible: p.disponible,
                    cantidad: 1
                });
            }

            buscador.value = '';
            ocultar(resultados);
            dibujarLineas();
        }

        cuerpo.addEventListener('click', function(e) {
            var boton = e.target.closest('[data-quitar]');
            if (!boton) return;

            lineas.splice(Number(boton.dataset.quitar), 1);
            dibujarLineas();
        });

        cuerpo.addEventListener('input', function(e) {
            if (!e.target.dataset.indice) return;

            var i = Number(e.target.dataset.indice);
            var valor = parseInt(e.target.value, 10);

            lineas[i].cantidad = isNaN(valor) || valor < 1 ? 1 : valor;
            actualizarTotales();
        });

        // ----------------------------------------------------------
        // Buscadores
        // ----------------------------------------------------------

        function ocultar(caja) {
            caja.classList.add('d-none');
            caja.innerHTML = '';
        }

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
                    var boton = document.createElement('button');
                    boton.type = 'button';
                    boton.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-3';

                    boton.innerHTML =
                        '<span class="text-start">' +
                        '<span class="fw-semibold">' + escapar(p.nombre) + '</span>' +
                        '<span class="text-muted small ms-2">' + escapar(p.codigo) + '</span>' +
                        '</span>' +
                        '<span class="text-nowrap">' +
                        '<span class="badge text-bg-' + (p.disponible > 0 ? 'secondary' : 'danger') + ' me-2">' +
                        (p.disponible > 0 ? p.disponible + ' disp.' : 'preventa') +
                        '</span>' +
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

        buscadorCliente.addEventListener('input', debounce(function() {
            buscar(urlClientes, buscadorCliente.value, function(items) {
                if (!items.length) return ocultar(resultadosCliente);

                resultadosCliente.innerHTML = '';

                items.forEach(function(c) {
                    var boton = document.createElement('button');
                    boton.type = 'button';
                    boton.className = 'list-group-item list-group-item-action text-start';

                    boton.innerHTML =
                        '<div class="fw-semibold">' + escapar(c.razon_social) + '</div>' +
                        '<div class="text-muted small">' + escapar(c.nro_doc) + ' · ' + escapar(c.condicion) + '</div>';

                    boton.addEventListener('click', function() {
                        cliente = c;
                        buscadorCliente.value = '';
                        ocultar(resultadosCliente);
                        dibujarCliente();
                    });

                    resultadosCliente.appendChild(boton);
                });

                resultadosCliente.classList.remove('d-none');
            });
        }, 300));

        document.addEventListener('click', function(e) {
            if (!e.target.closest('#resultadosProducto') && e.target !== buscador) ocultar(resultados);
            if (!e.target.closest('#resultadosCliente') && e.target !== buscadorCliente) ocultar(resultadosCliente);
        });

        // ----------------------------------------------------------
        // Cliente
        // ----------------------------------------------------------

        function dibujarCliente() {
            if (cliente) {
                clienteId.value = cliente.id;
                clienteNombre.textContent = cliente.razon_social;
                clienteDoc.textContent = cliente.nro_doc;
                clienteElegido.classList.remove('d-none');
                bloqueBuscador.classList.add('d-none');
            } else {
                clienteId.value = '';
                clienteElegido.classList.add('d-none');
                bloqueBuscador.classList.remove('d-none');
            }
        }

        btnQuitarCliente.addEventListener('click', function() {
            cliente = null;
            dibujarCliente();
        });

        // ----------------------------------------------------------
        // Modo de entrega y presupuesto
        // ----------------------------------------------------------

        function actualizarModo() {
            bloqueEnvio.classList.toggle('d-none', modoEntrega.value !== 'envio');
        }

        function actualizarValidez() {
            // La validez solo tiene sentido en un presupuesto.
            var esPresupuesto = !confirmar || !confirmar.checked;

            bloqueValidez.classList.toggle('d-none', !esPresupuesto);
            campoValidez.required = esPresupuesto;
        }

        modoEntrega.addEventListener('change', actualizarModo);
        if (confirmar) confirmar.addEventListener('change', actualizarValidez);

        // ----------------------------------------------------------
        // Arranque
        // ----------------------------------------------------------

        dibujarLineas();
        dibujarCliente();
        actualizarModo();
        actualizarValidez();
    })();
</script>