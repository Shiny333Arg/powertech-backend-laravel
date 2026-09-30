document.addEventListener('DOMContentLoaded', function () {

    const form = document.getElementById('editarPromocionForm');

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

            if (campo.multiple) {

                valoresOriginales[campo.id] =
                    Array.from(campo.selectedOptions).map(option => option.value);

            } else {

                valoresOriginales[campo.id] = campo.value;

            }

        }

    });

    function activarEdicion() {

        campos.forEach(campo => {

            if (campo.type !== 'hidden') {
                campo.disabled = false;
            }

        });

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

            } else if (campo.multiple) {

                const valores = valoresOriginales[campo.id] || [];

                Array.from(campo.options).forEach(option => {
                    option.selected = valores.includes(option.value);
                });

            } else {

                campo.value = valoresOriginales[campo.id];

            }

            campo.disabled = true;

        });

        btnEditar.disabled = false;
        btnActualizar.disabled = true;
        btnCancelar.disabled = true;

    }

    btnEditar.addEventListener('click', activarEdicion);

    btnCancelar.addEventListener('click', cancelarEdicion);

});