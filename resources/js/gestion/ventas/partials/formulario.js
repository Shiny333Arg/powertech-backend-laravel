(function () {
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
        return function () {
            var args = arguments;
            clearTimeout(t);
            t = setTimeout(function () {
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

        lineas.forEach(function (linea, i) {
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
        var items = lineas.reduce(function (a, l) {
            return a + Number(l.cantidad);
        }, 0);
        var subtotal = lineas.reduce(function (a, l) {
            return a + l.precio * l.cantidad;
        }, 0);
        var hayPreventa = lineas.some(function (l) {
            return l.cantidad > l.disponible;
        });

        totalItems.textContent = items;
        totalSubtotal.textContent = pesos(subtotal);
        avisoPreventa.classList.toggle('d-none', !hayPreventa);
        btnGuardar.disabled = lineas.length === 0;
    }

    function agregarProducto(p) {
        var existente = lineas.filter(function (l) {
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

    cuerpo.addEventListener('click', function (e) {
        var boton = e.target.closest('[data-quitar]');
        if (!boton) return;

        lineas.splice(Number(boton.dataset.quitar), 1);
        dibujarLineas();
    });

    cuerpo.addEventListener('input', function (e) {
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
            .then(function (r) {
                if (!r.ok) {
                    return r.text().then(function (cuerpo) {
                        console.error('Búsqueda ' + url + ' → HTTP ' + r.status, cuerpo.slice(0, 800));
                        return [];
                    });
                }

                var tipo = r.headers.get('content-type') || '';

                if (tipo.indexOf('application/json') === -1) {
                    return r.text().then(function (cuerpo) {
                        console.error('Búsqueda ' + url + ' → no devolvió JSON (¿redirect de middleware?)', cuerpo.slice(0, 800));
                        return [];
                    });
                }

                return r.json();
            })
            .then(alRecibir)
            .catch(function (e) {
                console.error('Búsqueda ' + url + ' falló', e);
                alRecibir([]);
            });
    }


    buscador.addEventListener('input', debounce(function () {
        buscar(urlProductos, buscador.value, function (items) {
            if (!items.length) return ocultar(resultados);

            resultados.innerHTML = '';

            items.forEach(function (p) {
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

                boton.addEventListener('click', function () {
                    agregarProducto(p);
                });
                resultados.appendChild(boton);
            });

            resultados.classList.remove('d-none');
        });
    }, 300));

    buscadorCliente.addEventListener('input', debounce(function () {
        buscar(urlClientes, buscadorCliente.value, function (items) {
            if (!items.length) return ocultar(resultadosCliente);

            resultadosCliente.innerHTML = '';

            items.forEach(function (c) {
                var boton = document.createElement('button');
                boton.type = 'button';
                boton.className = 'list-group-item list-group-item-action text-start';

                boton.innerHTML =
                    '<div class="fw-semibold">' + escapar(c.razon_social) + '</div>' +
                    '<div class="text-muted small">' + escapar(c.nro_doc) + ' · ' + escapar(c.condicion) + '</div>';

                boton.addEventListener('click', function () {
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

    document.addEventListener('click', function (e) {
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

    btnQuitarCliente.addEventListener('click', function () {
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