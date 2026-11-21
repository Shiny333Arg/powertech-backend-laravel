/**
 * Listado de pedidos a proveedores del panel, contra la API.
 *
 * Misma pantalla de siempre —el mismo encabezado, los mismos tres filtros, la
 * misma tabla de ocho columnas y el mismo paginado—; lo que cambió es de dónde
 * salen los datos:
 *
 *     GET /api/v1/gestion/compras?q=&estado=&origen=&page=
 *
 * <b>Es la única llamada de esta pantalla.</b> El listado no opera: no aprueba,
 * no envía, no cancela y no recibe. Todo eso se hace desde el detalle, que
 * sigue siendo Blade y es la segunda entrega de la migración. Acá la única
 * acción de fila es «Ver», que es un enlace.
 *
 * ──────────────────────────────────────────────────────────────────────────
 * MUESTRA PEDIDOS, NO REPARTOS
 * ──────────────────────────────────────────────────────────────────────────
 *
 * El endpoint devuelve sólo órdenes raíz, igual que hacía el controlador web:
 * un pedido a tres proveedores emite tres órdenes, pero acá es una fila sola.
 * Si cada una fuera una fila, el administrativo no sabría cuál tiene que
 * aprobar. De ahí la columna de proveedor: con un destinatario muestra su
 * nombre, y con varios dice cuántos son y nombra a los dos primeros.
 *
 * `proveedores` llega siempre —en un pedido simple tiene un solo elemento—, así
 * que la pantalla no necesita preguntar de qué forma es el documento para
 * dibujar esa celda.
 *
 * <b>Acá no hay ninguna regla de negocio.</b> Qué pedidos se listan, en qué
 * orden y quién puede verlos lo decide el backend; este archivo pinta lo que la
 * API contesta y muestra el mensaje que manda cuando dice que no.
 */

import { obtener, tratar } from '../api.js';
import * as avisos from '../avisos.js';

/** El mismo tamaño de página que tenía el `paginate(15)` del controlador web. */
const POR_PAGINA = 15;

const ICONO_VER = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16"><path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"/><path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0"/></svg>';

/**
 * @param {object} configuracion
 * @param {Record<string, string>} configuracion.colores  value del estado -> color de Bootstrap.
 *        Sale de EstadoOrdenCompra::color() en el servidor: el badge del
 *        listado y el del detalle tienen que verse igual, y el detalle sigue
 *        leyendo el enum.
 * @param {string} configuracion.urlDetalle  Ruta del detalle con `__ID__`.
 */
export function iniciar({ colores, urlDetalle }) {
    const caja       = document.getElementById('avisosCompras');
    const formulario = document.getElementById('formFiltros');
    const cuerpo     = document.getElementById('contenidoTablaCompras');
    const paginado   = document.getElementById('paginadoCompras');

    if (! cuerpo) {
        return;
    }

    const columnas = document.querySelectorAll('#tablaCompras thead th').length;

    /** Evita que una respuesta lenta pise a una pedida después. */
    let pedidoActual = 0;

    avisos.mostrarPendiente(caja);

    // ------------------------------------------------------------------
    // La URL es el estado de la pantalla
    // ------------------------------------------------------------------

    function filtrosDeLaUrl() {
        const parametros = new URLSearchParams(window.location.search);

        return {
            q: parametros.get('q') ?? '',
            estado: parametros.get('estado') ?? '',
            origen: parametros.get('origen') ?? '',
            page: Number.parseInt(parametros.get('page') ?? '1', 10) || 1,
        };
    }

    function url(filtros) {
        const parametros = new URLSearchParams();

        for (const clave of ['q', 'estado', 'origen']) {
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
        formulario.elements.estado.value = filtros.estado;
        formulario.elements.origen.value = filtros.origen;
    }

    // ------------------------------------------------------------------
    // Traer y pintar
    // ------------------------------------------------------------------

    async function cargar(filtros) {
        const pedido = ++pedidoActual;

        mostrarMensajeEnLaTabla('Cargando pedidos…');

        try {
            const respuesta = await obtener('gestion/compras', parametrosDe(filtros));

            if (pedido !== pedidoActual) {
                return; // llegó tarde: ya hay otra consulta en curso
            }

            const ultima = respuesta.meta?.last_page ?? 1;

            // La página quedó más allá del final: en vez de mostrar una tabla
            // vacía, se vuelve a la última con filas.
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
     * Un `estado=` vacío no es un filtro: el endpoint lo valida contra el enum
     * —a diferencia del controlador web, que lo pasaba crudo al where— y una
     * cadena vacía no es ninguno de sus casos.
     */
    function parametrosDe(filtros) {
        const parametros = { por_pagina: POR_PAGINA };

        for (const clave of ['q', 'estado', 'origen']) {
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

    function pintarFilas(ordenes) {
        if (ordenes.length === 0) {
            mostrarMensajeEnLaTabla('No se encontraron pedidos.');

            return;
        }

        cuerpo.replaceChildren(...ordenes.map(fila));
    }

    /** Una fila, con el mismo marcado que tenía el `@foreach`. */
    function fila(orden) {
        const tr = document.createElement('tr');

        tr.append(
            celdaNumero(orden),
            celdaProveedores(orden),
            celdaEstado(orden),
            celdaOrigen(orden),
            celdaDeTexto(String(orden.lineas_count ?? 0), 'text-nowrap'),
            celdaDeTexto(pesos(orden.total_estimado), 'text-nowrap'),
            celdaDeTexto(fechaCorta(orden.creada_el), 'text-nowrap text-muted'),
            celdaDeAcciones(orden),
        );

        return tr;
    }

    function celdaDeTexto(texto, clases = '') {
        const celda = document.createElement('td');

        celda.className = clases;
        celda.textContent = texto;

        return celda;
    }

    function celdaNumero(orden) {
        return celdaDeTexto(orden.numero, 'fw-semibold text-nowrap');
    }

    /**
     * A quién se le pide.
     *
     * Las tres formas que tenía la vista: sin destinatario todavía —un borrador
     * cuyos renglones no tienen proveedor—, uno solo, o varios. Con varios no
     * se listan todos para que la columna no crezca: el detalle completo está
     * en el pedido.
     */
    function celdaProveedores(orden) {
        const celda = document.createElement('td');
        celda.className = 'text-start';

        const destinatarios = orden.proveedores ?? [];

        if (destinatarios.length === 0) {
            const vacio = document.createElement('span');

            vacio.className = 'text-muted';
            vacio.textContent = '— sin proveedor —';
            celda.append(vacio);

            return celda;
        }

        if (destinatarios.length === 1) {
            celda.textContent = destinatarios[0].razon_social;

            return celda;
        }

        const insignia = document.createElement('span');

        insignia.className = 'badge text-bg-dark mb-1';
        insignia.textContent = `${destinatarios.length} proveedores`;

        const nombres = document.createElement('div');

        nombres.className = 'small text-muted';
        nombres.textContent = destinatarios.slice(0, 2).map((p) => p.razon_social).join(', ')
            + (destinatarios.length > 2 ? ` y ${destinatarios.length - 2} más` : '');

        celda.append(insignia, nombres);

        return celda;
    }

    function celdaEstado(orden) {
        const celda = document.createElement('td');
        const insignia = document.createElement('span');

        insignia.className = `badge text-bg-${colores[orden.estado] ?? 'secondary'}`;
        insignia.textContent = orden.estado_etiqueta ?? orden.estado ?? '—';

        celda.append(insignia);

        return celda;
    }

    function celdaOrigen(orden) {
        const celda = document.createElement('td');
        const insignia = document.createElement('span');

        insignia.className = 'badge text-bg-secondary';
        insignia.textContent = orden.origen_etiqueta ?? orden.origen ?? '—';

        celda.append(insignia);

        return celda;
    }

    /**
     * «Ver» y nada más.
     *
     * No hay botón por permiso: para llegar a esta pantalla hace falta
     * 'compra.ver', que es el mismo que exige el detalle, así que un botón
     * condicional no cambiaría nada. Las acciones del documento viven en el
     * detalle, donde dependen del estado de cada pedido.
     */
    function celdaDeAcciones(orden) {
        const celda = document.createElement('td');
        celda.className = 'text-nowrap';

        const ver = document.createElement('a');

        ver.href = urlDetalle.replace('__ID__', String(orden.id));
        ver.className = 'btn btn-sm btn-primary me-1';
        ver.title = 'Ver pedido';
        ver.innerHTML = ICONO_VER;

        celda.append(ver);

        return celda;
    }

    /** `$ 1.234,56`, como el `number_format($monto, 2, ',', '.')` de la vista. */
    function pesos(monto) {
        const numero = Number(monto ?? 0);

        return `$ ${numero.toLocaleString('es-AR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        })}`;
    }

    /** `d/m/Y`, como el `format('d/m/Y')` de la vista. */
    function fechaCorta(iso) {
        if (! iso) {
            return '—';
        }

        const fecha = new Date(iso);

        if (Number.isNaN(fecha.getTime())) {
            return '—';
        }

        const dia = String(fecha.getDate()).padStart(2, '0');
        const mes = String(fecha.getMonth() + 1).padStart(2, '0');

        return `${dia}/${mes}/${fecha.getFullYear()}`;
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
    // Arranque
    // ------------------------------------------------------------------

    formulario?.addEventListener('submit', (evento) => {
        evento.preventDefault();

        // Un filtro nuevo siempre empieza en la primera página.
        const filtros = {
            q: formulario.elements.q.value.trim(),
            estado: formulario.elements.estado.value,
            origen: formulario.elements.origen.value,
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
