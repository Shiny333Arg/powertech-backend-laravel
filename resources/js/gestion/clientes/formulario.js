/**
 * Alta y edición de clientes, contra la API.
 *
 * Un solo módulo para las dos pantallas porque son el mismo formulario:
 *
 *     POST /api/v1/gestion/clientes          alta
 *     GET  /api/v1/gestion/clientes/{id}     datos de la edición
 *     PUT  /api/v1/gestion/clientes/{id}     edición
 *
 * <b>Las validaciones no se repiten acá.</b> El documento y su dígito
 * verificador, la unicidad del correo y del número de documento, y la coherencia
 * fiscal —salvo consumidor final, todas las condiciones exigen CUIT— las sigue
 * decidiendo `ClienteRequest`, que es el MISMO FormRequest que usaba el
 * formulario tradicional y que comparte sus reglas con el registro de la tienda
 * a través de `ReglasDatosCliente`. Este archivo manda el formulario y, si
 * vuelve un 422, reparte los mensajes del backend en los mismos lugares donde
 * los pintaba Blade.
 *
 * ──────────────────────────────────────────────────────────────────────────
 * UN CLIENTE SON DOS FILAS
 * ──────────────────────────────────────────────────────────────────────────
 *
 * `users` —con qué correo entra a la tienda— y `clientes` —a quién se le
 * factura—. De ahí los dos bloques del formulario, y de ahí que el bloque de la
 * cuenta pueda no corresponder: el cliente de mostrador viejo existe sin fila en
 * `users`, y en ese caso sólo se editan sus datos de facturación. <b>Quién tiene
 * cuenta y quién no lo dice la API</b>, en `data.cuenta`: la página ya no lo
 * sabe, así que el bloque se muestra o se esconde al llegar el detalle.
 *
 * <b>La contraseña no está acá a propósito.</b> El cliente nace sin ella y la
 * elige él mismo desde el enlace que le llega por correo. Ni esta pantalla ni la
 * API la tocan nunca.
 *
 * <b>El estado no se toca desde el formulario.</b> El alta deja al cliente
 * activo y el PUT no lo cambia aunque viaje: la baja y la reactivación son sus
 * botones en el listado y en la ficha, contra el endpoint de estado, que exige
 * 'cliente.baja' —un permiso que el vendedor y el cajero no tienen—.
 */

import { obtener, crear, reemplazar, tratar, ErrorDeApi } from '../api.js';
import * as avisos from '../avisos.js';

/** Los campos de `users`. Sólo viajan si el cliente tiene cuenta. */
const CAMPOS_CUENTA = ['nombres', 'apellido', 'email'];

/** Los campos de `clientes`. */
const CAMPOS_CLIENTE = ['telefono', 'tipo_documento_id', 'nro_doc', 'condicion_iva_id', 'razon_social'];

const CAMPOS = [...CAMPOS_CUENTA, ...CAMPOS_CLIENTE];

/**
 * @param {object} configuracion
 * @param {number|null} configuracion.id    null en el alta.
 * @param {string} configuracion.urlFicha   Ficha del cliente, con `__ID__`.
 */
export function iniciar({ id = null, urlFicha }) {
    const formulario = document.getElementById('formularioCliente');

    if (! formulario) {
        return;
    }

    const esEdicion = id !== null;

    const caja    = document.getElementById('avisosCliente');
    const errores = document.getElementById('erroresCliente');
    const guardar = document.getElementById('botonGuardar');

    const bloqueCuenta  = document.getElementById('bloqueCuenta');
    const avisoSinCuenta = document.getElementById('avisoSinCuenta');
    const avisoCorreo   = document.getElementById('avisoCambioDeCorreo');
    const subtitulo     = document.getElementById('subtituloCliente');

    /** Lo que dice la API hoy: sirve para los mensajes y para saber si hay cuenta. */
    let guardado = null;

    /** Hasta que llegue el detalle no se sabe; en el alta siempre hay cuenta. */
    let conCuenta = true;

    // ------------------------------------------------------------------
    // Edición: los datos los trae la API, no Blade
    // ------------------------------------------------------------------

    async function traerCliente() {
        trabajando(true);

        try {
            const respuesta = await obtener(`gestion/clientes/${id}`);

            guardado = respuesta.data;

            volcarEnElFormulario(guardado);

        } catch (error) {
            const mensaje = tratar(error);

            if (mensaje !== null) {
                avisos.mostrar(caja, 'error', `No pudimos cargar el cliente: ${mensaje}`);
            }
        }

        trabajando(false);
    }

    function volcarEnElFormulario(cliente) {
        conCuenta = cliente.cuenta !== null && cliente.cuenta !== undefined;

        mostrarBloqueDeCuenta(conCuenta);

        if (conCuenta) {
            campo('nombres').value = cliente.cuenta.nombres ?? '';
            campo('apellido').value = cliente.cuenta.apellido ?? '';
            campo('email').value = cliente.cuenta.email ?? '';
        }

        campo('telefono').value = cliente.telefono ?? '';
        campo('nro_doc').value = cliente.nro_doc ?? '';
        campo('razon_social').value = cliente.razon_social ?? '';

        elegirOpcion(campo('tipo_documento_id'), cliente.tipo_documento?.id, cliente.tipo_documento?.nombre);
        elegirOpcion(campo('condicion_iva_id'), cliente.condicion_iva?.id, cliente.condicion_iva?.nombre);

        // El encabezado de la pantalla de edición: «Razón social · correo».
        if (subtitulo) {
            subtitulo.textContent = [cliente.razon_social, cliente.cuenta?.email]
                .filter((parte) => parte)
                .join(' · ');
        }

        // El aviso de que cambiar el correo desverifica la cuenta sólo tiene
        // sentido si YA la activó: al que todavía no eligió su contraseña se le
        // reenvía la invitación, que es otra cosa. Mismo criterio que tenía el
        // `@if` de la vista.
        avisoCorreo?.classList.toggle(
            'd-none',
            ! conCuenta || cliente.cuenta.invitacion_pendiente === true,
        );
    }

    function campo(nombre) {
        return formulario.elements[nombre];
    }

    /**
     * Muestra el bloque de la cuenta o el aviso de que no hay ninguna.
     *
     * Los campos de `users` se deshabilitan además de esconderse: un control
     * `disabled` no se envía ni participa de `reportValidity()`, así que el
     * `required` del correo no bloquea el guardado de un cliente de mostrador
     * que no tiene correo que completar.
     */
    function mostrarBloqueDeCuenta(si) {
        bloqueCuenta?.classList.toggle('d-none', ! si);
        avisoSinCuenta?.classList.toggle('d-none', si);

        for (const nombre of CAMPOS_CUENTA) {
            const elemento = campo(nombre);

            if (elemento) {
                elemento.disabled = ! si;
            }
        }
    }

    /**
     * Elige una opción del desplegable, agregándola si no estaba.
     *
     * Los desplegables los arma el servidor con el catálogo vigente y la vista
     * sólo ofrece DNI y CUIT. Si el cliente apunta a un tipo que ya no figura
     * —un CUIL o un "sin identificar" de cuando se aceptaban—, sin esto el
     * navegador mostraría la primera opción como si fuera la suya y guardar le
     * cambiaría el documento sin que nadie lo haya pedido. Es exactamente lo que
     * evitaba el `$documentos->push($suyo)` de la vista.
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

        try {
            esEdicion
                ? await editar()
                : await darDeAlta();

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
     * El alta termina en la ficha del cliente recién creado, igual que el
     * `redirect()->route('gestion.clientes.show', $cliente)` del panel.
     *
     * <b>Que el correo no haya salido no tira el alta abajo.</b> El endpoint
     * contesta 201 con `invitacion.enviada` en false y la pantalla avisa para
     * reenviarlo desde la ficha: es el mismo criterio del controlador, que
     * redirigía con el cliente creado y un error al lado.
     */
    async function darDeAlta() {
        const respuesta = await crear('gestion/clientes', cuerpo());

        const cliente = respuesta.data;

        respuesta.invitacion?.enviada === false
            ? avisos.recordar('error', `El cliente quedó creado, pero no se pudo enviar el correo a `
                + `${cliente.cuenta?.email}. Reenviá la invitación desde su ficha.`)
            : avisos.recordar('exito', `Cliente «${cliente.razon_social}» creado. `
                + 'Ya se le puede vender y facturar.');

        irALaFicha(cliente.id);
    }

    /**
     * La edición también vuelve a la ficha, como antes.
     *
     * El aviso de la casilla nueva lo escribe la pantalla con el dato que manda
     * la API en `correo.cambio`: la API manda el hecho, el texto es
     * presentación. Es el mismo criterio que `advertencias` en la baja del
     * proveedor.
     */
    async function editar() {
        const respuesta = await reemplazar(`gestion/clientes/${id}`, cuerpo());

        const cliente = respuesta.data;

        avisos.recordar('exito', respuesta.correo?.cambio === true
            ? `Cliente actualizado. Le mandamos un correo a ${cliente.cuenta?.email} `
                + 'para que confirme la casilla nueva.'
            : 'Cliente actualizado.');

        irALaFicha(id);
    }

    /**
     * El cuerpo que se manda.
     *
     * Las cadenas vacías llegan al backend como null —lo hace el middleware
     * ConvertEmptyStringsToNull, que también limpia el JSON—, que es lo que
     * esperan los campos 'nullable'. 'activo' no viaja nunca.
     *
     * Los campos de la cuenta se omiten cuando el cliente no tiene una:
     * `ClienteRequest::tieneCuenta()` ni los valida ni los escribe en ese caso,
     * y mandarlos en null haría que la regla de 'required' los rechazara.
     */
    function cuerpo() {
        const datos = {};

        const nombres = conCuenta ? CAMPOS : CAMPOS_CLIENTE;

        for (const nombre of nombres) {
            const elemento = campo(nombre);

            if (elemento) {
                datos[nombre] = elemento.value;
            }
        }

        return datos;
    }

    function irALaFicha(idDelCliente) {
        window.location.assign(urlFicha.replace('__ID__', String(idDelCliente)));
    }

    // ------------------------------------------------------------------
    // Pantalla
    // ------------------------------------------------------------------

    /** Deshabilita el botón de guardar mientras hay una llamada en curso. */
    function trabajando(si) {
        if (guardar) {
            guardar.disabled = si;
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

        for (const [clave, suyos] of Object.entries(porCampo ?? {})) {
            const detalle = document.getElementById(`error-${clave}`);

            if (detalle) {
                detalle.textContent = suyos[0];
            }

            campo(clave)?.classList.add('is-invalid');
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
    }

    // ------------------------------------------------------------------
    // Arranque
    // ------------------------------------------------------------------

    if (esEdicion) {
        traerCliente();
    } else {
        // En el alta siempre hay cuenta: toda cuenta nueva nace con su fila en
        // `users`, y el cliente de mostrador sin cuenta es justamente lo que
        // esta pantalla ya no crea.
        mostrarBloqueDeCuenta(true);
    }
}
