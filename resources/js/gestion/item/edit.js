document.addEventListener('DOMContentLoaded', function () {

    const form = document.getElementById('editarProductoForm');
    const btnEditar = document.getElementById('btnEditar');
    const btnActualizar = document.getElementById('btnActualizar');
    const btnCancelar = document.getElementById('btnCancelar');
    const inputImagen = document.getElementById('imagen');
    const previewContainer = document.getElementById('previewContainer');
    const imagenPreview = document.getElementById('imagenPreview');

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

    inputImagen.addEventListener('change', function () {
        const archivo = this.files[0];

        if (!archivo) {
            previewContainer.classList.add('d-none');
            imagenPreview.src = '#';
            return;
        }

        const lector = new FileReader();

        lector.onload = function (evento) {
            imagenPreview.src = evento.target.result;
            previewContainer.classList.remove('d-none');
        };

        lector.readAsDataURL(archivo);
    });



});