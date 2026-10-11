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