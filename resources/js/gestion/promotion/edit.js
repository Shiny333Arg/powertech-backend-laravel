document.addEventListener('DOMContentLoaded', function () {

    // ==========================================================
    // PRODUCTOS ALCANZADOS
    // ==========================================================
    // Expone bloquear/desbloquear/restaurar para que el bloqueo
    // generico del formulario lo maneje como a un campo mas.

    var productos = (function () {
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

            elegidos.forEach(function (p, i) {
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
            var yaEsta = elegidos.some(function (e) {
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

        cuerpo.addEventListener('click', function (e) {
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
                .then(function (r) {
                    if (!r.ok) {
                        return r.text().then(function (cuerpo) {
                            console.error('Búsqueda de productos → HTTP ' + r.status, cuerpo.slice(0, 800));
                            return [];
                        });
                    }

                    var tipo = r.headers.get('content-type') || '';

                    if (tipo.indexOf('application/json') === -1) {
                        return r.text().then(function (cuerpo) {
                            console.error('Búsqueda de productos → no devolvió JSON (¿redirect de middleware?)', cuerpo.slice(0, 800));
                            return [];
                        });
                    }

                    return r.json();
                })
                .then(alRecibir)
                .catch(function (e) {
                    console.error('Búsqueda de productos falló', e);
                    alRecibir([]);
                });
        }

        buscador.addEventListener('input', debounce(function () {
            if (bloqueado) return;

            buscar(buscador.value, function (items) {
                if (!items.length) return ocultar(resultados);

                resultados.innerHTML = '';

                items.forEach(function (p) {
                    var yaEsta = elegidos.some(function (e) {
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

                    boton.addEventListener('click', function () {
                        agregar(p);
                    });

                    resultados.appendChild(boton);
                });

                resultados.classList.remove('d-none');
            });
        }, 300));

        document.addEventListener('click', function (e) {
            if (!e.target.closest('#resultadosProducto') && e.target !== buscador) {
                ocultar(resultados);
            }
        });

        // El buscador no es un campo del form: Enter no debe enviarlo.
        buscador.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') e.preventDefault();
        });

        dibujar();

        return {
            bloquear: function () {
                bloqueado = true;
                buscador.disabled = true;
                buscador.value = '';
                ocultar(resultados);
                dibujar();
            },
            desbloquear: function () {
                bloqueado = false;
                buscador.disabled = false;
                dibujar();
            },
            restaurar: function () {
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
    @if ($errors -> any())
        activarEdicion();
    @endif

});