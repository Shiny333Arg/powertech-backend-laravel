/**
 * Alta y edición de proveedores, contra la API.
 *
 * Un solo módulo para las dos pantallas porque son el mismo formulario:
 *
 *     POST  /api/v1/gestion/proveedores            alta
 *     PUT   /api/v1/gestion/proveedores/{id}       edición
 *     PATCH /api/v1/gestion/proveedores/{id}/estado  baja / reactivación
 *
 * <b>Las validaciones no se repiten acá.</b> El CUIT y su dígito verificador,
 * la unicidad, la URL obligatoria cuando el canal es el portal y el resto del
 * domicilio cuando viene la calle los sigue decidiendo `ProveedorRequest`, que
 * es el MISMO FormRequest que usaba el formulario tradicional. Este archivo
 * manda el formulario y, si vuelve un 422, reparte los mensajes del backend en
 * los mismos lugares donde los pintaba Blade.
 *
 * ──────────────────────────────────────────────────────────────────────────
 * EL DOMICILIO FISCAL
 * ──────────────────────────────────────────────────────────────────────────
 *
 * Viaja anidado bajo `direccion`, en el mismo cuerpo del alta y de la edición,
 * igual que en el formulario Blade: no tiene endpoint propio porque es uno
 * solo por proveedor. Del otro lado lo guarda `App\Support\DomicilioFiscal`,
 * compartido con el panel, que lo reemplaza en lugar de acumular otro.
 *
 * <b>Sin calle no hay domicilio</b>, y eso también lo decide el backend: si la
 * calle llega vacía, el domicilio que ya estaba cargado NO se borra. La
 * pantalla manda lo que haya en los campos y no interpreta nada.
 *
 * <b>El estado no se toca desde el formulario.</b> El alta deja al proveedor
 * activo —el endpoint descarta el 'activo' del cuerpo a propósito— y el PUT no
 * lo cambia aunque viaje. La baja y la reactivación son sus botones, contra el
 * endpoint de estado, que exige `proveedor.baja`.
 */

import { obtener, crear, reemplazar, parchear, tratar, ErrorDeApi } from '../api.js';
import * as avisos from '../avisos.js';

/** Las columnas del proveedor que viajan al backend. */
const CAMPOS = [
    'razon_social',
    'cuit',
    'condicion_iva_id',
    'contacto',
    'email',
    'telefono',
    'canal_pedido',
    'portal_url',
    'plazo_entrega_dias',
];

/** Los campos del domicilio, que viajan anidados bajo 'direccion'. */
const DIRECCION = ['calle', 'numero', 'piso_depto', 'codigo_postal', 'localidad', 'provincia'];

/** El canal que exige la URL del portal. Lo define el enum CanalPedido. */
const PORTAL_EXTERNO = 'portal_externo';

/**
 * @param {object} configuracion
 * @param {number|null} configuracion.id    null en el alta.
 * @param {string} configuracion.urlListado
 */
export function iniciar({ id = null, urlListado }) {
    const formulario = document.getElementById('formularioProveedor');

    if (! formulario) {
        return;
    }

    const esEdicion = id !== null;

    const caja    = document.getElementById('avisosProveedor');
    const errores = document.getElementById('erroresProveedor');

    const guardar  = document.getElementById('botonGuardar') ?? document.getElementById('btnActualizar');
    const editar   = document.getElementById('btnEditar');
    const cancelar = document.getElementById('btnCancelar');
    const baja     = document.getElementById('botonBaja');
    const reactivar = document.getElementById('botonReactivar');

    /** Lo que dice la API hoy: sirve para cancelar y para los mensajes. */
    let guardado = null;

    /** En la edición los campos arrancan bloqueados, como antes. */
    let bloqueado = esEdicion;

    // ------------------------------------------------------------------
    // Edición: los datos los trae la API, no Blade
    // ------------------------------------------------------------------

    async function traerProveedor() {
        if (editar) {
            editar.disabled = true;
        }

        try {
            const respuesta = await obtener(`gestion/proveedores/${id}`);

            guardado = respuesta.data;

            volcarEnElFormulario(guardado);
            mostrarBotonDeEstado(guardado.activo);

            if (editar && bloqueado) {
                editar.disabled = false;
            }

        } catch (error) {
            const mensaje = tratar(error);

            if (mensaje !== null) {
                avisos.mostrar(caja, 'error', `No pudimos cargar el proveedor: ${mensaje}`);
            }
        }
    }

    function volcarEnElFormulario(proveedor) {
        campo('razon_social').value = proveedor.razon_social ?? '';

        // Los 11 dígitos tal como están en la columna, igual que antes: el
        // formato con guiones es cosa del listado.
        campo('cuit').value = proveedor.cuit ?? '';

        campo('contacto').value = proveedor.contacto ?? '';
        campo('email').value = proveedor.email ?? '';
        campo('telefono').value = proveedor.telefono ?? '';
        campo('portal_url').value = proveedor.portal_url ?? '';
        campo('plazo_entrega_dias').value = proveedor.plazo_entrega_dias ?? '';

        elegirOpcion(campo('condicion_iva_id'), proveedor.condicion_iva?.id, proveedor.condicion_iva?.nombre);
        elegirOpcion(campo('canal_pedido'), proveedor.canal_pedido, proveedor.canal_pedido_etiqueta);

        // El domicilio puede no existir: la clave viene en null y los campos
        // quedan vacíos, que es lo que mostraba la vista con $direccion?->...
        const domicilio = proveedor.domicilio_fiscal ?? null;

        for (const parte of DIRECCION) {
            const elemento = campoDeDireccion(parte);

            if (elemento) {
                elemento.value = domicilio?.[parte] ?? '';
            }
        }

        actualizarPortalUrl();
    }

    function campo(nombre) {
        return formulario.elements[nombre];
    }

    function campoDeDireccion(parte) {
        return formulario.elements[`direccion[${parte}]`];
    }

    /**
     * Elige una opción del desplegable, agregándola si no estaba.
     *
     * Los desplegables los arma el servidor con el catálogo vigente; si el
     * proveedor apunta a una condición de IVA que ya no figura, sin esto el
     * select quedaría vacío y al guardar se perdería el dato.
     */
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

    function mostrarBotonDeEstado(activo) {
        baja?.classList.toggle('d-none', ! activo);
        reactivar?.classList.toggle('d-none', activo);
    }

    // ------------------------------------------------------------------
    // La URL del portal sólo tiene sentido con un canal
    // ------------------------------------------------------------------

    function actualizarPortalUrl() {
        const contenedor = document.getElementById('portalUrlContainer');
        const canal = campo('canal_pedido');

        if (! contenedor || ! canal) {
            return;
        }

        contenedor.style.display = canal.value === PORTAL_EXTERNO ? '' : 'none';
    }

    formulario.elements.canal_pedido?.addEventListener('change', actualizarPortalUrl);

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

        const razonSocial = campo('razon_social').value.trim();

        try {
            if (esEdicion) {
                await reemplazar(`gestion/proveedores/${id}`, cuerpo());

                volverAlListado('exito', `Se guardaron los cambios en «${razonSocial}».`);
            } else {
                await crear('gestion/proveedores', cuerpo());

                volverAlListado('exito', `Se creó el proveedor «${razonSocial}».`);
            }

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

    /**
     * El cuerpo que se manda, con el domicilio anidado.
     *
     * Las cadenas vacías llegan al backend como null —lo hace el middleware
     * ConvertEmptyStringsToNull, que también limpia el JSON—, que es lo que
     * esperan los campos 'nullable'. 'activo' no viaja nunca.
     */
    function cuerpo() {
        const datos = {};

        for (const nombre of CAMPOS) {
            const elemento = campo(nombre);

            if (elemento) {
                datos[nombre] = elemento.value;
            }
        }

        datos.direccion = {};

        for (const parte of DIRECCION) {
            const elemento = campoDeDireccion(parte);

            if (elemento) {
                datos.direccion[parte] = elemento.value;
            }
        }

        return datos;
    }

    // ------------------------------------------------------------------
    // Baja y reactivación (sólo en la edición)
    // ------------------------------------------------------------------
    //
    // Como antes, no se vuelve al listado: se queda en la pantalla con el
    // proveedor al día, que es lo que hacía el `back()` del controlador.

    baja?.addEventListener('click', () => cambiarEstado(false,
        '¿Está seguro de que desea dar de baja este proveedor?'));

    reactivar?.addEventListener('click', () => cambiarEstado(true,
        '¿Está seguro de que desea dar de alta este proveedor?'));

    async function cambiarEstado(activo, pregunta) {
        if (! window.confirm(pregunta)) {
            return;
        }

        limpiarErrores();
        trabajando(true);

        const razonSocial = guardado?.razon_social ?? 'El proveedor';

        try {
            const respuesta = await parchear(`gestion/proveedores/${id}/estado`, { activo });

            avisos.mostrar(caja, 'exito', activo
                ? `«${razonSocial}» vuelve a estar en operación.`
                : mensajeDeBaja(razonSocial, respuesta.advertencias));

        } catch (error) {
            const mensaje = tratar(error);

            if (mensaje !== null) {
                avisos.mostrar(caja, 'error', mensaje);
            }
        }

        trabajando(false);

        await traerProveedor();
    }

    /**
     * El mismo aviso que daba el panel, armado con los números que manda la
     * API en `advertencias`.
     */
    function mensajeDeBaja(razonSocial, advertencias) {
        let aviso = `«${razonSocial}» salió de operación.`;

        const enReposicion = advertencias?.productos_en_reposicion_automatica ?? 0;
        const abiertas = advertencias?.ordenes_abiertas ?? 0;

        if (enReposicion > 0) {
            aviso += ` ${enReposicion} producto(s) dependían de él para reponerse: asignales otro proveedor.`;
        }

        if (abiertas > 0) {
            aviso += ` Quedan ${abiertas} orden(es) de compra abierta(s), que siguen su curso.`;
        }

        return aviso;
    }

    // ------------------------------------------------------------------
    // El bloqueo de campos de la pantalla de edición
    // ------------------------------------------------------------------
    //
    // Es el mismo de antes —los campos arrancan bloqueados y "Editar" los
    // abre—, pero ahora vive acá junto con el resto de la pantalla en vez de
    // en un <script> suelto dentro de la vista.

    function bloquearCampos(si) {
        bloqueado = si;

        formulario.querySelectorAll('input:not([type="hidden"]), select, textarea')
            .forEach((elemento) => {
                elemento.disabled = si;
            });

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

    editar?.addEventListener('click', () => {
        bloquearCampos(false);
        actualizarPortalUrl();
    });

    cancelar?.addEventListener('click', () => {
        limpiarErrores();

        if (guardado !== null) {
            volcarEnElFormulario(guardado);
        }

        bloquearCampos(true);
    });

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
     * Las claves del domicilio llegan con punto —'direccion.calle'—, que es
     * como las nombra el FormRequest y como se llaman los contenedores de la
     * vista.
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

        for (const [clave, suyos] of Object.entries(porCampo ?? {})) {
            const detalle = document.getElementById(`error-${clave}`);

            if (detalle) {
                detalle.textContent = suyos[0];
            }

            const elemento = clave.startsWith('direccion.')
                ? campoDeDireccion(clave.slice('direccion.'.length))
                : campo(clave);

            elemento?.classList.add('is-invalid');
        }

        errores?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function limpiarErrores() {
        errores?.replaceChildren();
        avisos.limpiar(caja);

        for (const nombre of CAMPOS) {
            document.getElementById(`error-${nombre}`)?.replaceChildren();
            campo(nombre)?.classList.remove('is-invalid');
        }

        for (const parte of DIRECCION) {
            document.getElementById(`error-direccion.${parte}`)?.replaceChildren();
            campoDeDireccion(parte)?.classList.remove('is-invalid');
        }
    }

    // ------------------------------------------------------------------
    // Arranque
    // ------------------------------------------------------------------

    if (esEdicion) {
        bloquearCampos(true);
        traerProveedor();
    } else {
        actualizarPortalUrl();
    }
}
