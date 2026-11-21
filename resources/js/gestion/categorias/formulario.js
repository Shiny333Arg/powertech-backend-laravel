/**
 * Alta y edición de categorías, contra la API.
 *
 * Un solo módulo para las dos pantallas porque son el mismo formulario: el de
 * alta manda `POST /api/v1/gestion/categorias` y el de edición
 * `PUT /api/v1/gestion/categorias/{id}`, y de ahí en más todo es igual —los
 * mismos campos, los mismos mensajes, el mismo destino al terminar—.
 *
 * <b>Las validaciones no se repiten acá.</b> Qué nombre es válido, si está
 * repetido o cuánto puede medir lo sigue decidiendo `CategoriaRequest`, que es
 * el MISMO FormRequest que usaba el formulario tradicional. Este archivo
 * manda el formulario y, si vuelve un 422, reparte los mensajes que escribió
 * el backend en los mismos lugares donde los pintaba Blade: la lista de
 * arriba y el `invalid-feedback` del campo. El único control del lado del
 * navegador es el `required` del HTML, que ya estaba.
 *
 * <b>El estado tiene su propio endpoint y su propio permiso.</b> El PUT no
 * cambia `activo` ni aunque viaje en el cuerpo —lo decidió la API para que
 * `categoria.editar` no sirva para dar de baja—, así que cuando el empleado
 * mueve el casillero la pantalla llama además a
 * `PATCH /api/v1/gestion/categorias/{id}/estado`. Si no tiene el permiso de
 * baja, el casillero viene deshabilitado desde la vista y esa llamada no
 * ocurre.
 */

import { obtener, crear, reemplazar, parchear, eliminar, tratar, ErrorDeApi } from '../api.js';
import * as avisos from '../avisos.js';

/**
 * @param {object} configuracion
 * @param {number|null} configuracion.id         null en el alta.
 * @param {string} configuracion.urlListado      A dónde se vuelve al terminar.
 * @param {boolean} [configuracion.puedeCambiarEstado]
 */
export function iniciar({ id = null, urlListado, puedeCambiarEstado = false }) {
    const formulario = document.getElementById('formularioCategoria');

    if (! formulario) {
        return;
    }

    const nombre   = formulario.elements.nombre;
    const activo   = formulario.elements.activo;
    const guardar  = document.getElementById('botonGuardar');
    const borrar   = document.getElementById('botonEliminar');
    const errores  = document.getElementById('erroresCategoria');
    const caja     = document.getElementById('avisosCategoria');

    const esEdicion = id !== null;

    /** El estado que tiene la categoría en la base, para saber si cambió. */
    let estadoGuardado = null;

    // ------------------------------------------------------------------
    // Edición: los datos los trae la API, no Blade
    // ------------------------------------------------------------------

    async function traerCategoria() {
        trabajando(true);

        try {
            const respuesta = await obtener(`gestion/categorias/${id}`);

            nombre.value = respuesta.data.nombre;
            activo.checked = respuesta.data.activo;
            estadoGuardado = respuesta.data.activo;

            trabajando(false);

        } catch (error) {
            const mensaje = tratar(error);

            if (mensaje !== null) {
                avisos.mostrar(caja, 'error', `No pudimos cargar la categoría: ${mensaje}`);
            }
        }
    }

    // ------------------------------------------------------------------
    // Guardar
    // ------------------------------------------------------------------

    formulario.addEventListener('submit', async (evento) => {
        evento.preventDefault();

        if (! formulario.reportValidity()) {
            return;
        }

        limpiarErrores();
        trabajando(true);

        const nombreEscrito = nombre.value.trim();

        try {
            esEdicion
                ? await guardarEdicion(nombreEscrito)
                : await guardarAlta(nombreEscrito);

        } catch (error) {
            trabajando(false);

            if (error instanceof ErrorDeApi && error.estado === 422) {
                mostrarErroresDeValidacion(error.errores);

                return;
            }

            const mensaje = tratar(error);

            if (mensaje !== null) {
                avisos.mostrar(caja, 'error', mensaje);
            }
        }
    });

    async function guardarAlta(nombreEscrito) {
        // `activo` viaja en el alta —el alta sí lo fija— y el slug lo deriva
        // del nombre el propio FormRequest, igual que antes.
        await crear('gestion/categorias', {
            nombre: nombreEscrito,
            activo: activo.checked,
        });

        volverAlListado('exito', `Se creó la categoría «${nombreEscrito}».`);
    }

    async function guardarEdicion(nombreEscrito) {
        await reemplazar(`gestion/categorias/${id}`, { nombre: nombreEscrito });

        // El PUT ya quedó aplicado. Si el cambio de estado falla, lo que se
        // informa es exactamente eso: los datos se guardaron y el estado no
        // cambió. Se hace en este orden porque guardar los datos es la
        // operación que el empleado vino a hacer.
        if (puedeCambiarEstado && estadoGuardado !== null && activo.checked !== estadoGuardado) {
            try {
                await parchear(`gestion/categorias/${id}/estado`, { activo: activo.checked });

            } catch (error) {
                const mensaje = tratar(error);

                if (mensaje !== null) {
                    avisos.mostrar(caja, 'error', `Se guardaron los cambios, pero el estado no se pudo cambiar: ${mensaje}`);
                }

                activo.checked = estadoGuardado;
                trabajando(false);

                return;
            }
        }

        volverAlListado('exito', `Se guardaron los cambios en «${nombreEscrito}».`);
    }

    // ------------------------------------------------------------------
    // Eliminar (sólo en la edición)
    // ------------------------------------------------------------------

    borrar?.addEventListener('click', async () => {
        if (! window.confirm('¿Está seguro de eliminar permanentemente esta categoría? Esta acción no se puede deshacer.')) {
            return;
        }

        limpiarErrores();
        trabajando(true);

        const nombreActual = nombre.value.trim();

        try {
            await eliminar(`gestion/categorias/${id}`);

            volverAlListado('exito', `Se eliminó la categoría «${nombreActual}».`);

        } catch (error) {
            trabajando(false);

            // 409: la categoría tiene productos. El mensaje lo escribe la API
            // —es el mismo texto que mostraba el panel— y acá sólo se muestra.
            const mensaje = tratar(error);

            if (mensaje !== null) {
                avisos.mostrar(caja, 'error', mensaje);
            }
        }
    });

    // ------------------------------------------------------------------
    // Pantalla
    // ------------------------------------------------------------------

    function volverAlListado(tipo, texto) {
        avisos.recordar(tipo, texto);

        window.location.assign(urlListado);
    }

    function trabajando(si) {
        if (guardar) {
            guardar.disabled = si;
        }

        if (borrar) {
            borrar.disabled = si;
        }

        nombre.disabled = si;

        // El casillero deshabilitado por falta de permiso se queda así.
        if (! esEdicion || puedeCambiarEstado) {
            activo.disabled = si;
        }
    }

    /**
     * Los mensajes del 422, en los dos lugares donde los ponía Blade: la lista
     * de arriba del formulario y el `invalid-feedback` de cada campo.
     *
     * @param {Record<string, string[]>|null} porCampo
     */
    function mostrarErroresDeValidacion(porCampo) {
        const mensajes = Object.values(porCampo ?? {}).flat();

        if (mensajes.length === 0) {
            return;
        }

        if (errores) {
            const alerta = document.createElement('div');
            alerta.className = 'alert alert-danger';

            const titulo = document.createElement('strong');
            titulo.textContent = 'Revisá estos campos:';

            const lista = document.createElement('ul');
            lista.className = 'mb-0 mt-2';

            for (const mensaje of mensajes) {
                const item = document.createElement('li');
                item.textContent = mensaje;
                lista.append(item);
            }

            alerta.append(titulo, lista);
            errores.replaceChildren(alerta);
        }

        const primero = porCampo?.nombre?.[0];

        if (primero) {
            nombre.classList.add('is-invalid');

            const detalle = formulario.querySelector('#errorNombre');

            if (detalle) {
                detalle.textContent = primero;
            }
        }

        nombre.focus();
    }

    function limpiarErrores() {
        errores?.replaceChildren();
        avisos.limpiar(caja);
        nombre.classList.remove('is-invalid');

        const detalle = formulario.querySelector('#errorNombre');

        if (detalle) {
            detalle.textContent = '';
        }
    }

    if (esEdicion) {
        traerCategoria();
    }
}
