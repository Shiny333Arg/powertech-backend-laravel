{{--
    Detalle de un pedido. Lo comparten create y edit.

    El proveedor NO es editable acá: en el alta se elige antes (create pide
    primero a quién se le pide, porque el buscador de productos depende de
    eso) y en la edición no se cambia, porque cambiar de destinatario es otro
    pedido, no una corrección de este.

    El costo unitario tampoco se edita: sale del catálogo (producto->costo). Se
    muestra para que el encargado vea cuánto pesa cada renglón, pero el dato
    tiene un solo dueño, que es la ficha del producto.

    El buscador filtra en el cliente sobre $productos, que ya viene acotada a
    los del proveedor. No usa la ruta gestion.buscar.productos como el alta de
    ventas: esa busca en todo el catálogo y acá un producto de otro proveedor
    no es una opción válida.

    Espera:
      $orden     OrdenCompra (nueva o existente)
      $proveedor Proveedor
      $productos Collection de los productos de ese proveedor
      $accion    URL del form
      $metodo    'POST' o 'PUT'
--}}

@php
    // Lo que necesita el buscador, y nada más: el resto del modelo no tiene
    // por qué viajar al HTML.
    $catalogo = $productos->map(fn ($producto) => [
        'id'         => $producto->id,
        'codigo'     => $producto->codigo,
        'nombre'     => $producto->nombre,
        'costo'      => $producto->costo === null ? null : (float) $producto->costo,
        'disponible' => (int) $producto->stock_disponible,
        'reposicion' => (int) $producto->punto_reposicion,
    ])->values();
@endphp

<form method="POST" action="{{ $accion }}" autocomplete="off" data-form-pedido>
    @csrf

    @if ($metodo === 'PUT')
        @method('PUT')
    @endif

    @if ($metodo === 'POST')
        <input type="hidden" name="proveedor_id" value="{{ $proveedor->id }}">
    @endif

    <section class="p-4 rounded-4 bg-light shadow-sm border mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
            <div>
                <h2 class="h5 fw-bold mb-1">Productos a pedir</h2>
                <p class="text-muted small mb-0">
                    Buscá por código o nombre entre los productos de {{ $proveedor->razon_social }}.
                    El costo lo toma del catálogo.
                </p>
            </div>

            <button type="button" class="btn btn-sm btn-outline-primary" data-agregar-renglon>
                <svg xmlns="http://www.w3.org/2000/svg"
                     width="16"
                     height="16"
                     fill="currentColor"
                     class="bi bi-plus-lg me-1"
                     viewBox="0 0 16 16"
                     aria-hidden="true">
                    <path fill-rule="evenodd"
                          d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2"/>
                </svg>
                Agregar renglón
            </button>
        </div>

        @error('lineas')
            <div class="alert alert-danger small mb-3 rounded-3">{{ $message }}</div>
        @enderror

        <div class="table-responsive">
            <table class="table table-bordered align-middle text-center mb-0" id="tablaProductos">

                <thead class="table-dark">

                    <tr>
                        <th class="text-nowrap">Producto</th>
                        <th class="text-nowrap" style="width: 12rem;">Cantidad</th>
                        <th class="text-nowrap" style="width: 10rem;">Costo unitario</th>
                        <th class="text-nowrap" style="width: 10rem;">Subtotal</th>
                        <th class="text-nowrap" style="width: 5rem;">Quitar</th>
                    </tr>

                </thead>

                <tbody data-renglones>
                    @php
                        // old() gana sobre las lineas guardadas: si la validacion
                        // rechazo el formulario, lo que el usuario escribio no se
                        // puede perder.
                        $renglones = old('lineas', $orden->lineas->map(fn ($linea) => [
                            'producto_id'     => $linea->producto_id,
                            'cantidad_pedida' => $linea->cantidad_pedida,
                            'costo_unitario'  => $linea->costo_unitario,
                        ])->all());

                        $renglones = $renglones ?: [['producto_id' => '', 'cantidad_pedida' => 1]];
                    @endphp

                    @foreach ($renglones as $i => $renglon)
                        @php
                            // El costo sale del producto. El guardado en la linea
                            // queda solo de red para el caso raro de un producto
                            // que ya no figura entre los del proveedor.
                            $guardado = $renglon['producto_id'] ?? '';
                            $elegido  = $productos->firstWhere('id', (int) $guardado);
                            $costo    = $elegido?->costo ?? ($renglon['costo_unitario'] ?? null);
                            $cantidad = (int) ($renglon['cantidad_pedida'] ?? 1);
                            $subtotal = $costo === null ? null : (float) $costo * max($cantidad, 0);

                            // Se cargo algo que el proveedor ya no vende: no se
                            // puede preseleccionar, hay que avisar y que elijan.
                            $huerfano = $guardado !== '' && $elegido === null;
                        @endphp

                        <tr>
                            <td class="text-start">
                                {{--
                                    El texto es solo la interfaz. Lo que viaja al
                                    servidor es el hidden de abajo, igual que el
                                    buscador de clientes del alta de ventas.
                                --}}
                                <div class="position-relative">
                                    <input type="text"
                                           class="form-control rounded-3 @error("lineas.{$i}.producto_id") is-invalid @enderror"
                                           value="{{ $elegido ? $elegido->codigo . ' — ' . $elegido->nombre : '' }}"
                                           placeholder="Buscá por código o nombre"
                                           autocomplete="off"
                                           role="combobox"
                                           aria-expanded="false"
                                           maxlength="150"
                                           data-buscador-producto>

                                    <input type="hidden"
                                           name="lineas[{{ $i }}][producto_id]"
                                           value="{{ $elegido?->id }}"
                                           data-producto-campo>

                                    {{-- Lo llena el script. Fijo, para que no lo recorte la tabla. --}}
                                    <div class="list-group shadow d-none"
                                         style="position: fixed; z-index: 1050; max-height: 18rem; overflow-y: auto;"
                                         data-resultados-producto></div>

                                    <div class="invalid-feedback" data-producto-error>
                                        @error("lineas.{$i}.producto_id"){{ $message }}@enderror
                                    </div>
                                </div>

                                <div class="text-muted small mt-1" data-producto-detalle>
                                    @if ($elegido)
                                        disp. {{ $elegido->stock_disponible }} / rep. {{ $elegido->punto_reposicion }}
                                    @endif
                                </div>

                                @if ($huerfano)
                                    <div class="text-danger small mt-1" data-aviso-catalogo>
                                        El producto cargado ya no figura entre los de este proveedor. Elegí otro.
                                    </div>
                                @endif
                            </td>

                            <td>
                                <input type="number" name="lineas[{{ $i }}][cantidad_pedida]"
                                       value="{{ $renglon['cantidad_pedida'] ?? 1 }}"
                                       min="1" max="999999" required
                                       class="form-control rounded-3 text-end @error("lineas.{$i}.cantidad_pedida") is-invalid @enderror">

                                <div class="invalid-feedback">
                                    @error("lineas.{$i}.cantidad_pedida"){{ $message }}@enderror
                                </div>
                            </td>

                            {{-- Solo lectura: el valor viaja en el hidden, que la pantalla no deja tocar. --}}
                            <td class="text-end text-nowrap">
                                <span data-costo-texto>
                                    {{ $costo === null ? '—' : '$ ' . number_format((float) $costo, 2, ',', '.') }}
                                </span>

                                <input type="hidden"
                                       name="lineas[{{ $i }}][costo_unitario]"
                                       value="{{ $costo }}"
                                       data-costo-campo>
                            </td>

                            <td class="text-end text-nowrap fw-semibold" data-subtotal>
                                {{ $subtotal === null ? '—' : '$ ' . number_format($subtotal, 2, ',', '.') }}
                            </td>

                            <td class="text-center">
                                <button type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        data-quitar-renglon
                                        aria-label="Quitar renglón"
                                        title="Quitar renglón">
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                         width="16"
                                         height="16"
                                         fill="currentColor"
                                         class="bi bi-trash"
                                         viewBox="0 0 16 16"
                                         aria-hidden="true">
                                        <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m5 0A.5.5 0 0 1 11 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m-3 0A.5.5 0 0 1 8 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5"/>
                                        <path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1 0-2h3.5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1H14a1 1 0 0 1 .5 1M5 4v9a1 1 0 0 0 1 1h5a1 1 0 0 0 1-1V4zM6.5 2a.5.5 0 0 0-.5.5V3h4v-.5a.5.5 0 0 0-.5-.5z"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>

                <tfoot class="table-light">
                    <tr>
                        <td colspan="3" class="text-end fw-semibold">Total estimado</td>
                        <td class="text-end fw-bold text-nowrap" data-total>—</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="form-text mt-2">
            El costo es el del catálogo y acá no se edita: para cambiarlo, andá a la ficha del
            producto. Los importes son una estimación para dimensionar el pedido, y en ningún caso
            se le informan al proveedor.
        </div>
    </section>

    <div class="mb-3">
        <label for="observaciones" class="form-label">Observaciones</label>
        <textarea id="observaciones" name="observaciones" rows="3"
                  class="form-control @error('observaciones') is-invalid @enderror"
                  placeholder="Se incluyen en el remito que recibe el proveedor.">{{ old('observaciones', $orden->observaciones) }}</textarea>

        @error('observaciones')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            {{ $metodo === 'POST' ? 'Crear el pedido' : 'Guardar los cambios' }}
        </button>

        <a href="{{ $orden->exists ? route('gestion.compras.show', $orden) : route('gestion.compras.index') }}"
           class="btn btn-outline-secondary">
            Cancelar
        </a>
    </div>
</form>

{{-- Los productos del proveedor, para el buscador. Sin ensuciar atributos HTML. --}}
<script type="application/json" id="catalogoProveedor">
    @json($catalogo)
</script>

@push('scripts')
    <script>
        // Renglones dinamicos. Se clona el primero y se le reescriben los
        // indices del name: Laravel arma el array por el indice, asi que dos
        // renglones con el mismo numero se pisan.
        (function () {
            'use strict';

            const cuerpo = document.querySelector('[data-renglones]');
            if (!cuerpo) return;

            const form       = document.querySelector('[data-form-pedido]');
            const celdaTotal = document.querySelector('[data-total]');
            const catalogo   = JSON.parse(document.getElementById('catalogoProveedor').textContent) || [];

            const TOPE = 50; // Resultados por busqueda: una lista larga no se lee.

            // Mismo formato que el resto de la pantalla: 1.234,56.
            const formato = new Intl.NumberFormat('es-AR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });

            const pesos = (numero) => '$ ' + formato.format(numero);

            // Sin acentos y en minusculas, para que "bateria" encuentre "Batería".
            const normalizar = (texto) => (texto ?? '')
                .toString()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase()
                .trim();

            const escapar = (texto) => {
                const div = document.createElement('div');
                div.textContent = texto ?? '';
                return div.innerHTML;
            };

            catalogo.forEach((producto) => {
                producto.busqueda = normalizar(producto.codigo + ' ' + producto.nombre);
                producto.etiqueta = producto.codigo + ' — ' + producto.nombre;
            });

            const reindexar = () => {
                cuerpo.querySelectorAll('tr').forEach((fila, i) => {
                    fila.querySelectorAll('[name]').forEach((campo) => {
                        campo.name = campo.name.replace(/lineas\[\d+\]/, `lineas[${i}]`);
                    });
                });
            };

            // ----------------------------------------------------------
            // Importes
            // ----------------------------------------------------------

            // El costo lo manda el catalogo, no el usuario: lo escribe elegir()
            // en el hidden, que es lo unico que viaja al servidor. Aca solo se
            // formatea lo que ya esta en el campo.
            const recalcularFila = (fila) => {
                const campo    = fila.querySelector('[data-costo-campo]');
                const texto    = fila.querySelector('[data-costo-texto]');
                const subtotal = fila.querySelector('[data-subtotal]');

                const costo    = parseFloat(campo?.value ?? '');
                const cantidad = parseInt(fila.querySelector('input[name*="cantidad_pedida"]')?.value, 10);

                if (Number.isNaN(costo)) {
                    if (texto) texto.textContent = '—';
                    if (subtotal) subtotal.textContent = '—';
                    return;
                }

                if (texto) texto.textContent = pesos(costo);

                if (subtotal) {
                    subtotal.textContent = Number.isNaN(cantidad) || cantidad < 1
                        ? '—'
                        : pesos(costo * cantidad);
                }
            };

            const recalcularTotal = () => {
                if (!celdaTotal) return;

                let total = 0;
                let hayAlguno = false;

                cuerpo.querySelectorAll('tr').forEach((fila) => {
                    const costo    = parseFloat(fila.querySelector('[data-costo-campo]')?.value ?? '');
                    const cantidad = parseInt(fila.querySelector('input[name*="cantidad_pedida"]')?.value, 10);

                    if (Number.isNaN(costo) || Number.isNaN(cantidad) || cantidad < 1) return;

                    total += costo * cantidad;
                    hayAlguno = true;
                });

                celdaTotal.textContent = hayAlguno ? pesos(total) : '—';
            };

            const recalcularTodo = () => {
                cuerpo.querySelectorAll('tr').forEach(recalcularFila);
                recalcularTotal();
            };

            // ----------------------------------------------------------
            // Buscador de producto (uno por renglon)
            // ----------------------------------------------------------

            let filaAbierta = null;

            const cajaDe  = (fila) => fila.querySelector('[data-resultados-producto]');
            const campoDe = (fila) => fila.querySelector('[data-buscador-producto]');

            // La caja va con position: fixed porque .table-responsive recorta
            // todo lo que se sale de la tabla. Se la ubica contra el input.
            const ubicar = (fila) => {
                const caja = cajaDe(fila);
                const r = campoDe(fila).getBoundingClientRect();

                caja.style.top   = (r.bottom + 2) + 'px';
                caja.style.left  = r.left + 'px';
                caja.style.width = r.width + 'px';
            };

            const cerrar = (fila) => {
                if (!fila) return;

                const caja = cajaDe(fila);
                caja.classList.add('d-none');
                caja.innerHTML = '';
                campoDe(fila).setAttribute('aria-expanded', 'false');

                if (filaAbierta === fila) filaAbierta = null;
            };

            const cerrarTodo = () => cuerpo.querySelectorAll('tr').forEach(cerrar);

            const opcionesDe = (fila) =>
                Array.prototype.slice.call(cajaDe(fila).querySelectorAll('[data-producto-id]'));

            const marcar = (fila, i) => {
                const opciones = opcionesDe(fila);

                opciones.forEach((o) => o.classList.remove('active'));

                if (i >= 0 && opciones[i]) {
                    opciones[i].classList.add('active');
                    opciones[i].scrollIntoView({ block: 'nearest' });
                }
            };

            const limpiarProducto = (fila) => {
                fila.querySelector('[data-producto-campo]').value = '';
                fila.querySelector('[data-costo-campo]').value = '';
                fila.querySelector('[data-producto-detalle]').textContent = '';
            };

            const elegir = (fila, producto) => {
                if (!producto) return;

                const input = campoDe(fila);

                input.value = producto.etiqueta;
                input.classList.remove('is-invalid');

                fila.querySelector('[data-producto-campo]').value = producto.id;
                fila.querySelector('[data-costo-campo]').value = producto.costo ?? '';
                fila.querySelector('[data-producto-detalle]').textContent =
                    'disp. ' + producto.disponible + ' / rep. ' + producto.reposicion;

                const aviso = fila.querySelector('[data-aviso-catalogo]');
                if (aviso) aviso.remove();

                cerrar(fila);
                recalcularFila(fila);
                recalcularTotal();
            };

            const mostrar = (fila) => {
                const caja = cajaDe(fila);
                const q = normalizar(campoDe(fila).value);

                const encontrados = catalogo.filter((p) => q === '' || p.busqueda.indexOf(q) !== -1);

                caja.innerHTML = '';

                if (!encontrados.length) {
                    caja.innerHTML = '<div class="list-group-item text-muted small">' +
                        'Ningún producto de este proveedor coincide con la búsqueda.</div>';
                } else {
                    encontrados.slice(0, TOPE).forEach((p) => {
                        const bajo = p.disponible <= p.reposicion;

                        const boton = document.createElement('button');
                        boton.type = 'button';
                        boton.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-3 text-start';
                        boton.dataset.productoId = p.id;

                        boton.innerHTML =
                            '<span>' +
                                '<span class="fw-semibold">' + escapar(p.nombre) + '</span>' +
                                '<span class="text-muted small ms-2">' + escapar(p.codigo) + '</span>' +
                            '</span>' +
                            '<span class="text-nowrap">' +
                                '<span class="badge text-bg-' + (bajo ? 'warning' : 'secondary') + ' me-2">' +
                                    'disp. ' + p.disponible + ' / rep. ' + p.reposicion +
                                '</span>' +
                                (p.costo === null ? '—' : pesos(p.costo)) +
                            '</span>';

                        caja.appendChild(boton);
                    });

                    if (encontrados.length > TOPE) {
                        const pie = document.createElement('div');
                        pie.className = 'list-group-item text-muted small';
                        pie.textContent = 'Hay ' + (encontrados.length - TOPE) + ' resultados más. Afiná la búsqueda.';
                        caja.appendChild(pie);
                    }
                }

                ubicar(fila);
                caja.classList.remove('d-none');
                campoDe(fila).setAttribute('aria-expanded', 'true');
                filaAbierta = fila;
            };

            // ----------------------------------------------------------
            // Eventos (delegados: asi los renglones clonados andan solos)
            // ----------------------------------------------------------

            cuerpo.addEventListener('focusin', (evento) => {
                if (!evento.target.matches('[data-buscador-producto]')) return;

                const fila = evento.target.closest('tr');
                if (filaAbierta && filaAbierta !== fila) cerrar(filaAbierta);

                mostrar(fila);
            });

            const alEditar = (evento) => {
                const fila = evento.target.closest('tr');
                if (!fila) return;

                if (evento.target.matches('[data-buscador-producto]')) {
                    // Lo que escribe invalida la eleccion anterior: el texto no
                    // alcanza, hace falta volver a elegir de la lista.
                    limpiarProducto(fila);
                    mostrar(fila);
                }

                recalcularFila(fila);
                recalcularTotal();
            };

            cuerpo.addEventListener('input', alEditar);
            cuerpo.addEventListener('change', alEditar);

            cuerpo.addEventListener('keydown', (evento) => {
                if (!evento.target.matches('[data-buscador-producto]')) return;

                const fila = evento.target.closest('tr');
                const abierta = !cajaDe(fila).classList.contains('d-none');

                if (evento.key === 'Escape') {
                    cerrar(fila);
                    return;
                }

                if (evento.key === 'ArrowDown' || evento.key === 'ArrowUp') {
                    evento.preventDefault();

                    if (!abierta) {
                        mostrar(fila);
                        return;
                    }

                    const opciones = opcionesDe(fila);
                    const actual = opciones.findIndex((o) => o.classList.contains('active'));

                    marcar(fila, evento.key === 'ArrowDown'
                        ? Math.min(actual + 1, opciones.length - 1)
                        : Math.max(actual - 1, 0));

                    return;
                }

                if (evento.key === 'Enter' && abierta) {
                    // Nunca manda el formulario con la lista abierta.
                    evento.preventDefault();

                    const opciones = opcionesDe(fila);
                    const actual = opciones.findIndex((o) => o.classList.contains('active'));

                    // Con uno marcado, o con un unico candidato, Enter elige.
                    const opcion = actual >= 0 ? opciones[actual] : (opciones.length === 1 ? opciones[0] : null);
                    if (!opcion) return;

                    elegir(fila, catalogo.find((p) => String(p.id) === opcion.dataset.productoId));
                }
            });

            cuerpo.addEventListener('click', (evento) => {
                const opcion = evento.target.closest('[data-producto-id]');

                if (opcion) {
                    const fila = opcion.closest('tr');
                    elegir(fila, catalogo.find((p) => String(p.id) === opcion.dataset.productoId));
                    campoDe(fila).focus();
                    return;
                }

                if (!evento.target.closest('[data-quitar-renglon]')) return;

                // Nunca se queda sin renglones: un pedido vacio no se puede
                // guardar, y un formulario sin filas no deja volver atras.
                if (cuerpo.querySelectorAll('tr').length === 1) return;

                cerrarTodo();
                evento.target.closest('tr').remove();
                reindexar();
                recalcularTotal();
            });

            document.addEventListener('click', (evento) => {
                if (evento.target.closest('[data-resultados-producto]')) return;
                if (evento.target.closest('[data-buscador-producto]')) return;

                cerrarTodo();
            });

            // La caja va fija contra la ventana: si algo se mueve, se reubica.
            const reubicar = () => filaAbierta && ubicar(filaAbierta);

            window.addEventListener('resize', reubicar);
            window.addEventListener('scroll', reubicar, true);

            document.querySelector('[data-agregar-renglon]')?.addEventListener('click', () => {
                cerrarTodo();

                const nueva = cuerpo.querySelector('tr').cloneNode(true);

                nueva.querySelector('[data-buscador-producto]').value = '';
                nueva.querySelector('[data-producto-campo]').value = '';
                nueva.querySelector('[data-costo-campo]').value = '';
                nueva.querySelector('[data-producto-detalle]').textContent = '';
                nueva.querySelector('input[name*="cantidad_pedida"]').value = 1;

                const caja = nueva.querySelector('[data-resultados-producto]');
                caja.innerHTML = '';
                caja.classList.add('d-none');

                // Los errores son del renglon que se clono, no de este.
                nueva.querySelectorAll('.is-invalid').forEach((campo) => campo.classList.remove('is-invalid'));
                nueva.querySelectorAll('.invalid-feedback').forEach((error) => (error.textContent = ''));
                nueva.querySelectorAll('[data-aviso-catalogo]').forEach((aviso) => aviso.remove());

                cuerpo.appendChild(nueva);
                reindexar();
                recalcularFila(nueva);
                recalcularTotal();

                nueva.querySelector('[data-buscador-producto]').focus();
            });

            // El hidden no se puede marcar required: el navegador no valida
            // campos ocultos. Texto escrito sin elegir de la lista no vale.
            form?.addEventListener('submit', (evento) => {
                let primero = null;

                cuerpo.querySelectorAll('tr').forEach((fila) => {
                    const input = campoDe(fila);
                    const vacio = !fila.querySelector('[data-producto-campo]').value;

                    input.classList.toggle('is-invalid', vacio);
                    fila.querySelector('[data-producto-error]').textContent = vacio
                        ? 'Elegí un producto de la lista.'
                        : '';

                    if (vacio && !primero) primero = input;
                });

                if (!primero) return;

                evento.preventDefault();
                cerrarTodo();
                primero.focus();
            });

            recalcularTodo();
        })();
    </script>
@endpush