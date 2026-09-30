document.addEventListener('DOMContentLoaded', function () {

    const form = document.getElementById('editarProveedorForm');
    const btnEditar = document.getElementById('btnEditar');
    const btnActualizar = document.getElementById('btnActualizar');
    const btnCancelar = document.getElementById('btnCancelar');

    const campos = form.querySelectorAll(
        'input:not([type="hidden"]), select, textarea'
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


    function actualizarPortalUrl() {

        const canal = document.getElementById('canal_pedido');
        const portal = document.getElementById('portal_url');

        if (!canal || !portal) {
            return;
        }

        /*
         * El valor esperado para PortalExterno es obtenido desde
         * CanalPedido::PortalExterno->value.
         *
         * Si el enum cambia ese valor, modificar esta constante.
         */
        const esPortalExterno = canal.value === 'portal_externo';

        portal.closest('.col-md-6').style.display =
            esPortalExterno ? '' : 'none';

    }


    function activarEdicion() {

        campos.forEach(campo => {

            if (campo.type !== 'hidden') {
                campo.disabled = false;
            }

        });

        btnEditar.disabled = true;
        btnActualizar.disabled = false;
        btnCancelar.disabled = false;

        actualizarPortalUrl();
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

        btnEditar.disabled = false;
        btnActualizar.disabled = true;
        btnCancelar.disabled = true;

        actualizarPortalUrl();
    }


    btnEditar.addEventListener('click', activarEdicion);

    btnCancelar.addEventListener('click', cancelarEdicion);


    /*
     * Si cambia el canal de pedido mientras se está editando,
     * mostramos u ocultamos el campo de portal.
     */
    const canalPedido = document.getElementById('canal_pedido');

    if (canalPedido) {

        canalPedido.addEventListener('change', actualizarPortalUrl);

    }


    // Estado inicial.
    actualizarPortalUrl();

});