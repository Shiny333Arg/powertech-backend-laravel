/**
 * Alta y edición de productos, contra la API.
 *
 * Un solo módulo para las dos pantallas porque son el mismo formulario:
 *
 *     POST /api/v1/gestion/productos                      alta
 *     POST /api/v1/gestion/productos/{id} + _method=PUT   edición
 *     PATCH /api/v1/gestion/productos/{id}/publicacion    publicar / retirar
 *     PATCH /api/v1/gestion/productos/{id}/estado         baja / reactivación
 *
 * <b>Todo viaja como multipart/form-data</b>, con o sin foto, y la edición va
 * por POST con `_method=PUT`: PHP no parsea el cuerpo multipart de un PUT. Es
 * la misma convención que usaba el formulario Blade con @method('PUT') y la
 * que documentan las rutas.
 *
 * <b>Las validaciones no se repiten acá.</b> Qué código es válido, si está
 * repetido, el peso máximo de la imagen y las dos reglas cruzadas de
 * reposición automática los sigue decidiendo `ProductoRequest`, que es el
 * MISMO FormRequest que usaba el formulario tradicional. Este archivo manda el
 * formulario y, si vuelve un 422, reparte los mensajes del backend en los
 * mismos lugares donde los pintaba Blade.
 *
 * ──────────────────────────────────────────────────────────────────────────
 * LA PUBLICACIÓN NO VIAJA EN EL PUT
 * ──────────────────────────────────────────────────────────────────────────
 *
 * El cambio del interruptor se resuelve con PATCH .../publicacion, nunca con
 * el PUT, porque es el endpoint que exige 'producto.publicar' y el que rechaza
 * publicar un producto dado de baja.
 *
 * El PUT manda igual `publicado_app`, con el valor QUE YA TENÍA el producto.
 * Hoy el backend lo descarta —`update()` lo excluye a propósito, después de
 * que esta migración mostrara que `prepareForValidation()` lo metía en
 * `validated()` aunque el cuerpo no lo trajera, y que un PUT podía entonces
 * despublicar solo o publicar con apenas 'producto.editar'—, así que mandarlo
 * no cambia nada. Se manda de todos modos por si el campo volviera a
 * escribirse: con el valor viejo, el peor caso es que el PUT guarde lo que ya
 * estaba guardado.
 */

import { obtener, enviarFormulario, parchear, tratar, ErrorDeApi } from '../api.js';
import * as avisos from '../avisos.js';

/** Los campos de texto, número y selección que viajan al backend. */
const CAMPOS = [
    'codigo',
    'nombre',
    'marca',
    'descripcion',
    'categoria_id',
    'alicuota_iva_id',
    'proveedor_id',
    'precio',
    'costo',
    'punto_reposicion',
    'cantidad_reposicion',
    'peso_gramos',
];

/** Los dos interruptores. */
const BANDERAS = ['reposicion_automatica', 'publicado_app'];

/**
 * @param {object} configuracion
 * @param {number|null} configuracion.id    null en el alta.
 * @param {string} configuracion.urlListado
 * @param {boolean} [configuracion.puedePublicar]
 */
export function iniciar({ id = null, urlListado, puedePublicar = false }) {
    const formulario = document.getElementById('formularioProducto');

    if (! formulario) {
        return;
    }

    const esEdicion = id !== null;

    const caja    = document.getElementById('avisosProducto');
    const errores = document.getElementById('erroresProducto');

    const imagen   = formulario.elements.imagen;
    const guardar  = document.getElementById('botonGuardar') ?? document.getElementById('btnActualizar');
    const editar   = document.getElementById('btnEditar');
    const cancelar = document.getElementById('btnCancelar');
    const baja     = document.getElementById('botonBaja');
    const reactivar = document.getElementById('botonReactivar');

    /** Lo que dice la API hoy: sirve para cancelar y para el PUT inocuo. */
    let guardado = null;

    // ------------------------------------------------------------------
    // Edición: los datos los trae la API, no Blade
    // ------------------------------------------------------------------

    async function traerProducto() {
        if (editar) {
            editar.disabled = true;
        }

        try {
            const respuesta = await obtener(`gestion/productos/${id}`);

            guardado = respuesta.data;

            volcarEnElFormulario(guardado);
            mostrarImagenActual(guardado.imagen_url);
            mostrarBotonDeEstado(guardado.activo);

            if (editar && bloqueado) {
                editar.disabled = false;
            }

        } catch (error) {
            const mensaje = tratar(error);

            if (mensaje !== null) {
                avisos.mostrar(caja, 'error', `No pudimos cargar el producto: ${mensaje}`);
            }
        }
    }

    function volcarEnElFormulario(producto) {
        formulario.elements.codigo.value = producto.codigo ?? '';
        formulario.elements.nombre.value = producto.nombre ?? '';
        formulario.elements.marca.value = producto.marca ?? '';
        formulario.elements.descripcion.value = producto.descripcion ?? '';

        formulario.elements.precio.value = producto.precio ?? '';
        formulario.elements.costo.value = producto.costo ?? '';
        formulario.elements.punto_reposicion.value = producto.punto_reposicion ?? 0;
        formulario.elements.cantidad_reposicion.value = producto.cantidad_reposicion ?? 0;
        formulario.elements.peso_gramos.value = producto.peso_gramos ?? '';

        // Los desplegables sólo traen activos. Un producto puede apuntar a una
        // categoría o a un proveedor dados de baja después de cargarlo: sin
        // esto el select quedaría vacío y al guardar se perdería la relación.
        elegirOpcion(formulario.elements.categoria_id, producto.categoria?.id, producto.categoria?.nombre);
        elegirOpcion(formulario.elements.proveedor_id, producto.proveedor?.id, producto.proveedor?.razon_social);
        elegirOpcion(formulario.elements.alicuota_iva_id, producto.alicuota_iva?.id, producto.alicuota_iva?.nombre);

        formulario.elements.reposicion_automatica.checked = producto.reposicion_automatica;
        formulario.elements.publicado_app.checked = producto.publicado_app;
    }

    function elegirOpcion(select, valor, texto) {
        if (! select) {
            return;
        }

        if (valor === undefined || valor === null) {
            select.value = '';

            return;
        }

        const existe = Array.from(select.options).some((opcion) => opcion.value === String(valor));

        if (! existe) {
            const opcion = document.createElement('option');

            opcion.value = String(valor);
            opcion.textContent = texto ?? String(valor);

            select.append(opcion);
        }

        select.value = String(valor);
    }

    function mostrarImagenActual(url) {
        const actual = document.getElementById('imagenActual');
        const sinImagen = document.getElementById('sinImagen');

        if (! actual || ! sinImagen) {
            return;
        }

        if (url) {
            actual.src = url;
            actual.classList.remove('d-none');
            sinImagen.classList.add('d-none');
        } else {
            actual.classList.add('d-none');
            sinImagen.classList.remove('d-none');
        }
    }

    function mostrarBotonDeEstado(activo) {
        baja?.classList.toggle('d-none', ! activo);
        reactivar?.classList.toggle('d-none', activo);
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

        const nombre = formulario.elements.nombre.value.trim();

        try {
            esEdicion
                ? await guardarEdicion(nombre)
                : await guardarAlta(nombre);

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

    async function guardarAlta(nombre) {
        const cuerpo = cuerpoComun();

        // El stock inicial sólo existe en el alta: no es la columna de saldo
        // sino la cantidad del movimiento de tipo 'inicial' que registra
        // StockService. En la edición el campo está 'prohibited'.
        cuerpo.append('stock_inicial', formulario.elements.stock_inicial?.value ?? '0');

        // El alta sí decide la publicación: el endpoint la acepta y no exige
        // 'producto.publicar', igual que el formulario de antes.
        cuerpo.append('publicado_app', formulario.elements.publicado_app.checked ? '1' : '0');

        agregarImagen(cuerpo);

        await enviarFormulario('gestion/productos', cuerpo);

        volverAlListado('exito', `Se creó «${nombre}».`);
    }

    async function guardarEdicion(nombre) {
        const cuerpo = cuerpoComun();

        cuerpo.append('_method', 'PUT');

        // El valor viejo, a propósito: ver el encabezado del archivo.
        cuerpo.append('publicado_app', guardado?.publicado_app ? '1' : '0');

        agregarImagen(cuerpo);

        await enviarFormulario(`gestion/productos/${id}`, cuerpo);

        const queria = formulario.elements.publicado_app.checked;

        // El PUT ya quedó aplicado. Si la publicación falla, lo que se informa
        // es exactamente eso: los datos se guardaron y la publicación no
        // cambió.
        if (puedePublicar && guardado !== null && queria !== guardado.publicado_app) {
            try {
                await parchear(`gestion/productos/${id}/publicacion`, { publicado_app: queria });

            } catch (error) {
                const mensaje = tratar(error);

                if (mensaje !== null) {
                    avisos.mostrar(caja, 'error', `Se guardaron los cambios, pero la publicación no cambió: ${mensaje}`);
                }

                formulario.elements.publicado_app.checked = guardado.publicado_app;
                trabajando(false);

                await traerProducto();

                return;
            }
        }

        volverAlListado('exito', `Se guardaron los cambios en «${nombre}».`);
    }

    /** Los campos que son iguales en el alta y en la edición. */
    function cuerpoComun() {
        const cuerpo = new FormData();

        for (const campo of CAMPOS) {
            const elemento = formulario.elements[campo];

            if (elemento) {
                // Las cadenas vacías llegan al backend como null —lo hace el
                // middleware ConvertEmptyStringsToNull—, que es lo que esperan
                // los campos 'nullable': marca, costo, proveedor, peso.
                cuerpo.append(campo, elemento.value);
            }
        }

        cuerpo.append('reposicion_automatica', formulario.elements.reposicion_automatica.checked ? '1' : '0');

        // 'activo' no viaja nunca: está 'prohibited' en ProductoRequest. La
        // baja y la reactivación tienen su endpoint y su permiso.

        return cuerpo;
    }

    function agregarImagen(cuerpo) {
        const archivo = imagen?.files?.[0];

        if (archivo) {
            cuerpo.append('imagen', archivo);
        }
    }

    // ------------------------------------------------------------------
    // Baja y reactivación (sólo en la edición)
    // ------------------------------------------------------------------
    //
    // Como antes, no se vuelve al listado: se queda en la pantalla con el
    // producto al día, que es lo que hacía el `back()` del controlador.

    baja?.addEventListener('click', () => cambiarEstado(false,
        '¿Está seguro de que desea dar de baja este producto?'));

    reactivar?.addEventListener('click', () => cambiarEstado(true,
        '¿Está seguro de que desea dar de alta este producto?'));

    async function cambiarEstado(activo, pregunta) {
        if (! window.confirm(pregunta)) {
            return;
        }

        limpiarErrores();
        trabajando(true);

        try {
            await parchear(`gestion/productos/${id}/estado`, { activo });

            avisos.mostrar(caja, 'exito', activo
                ? `«${guardado?.nombre ?? 'El producto'}» vuelve a estar en operación.`
                : `«${guardado?.nombre ?? 'El producto'}» salió de operación. No se puede cargar en ventas nuevas.`);

        } catch (error) {
            const mensaje = tratar(error);

            if (mensaje !== null) {
                avisos.mostrar(caja, 'error', mensaje);
            }
        }

        trabajando(false);

        await traerProducto();
    }

    // ------------------------------------------------------------------
    // El bloqueo de campos de la pantalla de edición
    // ------------------------------------------------------------------
    //
    // Es el mismo de antes —los campos arrancan bloqueados y "Editar" los
    // abre—, pero ahora vive acá junto con el resto de la pantalla en vez de
    // en un <script> suelto dentro de la vista.

    let bloqueado = esEdicion;

    function camposEditables() {
        return formulario.querySelectorAll('input:not([type="hidden"]), select, textarea');
    }

    function bloquearCampos(si) {
        bloqueado = si;

        camposEditables().forEach((campo) => {
            campo.disabled = si;
        });

        // El interruptor de publicación sigue bloqueado sin el permiso.
        if (! puedePublicar && formulario.elements.publicado_app) {
            formulario.elements.publicado_app.disabled = true;
        }

        if (editar) {
            editar.disabled = ! si;
        }

        if (guardar) {
            guardar.disabled = si;
        }

        if (cancelar) {
            cancelar.disabled = si;
        }
    }

    editar?.addEventListener('click', () => bloquearCampos(false));

    cancelar?.addEventListener('click', () => {
        limpiarErrores();

        if (guardado !== null) {
            volcarEnElFormulario(guardado);
        }

        if (imagen) {
            imagen.value = '';
        }

        ocultarVistaPrevia();
        bloquearCampos(true);
    });

    // ------------------------------------------------------------------
    // Vista previa de la imagen nueva
    // ------------------------------------------------------------------

    imagen?.addEventListener('change', () => {
        const contenedor = document.getElementById('previewContainer');
        const vista = document.getElementById('imagenPreview');
        const archivo = imagen.files?.[0];

        if (! contenedor || ! vista) {
            return;
        }

        if (! archivo) {
            ocultarVistaPrevia();

            return;
        }

        const lector = new FileReader();

        lector.onload = (evento) => {
            vista.src = evento.target.result;
            contenedor.classList.remove('d-none');
        };

        lector.readAsDataURL(archivo);
    });

    function ocultarVistaPrevia() {
        const contenedor = document.getElementById('previewContainer');
        const vista = document.getElementById('imagenPreview');

        contenedor?.classList.add('d-none');

        if (vista) {
            vista.src = '#';
        }
    }

    // ------------------------------------------------------------------
    // Pantalla
    // ------------------------------------------------------------------

    function volverAlListado(tipo, texto) {
        avisos.recordar(tipo, texto);

        window.location.assign(urlListado);
    }

    /** Deshabilita la botonera mientras hay una llamada en curso. */
    function trabajando(si) {
        for (const boton of [guardar, editar, cancelar, baja, reactivar]) {
            if (boton) {
                boton.disabled = si;
            }
        }

        if (! si && esEdicion) {
            // Al terminar, la botonera vuelve al estado que corresponda según
            // los campos estén abiertos o bloqueados.
            if (editar) {
                editar.disabled = ! bloqueado;
            }

            if (guardar) {
                guardar.disabled = bloqueado;
            }

            if (cancelar) {
                cancelar.disabled = bloqueado;
            }
        }
    }

    /**
     * Los mensajes del 422, en los dos lugares donde los ponía Blade: la lista
     * de arriba del formulario y el detalle de cada campo.
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

        for (const [campo, suyos] of Object.entries(porCampo ?? {})) {
            const detalle = document.getElementById(`error-${campo}`);

            if (detalle) {
                detalle.textContent = suyos[0];
            }

            const elemento = formulario.elements[campo];

            if (elemento && ! BANDERAS.includes(campo)) {
                elemento.classList.add('is-invalid');
            }
        }

        errores?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function limpiarErrores() {
        errores?.replaceChildren();
        avisos.limpiar(caja);

        for (const campo of [...CAMPOS, ...BANDERAS, 'imagen', 'stock_inicial']) {
            document.getElementById(`error-${campo}`)?.replaceChildren();

            formulario.elements[campo]?.classList.remove('is-invalid');
        }
    }

    // ------------------------------------------------------------------
    // Arranque
    // ------------------------------------------------------------------

    if (esEdicion) {
        bloquearCampos(true);
        traerProducto();
    }
}
