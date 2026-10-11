(function () {
    'use strict';

    var saldo = Number(@json($saldo));

    var contenedor = document.getElementById('filasPago');
    var btnAgregar = document.getElementById('btnAgregarPago');
    var ingresado = document.getElementById('montoIngresado');
    var falta = document.getElementById('montoFalta');
    var avisoExceso = document.getElementById('avisoExcedido');
    var avisoParcial = document.getElementById('avisoParcial');
    var btnCobrar = document.getElementById('btnCobrar');

    // La primera fila arranca con el saldo completo: el caso normal del
    // mostrador es un solo medio de pago por el total.
    var pagos = [{ metodo: 'efectivo', monto: saldo, cuotas: '' }];

    function pesos(n) {
        return '$ ' + Number(n).toLocaleString('es-AR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function dibujar() {
        contenedor.innerHTML = '';

        pagos.forEach(function (pago, i) {
            var fila = document.createElement('div');
            fila.className = 'row g-3 align-items-end mb-3';

            fila.innerHTML =
                '<div class="col-12 col-md-4">' +
                '<label class="form-label fw-semibold" for="metodo' + i + '">Medio</label>' +
                '<select class="form-select rounded-3" id="metodo' + i + '" ' +
                'name="pagos[' + i + '][metodo]" data-campo="metodo" data-indice="' + i + '">' +
                '<option value="efectivo">Efectivo</option>' +
                '<option value="tarjeta">Tarjeta</option>' +
                '<option value="transferencia">Transferencia</option>' +
                '</select>' +
                '</div>' +
                '<div class="col-8 col-md-4">' +
                '<label class="form-label fw-semibold" for="monto' + i + '">Importe</label>' +
                '<input type="number" class="form-control rounded-3" id="monto' + i + '" ' +
                'name="pagos[' + i + '][monto]" value="' + pago.monto + '" ' +
                'min="0.01" step="0.01" required data-campo="monto" data-indice="' + i + '">' +
                '</div>' +
                '<div class="col-4 col-md-3' + (pago.metodo === 'tarjeta' ? '' : ' d-none') + '">' +
                '<label class="form-label fw-semibold" for="cuotas' + i + '">Cuotas</label>' +
                '<input type="number" class="form-control rounded-3" id="cuotas' + i + '" ' +
                (pago.metodo === 'tarjeta' ? 'name="pagos[' + i + '][cuotas]" ' : '') +
                'value="' + (pago.cuotas || 1) + '" min="1" max="24" ' +
                'data-campo="cuotas" data-indice="' + i + '">' +
                '</div>' +
                '<div class="col-12 col-md-1 d-grid">' +
                (pagos.length > 1
                    ? '<button type="button" class="btn btn-outline-danger" data-quitar="' + i + '" ' +
                    'title="Quitar medio de pago">&times;</button>'
                    : '') +
                '</div>';

            contenedor.appendChild(fila);

            fila.querySelector('[data-campo="metodo"]').value = pago.metodo;
        });

        actualizarTotales();
    }

    function actualizarTotales() {
        var cobrado = pagos.reduce(function (a, p) {
            var n = parseFloat(p.monto);
            return a + (isNaN(n) ? 0 : n);
        }, 0);

        var restante = Math.round((saldo - cobrado) * 100) / 100;

        ingresado.textContent = pesos(cobrado);
        falta.textContent = pesos(restante);
        falta.classList.toggle('text-danger', restante < 0);

        avisoExceso.classList.toggle('d-none', restante >= 0);
        avisoParcial.classList.toggle('d-none', !(restante > 0 && cobrado > 0));

        btnCobrar.disabled = cobrado <= 0 || restante < 0;
    }

    contenedor.addEventListener('input', function (e) {
        var campo = e.target.dataset.campo;
        if (!campo) return;

        pagos[Number(e.target.dataset.indice)][campo] = e.target.value;
        actualizarTotales();
    });

    contenedor.addEventListener('change', function (e) {
        if (e.target.dataset.campo !== 'metodo') return;

        pagos[Number(e.target.dataset.indice)].metodo = e.target.value;
        dibujar();
    });

    contenedor.addEventListener('click', function (e) {
        var boton = e.target.closest('[data-quitar]');
        if (!boton) return;

        pagos.splice(Number(boton.dataset.quitar), 1);
        dibujar();
    });

    btnAgregar.addEventListener('click', function () {
        var cobrado = pagos.reduce(function (a, p) {
            var n = parseFloat(p.monto);
            return a + (isNaN(n) ? 0 : n);
        }, 0);

        pagos.push({
            metodo: 'efectivo',
            monto: Math.max(0, Math.round((saldo - cobrado) * 100) / 100),
            cuotas: ''
        });

        dibujar();
    });

    dibujar();
})();