document.addEventListener('DOMContentLoaded', function () {

    const form = document.getElementById('editarEmpleadoForm');
    const btnEditar = document.getElementById('btnEditar');
    const btnActualizar = document.getElementById('btnActualizar');
    const btnCancelar = document.getElementById('btnCancelar');

    const camposEditables = form.querySelectorAll(
        'input:not([type="hidden"]):not(#estado):not(#fecha_creacion), select'
    );

    // Guardamos los valores originales para poder cancelar.
    const valoresOriginales = new Map();

    camposEditables.forEach(campo => {
        valoresOriginales.set(campo.name, campo.value);
    });

    function habilitarEdicion() {
        camposEditables.forEach(campo => {
            campo.disabled = false;
        });

        btnEditar.disabled = true;
        btnActualizar.disabled = false;
        btnCancelar.disabled = false;
    }

    function cancelarEdicion() {
        camposEditables.forEach(campo => {
            if (valoresOriginales.has(campo.name)) {
                campo.value = valoresOriginales.get(campo.name);
            }

            campo.disabled = true;
        });

        btnEditar.disabled = false;
        btnActualizar.disabled = true;
        btnCancelar.disabled = true;
    }

    btnEditar.addEventListener('click', habilitarEdicion);
    btnCancelar.addEventListener('click', cancelarEdicion);

});