{{--
    Detalle de un pedido a proveedores. Lo comparten el alta y la edición.

    El proveedor NO se elige: sale de los productos que se carguen. Si todos los
    renglones son del mismo, el pedido queda dirigido a él y se comporta como
    siempre; si hay varios, al aprobarlo se emite una orden por proveedor y cada
    uno recibe por correo solamente sus renglones. Por eso la columna de
    proveedor es informativa: muestra a quién se le va a pedir cada cosa.

    Cuando un producto pueda tener varios proveedores, esa celda pasa a ser un
    desplegable y no hay que tocar nada más: la línea ya guarda su proveedor_id.

    El costo unitario no se edita ni se muestra por renglón: sale del catálogo
    (producto->costo) y el dato tiene un solo dueño, que es la ficha del
    producto. Lo que sí se ve es el estimado del pedido, que es la referencia que
    el administrativo necesita para aprobar.

    El buscador va contra gestion.buscar.productos, igual que el alta de ventas:
    ahora el catálogo entero es una opción válida, así que no tiene sentido
    bajarlo al HTML como hacía la versión anterior.

    El botón "traer lo que falta pedir" va contra gestion.compras.sugerencias y
    carga de una todos los productos bajo su punto de reposición que ningún
    proveedor tiene todavía. Los borradores automáticos que queden cubiertos se
    dan de baja solos cuando este pedido se apruebe, no antes: si el formulario
    se abandona, esos borradores siguen en pie.

    Espera:
      $orden            OrdenCompra (nueva o existente)
      $lineasIniciales  array de renglones ya hidratados, lo arma el controlador
      $accion           URL del form
      $metodo           'POST' o 'PUT'
--}}

<form action="{{ $accion }}" method="POST" autocomplete="off" id="formPedido">

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

            <!-- Buscador y renglones -->
            <section class="p-4 rounded-4 bg-light shadow-sm border mb-4">

                <h2 class="h5 fw-bold mb-1">Productos a pedir</h2>

                <p class="text-muted small">
                    Buscá por código, nombre o marca en todo el catálogo. El proveedor
                    de cada renglón sale de la ficha del producto.
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


                {{--
                    El atajo del administrativo: en vez de buscar uno por uno los
                    diez productos que están en falta, los trae todos.

                    Deja afuera lo que ya está en manos de un proveedor —orden
                    aprobada, enviada o recibida a medias—, porque eso ya se
                    pidió. Lo que solo figura en un borrador SÍ entra: un
                    borrador no es un compromiso y este pedido lo reemplaza.
                --}}
                <div class="d-flex flex-wrap align-items-center gap-2 mb-3">

                    <button
                        type="button"
                        class="btn btn-outline-primary btn-sm fw-semibold"
                        id="btnSugerencias">
                        Traer lo que falta pedir
                    </button>

                    <span class="text-muted small" id="ayudaSugerencias">
                        Productos bajo su punto de reposición que todavía no le llegaron a ningún proveedor.
                    </span>

                </div>

                <div class="alert alert-info small d-none" id="avisoSugerencias" role="status"></div>


                <div class="table-responsive">

                    <table class="table table-bordered align-middle text-center mb-0" id="tablaRenglones">

                        <thead class="table-dark">

                            <tr>
                                <th class="text-nowrap">Producto</th>
                                <th class="text-nowrap" style="width: 10rem;">Marca</th>
                                <th class="text-nowrap" style="width: 14rem;">Proveedor</th>
                                <th class="text-nowrap" style="width: 9rem;">Cantidad</th>
                                <th class="text-nowrap" style="width: 5rem;">Quitar</th>
                            </tr>

                        </thead>

                        <tbody id="cuerpoRenglones">
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
                    placeholder="Notas internas sobre este pedido.">{{ old('observaciones', $orden->observaciones) }}</textarea>

                <div class="form-text">
                    Son internas: no salen en el remito que recibe el proveedor.
                </div>

                @error('observaciones')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
                @enderror

            </section>

        </div>


        <!-- Columna derecha: resumen -->
        <div class="col-12 col-lg-4">

            <!-- Cómo se va a emitir -->
            <section class="p-4 rounded-4 bg-light shadow-sm border mb-4">

                <h2 class="h6 fw-bold mb-1">Cómo se va a emitir</h2>

                <p class="text-muted small mb-3">
                    Un pedido con productos de varios proveedores se reparte al aprobarlo:
                    cada uno recibe una orden con sus renglones y nada más.
                </p>

                <div id="resumenProveedores" class="vstack gap-2">
                    {{-- Lo dibuja el script del pie --}}
                </div>

            </section>


            <!-- Totales -->
            <section class="p-4 rounded-4 bg-white shadow-sm border">

                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted">Renglones</span>
                    <span class="fw-semibold" id="totalRenglones">0</span>
                </div>

                <hr>

                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted">Unidades</span>
                    <span class="fw-semibold" id="totalUnidades">0</span>
                </div>

                <hr>

                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted">Total estimado</span>
                    <span class="h5 fw-bold mb-0" id="totalEstimado">$ 0,00</span>
                </div>

                <div class="form-text">
                    Al costo del catálogo. El precio real lo fija la factura del proveedor
                    cuando llega la mercadería.
                </div>

                <div class="alert alert-warning small mt-3 d-none" id="avisoSinProveedor">
                    Hay renglones sin proveedor al que pedirles. Quitalos, o asignales un
                    proveedor desde la ficha del producto.
                </div>

                <div class="mt-4 d-flex flex-wrap gap-2">

                    <button
                        type="submit"
                        class="btn btn-success px-4 fw-semibold shadow-sm"
                        id="btnGuardar"
                        disabled>
                        {{ $metodo === 'PUT' ? 'Guardar cambios' : 'Guardar borrador' }}
                    </button>

                    <a
                        href="{{ $orden->exists ? route('gestion.compras.show', $orden) : route('gestion.compras.index') }}"
                        class="btn btn-outline-secondary px-4 fw-semibold shadow-sm">
                        Volver
                    </a>

                </div>

                <div class="form-text mt-3">
                    Se guarda en borrador. Ninguna orden sale sin que un administrativo
                    la apruebe.
                </div>

            </section>

        </div>

    </div>

</form>


{{-- Datos iniciales para el script, sin ensuciar atributos HTML --}}
<script type="application/json" id="datosRenglones">
    @json($lineasIniciales)
</script>


<script>
    (function() {
        'use strict';

        var urlProductos = @json(route('gestion.buscar.productos'));
        var urlSugerencias = @json(route('gestion.compras.sugerencias'));

        var renglones = JSON.parse(document.getElementById('datosRenglones').textContent) || [];

        var cuerpo = document.getElementById('cuerpoRenglones');
        var buscador = document.getElementById('buscadorProducto');
        var resultados = document.getElementById('resultadosProducto');
        var resumen = document.getElementById('resumenProveedores');
        var totalRenglones = document.getElementById('totalRenglones');
        var totalUnidades = document.getElementById('totalUnidades');
        var totalEstimado = document.getElementById('totalEstimado');
        var avisoSinProveedor = document.getElementById('avisoSinProveedor');
        var btnGuardar = document.getElementById('btnGuardar');
        var btnSugerencias = document.getElementById('btnSugerencias');
        var avisoSugerencias = document.getElementById('avisoSugerencias');

        // ----------------------------------------------------------
        // Utilidades
        // ----------------------------------------------------------

        function pesos(n) {
            return '$ ' + Number(n || 0).toLocaleString('es-AR', {
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

        function plural(n, singular, plural) {
            return n + ' ' + (n === 1 ? singular : plural);
        }

        // Un renglón se puede pedir si tiene proveedor y ese proveedor está
        // activo. Es la misma condición que valida el servidor: conviene que el
        // usuario la vea antes de mandar el formulario y que se lo rechacen.
        function pedible(renglon) {
            return !!(renglon.proveedor && renglon.proveedor.activo);
        }

        // ----------------------------------------------------------
        // Renglones
        // ----------------------------------------------------------

        function celdaProveedor(renglon) {
            if (!renglon.proveedor) {
                return '<span class="badge text-bg-danger">Sin proveedor</span>';
            }

            var nombre = '<div class="small">' + escapar(renglon.proveedor.razon_social) + '</div>';

            if (!renglon.proveedor.activo) {
                nombre += '<span class="badge text-bg-warning mt-1">Dado de baja</span>';
            }

            return nombre;
        }

        function dibujarRenglones() {
            cuerpo.innerHTML = '';

            if (!renglones.length) {
                cuerpo.innerHTML =
                    '<tr><td colspan="5" class="text-center py-4 text-muted">' +
                    'Todavía no cargaste productos. Buscá uno arriba para empezar.' +
                    '</td></tr>';
            }

            renglones.forEach(function(renglon, i) {
                var fila = document.createElement('tr');

                if (!pedible(renglon)) {
                    fila.className = 'table-warning';
                }

                fila.innerHTML =
                    '<td class="text-start">' +
                    '<div class="fw-semibold">' + escapar(renglon.nombre) + '</div>' +
                    '<div class="text-muted small">' + escapar(renglon.codigo) + '</div>' +
                    '<input type="hidden" name="lineas[' + i + '][producto_id]" value="' + renglon.producto_id + '">' +
                    '</td>' +

                    '<td class="small">' + (renglon.marca ? escapar(renglon.marca) : '<span class="text-muted">—</span>') + '</td>' +

                    '<td>' + celdaProveedor(renglon) + '</td>' +

                    '<td>' +
                    '<input type="number" class="form-control form-control-sm text-center" ' +
                    'name="lineas[' + i + '][cantidad_pedida]" value="' + renglon.cantidad + '" ' +
                    'min="1" max="999999" data-indice="' + i + '">' +
                    '</td>' +

                    '<td>' +
                    '<button type="button" class="btn btn-sm btn-outline-danger" ' +
                    'data-quitar="' + i + '" title="Quitar">&times;</button>' +
                    '</td>';

                cuerpo.appendChild(fila);
            });

            actualizarResumen();
        }

        function actualizarResumen() {
            var unidades = renglones.reduce(function(a, r) {
                return a + Number(r.cantidad);
            }, 0);

            var estimado = renglones.reduce(function(a, r) {
                return a + (Number(r.costo) || 0) * Number(r.cantidad);
            }, 0);

            var huerfanos = renglones.filter(function(r) {
                return !pedible(r);
            });

            totalRenglones.textContent = renglones.length;
            totalUnidades.textContent = unidades;
            totalEstimado.textContent = pesos(estimado);

            avisoSinProveedor.classList.toggle('d-none', huerfanos.length === 0);

            // Guardar con un renglón sin proveedor lo rechaza el servidor: no
            // tiene sentido dejar que mande el formulario y pierda el tiempo.
            btnGuardar.disabled = renglones.length === 0 || huerfanos.length > 0;

            dibujarResumen();
        }

        function dibujarResumen() {
            var grupos = [];

            renglones.forEach(function(r) {
                if (!r.proveedor) return;

                var grupo = grupos.filter(function(g) {
                    return g.id === r.proveedor.id;
                })[0];

                if (!grupo) {
                    grupo = {
                        id: r.proveedor.id,
                        nombre: r.proveedor.razon_social,
                        activo: r.proveedor.activo,
                        items: 0,
                        unidades: 0
                    };
                    grupos.push(grupo);
                }

                grupo.items++;
                grupo.unidades += Number(r.cantidad);
            });

            if (!grupos.length) {
                resumen.innerHTML = '<div class="text-muted small">Cargá productos para ver a quiénes se les va a pedir.</div>';
                return;
            }

            var encabezado = grupos.length === 1 ?
                '<div class="small text-muted">Va a quedar una sola orden, dirigida a:</div>' :
                '<div class="small text-muted">Al aprobarlo se van a emitir <strong>' + grupos.length + ' órdenes</strong>:</div>';

            resumen.innerHTML = encabezado + grupos.map(function(g) {
                return '<div class="d-flex justify-content-between align-items-center border rounded-3 bg-white p-2">' +
                    '<span class="small fw-semibold">' + escapar(g.nombre) +
                    (g.activo ? '' : ' <span class="badge text-bg-warning">baja</span>') +
                    '</span>' +
                    '<span class="text-muted small text-nowrap">' + g.items + ' ítem' + (g.items === 1 ? '' : 's') +
                    ' · ' + g.unidades + ' u.</span>' +
                    '</div>';
            }).join('');
        }

        function agregarProducto(p) {
            var existente = renglones.filter(function(r) {
                return r.producto_id === p.id;
            })[0];

            // Sumar en vez de abrir otro renglón: el servidor rechaza productos
            // repetidos, y el proveedor no tiene por qué recibir un remito con
            // el mismo ítem dos veces.
            if (existente) {
                existente.cantidad++;
            } else {
                renglones.push({
                    producto_id: p.id,
                    codigo: p.codigo,
                    nombre: p.nombre,
                    marca: p.marca,
                    costo: p.costo,
                    cantidad: 1,
                    proveedor: p.proveedor
                });
            }

            buscador.value = '';
            ocultar(resultados);
            dibujarRenglones();
        }

        cuerpo.addEventListener('click', function(e) {
            var boton = e.target.closest('[data-quitar]');
            if (!boton) return;

            renglones.splice(Number(boton.dataset.quitar), 1);
            dibujarRenglones();
        });

        cuerpo.addEventListener('input', function(e) {
            if (!e.target.dataset.indice) return;

            var i = Number(e.target.dataset.indice);
            var valor = parseInt(e.target.value, 10);

            renglones[i].cantidad = isNaN(valor) || valor < 1 ? 1 : valor;
            actualizarResumen();
        });

        // ----------------------------------------------------------
        // Buscador
        // ----------------------------------------------------------

        function ocultar(caja) {
            caja.classList.add('d-none');
            caja.innerHTML = '';
        }

        function buscar(url, texto, alRecibir) {
            if (texto.trim().length < 2) return alRecibir([]);

            pedirJson(url + '?q=' + encodeURIComponent(texto), alRecibir, function() {
                alRecibir([]);
            });
        }

        // El manejo de respuestas raras está acá y no en cada llamada porque la
        // falla típica no es un 500: es un middleware que redirige al login y
        // devuelve HTML con status 200. Sin este chequeo, r.json() explota con
        // un error de parseo que no dice nada.
        function pedirJson(url, alRecibir, alFallar) {
            fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                })
                .then(function(r) {
                    if (!r.ok) {
                        return r.text().then(function(cuerpo) {
                            console.error('Pedido ' + url + ' → HTTP ' + r.status, cuerpo.slice(0, 800));
                            alFallar(r.status);
                            return null;
                        });
                    }

                    var tipo = r.headers.get('content-type') || '';

                    if (tipo.indexOf('application/json') === -1) {
                        return r.text().then(function(cuerpo) {
                            console.error('Pedido ' + url + ' → no devolvió JSON (¿redirect de middleware?)', cuerpo.slice(0, 800));
                            alFallar(0);
                            return null;
                        });
                    }

                    return r.json();
                })
                .then(function(datos) {
                    if (datos !== null) alRecibir(datos);
                })
                .catch(function(e) {
                    console.error('Pedido ' + url + ' falló', e);
                    alFallar(0);
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

                    // El disponible va en el desplegable porque es el motivo por
                    // el que alguien abre esta pantalla: se pide lo que falta.
                    var falta = p.disponible <= p.reposicion;

                    boton.innerHTML =
                        '<span class="text-start">' +
                        '<span class="fw-semibold">' + escapar(p.nombre) + '</span>' +
                        '<span class="text-muted small ms-2">' + escapar(p.codigo) + '</span>' +
                        '<span class="d-block text-muted small">' +
                        (p.marca ? escapar(p.marca) + ' · ' : '') +
                        (p.proveedor ? escapar(p.proveedor.razon_social) : 'sin proveedor') +
                        '</span>' +
                        '</span>' +
                        '<span class="text-nowrap">' +
                        '<span class="badge text-bg-' + (falta ? 'danger' : 'secondary') + '">' +
                        p.disponible + ' disp.' +
                        '</span>' +
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

        // Enter en el buscador no manda el formulario: lo normal es que el
        // usuario esté buscando, no terminando.
        buscador.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') e.preventDefault();
        });

        // ----------------------------------------------------------
        // Traer lo que falta pedir
        // ----------------------------------------------------------

        function avisar(html, clase) {
            avisoSugerencias.className = 'alert ' + clase + ' small';
            avisoSugerencias.innerHTML = html;
        }

        /**
         * Mezcla lo sugerido con lo que ya está cargado.
         *
         * La cantidad SOLO SUBE, igual que OrdenCompraService::ampliar(). Si el
         * administrativo escribió un número mayor, es una decisión suya y el
         * botón no se la pisa; si escribió uno menor, la sugerencia lo corrige
         * porque esa es la cantidad que saca al producto del punto de
         * reposición.
         */
        function fusionar(sugeridos) {
            var agregados = 0;
            var ajustados = 0;

            sugeridos.forEach(function(s) {
                var existente = renglones.filter(function(r) {
                    return r.producto_id === s.producto_id;
                })[0];

                if (!existente) {
                    renglones.push(s);
                    agregados++;
                    return;
                }

                if (Number(s.cantidad) > Number(existente.cantidad)) {
                    existente.cantidad = Number(s.cantidad);
                    ajustados++;
                }
            });

            return {
                agregados: agregados,
                ajustados: ajustados
            };
        }

        function listaOmitidos(omitidos) {
            return '<ul class="mb-0 mt-2">' + omitidos.map(function(o) {
                return '<li>' + escapar(o.nombre) + ' <span class="text-muted">(' + escapar(o.codigo) + ')</span> — ' +
                    escapar(o.motivo) + '</li>';
            }).join('') + '</ul>';
        }

        function contarSugerencias(resultado, omitidos) {
            var partes = [];

            if (resultado.agregados) partes.push('Se agregaron ' + plural(resultado.agregados, 'producto', 'productos') + '.');
            if (resultado.ajustados) partes.push('Se subió la cantidad de ' + plural(resultado.ajustados, 'renglón', 'renglones') + ' que ya estaban cargados.');

            if (!partes.length) {
                partes.push(omitidos.length ?
                    'No hay nada más para agregar.' :
                    'No hay productos en falta sin pedir: está todo cubierto.');
            }

            return partes.join(' ');
        }

        btnSugerencias.addEventListener('click', function() {
            btnSugerencias.disabled = true;
            btnSugerencias.textContent = 'Buscando…';

            avisoSugerencias.classList.add('d-none');

            pedirJson(urlSugerencias, function(datos) {
                btnSugerencias.disabled = false;
                btnSugerencias.textContent = 'Traer lo que falta pedir';

                var sugeridos = datos.renglones || [];
                var omitidos = datos.omitidos || [];

                var resultado = fusionar(sugeridos);

                dibujarRenglones();

                var mensaje = contarSugerencias(resultado, omitidos);
                var clase = 'alert-info';

                // Los que no se pueden pedir no se esconden: un producto en
                // falta que nadie va a reponer porque le falta el proveedor es
                // justamente lo que hay que ver.
                if (omitidos.length) {
                    mensaje += '<div class="mt-2"><strong>No se pudieron agregar ' +
                        plural(omitidos.length, 'producto', 'productos') +
                        ':</strong>' + listaOmitidos(omitidos) + '</div>';
                    clase = 'alert-warning';
                }

                avisar(mensaje, clase);
                avisoSugerencias.classList.remove('d-none');

            }, function(estado) {
                btnSugerencias.disabled = false;
                btnSugerencias.textContent = 'Traer lo que falta pedir';

                avisar(estado === 403 ?
                    'No tenés permiso para armar pedidos a proveedores.' :
                    'No se pudo traer la lista. Probá de nuevo, o cargá los productos con el buscador.',
                    'alert-danger');

                avisoSugerencias.classList.remove('d-none');
            });
        });

        // ----------------------------------------------------------
        // Arranque
        // ----------------------------------------------------------

        dibujarRenglones();
    })();
</script>
