/**
 * Listado de clientes del panel, contra la API.
 *
 * Misma pantalla de siempre —el mismo encabezado, los mismos dos filtros, la
 * misma tabla de seis columnas y el mismo paginado—; lo que cambió es de dónde
 * salen los datos:
 *
 *     GET   /api/v1/gestion/clientes?q=&activo=&page=
 *     PATCH /api/v1/gestion/clientes/{id}/estado    baja y reactivación
 *
 * No hay DELETE y no es un olvido: el cliente está referenciado por ventas y
 * por comprobantes emitidos —la factura dice a quién se le vendió—, así que la
 * única baja es `activo = false`.
 *
 * <b>Acá no hay ninguna regla de negocio.</b> Quién puede editar y quién puede
 * dar de baja lo decide el backend; este archivo pinta lo que la API contesta y
 * muestra el mensaje que manda cuando dice que no.
 *
 * ──────────────────────────────────────────────────────────────────────────
 * LOS BOTONES POR PERMISO, QUE ACÁ NO SON DECORATIVOS
 * ──────────────────────────────────────────────────────────────────────────
 *
 * En proveedores los dos roles que entran a la pantalla tienen los cuatro
 * permisos, así que esconder botones no cambiaba nada en la práctica. En
 * clientes sí: según la matriz de PermisoSeeder, el vendedor y el cajero tienen
 * 'cliente.ver', 'cliente.crear' y 'cliente.editar' y NO tienen 'cliente.baja'.
 * Con esos roles la columna de opciones sale sin los botones de baja y
 * reactivación. El permiso de verdad lo sigue exigiendo la API en cada llamada.
 */

import { obtener, parchear, tratar } from '../api.js';
import * as avisos from '../avisos.js';

/** El mismo tamaño de página que tenía el `paginate(25)` del controlador web. */
const POR_PAGINA = 25;

const ICONOS = {
    ver: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16"><path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"/><path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0"/></svg>',
    editar: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil" viewBox="0 0 16 16"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325"/></svg>',
    baja: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14zM4.5 7.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1 0-1z"/></svg>',
    reactivar: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 3a5 5 0 1 1-4.546 2.917.5.5 0 0 1 .91-.414A4 4 0 1 0 8 4z"/><path d="M8 1v4H4"/></svg>',
};

/**
 * @param {object} configuracion
 * @param {{crear: boolean, editar: boolean, baja: boolean}} configuracion.permisos
 *        Los resuelve ClientePolicy en el servidor; acá sólo deciden qué
 *        botones se dibujan. Quien llame igual al endpoint recibe un 403.
 * @param {string} configuracion.urlFicha    Ruta de la ficha con `__ID__`.
 * @param {string} configuracion.urlEdicion  Ruta de edición con `__ID__`.
 */
export function iniciar({ permisos, urlFicha, urlEdicion }) {
    const caja       = document.getElementById('avisosClientes');
    const formulario = document.getElementById('formFiltros');
    const cuerpo     = document.getElementById('contenidoTablaClientes');
    const paginado   = document.getElementById('paginadoClientes');

    if (! cuerpo) {
        return;
    }

    const columnas = document.querySelectorAll('#tablaClientes thead th').length;

    /** Evita que una respuesta lenta pise a una pedida después. */
    let pedidoActual = 0;

    avisos.mostrarPendiente(caja);

    // ------------------------------------------------------------------
    // La URL es el estado de la pantalla
    // ------------------------------------------------------------------
    //
    // Los nombres de los parámetros son los que tenía el formulario GET del
    // panel —`q` y `activo`—, así que una URL filtrada que alguien haya
    // guardado sigue abriendo el mismo listado.

    function filtrosDeLaUrl() {
        const parametros = new URLSearchParams(window.location.search);

        return {
            q: parametros.get('q') ?? '',
            activo: parametros.get('activo') ?? '',
            page: Number.parseInt(parametros.get('page') ?? '1', 10) || 1,
        };
    }

    function url(filtros) {
        const parametros = new URLSearchParams();

        for (const clave of ['q', 'activo']) {
            if (filtros[clave] !== '') {
                parametros.set(clave, filtros[clave]);
            }
        }

        if (filtros.page > 1) {
            parametros.set('page', String(filtros.page));
        }

        const consulta = parametros.toString();

        return consulta === '' ? window.location.pathname : `?${consulta}`;
    }

    function escribirEnLaUrl(filtros) {
        window.history.pushState({}, '', url(filtros));
    }

    function sincronizarControles(filtros) {
        if (! formulario) {
            return;
        }

        formulario.elements.q.value = filtros.q;
        formulario.elements.activo.value = filtros.activo;
    }

    // ------------------------------------------------------------------
    // Traer y pintar
    // ------------------------------------------------------------------

    async function cargar(filtros) {
        const pedido = ++pedidoActual;

        mostrarMensajeEnLaTabla('Cargando clientes…');

        try {
            const respuesta = await obtener('gestion/clientes', parametrosDe(filtros));

            if (pedido !== pedidoActual) {
                return; // llegó tarde: ya hay otra consulta en curso
            }

            const ultima = respuesta.meta?.last_page ?? 1;

            // La página quedó más allá del final: en vez de mostrar una tabla
            // vacía, se vuelve a la última con filas. Pasa al dar de baja el
            // último cliente de la página con el filtro de situación puesto.
            if (respuesta.data.length === 0 && filtros.page > ultima && ultima >= 1) {
                const corregidos = { ...filtros, page: ultima };

                escribirEnLaUrl(corregidos);

                return cargar(corregidos);
            }

            pintarFilas(respuesta.data);
            pintarPaginado(respuesta.meta, filtros);

        } catch (error) {
            if (pedido !== pedidoActual) {
                return;
            }

            const mensaje = tratar(error);

            if (mensaje !== null) {
                mostrarMensajeEnLaTabla('No se pudo cargar el listado.');
                avisos.mostrar(caja, 'error', mensaje);
            }
        }
    }

    /**
     * Sólo los filtros que tienen valor.
     *
     * Un `activo=` vacío no es un filtro: el endpoint lo valida como booleano
     * opcional de tres estados y sin el parámetro muestra activos e inactivos,
     * que es lo que hace falta en la pantalla desde donde se reactiva.
     */
    function parametrosDe(filtros) {
        const parametros = { por_pagina: POR_PAGINA };

        for (const clave of ['q', 'activo']) {
            if (filtros[clave] !== '') {
                parametros[clave] = filtros[clave];
            }
        }

        if (filtros.page > 1) {
            parametros.page = filtros.page;
        }

        return parametros;
    }

    function mostrarMensajeEnLaTabla(texto) {
        const fila  = document.createElement('tr');
        const celda = document.createElement('td');

        celda.colSpan = columnas;
        celda.className = 'text-center py-4 text-muted';
        celda.textContent = texto;

        fila.append(celda);
        cuerpo.replaceChildren(fila);

        paginado?.replaceChildren();
    }

    function pintarFilas(clientes) {
        if (clientes.length === 0) {
            mostrarMensajeEnLaTabla('No se encontraron clientes.');

            return;
        }

        cuerpo.replaceChildren(...clientes.map(fila));
    }

    /** Una fila, con el mismo marcado que tenía el `@forelse`. */
    function fila(cliente) {
        const tr = document.createElement('tr');

        tr.append(
            celdaRazonSocial(cliente),
            celdaDeDocumento(cliente),
            celdaDeTexto(cliente.condicion_iva?.nombre ?? ''),
            // El guion largo es el que mostraba la vista cuando no hay teléfono.
            celdaDeTexto(cliente.telefono || '—'),
            celdaDeEstado(cliente),
            celdaDeAcciones(cliente),
        );

        return tr;
    }

    function celdaDeTexto(texto) {
        const celda = document.createElement('td');

        celda.textContent = texto;

        return celda;
    }

    function celdaRazonSocial(cliente) {
        const celda = document.createElement('td');

        celda.className = 'text-start';
        celda.textContent = cliente.razon_social ?? '';

        return celda;
    }

    /**
     * «DNI 27123456», como lo armaba la vista con el nombre del tipo y el
     * número. El tipo lo manda el recurso: así el panel y la API lo nombran
     * igual.
     */
    function celdaDeDocumento(cliente) {
        const celda = document.createElement('td');

        celda.className = 'text-nowrap';
        celda.textContent = [cliente.tipo_documento?.nombre, cliente.nro_doc]
            .filter((parte) => parte)
            .join(' ');

        return celda;
    }

    function celdaDeEstado(cliente) {
        const celda = document.createElement('td');
        const insignia = document.createElement('span');

        insignia.className = cliente.activo ? 'badge text-bg-success' : 'badge text-bg-secondary';
        insignia.textContent = cliente.activo ? 'Activo' : 'Inactivo';

        celda.append(insignia);

        return celda;
    }

    /**
     * La columna de opciones.
     *
     * Siempre tiene al menos "Ver ficha": llegar a esta pantalla ya exige
     * 'cliente.ver', que es el mismo permiso que la ficha, así que ese botón no
     * va detrás de un permiso —tampoco iba en la vista—.
     */
    function celdaDeAcciones(cliente) {
        const celda = document.createElement('td');
        celda.className = 'text-nowrap';

        const ver = document.createElement('a');

        ver.href = urlFicha.replace('__ID__', String(cliente.id));
        ver.className = 'btn btn-sm btn-primary me-1';
        ver.title = 'Ver ficha';
        ver.innerHTML = ICONOS.ver;

        celda.append(ver);

        if (permisos.editar) {
            const editar = document.createElement('a');

            editar.href = urlEdicion.replace('__ID__', String(cliente.id));
            editar.className = 'btn btn-sm btn-warning me-1';
            editar.title = 'Editar';
            editar.innerHTML = ICONOS.editar;

            celda.append(editar);
        }

        if (permisos.baja) {
            celda.append(cliente.activo
                ? boton('btn-secondary', 'Dar de baja', ICONOS.baja, () => cambiarEstado(cliente, false))
                : boton('btn-success', 'Reactivar', ICONOS.reactivar, () => cambiarEstado(cliente, true)));
        }

        return celda;
    }

    function boton(clases, titulo, icono, alHacerClic) {
        const elemento = document.createElement('button');

        elemento.type = 'button';
        elemento.className = `btn btn-sm ${clases}`;
        elemento.title = titulo;
        elemento.innerHTML = icono;

        elemento.addEventListener('click', () => alHacerClic());

        return elemento;
    }

    // ------------------------------------------------------------------
    // Paginado
    // ------------------------------------------------------------------
    //
    // El marcado que emite Bootstrap 5 en Laravel (ver
    // Paginator::useBootstrapFive() en AppServiceProvider), con `onEachSide(2)`
    // como tenía la vista.

    function pintarPaginado(meta, filtros) {
        if (! paginado) {
            return;
        }

        const ultima = meta?.last_page ?? 1;
        const actual = meta?.current_page ?? 1;

        if (ultima <= 1) {
            paginado.replaceChildren();

            return;
        }

        const lista = document.createElement('ul');
        lista.className = 'pagination';

        lista.append(itemDePagina('‹', actual - 1, actual <= 1, false, filtros, 'Anterior'));

        for (const numero of numerosVisibles(actual, ultima)) {
            lista.append(numero === null
                ? separador()
                : itemDePagina(String(numero), numero, false, numero === actual, filtros));
        }

        lista.append(itemDePagina('›', actual + 1, actual >= ultima, false, filtros, 'Siguiente'));

        const navegacion = document.createElement('nav');
        navegacion.append(lista);

        paginado.replaceChildren(navegacion);
    }

    /** Números con dos páginas a cada lado; `null` es el «…». */
    function numerosVisibles(actual, ultima, aCadaLado = 2) {
        const visibles = [];
        let anterior = 0;

        for (let numero = 1; numero <= ultima; numero++) {
            const cerca = Math.abs(numero - actual) <= aCadaLado;
            const punta = numero === 1 || numero === ultima;

            if (! cerca && ! punta) {
                continue;
            }

            if (anterior !== 0 && numero - anterior > 1) {
                visibles.push(null);
            }

            visibles.push(numero);
            anterior = numero;
        }

        return visibles;
    }

    function separador() {
        const item = document.createElement('li');

        item.className = 'page-item disabled';
        item.innerHTML = '<span class="page-link">…</span>';

        return item;
    }

    function itemDePagina(texto, pagina, deshabilitado, activo, filtros, etiqueta = null) {
        const item = document.createElement('li');

        item.className = `page-item${deshabilitado ? ' disabled' : ''}${activo ? ' active' : ''}`;

        if (deshabilitado || activo) {
            const span = document.createElement('span');

            span.className = 'page-link';
            span.textContent = texto;

            if (activo) {
                item.setAttribute('aria-current', 'page');
            }

            item.append(span);

            return item;
        }

        const enlace = document.createElement('a');

        enlace.className = 'page-link';
        enlace.textContent = texto;
        enlace.href = url({ ...filtros, page: pagina });

        if (etiqueta !== null) {
            enlace.setAttribute('aria-label', etiqueta);
        }

        enlace.addEventListener('click', (evento) => {
            evento.preventDefault();

            const siguientes = { ...filtros, page: pagina };

            escribirEnLaUrl(siguientes);
            cargar(siguientes);

            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        item.append(enlace);

        return item;
    }

    // ------------------------------------------------------------------
    // Baja y reactivación
    // ------------------------------------------------------------------

    async function cambiarEstado(cliente, activo) {
        // Decir qué implica: la baja inhabilita la cuenta además de sacarlo
        // del mostrador, y eso el empleado lo tiene que saber ANTES.
        const pregunta = activo
            ? '¿Reactivar este cliente? Vuelve a la operación y recupera el acceso a su cuenta.'
            : '¿Dar de baja este cliente? Su cuenta queda inhabilitada: no va a poder '
                + 'ingresar ni comprar online, y se le revocan los tokens de la app.';

        if (! window.confirm(pregunta)) {
            return;
        }

        bloquearAcciones(true);

        try {
            await parchear(`gestion/clientes/${cliente.id}/estado`, { activo });

            // Los mismos textos que dejaba el `with('status', ...)` del panel.
            avisos.mostrar(caja, 'exito', activo ? 'Cliente reactivado.' : 'Cliente dado de baja.');

        } catch (error) {
            const mensaje = tratar(error);

            if (mensaje !== null) {
                avisos.mostrar(caja, 'error', mensaje);
            }

        } finally {
            bloquearAcciones(false);
        }

        await cargar(filtrosDeLaUrl());
    }

    function bloquearAcciones(si) {
        cuerpo.querySelectorAll('button').forEach((boton) => {
            boton.disabled = si;
        });
    }

    // ------------------------------------------------------------------
    // Arranque
    // ------------------------------------------------------------------

    formulario?.addEventListener('submit', (evento) => {
        evento.preventDefault();

        // Un filtro nuevo siempre empieza en la primera página.
        const filtros = {
            q: formulario.elements.q.value.trim(),
            activo: formulario.elements.activo.value,
            page: 1,
        };

        escribirEnLaUrl(filtros);
        cargar(filtros);
    });

    window.addEventListener('popstate', () => {
        const filtros = filtrosDeLaUrl();

        sincronizarControles(filtros);
        cargar(filtros);
    });

    const inicial = filtrosDeLaUrl();

    sincronizarControles(inicial);
    cargar(inicial);
}
