/**
 * Ficha del cliente, contra la API.
 *
 * Misma pantalla de siempre —encabezado, historial de compras, direcciones,
 * contacto y estado de la cuenta—; lo que cambió es de dónde salen los datos:
 *
 *     GET    /api/v1/gestion/clientes/{id}                       la ficha
 *     GET    /api/v1/gestion/clientes/{id}/ventas?page=          el historial
 *     PATCH  /api/v1/gestion/clientes/{id}/estado                baja / reactivación
 *     POST   /api/v1/gestion/clientes/{id}/invitacion            reenviar el enlace
 *     POST   /api/v1/gestion/clientes/{id}/direcciones           agregar
 *     DELETE /api/v1/gestion/clientes/{id}/direcciones/{dir}     eliminar
 *
 * <b>Acá no hay ninguna regla de negocio.</b> Si la invitación se puede
 * reenviar, si la baja corresponde y qué significa cada estado de la cuenta lo
 * decide el backend; este archivo pinta lo que la API contesta y muestra el
 * mensaje que manda cuando dice que no.
 *
 * ──────────────────────────────────────────────────────────────────────────
 * EL ESTADO DE LA CUENTA
 * ──────────────────────────────────────────────────────────────────────────
 *
 * Es lo propio de esta ficha y sale de tres datos que manda el detalle en
 * `cuenta`: si existe, si está activa, si la invitación sigue pendiente y si el
 * correo está verificado. La frase de cada caso es la que escribía
 * `partials/acceso.blade.php`, y lo que dice vale SÓLO para la tienda online:
 * ninguno de estos estados frena una venta, un cobro ni una factura de
 * mostrador, para lo que alcanza con que el cliente exista y tenga sus datos
 * fiscales.
 *
 * ──────────────────────────────────────────────────────────────────────────
 * EL HISTORIAL DE COMPRAS
 * ──────────────────────────────────────────────────────────────────────────
 *
 * Se pide aparte porque se pagina solo, de diez en diez como antes. Va bajo
 * 'cliente.ver' —el permiso con el que el panel ya mostraba esa tabla—, pero el
 * enlace de cada fila lleva al detalle de la venta, que sigue exigiendo
 * 'venta.ver': eso no cambió.
 */

import { obtener, crear, parchear, eliminar, tratar, ErrorDeApi } from '../api.js';
import * as avisos from '../avisos.js';

/** Los campos del formulario de alta de dirección. */
const CAMPOS_DIRECCION = [
    'tipo', 'calle', 'numero', 'piso_depto',
    'localidad', 'provincia', 'codigo_postal', 'es_predeterminada',
];

/**
 * El color de cada estado, igual que el `match` de
 * `resources/views/gestion/partials/estado.blade.php`: gris espera, amarillo
 * pide una acción, azul avanza, verde cerrado, rojo cancelado.
 */
const COLOR_DE_ESTADO = {
    presupuesto: 'secondary',
    confirmada: 'warning',
    lista_retiro: 'warning',
    pagada: 'info',
    pendiente_stock: 'danger',
    en_preparacion: 'primary',
    despachada: 'primary',
    entregada: 'success',
    devuelta: 'warning',
    devuelta_parcial: 'warning',
    cancelada: 'danger',
    vencida: 'danger',
};

/**
 * @param {object} configuracion
 * @param {number} configuracion.id
 * @param {{crear: boolean, editar: boolean, baja: boolean}} configuracion.permisos
 *        Los resuelve ClientePolicy en el servidor; acá sólo deciden qué se
 *        dibuja. El permiso de verdad lo exige la API en cada llamada.
 * @param {string} configuracion.urlEdicion      Edición de este cliente.
 * @param {string} configuracion.urlVenta        Detalle de una venta, con `__ID__`.
 * @param {number} configuracion.horasDeVigencia Las del enlace de invitación.
 */
export function iniciar({ id, permisos, urlEdicion, urlVenta, horasDeVigencia }) {
    const caja = document.getElementById('avisosCliente');

    // Encabezado
    const razonSocial = document.getElementById('fichaRazonSocial');
    const insigniaBaja = document.getElementById('fichaInsigniaBaja');
    const documento = document.getElementById('fichaDocumento');
    const avisoFuera = document.getElementById('avisoFueraDeOperacion');

    // Botonera
    const botonEditar = document.getElementById('botonEditar');
    const botonBaja = document.getElementById('botonBaja');
    const botonReactivar = document.getElementById('botonReactivar');

    // Contacto y cuenta
    const email = document.getElementById('fichaEmail');
    const telefono = document.getElementById('fichaTelefono');
    const estadoCuenta = document.getElementById('estadoCuenta');
    const detalleCuenta = document.getElementById('detalleCuenta');

    // Direcciones
    const listaDirecciones = document.getElementById('listaDirecciones');
    const sinDirecciones = document.getElementById('sinDirecciones');
    const formularioDireccion = document.getElementById('formularioDireccion');
    const erroresDireccion = document.getElementById('erroresDireccion');

    // Historial
    const cuerpoVentas = document.getElementById('contenidoTablaVentas');
    const paginadoVentas = document.getElementById('paginadoVentas');
    const resumenVentas = document.getElementById('resumenVentas');

    /** Lo que dice la API hoy. */
    let cliente = null;

    avisos.mostrarPendiente(caja);

    // ------------------------------------------------------------------
    // La ficha
    // ------------------------------------------------------------------

    async function traerCliente() {
        try {
            const respuesta = await obtener(`gestion/clientes/${id}`);

            cliente = respuesta.data;

            pintarCliente(cliente);

        } catch (error) {
            const mensaje = tratar(error);

            if (mensaje !== null) {
                avisos.mostrar(caja, 'error', `No pudimos cargar el cliente: ${mensaje}`);
            }
        }
    }

    function pintarCliente(datos) {
        if (razonSocial) {
            razonSocial.textContent = datos.razon_social ?? '';
        }

        insigniaBaja?.classList.toggle('d-none', datos.activo !== false);
        avisoFuera?.classList.toggle('d-none', datos.activo !== false);

        if (documento) {
            // «DNI 27123456 · Consumidor Final», como lo armaba la vista.
            documento.textContent = [
                [datos.tipo_documento?.nombre, datos.nro_doc].filter((p) => p).join(' '),
                datos.condicion_iva?.nombre,
            ].filter((parte) => parte).join(' · ');
        }

        if (botonEditar) {
            botonEditar.href = urlEdicion;
            botonEditar.classList.toggle('d-none', ! permisos.editar);
        }

        // Un solo botón a la vez, el que corresponde al estado. Si el rol no
        // tiene 'cliente.baja' no se dibuja ninguno: es el caso del vendedor y
        // del cajero.
        botonBaja?.classList.toggle('d-none', ! permisos.baja || datos.activo !== true);
        botonReactivar?.classList.toggle('d-none', ! permisos.baja || datos.activo !== false);

        pintarContacto(datos);
        pintarCuenta(datos.cuenta ?? null);
        pintarDirecciones(datos.direcciones ?? []);
    }

    function pintarContacto(datos) {
        if (email) {
            // El cliente de mostrador sin cuenta no tiene correo: la línea
            // desaparece, como hacía el `@if ($cliente->user)` de la vista.
            email.textContent = datos.cuenta?.email ?? '';
            email.classList.toggle('d-none', ! datos.cuenta?.email);
        }

        if (telefono) {
            telefono.textContent = datos.telefono || 'Sin teléfono cargado';
            telefono.classList.toggle('text-muted', ! datos.telefono);
        }
    }

    // ------------------------------------------------------------------
    // La cuenta en la tienda
    // ------------------------------------------------------------------

    /**
     * El badge, el botón de reenviar y la línea de detalle, con los mismos
     * textos que `partials/acceso.blade.php`.
     */
    function pintarCuenta(cuenta) {
        if (! estadoCuenta) {
            return;
        }

        const insignia = document.createElement('span');

        const { clase, texto, detalle } = estadoDeLaCuenta(cuenta);

        insignia.className = clase;
        insignia.textContent = texto;

        estadoCuenta.replaceChildren(insignia);

        // Reenviar sólo tiene sentido si la cuenta está activa y la invitación
        // sigue pendiente: una vez elegida la contraseña, el camino es
        // "¿Olvidaste tu contraseña?". Lo mismo decidía la vista, y el endpoint
        // lo vuelve a decidir con un 422.
        if (cuenta?.activo && cuenta.invitacion_pendiente && permisos.editar) {
            const boton = document.createElement('button');

            boton.type = 'button';
            boton.id = 'botonReenviarInvitacion';
            boton.className = 'btn btn-sm btn-outline-primary';
            boton.textContent = 'Reenviar invitación';

            boton.addEventListener('click', () => reenviarInvitacion(boton));

            estadoCuenta.append(boton);
        }

        if (detalleCuenta) {
            detalleCuenta.textContent = detalle;
        }
    }

    /**
     * Los cinco estados posibles, en el mismo orden de precedencia que tenía la
     * vista: sin cuenta, acceso bloqueado, invitación pendiente, correo sin
     * verificar, activa.
     */
    function estadoDeLaCuenta(cuenta) {
        if (! cuenta) {
            return {
                clase: 'badge text-bg-light border',
                texto: 'Sin cuenta',
                detalle: 'Cliente de mostrador: se le puede vender y facturar, pero no tiene con qué '
                    + 'entrar a la tienda. Si más adelante se registra, la cuenta se vincula a esta '
                    + 'ficha en lugar de duplicarla.',
            };
        }

        if (! cuenta.activo) {
            // La cuenta se inhabilita junto con la baja del cliente, en la
            // misma transacción, así que este estado y "Dado de baja" van
            // siempre juntos y la frase no puede prometer mostrador.
            return {
                clase: 'badge text-bg-secondary',
                texto: 'Acceso bloqueado',
                detalle: 'No puede ingresar ni comprar online, y sus tokens de la app quedaron revocados. '
                    + 'Se inhabilitó junto con la baja del cliente: al reactivarlo recupera el acceso.',
            };
        }

        if (cuenta.invitacion_pendiente) {
            return {
                clase: 'badge text-bg-warning',
                texto: 'Invitación pendiente',
                detalle: `Todavía no eligió su contraseña. El enlace que recibió en ${cuenta.email} vence a las `
                    + `${horasDeVigencia} horas; si se le venció, reenviáselo. Mientras tanto se le puede `
                    + 'vender y facturar con normalidad.',
            };
        }

        if (! cuenta.email_verificado) {
            return {
                clase: 'badge text-bg-warning',
                texto: 'Correo sin verificar',
                detalle: `Le cambiaron el correo y todavía no confirmó ${cuenta.email}. Hasta que lo haga `
                    + 'no puede comprar online, pero sí desde el mostrador.',
            };
        }

        return {
            clase: 'badge text-bg-success',
            texto: 'Activa',
            detalle: `Ingresa con ${cuenta.email}. Si olvida la contraseña, la recupera desde `
                + '"¿Olvidaste tu contraseña?" en la pantalla de ingreso.',
        };
    }

    async function reenviarInvitacion(boton) {
        boton.disabled = true;

        try {
            const respuesta = await crear(`gestion/clientes/${id}/invitacion`, {});

            avisos.mostrar(caja, 'exito', `Le reenviamos la invitación a ${respuesta.data?.email ?? ''}.`.trim());

        } catch (error) {
            // El 422 de "ya activó su cuenta" trae su mensaje: se muestra tal
            // cual, igual que hacía el `withErrors(['estado' => ...])`.
            const mensaje = error instanceof ErrorDeApi && error.estado === 422
                ? error.message
                : tratar(error);

            if (mensaje !== null) {
                avisos.mostrar(caja, 'error', mensaje);
            }

        } finally {
            boton.disabled = false;
        }

        // La ficha se repinta: si el estado de la cuenta cambió mientras tanto,
        // el botón desaparece solo.
        await traerCliente();
    }

    // ------------------------------------------------------------------
    // Baja y reactivación
    // ------------------------------------------------------------------
    //
    // Como antes, no se vuelve al listado: se queda en la ficha con el cliente
    // al día, que es lo que hacía el `back()` del controlador.

    botonBaja?.addEventListener('click', () => cambiarEstado(false,
        '¿Dar de baja este cliente? Su cuenta queda inhabilitada: no va a poder '
            + 'ingresar ni comprar online, y se le revocan los tokens de la app.'));

    botonReactivar?.addEventListener('click', () => cambiarEstado(true,
        '¿Reactivar este cliente? Vuelve a la operación y recupera el acceso a su cuenta.'));

    async function cambiarEstado(activo, pregunta) {
        if (! window.confirm(pregunta)) {
            return;
        }

        bloquearBotonera(true);

        try {
            await parchear(`gestion/clientes/${id}/estado`, { activo });

            // Los mismos textos que dejaba el `with('status', ...)` del panel.
            avisos.mostrar(caja, 'exito', activo ? 'Cliente reactivado.' : 'Cliente dado de baja.');

        } catch (error) {
            const mensaje = tratar(error);

            if (mensaje !== null) {
                avisos.mostrar(caja, 'error', mensaje);
            }
        }

        bloquearBotonera(false);

        await traerCliente();
    }

    function bloquearBotonera(si) {
        for (const boton of [botonBaja, botonReactivar]) {
            if (boton) {
                boton.disabled = si;
            }
        }
    }

    // ------------------------------------------------------------------
    // Direcciones
    // ------------------------------------------------------------------

    function pintarDirecciones(direcciones) {
        if (! listaDirecciones) {
            return;
        }

        sinDirecciones?.classList.toggle('d-none', direcciones.length > 0);
        listaDirecciones.classList.toggle('d-none', direcciones.length === 0);

        listaDirecciones.replaceChildren(...direcciones.map(itemDeDireccion));
    }

    /** Un `<li>` con el mismo marcado que tenía el `@foreach`. */
    function itemDeDireccion(direccion) {
        const item = document.createElement('li');
        item.className = 'list-group-item d-flex flex-wrap justify-content-between align-items-start gap-3';

        const bloque = document.createElement('div');

        const tipo = document.createElement('span');
        tipo.className = 'badge text-bg-light border';
        tipo.textContent = direccion.tipo_etiqueta ?? direccion.tipo ?? '';
        bloque.append(tipo);

        if (direccion.es_predeterminada) {
            const predeterminada = document.createElement('span');

            predeterminada.className = 'text-muted small';
            predeterminada.textContent = ' Predeterminada';

            bloque.append(predeterminada);
        }

        const domicilio = document.createElement('address');
        domicilio.className = 'mb-0 mt-1';

        const primeraLinea = [direccion.calle, direccion.numero].filter((p) => p).join(' ')
            + (direccion.piso_depto ? ` (${direccion.piso_depto})` : '');

        const segundaLinea = `${[direccion.localidad, direccion.provincia].filter((p) => p).join(', ')}`
            + (direccion.codigo_postal ? ` (${direccion.codigo_postal})` : '');

        domicilio.append(
            document.createTextNode(primeraLinea),
            document.createElement('br'),
            document.createTextNode(segundaLinea),
        );

        bloque.append(domicilio);
        item.append(bloque);

        if (permisos.editar) {
            const borrar = document.createElement('button');

            borrar.type = 'button';
            borrar.className = 'btn btn-sm btn-outline-danger';
            borrar.title = 'Eliminar dirección';
            borrar.textContent = 'Eliminar';

            borrar.addEventListener('click', () => borrarDireccion(direccion, borrar));

            item.append(borrar);
        }

        return item;
    }

    async function borrarDireccion(direccion, boton) {
        if (! window.confirm('¿Está seguro de eliminar esta dirección?')) {
            return;
        }

        boton.disabled = true;

        try {
            await eliminar(`gestion/clientes/${id}/direcciones/${direccion.id}`);

            avisos.mostrar(caja, 'exito', 'Dirección eliminada.');

        } catch (error) {
            boton.disabled = false;

            const mensaje = tratar(error);

            if (mensaje !== null) {
                avisos.mostrar(caja, 'error', mensaje);
            }

            return;
        }

        await traerCliente();
    }

    formularioDireccion?.addEventListener('submit', async (evento) => {
        evento.preventDefault();

        if (! formularioDireccion.reportValidity()) {
            return;
        }

        limpiarErroresDeDireccion();

        const guardar = formularioDireccion.querySelector('button[type="submit"]');

        if (guardar) {
            guardar.disabled = true;
        }

        try {
            await crear(`gestion/clientes/${id}/direcciones`, cuerpoDeLaDireccion());

            formularioDireccion.reset();

            avisos.mostrar(caja, 'exito', 'Dirección agregada.');

            await traerCliente();

        } catch (error) {
            if (error instanceof ErrorDeApi && error.estado === 422) {
                mostrarErroresDeDireccion(error.errores);
            } else {
                const mensaje = tratar(error);

                if (mensaje !== null) {
                    avisos.mostrar(caja, 'error', mensaje);
                }
            }

        } finally {
            if (guardar) {
                guardar.disabled = false;
            }
        }
    });

    /**
     * El cuerpo del alta de dirección.
     *
     * `es_predeterminada` viaja como booleano y no como el "1" del casillero:
     * es lo que ya hacía `DireccionRequest::prepareForValidation()` con
     * `boolean()`, y mandarlo así deja la regla intacta.
     */
    function cuerpoDeLaDireccion() {
        const datos = {};

        for (const nombre of CAMPOS_DIRECCION) {
            const elemento = formularioDireccion.elements[nombre];

            if (! elemento) {
                continue;
            }

            datos[nombre] = elemento.type === 'checkbox' ? elemento.checked : elemento.value;
        }

        return datos;
    }

    function mostrarErroresDeDireccion(porCampo) {
        const mensajes = Object.values(porCampo ?? {}).flat();

        if (erroresDireccion && mensajes.length > 0) {
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
            erroresDireccion.replaceChildren(alerta);
        }

        for (const [clave, suyos] of Object.entries(porCampo ?? {})) {
            const detalle = document.getElementById(`error-direccion-${clave}`);

            if (detalle) {
                detalle.textContent = suyos[0];
            }

            formularioDireccion.elements[clave]?.classList.add('is-invalid');
        }
    }

    function limpiarErroresDeDireccion() {
        erroresDireccion?.replaceChildren();

        for (const nombre of CAMPOS_DIRECCION) {
            document.getElementById(`error-direccion-${nombre}`)?.replaceChildren();
            formularioDireccion?.elements[nombre]?.classList.remove('is-invalid');
        }
    }

    // ------------------------------------------------------------------
    // Historial de compras
    // ------------------------------------------------------------------

    async function traerVentas(pagina = 1) {
        if (! cuerpoVentas) {
            return;
        }

        mostrarMensajeEnVentas('Cargando compras…');

        try {
            const respuesta = await obtener(
                `gestion/clientes/${id}/ventas`,
                pagina > 1 ? { page: pagina } : {},
            );

            pintarVentas(respuesta.data);
            pintarResumenDeVentas(respuesta.meta);
            pintarPaginadoDeVentas(respuesta.meta);

        } catch (error) {
            const mensaje = tratar(error);

            if (mensaje !== null) {
                mostrarMensajeEnVentas('No se pudo cargar el historial.');
                avisos.mostrar(caja, 'error', mensaje);
            }
        }
    }

    function mostrarMensajeEnVentas(texto) {
        const fila = document.createElement('tr');
        const celda = document.createElement('td');

        celda.colSpan = 4;
        celda.className = 'text-center py-4 text-muted';
        celda.textContent = texto;

        fila.append(celda);
        cuerpoVentas.replaceChildren(fila);

        paginadoVentas?.replaceChildren();
    }

    function pintarVentas(ventas) {
        if (ventas.length === 0) {
            mostrarMensajeEnVentas('Todavía no compró nada.');

            return;
        }

        cuerpoVentas.replaceChildren(...ventas.map(filaDeVenta));
    }

    /** Una fila del historial, con el mismo marcado que tenía el `@forelse`. */
    function filaDeVenta(venta) {
        const tr = document.createElement('tr');

        // Número, con el enlace al detalle de la venta. El detalle sigue
        // exigiendo 'venta.ver': que el enlace esté no significa que entre.
        const celdaNumero = document.createElement('td');
        celdaNumero.className = 'text-nowrap fw-semibold';

        const enlace = document.createElement('a');
        enlace.href = urlVenta.replace('__ID__', String(venta.id));
        enlace.textContent = venta.numero_formateado ?? venta.numero ?? '';
        celdaNumero.append(enlace);

        const celdaFecha = document.createElement('td');
        celdaFecha.className = 'text-nowrap';
        celdaFecha.textContent = fechaCorta(venta.fecha);

        const celdaEstado = document.createElement('td');
        const insignia = document.createElement('span');
        insignia.className = `badge text-bg-${COLOR_DE_ESTADO[venta.estado] ?? 'secondary'} text-nowrap`;
        insignia.textContent = venta.estado_etiqueta ?? venta.estado ?? '';
        celdaEstado.append(insignia);

        const celdaTotal = document.createElement('td');
        celdaTotal.className = 'text-nowrap fw-semibold';
        celdaTotal.textContent = pesos(venta.total);

        tr.append(celdaNumero, celdaFecha, celdaEstado, celdaTotal);

        return tr;
    }

    /** `d/m/Y`, como el `format('d/m/Y')` de la vista. */
    function fechaCorta(iso) {
        if (! iso) {
            return '';
        }

        const fecha = new Date(iso);

        if (Number.isNaN(fecha.getTime())) {
            return '';
        }

        const dia = String(fecha.getDate()).padStart(2, '0');
        const mes = String(fecha.getMonth() + 1).padStart(2, '0');

        return `${dia}/${mes}/${fecha.getFullYear()}`;
    }

    /** `$ 1.234,56`, como el `number_format($total, 2, ',', '.')` de la vista. */
    function pesos(monto) {
        const numero = Number(monto ?? 0);

        return `$ ${numero.toLocaleString('es-AR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        })}`;
    }

    /** «Mostrando 1–10 de 23», como lo escribía la vista con el paginador. */
    function pintarResumenDeVentas(meta) {
        if (! resumenVentas) {
            return;
        }

        const total = meta?.total ?? 0;

        if (total === 0) {
            resumenVentas.textContent = '';
            resumenVentas.classList.add('d-none');

            return;
        }

        resumenVentas.classList.remove('d-none');
        resumenVentas.textContent = `Mostrando ${meta.from}–${meta.to} de ${total}`;
    }

    function pintarPaginadoDeVentas(meta) {
        if (! paginadoVentas) {
            return;
        }

        const ultima = meta?.last_page ?? 1;
        const actual = meta?.current_page ?? 1;

        if (ultima <= 1) {
            paginadoVentas.replaceChildren();

            return;
        }

        const lista = document.createElement('ul');
        lista.className = 'pagination';

        for (let numero = 1; numero <= ultima; numero++) {
            const item = document.createElement('li');

            item.className = `page-item${numero === actual ? ' active' : ''}`;

            if (numero === actual) {
                const span = document.createElement('span');

                span.className = 'page-link';
                span.textContent = String(numero);

                item.setAttribute('aria-current', 'page');
                item.append(span);
            } else {
                const enlace = document.createElement('a');

                enlace.className = 'page-link';
                enlace.textContent = String(numero);
                // El historial se pagina sin tocar la URL de la ficha: antes
                // viajaba en el parámetro 'compras' con un fragmento, y acá no
                // hay recarga de la que volver.
                enlace.href = '#compras';

                enlace.addEventListener('click', (evento) => {
                    evento.preventDefault();

                    traerVentas(numero);
                });

                item.append(enlace);
            }

            lista.append(item);
        }

        const navegacion = document.createElement('nav');
        navegacion.append(lista);

        paginadoVentas.replaceChildren(navegacion);
    }

    // ------------------------------------------------------------------
    // Arranque
    // ------------------------------------------------------------------

    traerCliente();
    traerVentas();
}
