/**
 * Listado de proveedores del panel, contra la API.
 *
 * Misma pantalla de siempre —el mismo encabezado, los mismos tres filtros, la
 * misma tabla de nueve columnas y el mismo paginado—; lo que cambió es de
 * dónde salen los datos:
 *
 *     GET   /api/v1/gestion/proveedores?q=&canal_pedido=&estado=&page=
 *     PATCH /api/v1/gestion/proveedores/{id}/estado    baja y reactivación
 *
 * No hay DELETE y no es un olvido: el proveedor está referenciado por
 * productos y por órdenes de compra —la orden dice a quién se le compró—, así
 * que la única baja es `activo = false`.
 *
 * <b>Acá no hay ninguna regla de negocio.</b> Qué se puede editar, quién puede
 * dar de baja y qué consecuencias tiene lo decide el backend; este archivo
 * pinta lo que la API contesta y muestra el mensaje que manda cuando dice que
 * no.
 *
 * ──────────────────────────────────────────────────────────────────────────
 * EL AVISO DE LA BAJA
 * ──────────────────────────────────────────────────────────────────────────
 *
 * Dar de baja un proveedor no se bloquea, pero tiene una consecuencia que no
 * se ve: los productos que dependían de él para reponerse dejan de generar
 * orden automática. El endpoint de estado devuelve esos dos números en
 * `advertencias` —productos en reposición automática y órdenes abiertas— y la
 * pantalla arma la frase, que es la misma que mostraba el panel. La API manda
 * datos; el texto es presentación.
 */

import { obtener, parchear, tratar } from '../api.js';
import * as avisos from '../avisos.js';

/** El mismo tamaño de página que tenía el `paginate(10)` del controlador web. */
const POR_PAGINA = 10;

const ICONOS = {
    editar: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325"/></svg>',
    baja: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14zM4.5 7.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1 0-1z"/></svg>',
    reactivar: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 3a5 5 0 1 1-4.546 2.917.5.5 0 0 1 .91-.414A4 4 0 1 0 8 4z"/><path d="M8 1v4H4"/></svg>',
};

/**
 * El CUIT con guiones, como lo mostraba la vista.
 *
 * En la columna está guardado sin separadores —ProveedorRequest lo normaliza
 * para que el índice único sirva—, así que el formato es cosa de la pantalla.
 */
export function cuitConGuiones(cuit) {
    const digitos = String(cuit ?? '').replace(/\D/g, '');

    return digitos.length === 11
        ? `${digitos.slice(0, 2)}-${digitos.slice(2, 10)}-${digitos.slice(10)}`
        : String(cuit ?? '');
}

/**
 * @param {object} configuracion
 * @param {{crear: boolean, editar: boolean, cambiarEstado: boolean}} configuracion.permisos
 *        Los resuelve ProveedorPolicy en el servidor; acá sólo deciden qué
 *        botones se dibujan. Quien llame igual al endpoint recibe un 403.
 * @param {string} configuracion.urlEdicion  Ruta de edición con `__ID__`.
 */
export function iniciar({ permisos, urlEdicion }) {
    const caja       = document.getElementById('avisosProveedores');
    const formulario = document.getElementById('formFiltros');
    const cuerpo     = document.getElementById('contenidoTablaProveedores');
    const paginado   = document.getElementById('paginadoProveedores');

    if (! cuerpo) {
        return;
    }

    const columnas = document.querySelectorAll('#tablaProveedores thead th').length;

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
            canal_pedido: parametros.get('canal_pedido') ?? '',
            estado: parametros.get('estado') ?? '',
            page: Number.parseInt(parametros.get('page') ?? '1', 10) || 1,
        };
    }

    function url(filtros) {
        const parametros = new URLSearchParams();

        for (const clave of ['q', 'canal_pedido', 'estado']) {
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
        formulario.elements.canal_pedido.value = filtros.canal_pedido;
        formulario.elements.estado.value = filtros.estado;
    }

    // ------------------------------------------------------------------
    // Traer y pintar
    // ------------------------------------------------------------------

    async function cargar(filtros) {
        const pedido = ++pedidoActual;

        mostrarMensajeEnLaTabla('Cargando proveedores…');

        try {
            const respuesta = await obtener('gestion/proveedores', parametrosDe(filtros));

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
     * Un `estado=` vacío no es un filtro: el endpoint valida
     * `in:todos,activos,inactivos` y una cadena vacía no está en esa lista.
     */
    function parametrosDe(filtros) {
        const parametros = { por_pagina: POR_PAGINA };

        for (const clave of ['q', 'canal_pedido', 'estado']) {
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

    function pintarFilas(proveedores) {
        if (proveedores.length === 0) {
            mostrarMensajeEnLaTabla('No se encontraron proveedores.');

            return;
        }

        cuerpo.replaceChildren(...proveedores.map(fila));
    }

    /** Una fila, con el mismo marcado que tenía el `@forelse`. */
    function fila(proveedor) {
        const tr = document.createElement('tr');

        tr.append(
            celdaRazonSocial(proveedor),
            celdaDeTexto(cuitConGuiones(proveedor.cuit)),
            celdaDeTexto(proveedor.condicion_iva?.nombre ?? '—'),
            celdaDeContacto(proveedor),
            // La etiqueta la define el enum CanalPedido y la manda el recurso:
            // así el panel y la API nombran cada canal igual.
            celdaDeTexto(proveedor.canal_pedido_etiqueta ?? proveedor.canal_pedido ?? '—'),
            celdaDeTexto(String(proveedor.productos_count ?? 0)),
            celdaDeTexto(String(proveedor.ordenes_abiertas_count ?? 0)),
            celdaDeEstado(proveedor),
        );

        const acciones = celdaDeAcciones(proveedor);

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

    /** Razón social con el correo debajo, como en la vista. */
    function celdaRazonSocial(proveedor) {
        const celda = document.createElement('td');

        celda.className = 'text-start';

        const nombre = document.createElement('strong');
        nombre.textContent = proveedor.razon_social;
        celda.append(nombre);

        if (proveedor.email) {
            const correo = document.createElement('small');

            correo.className = 'text-muted';
            correo.textContent = proveedor.email;

            celda.append(document.createElement('br'), correo);
        }

        return celda;
    }

    /** Contacto con el teléfono debajo. */
    function celdaDeContacto(proveedor) {
        const celda = document.createElement('td');

        celda.append(document.createTextNode(proveedor.contacto || '—'));

        if (proveedor.telefono) {
            const telefono = document.createElement('small');

            telefono.className = 'text-muted';
            telefono.textContent = proveedor.telefono;

            celda.append(document.createElement('br'), telefono);
        }

        return celda;
    }

    function celdaDeEstado(proveedor) {
        const celda = document.createElement('td');
        const insignia = document.createElement('span');

        insignia.className = proveedor.activo ? 'badge text-bg-success' : 'badge text-bg-secondary';
        insignia.textContent = proveedor.activo ? 'Activo' : 'Inactivo';

        celda.append(insignia);

        return celda;
    }

    function celdaDeAcciones(proveedor) {
        if (! permisos.editar && ! permisos.cambiarEstado) {
            return null;
        }

        const celda = document.createElement('td');
        celda.className = 'text-nowrap';

        if (permisos.editar) {
            const editar = document.createElement('a');

            editar.href = urlEdicion.replace('__ID__', String(proveedor.id));
            editar.className = 'btn btn-sm btn-warning me-1';
            editar.title = 'Editar';
            editar.innerHTML = ICONOS.editar;

            celda.append(editar);
        }

        if (permisos.cambiarEstado) {
            celda.append(proveedor.activo
                ? boton('btn-secondary me-1', 'Dar de baja', ICONOS.baja, () => cambiarEstado(proveedor, false))
                : boton('btn-success me-1', 'Reactivar', ICONOS.reactivar, () => cambiarEstado(proveedor, true)));
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

    async function cambiarEstado(proveedor, activo) {
        const pregunta = activo
            ? '¿Está seguro de reactivar este proveedor?'
            : '¿Está seguro de dar de baja este proveedor?';

        if (! window.confirm(pregunta)) {
            return;
        }

        bloquearAcciones(true);

        try {
            const respuesta = await parchear(`gestion/proveedores/${proveedor.id}/estado`, { activo });

            avisos.mostrar(caja, 'exito', activo
                ? `«${proveedor.razon_social}» vuelve a estar en operación.`
                : mensajeDeBaja(proveedor, respuesta.advertencias));

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

    /**
     * El mismo aviso que daba el panel, armado con los números que manda la
     * API en `advertencias`.
     */
    function mensajeDeBaja(proveedor, advertencias) {
        let aviso = `«${proveedor.razon_social}» salió de operación.`;

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
            canal_pedido: formulario.elements.canal_pedido.value,
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
