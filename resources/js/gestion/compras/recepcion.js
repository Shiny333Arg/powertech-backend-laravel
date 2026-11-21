/**
 * Recepción de mercadería contra un pedido a proveedor, contra la API.
 *
 * Misma pantalla de siempre —un renglón por producto con lo que falta de cada
 * uno, los dos atajos de carga, el número de remito y el historial de entregas
 * anteriores—; lo que cambió es de dónde salen los datos y a dónde va el
 * guardado:
 *
 *     GET  /api/v1/gestion/compras/{orden}              el pedido y su historial
 *     POST /api/v1/gestion/compras/{orden}/recepciones  la entrega
 *
 * <b>Es el único endpoint de la API de compras que toca el inventario</b>, y
 * esta pantalla no replica nada de lo que hace: RecepcionService acumula en
 * cantidad_recibida con un UPDATE relativo, registra el movimiento de compra
 * con el remito en el motivo, decide el estado del pedido mirando el acumulado
 * de todos los renglones, sincroniza el pedido madre y recorre la cola de
 * preventas después del commit. Acá se cargan unidades y se muestra lo que el
 * backend contesta.
 *
 * ──────────────────────────────────────────────────────────────────────────
 * LA PANTALLA SE QUEDA DONDE ESTÁ
 * ──────────────────────────────────────────────────────────────────────────
 *
 * El POST web redirigía al detalle del pedido con un `session('status')`. Sin
 * redirect no hay flash que arrastrar, y el detalle sigue siendo Blade —es la
 * segunda entrega de la migración—, así que no hay quién pinte un aviso
 * guardado. El resultado de la entrega se muestra entonces acá, y la tabla y el
 * historial se repintan con el detalle que devuelve el 201: cargar un segundo
 * remito contra el mismo pedido ya no obliga a recargar la página.
 *
 * ──────────────────────────────────────────────────────────────────────────
 * DOS RESPUESTAS QUE NO SON ERRORES DE LA PANTALLA
 * ──────────────────────────────────────────────────────────────────────────
 *
 * <b>422 con 'confirmar_excedente'.</b> Recibir más de lo pedido está
 * permitido, pero se confirma: la primera vez el backend contesta con el aviso
 * y la casilla destapada, y si el operador la tilda, pasa. Es la misma regla de
 * RecepcionRequest que tenía el formulario Blade, con el mismo texto.
 *
 * <b>409 'REMITO_DUPLICADO'.</b> Lo agrega la API y el POST web no lo tenía:
 * ese remito ya entró contra este pedido. Sin esa guarda, un reintento por
 * timeout de red sumaría las unidades dos veces al físico. El mensaje que
 * manda el backend se muestra tal cual.
 */

import { crear, obtener, tratar } from '../api.js';
import * as avisos from '../avisos.js';

/**
 * @param {object} configuracion
 * @param {number} configuracion.id  El pedido contra el que entra la mercadería.
 * @param {Record<string, string>} configuracion.colores  value del estado -> color de Bootstrap.
 */
export function iniciar({ id, colores }) {
    const caja     = document.getElementById('avisosRecepcion');
    const cuerpo   = document.getElementById('cuerpoRecepcion');
    const cabecera = document.getElementById('encabezadoPedido');
    const insigniaEstado = document.getElementById('estadoPedido');

    const bloqueExcedente  = document.getElementById('bloqueExcedente');
    const detalleExcedente = document.getElementById('detalleExcedente');
    const confirmar        = document.getElementById('confirmar_excedente');

    const bloqueHistorial = document.getElementById('bloqueHistorial');
    const cuerpoHistorial = document.getElementById('cuerpoHistorial');

    const remito      = document.getElementById('remito');
    const errorRemito = document.getElementById('errorRemito');
    const btnRegistrar = document.getElementById('btnRegistrar');

    if (! cuerpo) {
        return;
    }

    /** Los campos de cantidad, por id de renglón, para leerlos al guardar. */
    const campos = new Map();

    let registrando = false;

    /** El pedido quedó recibido por completo: no admite más entregas. */
    let cerrado = false;

    // ------------------------------------------------------------------
    // Utilidades
    // ------------------------------------------------------------------

    function plural(cantidad, singular, muchos) {
        return `${cantidad} ${cantidad === 1 ? singular : muchos}`;
    }

    /** `d/m/Y H:i`, como el `format('d/m/Y H:i')` de la vista. */
    function fechaYHora(iso) {
        if (! iso) {
            return '—';
        }

        const fecha = new Date(iso);

        if (Number.isNaN(fecha.getTime())) {
            return '—';
        }

        const dia  = String(fecha.getDate()).padStart(2, '0');
        const mes  = String(fecha.getMonth() + 1).padStart(2, '0');
        const hora = String(fecha.getHours()).padStart(2, '0');
        const min  = String(fecha.getMinutes()).padStart(2, '0');

        return `${dia}/${mes}/${fecha.getFullYear()} ${hora}:${min}`;
    }

    function celda(texto, clases = '') {
        const td = document.createElement('td');

        td.className = clases;
        td.textContent = texto;

        return td;
    }

    function mensajeEnLaTabla(texto) {
        const fila = document.createElement('tr');
        const td   = celda(texto, 'text-center py-4 text-muted');

        td.colSpan = 6;
        fila.append(td);
        cuerpo.replaceChildren(fila);
    }

    // ------------------------------------------------------------------
    // Encabezado
    // ------------------------------------------------------------------

    function pintarEncabezado(pedido) {
        cabecera.textContent = `Pedido ${pedido.numero}`
            + (pedido.proveedor ? ` · ${pedido.proveedor.razon_social}` : '');

        insigniaEstado.className = `badge text-bg-${colores[pedido.estado] ?? 'secondary'} align-middle ms-1`;
        insigniaEstado.textContent = pedido.estado_etiqueta ?? pedido.estado ?? '';
        insigniaEstado.classList.remove('d-none');
    }

    // ------------------------------------------------------------------
    // Renglones
    // ------------------------------------------------------------------

    function pintarRenglones(lineas) {
        campos.clear();

        if (lineas.length === 0) {
            mensajeEnLaTabla('Este pedido no tiene renglones.');

            return;
        }

        cuerpo.replaceChildren(...lineas.map(fila));
    }

    function fila(linea) {
        const tr = document.createElement('tr');

        tr.append(
            celda(linea.producto?.codigo ?? '—', 'text-nowrap'),
            celdaProducto(linea),
            celda(String(linea.cantidad_pedida), 'text-nowrap fw-semibold'),
            celda(String(linea.cantidad_recibida), 'text-nowrap'),
            celdaPendiente(linea),
            celdaCantidad(linea),
        );

        return tr;
    }

    function celdaProducto(linea) {
        const td = document.createElement('td');
        td.className = 'text-start';

        const nombre = document.createElement('div');
        nombre.className = 'fw-semibold';
        nombre.textContent = linea.producto?.nombre ?? '—';
        td.append(nombre);

        // Lo que ya entró por encima de lo pedido. Se acepta a propósito —la
        // mercadería está en el depósito— y se informa.
        if (Number(linea.excedente) > 0) {
            const aviso = document.createElement('div');
            aviso.className = 'text-muted small mt-1';

            const insignia = document.createElement('span');
            insignia.className = 'badge text-bg-info';
            insignia.textContent = 'De más';

            aviso.append(
                insignia,
                document.createTextNode(` Ya entraron ${linea.excedente} unidades por encima de lo pedido.`),
            );

            td.append(aviso);
        }

        return td;
    }

    function celdaPendiente(linea) {
        const td = document.createElement('td');
        td.className = 'text-nowrap';

        if (Number(linea.pendiente) > 0) {
            const insignia = document.createElement('span');

            insignia.className = 'badge text-bg-warning';
            insignia.textContent = String(linea.pendiente);
            td.append(insignia);

            return td;
        }

        const completo = document.createElement('span');

        completo.className = 'text-success';
        completo.textContent = '—';
        td.append(completo);

        return td;
    }

    function celdaCantidad(linea) {
        const td = document.createElement('td');

        const campo = document.createElement('input');

        campo.type = 'number';
        campo.className = 'form-control text-center js-cantidad';
        campo.min = '0';
        campo.step = '1';
        campo.inputMode = 'numeric';
        campo.value = '0';
        campo.dataset.pendiente = String(linea.pendiente);
        campo.setAttribute('aria-label', `Unidades recibidas de ${linea.producto?.nombre ?? 'el renglón'}`);

        campos.set(linea.id, campo);
        td.append(campo);

        return td;
    }

    function asignarCantidades(cualDeLasDos) {
        for (const campo of campos.values()) {
            campo.value = cualDeLasDos === 'pendiente' ? campo.dataset.pendiente : '0';
        }
    }

    // ------------------------------------------------------------------
    // Historial
    // ------------------------------------------------------------------

    function pintarHistorial(recepciones) {
        if (recepciones.length === 0) {
            bloqueHistorial.classList.add('d-none');
            cuerpoHistorial.replaceChildren();

            return;
        }

        cuerpoHistorial.replaceChildren(...recepciones.map((movimiento) => {
            const tr = document.createElement('tr');

            tr.append(
                celda(fechaYHora(movimiento.registrado_el), 'text-nowrap'),
                celda(movimiento.producto?.nombre ?? '—'),
                celda(`+${movimiento.cantidad_fisico}`, 'text-center fw-semibold'),
                celda(movimiento.motivo ?? '—', 'text-nowrap'),
                celda(movimiento.usuario?.nombre ?? 'Sistema', 'text-nowrap'),
            );

            return tr;
        }));

        bloqueHistorial.classList.remove('d-none');
    }

    // ------------------------------------------------------------------
    // Traer el pedido
    // ------------------------------------------------------------------

    function pintarPedido(pedido) {
        pintarEncabezado(pedido);
        pintarRenglones(pedido.lineas ?? []);
        pintarHistorial(pedido.recepciones ?? []);
    }

    async function traerPedido() {
        try {
            const respuesta = await obtener(`gestion/compras/${id}`);

            pintarPedido(respuesta.data);

        } catch (error) {
            const mensaje = tratar(error);

            if (mensaje !== null) {
                mensajeEnLaTabla('No se pudo cargar el pedido.');
                cabecera.textContent = '';
                avisos.mostrar(caja, 'error', mensaje);
            }
        }
    }

    // ------------------------------------------------------------------
    // Registrar la entrega
    // ------------------------------------------------------------------

    /**
     * El cuerpo que se manda.
     *
     * `cantidades` es un mapa `id de renglón → unidades que entran ahora`, la
     * misma forma que mandaba el formulario Blade. Los ceros viajan igual: un
     * renglón en cero no es un error, es el producto que no vino en esta
     * entrega, y RecepcionRequest es quien los descarta.
     */
    function cuerpoDeLaEntrega() {
        const cantidades = {};

        for (const [linea, campo] of campos.entries()) {
            cantidades[linea] = campo.value === '' ? 0 : campo.value;
        }

        return {
            cantidades,
            remito: remito.value,
            confirmar_excedente: confirmar.checked,
        };
    }

    /** El mismo resumen que escribía RecepcionController::resumen(). */
    function resumenDeLaEntrega(recepcion) {
        const unidades = plural(recepcion.unidades, 'unidad', 'unidades');

        let mensaje = recepcion.cerrada
            ? `Entraron ${unidades} y el pedido quedó recibido por completo.`
            : `Entraron ${unidades}. El pedido sigue abierto por lo que falta.`;

        if (recepcion.ventas_liberadas > 0) {
            mensaje += recepcion.ventas_liberadas === 1
                ? ' Se destrabó 1 venta que esperaba stock: ya está en preparación.'
                : ` Se destrabaron ${recepcion.ventas_liberadas} ventas que esperaban stock: ya están en preparación.`;
        }

        return mensaje;
    }

    async function registrar() {
        if (registrando) {
            return;
        }

        registrando = true;
        btnRegistrar.disabled = true;
        limpiarErrores();

        try {
            const respuesta = await crear(`gestion/compras/${id}/recepciones`, cuerpoDeLaEntrega());

            pintarPedido(respuesta.data);
            esconderExcedente();

            // El remito se limpia: la entrega siguiente viene con el suyo, y
            // repetir el mismo número contra este pedido lo rechaza el backend
            // con un 409.
            remito.value = '';

            avisos.mostrar(caja, 'exito', resumenDeLaEntrega(respuesta.recepcion ?? {}));

            // Un pedido ya recibido por completo no admite más entregas: la
            // policy devolvería 403. El botón queda deshabilitado en lugar de
            // dejar que lo descubra mandando.
            cerrado = Boolean(respuesta.recepcion?.cerrada);

        } catch (error) {
            if (error?.estado === 422) {
                mostrarErroresDeValidacion(error.errores);

            } else {
                const mensaje = tratar(error);

                if (mensaje !== null) {
                    avisos.mostrar(caja, 'error', mensaje);
                }
            }

        } finally {
            registrando = false;
            btnRegistrar.disabled = cerrado;
        }
    }

    /**
     * Los mensajes del 422.
     *
     * El de 'confirmar_excedente' destapa la casilla con su aviso, que es lo que
     * hacía el @error('confirmar_excedente') de la vista. Los de 'remito' van
     * junto al campo, y el resto —'cantidades' y sus renglones— al cartel de
     * arriba, que es donde los ponía el partial de alertas.
     *
     * @param {Record<string, string[]>|null} porCampo
     */
    function mostrarErroresDeValidacion(porCampo) {
        const errores = porCampo ?? {};

        if (errores.confirmar_excedente) {
            mostrarExcedente(errores.confirmar_excedente[0]);
        }

        if (errores.remito) {
            remito.classList.add('is-invalid');
            errorRemito.textContent = errores.remito[0];
        }

        const otros = Object.entries(errores)
            .filter(([clave]) => clave !== 'confirmar_excedente' && clave !== 'remito')
            .flatMap(([, suyos]) => suyos);

        if (otros.length > 0) {
            avisos.mostrar(caja, 'error', otros.join(' '));
        }
    }

    function mostrarExcedente(texto) {
        detalleExcedente.textContent = texto;
        bloqueExcedente.classList.remove('d-none');
        confirmar.checked = false;
        bloqueExcedente.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function esconderExcedente() {
        bloqueExcedente.classList.add('d-none');
        detalleExcedente.textContent = '';
        confirmar.checked = false;
    }

    function limpiarErrores() {
        avisos.limpiar(caja);
        remito.classList.remove('is-invalid');
        errorRemito.textContent = '';
    }

    // ------------------------------------------------------------------
    // Arranque
    // ------------------------------------------------------------------

    document.getElementById('completarPendiente')
        ?.addEventListener('click', () => asignarCantidades('pendiente'));

    document.getElementById('limpiarCantidades')
        ?.addEventListener('click', () => asignarCantidades('cero'));

    btnRegistrar.addEventListener('click', () => registrar());

    traerPedido();
}
