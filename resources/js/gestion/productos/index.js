/**
 * Listado de productos del panel, contra la API.
 *
 * Misma pantalla de siempre —el mismo encabezado, los mismos cuatro filtros,
 * la misma tabla y el mismo paginado de Bootstrap—; lo que cambió es de dónde
 * salen los datos:
 *
 *     GET   /api/v1/gestion/productos?q=&categoria_id=&estado=&stock=&page=
 *     PATCH /api/v1/gestion/productos/{id}/estado     baja y reactivación
 *
 * No hay DELETE y no es un olvido: un producto no se elimina nunca —está
 * referenciado por ventas, comprobantes, órdenes de compra y movimientos de
 * stock—, así que la única baja es `activo = false`.
 *
 * <b>Acá no hay ninguna regla de negocio.</b> Qué puede editarse, quién puede
 * dar de baja y qué pasa con el stock lo decide el backend; este archivo pinta
 * lo que la API contesta y muestra el mensaje que la API manda cuando dice que
 * no.
 *
 * Los filtros y la página siguen viviendo en la URL, como en categorías: un
 * listado filtrado se puede guardar y compartir, y el botón de volver del
 * navegador funciona.
 */

import { obtener, parchear, tratar } from '../api.js';
import * as avisos from '../avisos.js';

/** El mismo tamaño de página que tenía el `paginate(10)` del controlador web. */
const POR_PAGINA = 10;

const ICONOS = {
    editar: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil" viewBox="0 0 16 16"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325"/></svg>',
    historial: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-clock" viewBox="0 0 16 16"><path d="M8 3.5a.5.5 0 0 1 .5.5v3.25l2.25 1.35a.5.5 0 0 1-.5.866l-2.5-1.5A.5.5 0 0 1 7.5 7.5V4a.5.5 0 0 1 .5-.5z"/><path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm0-1A7 7 0 1 1 8 1a7 7 0 0 1 0 14z"/></svg>',
    baja: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14zM4.5 7.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1 0-1z"/></svg>',
    reactivar: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 3a5 5 0 1 1-4.546 2.917.5.5 0 0 1 .91-.414A4 4 0 1 0 8 4z"/><path d="M8 1v4H4"/></svg>',
};

const PESOS = new Intl.NumberFormat('es-AR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

/**
 * @param {object} configuracion
 * @param {{editar: boolean, cambiarEstado: boolean, verStock: boolean}} configuracion.permisos
 *        Los resuelve ProductoPolicy en el servidor; acá sólo deciden qué
 *        botones se dibujan. Quien llame igual al endpoint recibe un 403.
 * @param {string} configuracion.urlEdicion    Ruta de edición con `__ID__`.
 * @param {string} configuracion.urlHistorial  Ruta del kardex con `__ID__`.
 * @param {string} configuracion.imagenPorDefecto
 */
export function iniciar({ permisos, urlEdicion, urlHistorial, imagenPorDefecto }) {
    const caja       = document.getElementById('avisosProductos');
    const formulario = document.getElementById('formFiltros');
    const cuerpo     = document.getElementById('contenidoTablaProductos');
    const paginado   = document.getElementById('paginadoProductos');

    if (! cuerpo) {
        return;
    }

    const columnas = document.querySelectorAll('#tablaProductos thead th').length;

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
            categoria_id: parametros.get('categoria_id') ?? '',
            estado: parametros.get('estado') ?? '',
            stock: parametros.get('stock') ?? '',
            page: Number.parseInt(parametros.get('page') ?? '1', 10) || 1,
        };
    }

    function url(filtros) {
        const parametros = new URLSearchParams();

        for (const clave of ['q', 'categoria_id', 'estado', 'stock']) {
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
        formulario.elements.categoria_id.value = filtros.categoria_id;
        formulario.elements.estado.value = filtros.estado;
        formulario.elements.stock.value = filtros.stock;
    }

    // ------------------------------------------------------------------
    // Traer y pintar
    // ------------------------------------------------------------------

    async function cargar(filtros) {
        const pedido = ++pedidoActual;

        mostrarMensajeEnLaTabla('Cargando productos…');

        try {
            const respuesta = await obtener('gestion/productos', parametrosDe(filtros));

            if (pedido !== pedidoActual) {
                return; // llegó tarde: ya hay otra consulta en curso
            }

            const ultima = respuesta.meta?.last_page ?? 1;

            // La página quedó más allá del final —se filtró o se vació—: en
            // vez de mostrar una tabla vacía, se vuelve a la última con filas.
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
     * Un `estado=` vacío no es un filtro: el endpoint valida
     * `in:todos,activos,inactivos` y una cadena vacía no está en esa lista.
     */
    function parametrosDe(filtros) {
        const parametros = { por_pagina: POR_PAGINA };

        for (const clave of ['q', 'categoria_id', 'estado', 'stock']) {
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

    function pintarFilas(productos) {
        if (productos.length === 0) {
            mostrarMensajeEnLaTabla('No se encontraron productos.');

            return;
        }

        cuerpo.replaceChildren(...productos.map(fila));
    }

    /** Una fila, con el mismo marcado que tenía el `@forelse`. */
    function fila(producto) {
        const tr = document.createElement('tr');

        tr.append(
            celdaConFoto(producto),
            celdaDeTexto(producto.codigo),
            celdaDeTexto(producto.nombre),
            celdaDeTexto(producto.marca || '—'),
            celdaDeTexto(producto.categoria?.nombre ?? '—'),
            celdaDePrecio(producto.precio),
            celdaDeStock(producto.stock_disponible),
            celdaDeEstado(producto),
        );

        const acciones = celdaDeAcciones(producto);

        if (acciones !== null) {
            tr.append(acciones);
        }

        return tr;
    }

    function celdaDeTexto(texto) {
        const celda = document.createElement('td');

        celda.textContent = texto;

        return celda;
    }

    function celdaConFoto(producto) {
        const celda = document.createElement('td');
        const foto  = document.createElement('img');

        // `imagen_url` ya viene armada por el resource; cuando el producto no
        // tiene foto, llega null y se usa el placeholder del proyecto.
        foto.src = producto.imagen_url ?? imagenPorDefecto;
        foto.alt = producto.imagen_url ? producto.nombre : 'Sin imagen';
        foto.className = 'w-100 h-100';
        foto.style.objectFit = 'contain';
        foto.loading = 'lazy';

        celda.append(foto);

        return celda;
    }

    function celdaDePrecio(precio) {
        const celda = document.createElement('td');

        celda.className = 'text-nowrap';
        celda.textContent = `$ ${PESOS.format(precio)}`;

        return celda;
    }

    function celdaDeStock(disponible) {
        const celda = document.createElement('td');
        const insignia = document.createElement('span');

        // Los mismos tres cortes de la vista: negativo es preventa, cero
        // avisa, positivo tranquiliza.
        const color = disponible < 0
            ? 'text-bg-danger'
            : (disponible === 0 ? 'text-bg-warning' : 'text-bg-success');

        insignia.className = `badge ${color}`;
        insignia.textContent = String(disponible);

        celda.append(insignia);

        return celda;
    }

    function celdaDeEstado(producto) {
        const celda = document.createElement('td');
        const estado = document.createElement('span');

        estado.className = producto.activo ? 'badge text-bg-success' : 'badge text-bg-secondary';
        estado.textContent = producto.activo ? 'Activo' : 'Inactivo';

        celda.append(estado);

        if (producto.publicado_app) {
            const publicado = document.createElement('span');

            publicado.className = 'badge text-bg-primary mt-1';
            publicado.textContent = 'Publicado';

            celda.append(' ', publicado);
        }

        return celda;
    }

    function celdaDeAcciones(producto) {
        if (! permisos.editar && ! permisos.cambiarEstado && ! permisos.verStock) {
            return null;
        }

        const celda = document.createElement('td');
        celda.className = 'text-nowrap';

        if (permisos.editar) {
            celda.append(enlace(urlEdicion, producto.id, 'btn-warning me-1', 'Editar', ICONOS.editar));
        }

        if (permisos.verStock) {
            celda.append(enlace(urlHistorial, producto.id, 'btn-info text-white me-1', 'Historial de stock', ICONOS.historial));
        }

        if (permisos.cambiarEstado) {
            celda.append(producto.activo
                ? boton('btn-secondary me-1', 'Dar de baja', ICONOS.baja, () => cambiarEstado(producto, false))
                : boton('btn-success', 'Reactivar', ICONOS.reactivar, () => cambiarEstado(producto, true)));
        }

        return celda;
    }

    function enlace(plantilla, id, clases, titulo, icono) {
        const elemento = document.createElement('a');

        elemento.href = plantilla.replace('__ID__', String(id));
        elemento.className = `btn btn-sm ${clases}`;
        elemento.title = titulo;
        elemento.innerHTML = icono;

        return elemento;
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

        const enlacePagina = document.createElement('a');

        enlacePagina.className = 'page-link';
        enlacePagina.textContent = texto;
        enlacePagina.href = url({ ...filtros, page: pagina });

        if (etiqueta !== null) {
            enlacePagina.setAttribute('aria-label', etiqueta);
        }

        // El href es real —se puede abrir en otra pestaña—, pero el clic no
        // recarga: cambia la URL y vuelve a pedir el listado.
        enlacePagina.addEventListener('click', (evento) => {
            evento.preventDefault();

            const siguientes = { ...filtros, page: pagina };

            escribirEnLaUrl(siguientes);
            cargar(siguientes);

            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        item.append(enlacePagina);

        return item;
    }

    // ------------------------------------------------------------------
    // Baja y reactivación
    // ------------------------------------------------------------------
    //
    // La publicación NO se toca acá: tiene permiso propio
    // (`producto.publicar`) y se cambia desde la pantalla de edición. Dar de
    // baja tampoco despublica, a propósito: la vitrina filtra por las dos
    // banderas, así que la decisión de publicación se conserva por si el
    // producto se reactiva.

    async function cambiarEstado(producto, activo) {
        const pregunta = activo
            ? '¿Está seguro de reactivar este producto?'
            : '¿Está seguro de dar de baja este producto?';

        if (! window.confirm(pregunta)) {
            return;
        }

        bloquearAcciones(true);

        try {
            await parchear(`gestion/productos/${producto.id}/estado`, { activo });

            avisos.mostrar(caja, 'exito', activo
                ? `«${producto.nombre}» vuelve a estar en operación.`
                : `«${producto.nombre}» salió de operación. No se puede cargar en ventas nuevas.`);

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
            categoria_id: formulario.elements.categoria_id.value,
            estado: formulario.elements.estado.value,
            stock: formulario.elements.stock.value,
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
