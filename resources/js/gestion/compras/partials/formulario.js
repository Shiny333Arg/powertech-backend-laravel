// Renglones dinamicos. Se clona el primero y se le reescriben los
// indices del name: Laravel arma el array por el indice, asi que dos
// renglones con el mismo numero se pisan.
(function () {
    const cuerpo = document.querySelector('[data-renglones]');
    if (!cuerpo) return;

    const reindexar = () => {
        cuerpo.querySelectorAll('tr').forEach((fila, i) => {
            fila.querySelectorAll('[name]').forEach((campo) => {
                campo.name = campo.name.replace(/lineas\[\d+\]/, `lineas[${i}]`);
            });
        });
    };

    document.querySelector('[data-agregar-renglon]')?.addEventListener('click', () => {
        const nueva = cuerpo.querySelector('tr').cloneNode(true);

        nueva.querySelectorAll('select, input').forEach((campo) => {
            campo.value = campo.type === 'number' && campo.min === '1' ? 1 : '';
            campo.classList.remove('is-invalid');
        });

        nueva.querySelectorAll('.invalid-feedback').forEach((error) => error.remove());

        cuerpo.appendChild(nueva);
        reindexar();
    });

    cuerpo.addEventListener('click', (evento) => {
        if (!evento.target.closest('[data-quitar-renglon]')) return;

        // Nunca se queda sin renglones: un pedido vacio no se puede
        // guardar, y un formulario sin filas no deja volver atras.
        if (cuerpo.querySelectorAll('tr').length === 1) return;

        evento.target.closest('tr').remove();
        reindexar();
    });

    // Al elegir producto, se precarga su costo de catalogo si el campo
    // esta vacio. Es una sugerencia, no una imposicion.
    cuerpo.addEventListener('change', (evento) => {
        const select = evento.target.closest('select');
        if (!select) return;

        const costo = select.selectedOptions[0]?.dataset.costo;
        const campo = select.closest('tr').querySelector('input[name*="costo_unitario"]');

        if (costo && campo && campo.value === '') {
            campo.value = costo;
        }
    });
})();