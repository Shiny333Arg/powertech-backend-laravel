/**
 * Listado de categorías del panel, contra la API.
 *
 * La pantalla es la misma de siempre —el mismo encabezado, los mismos filtros,
 * la misma tabla y el mismo paginado de Bootstrap—; lo que cambió es de dónde
 * salen los datos: ya no vienen renderizados por Blade desde la base, sino de
 *
 *     GET /api/v1/gestion/categorias?nombre=&estado=&por_pagina=&page=
 *
 * y las tres acciones de cada fila van a sus endpoints:
 *
 *     PATCH  /api/v1/gestion/categorias/{id}/estado   baja y reactivación
 *     DELETE /api/v1/gestion/categorias/{id}          borrado físico
 *
 * <b>Acá no hay ninguna regla de negocio.</b> Qué nombres son válidos, si una
 * categoría se puede eliminar o qué permiso hace falta para darla de baja lo
 * decide el backend, igual que antes; este archivo pinta lo que la API
 * contesta y muestra el mensaje que la API manda cuando dice que no. El
 * conteo de productos que trae el recurso sirve para avisar, no para decidir:
 * el intento de borrado se hace igual y el 409 lo decide la FK.
 *
 * <b>Los filtros y la página siguen viviendo en la URL.</b> `?estado=activas`
 * sigue siendo un enlace que se puede guardar o compartir, y el botón de
 * volver del navegador sigue funcionando: cambian con `history.pushState` y se
 * leen con `URLSearchParams`, en vez de recargar la página entera.
 */

import { obtener, parchear, eliminar, tratar } from '../api.js';
import * as avisos from '../avisos.js';

/** El mismo tamaño de página que tenía el `paginate(10)` del controlador web. */
const POR_PAGINA = 10;

const ICONOS = {
    editar: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil" viewBox="0 0 16 16"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325"/></svg>',
    baja: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14zM4.5 7.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1 0-1z"/></svg>',
    reactivar: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 3a5 5 0 1 1-4.546 2.917.5.5 0 0 1 .91-.414A4 4 0 1 0 8 4z"/><path d="M8 1v4H4"/></svg>',
    eliminar: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash3" viewBox="0 0 16 16"><path d="M6.5 1h3a.5.5 0 0 1 .5.5v1H6v-1a.5.5 0 0 1 .5-.5M11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3A1.5 1.5 0 0 0 5 1.5v1H1.5a.5.5 0 0 0 0 1h.538l.853 10.66A2 2 0 0 0 4.885 16h6.23a2 2 0 0 0 1.994-1.84l.853-10.66h.538a.5.5 0 0 0 0-1zm1.958 1-.846 10.58a1 1 0 0 1-.997.92h-6.23a1 1 0 0 1-.997-.92L3.042 3.5zm-7.487 1a.5.5 0 0 1 .528.47l.5 8.5a.5.5 0 0 1-.998.06L5 5.03a.5.5 0 0 1 .47-.53Zm5.058 0a.5.5 0 0 1 .47.53l-.5 8.5a.5.5 0 1 1-.998-.06l.5-8.5a.5.5 0 0 1 .528-.47M8 4.5a.5.5 0 0 1 .5.5v8.5a.5.5 0 0 1-1 0V5a.5.5 0 0 1 .5-.5"/></svg>',
};

/**
 * @param {object} configuracion
 * @param {{editar: boolean, cambiarEstado: boolean, eliminar: boolean}} configuracion.permisos
 *        Los resuelve la policy en el servidor; acá sólo deciden qué botones
 *        se dibujan. Quien llame igual al endpoint sin permiso recibe un 403.
 * @param {string} configuracion.urlEdicion  La ruta de edición con `__ID__` en
 *        lugar del identificador, para no escribir URLs del panel en el JS.
 */
export function iniciar({ permisos, urlEdicion }) {
    const caja       = document.getElementById('avisosCategorias');
    const formulario = document.getElementById('formFiltros');
    const cuerpo     = document.getElementById('contenidoTablaCategorias');
    const paginado   = document.getElementById('paginadoCategorias');

    if (! cuerpo) {
        return;
    }

    /** Las columnas de la tabla, para el colspan de los mensajes. */
    const columnas = document.querySelectorAll('#tablaCategorias thead th').length;

    /** Evita que una respuesta lenta pise a una pedida después. */
    let pedidoActual = 0;

    avisos.mostrarPendiente(caja);

    // ------------------------------------------------------------------
    // La URL es el estado de la pantalla
    // ------------------------------------------------------------------

    function filtrosDeLaUrl() {
        const parametros = new URLSearchParams(window.location.search);

        return {
            nombre: parametros.get('nombre') ?? '',
            estado: parametros.get('estado') ?? 'todas',
            page: Number.parseInt(parametros.get('page') ?? '1', 10) || 1,
        };
    }

    function escribirEnLaUrl({ nombre, estado, page }) {
        const parametros = new URLSearchParams();

        if (nombre !== '') {
            parametros.set('nombre', nombre);
        }

        if (estado !== 'todas') {
            parametros.set('estado', estado);
        }

        if (page > 1) {
            parametros.set('page', String(page));
        }

        const consulta = parametros.toString();

        window.history.pushState({}, '', consulta === '' ? window.location.pathname : `?${consulta}`);
    }

    /** Deja los controles mostrando lo que dice la URL. */
    function sincronizarControles({ nombre, estado }) {
        if (formulario) {
            formulario.elements.nombre.value = nombre;
            formulario.elements.estado.value = estado;
        }
    }

    // ------------------------------------------------------------------
    // Traer y pintar
    // ------------------------------------------------------------------

    async function cargar(filtros) {
        const pedido = ++pedidoActual;

        mostrarMensajeEnLaTabla('Cargando categorías…');

        try {
            const respuesta = await obtener('gestion/categorias', {
                nombre: filtros.nombre,
                estado: filtros.estado,
                por_pagina: POR_PAGINA,
                page: filtros.page > 1 ? filtros.page : null,
            });

            if (pedido !== pedidoActual) {
                return; // llegó tarde: ya hay otra consulta en curso
            }

            // La última fila de la última página se acaba de eliminar o de
            // filtrar: en vez de dejar la tabla vacía, se vuelve a la página
            // que sí tiene filas.
            const ultima = respuesta.meta?.last_page ?? 1;

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

    function pintarFilas(categorias) {
        if (categorias.length === 0) {
            mostrarMensajeEnLaTabla('No se encontraron categorías.');

            return;
        }

        cuerpo.replaceChildren(...categorias.map(fila));
    }

    /** Una fila de la tabla, con el mismo marcado que tenía el `@forelse`. */
    function fila(categoria) {
        const tr = document.createElement('tr');

        const nombre = document.createElement('td');
        nombre.textContent = categoria.nombre;

        const estado = document.createElement('td');
        const insignia = document.createElement('span');
        insignia.className = categoria.activo ? 'badge text-bg-success' : 'badge text-bg-secondary';
        insignia.textContent = categoria.activo ? 'Activa' : 'Inactiva';
        estado.append(insignia);

        const productos = document.createElement('td');
        productos.textContent = String(categoria.productos_count ?? 0);

        tr.append(nombre, estado, productos);

        const acciones = celdaDeAcciones(categoria);

        if (acciones !== null) {
            tr.append(acciones);
        }

        return tr;
    }

    function celdaDeAcciones(categoria) {
        if (! permisos.editar && ! permisos.cambiarEstado && ! permisos.eliminar) {
            return null;
        }

        const celda = document.createElement('td');
        celda.className = 'text-nowrap';

        if (permisos.editar) {
            const editar = document.createElement('a');

            editar.href = urlEdicion.replace('__ID__', String(categoria.id));
            editar.className = 'btn btn-sm btn-warning me-1';
            editar.title = 'Editar';
            editar.innerHTML = ICONOS.editar;

            celda.append(editar);
        }

        if (permisos.cambiarEstado) {
            celda.append(categoria.activo
                ? boton('btn-secondary me-1', 'Dar de baja', ICONOS.baja, () => cambiarEstado(categoria, false))
                : boton('btn-success me-1', 'Reactivar', ICONOS.reactivar, () => cambiarEstado(categoria, true)));
        }

        if (permisos.eliminar) {
            celda.append(boton('btn-danger', 'Eliminar', ICONOS.eliminar, () => borrar(categoria)));
        }

        return celda;
    }

    function boton(clases, titulo, icono, alHacerClic) {
        const elemento = document.createElement('button');

        elemento.type = 'button';
        elemento.className = `btn btn-sm ${clases}`;
        elemento.title = titulo;
        elemento.innerHTML = icono;

        elemento.addEventListener('click', () => alHacerClic(elemento));

        return elemento;
    }

    // ------------------------------------------------------------------
    // Paginado
    // ------------------------------------------------------------------
    //
    // El mismo marcado que emite Bootstrap 5 en Laravel (ver
    // Paginator::useBootstrapFive() en AppServiceProvider), con `onEachSide(2)`
    // como en la vista: dos páginas a cada lado de la actual.

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

        // El `href` es real —se puede abrir en otra pestaña o guardar—, pero el
        // clic no recarga la página: cambia la URL y vuelve a pedir el listado.
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

    function url({ nombre, estado, page }) {
        const parametros = new URLSearchParams();

        if (nombre !== '') {
            parametros.set('nombre', nombre);
        }

        if (estado !== 'todas') {
            parametros.set('estado', estado);
        }

        if (page > 1) {
            parametros.set('page', String(page));
        }

        const consulta = parametros.toString();

        return consulta === '' ? window.location.pathname : `?${consulta}`;
    }

    // ------------------------------------------------------------------
    // Acciones de cada fila
    // ------------------------------------------------------------------
    //
    // Las dos preguntan con el mismo `confirm()` que tenían los formularios, y
    // las dos vuelven a pedir el listado al terminar: el estado y el conteo de
    // productos los decide el backend, así que se releen en vez de retocarse a
    // mano en la tabla.

    async function cambiarEstado(categoria, activo) {
        const pregunta = activo
            ? '¿Está seguro de reactivar esta categoría?'
            : '¿Está seguro de dar de baja esta categoría?';

        if (! window.confirm(pregunta)) {
            return;
        }

        await operar(
            () => parchear(`gestion/categorias/${categoria.id}/estado`, { activo }),
            activo
                ? `«${categoria.nombre}» vuelve a estar en operación.`
                : `«${categoria.nombre}» ya no está en operación.`,
        );
    }

    async function borrar(categoria) {
        if (! window.confirm('¿Está seguro de eliminar esta categoría? Esta acción no se puede deshacer.')) {
            return;
        }

        await operar(
            () => eliminar(`gestion/categorias/${categoria.id}`),
            `Se eliminó la categoría «${categoria.nombre}».`,
        );
    }

    /**
     * Ejecuta una operación contra la API y deja la pantalla consistente:
     * aviso, listado al día y botones habilitados otra vez.
     *
     * El mensaje de error sale de la API —el 409 de «tiene productos
     * asociados» incluido—: acá no se vuelve a escribir ninguna regla.
     */
    async function operar(operacion, mensajeDeExito) {
        bloquearAcciones(true);

        try {
            await operacion();

            avisos.mostrar(caja, 'exito', mensajeDeExito);

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

        // Un filtro nuevo siempre empieza en la primera página: quedarse en la
        // cuarta con tres resultados mostraría una tabla vacía.
        const filtros = {
            nombre: formulario.elements.nombre.value.trim(),
            estado: formulario.elements.estado.value,
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
