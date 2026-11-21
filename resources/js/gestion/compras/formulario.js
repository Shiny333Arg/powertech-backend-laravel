/**
 * Alta y edición del detalle de un pedido a proveedores, contra la API.
 *
 * Misma pantalla de siempre —el buscador del catálogo, el botón de «traer lo
 * que falta pedir», la tabla de renglones, el resumen por proveedor y los
 * totales—; lo que cambió es de dónde salen los datos y a dónde va el guardado:
 *
 *     GET  /api/v1/gestion/compras/{orden}        el pedido que se edita
 *     GET  /api/v1/gestion/productos?q=&estado=   el buscador
 *     GET  /api/v1/gestion/compras/sugerencias    lo que falta pedir
 *     POST /api/v1/gestion/compras                alta del borrador
 *     PUT  /api/v1/gestion/compras/{orden}        edición del detalle
 *
 * ──────────────────────────────────────────────────────────────────────────
 * EL DESTINATARIO NO SE ELIGE
 * ──────────────────────────────────────────────────────────────────────────
 *
 * Sale de los productos que se carguen: si todos los renglones son del mismo
 * proveedor el pedido queda dirigido a él, y si hay varios se reparte en una
 * orden por proveedor al aprobarse. Por eso el cuerpo que se manda son
 * `lineas` y `observaciones` y nada más: ni proveedor, ni estado, ni origen, ni
 * costos, ni total. Todo eso lo decide el backend —ver OrdenCompraRequest—, y
 * esta pantalla no lo manda ni siquiera informativamente.
 *
 * <b>El costo que se muestra es del catálogo y es una estimación.</b> El
 * servidor lo vuelve a tomar de la ficha del producto al guardar, así que el
 * total de la columna derecha puede diferir en centavos del que quede
 * persistido si alguien cambió el costo mientras el formulario estaba abierto.
 * Era igual antes de la migración.
 *
 * <b>Acá no hay ninguna regla de negocio.</b> Que un renglón sin proveedor
 * activo no se pueda pedir lo decide OrdenCompraRequest contra la base; la
 * pantalla lo muestra antes para que el administrativo no arme el pedido entero
 * y se lo rechacen al final, pero quien manda igual recibe un 422.
 */

import { crear, obtener, reemplazar, tratar } from '../api.js';
import * as avisos from '../avisos.js';

/** Lo mismo que pedía el buscador anterior antes de salir a consultar. */
const MINIMO_PARA_BUSCAR = 2;

/** Tope de resultados del desplegable, como el `limit(20)` de la ruta vieja. */
const RESULTADOS = 20;

/**
 * @param {object} configuracion
 * @param {number|null} configuracion.id  El pedido que se edita, o null en el alta.
 * @param {string} configuracion.urlDetalle  Ruta del detalle con `__ID__`.
 */
export function iniciar({ id = null, urlDetalle }) {
    const esEdicion = id !== null;

    const caja        = document.getElementById('avisosPedido');
    const cuerpo      = document.getElementById('cuerpoRenglones');
    const buscador    = document.getElementById('buscadorProducto');
    const resultados  = document.getElementById('resultadosProducto');
    const resumen     = document.getElementById('resumenProveedores');
    const observaciones = document.getElementById('observaciones');
    const errorLineas = document.getElementById('errorLineas');

    const totalRenglones = document.getElementById('totalRenglones');
    const totalUnidades  = document.getElementById('totalUnidades');
    const totalEstimado  = document.getElementById('totalEstimado');

    const avisoSinProveedor = document.getElementById('avisoSinProveedor');
    const btnGuardar        = document.getElementById('btnGuardar');
    const btnSugerencias    = document.getElementById('btnSugerencias');
    const avisoSugerencias  = document.getElementById('avisoSugerencias');

    // Sólo existen en la edición.
    const destino        = document.getElementById('destinoDelPedido');
    const avisoAutomatico = document.getElementById('avisoOrigenAutomatico');
    const numeroPedido   = document.getElementById('numeroPedido');
    const numeroVolver   = document.getElementById('numeroVolver');

    if (! cuerpo) {
        return;
    }

    /** @type {Array<{producto_id:number, codigo:string, nombre:string, marca:?string, costo:?number, cantidad:number, proveedor:?object}>} */
    let renglones = [];

    let guardando = false;

    // ------------------------------------------------------------------
    // Utilidades
    // ------------------------------------------------------------------

    /** `$ 1.234,56`, como el `number_format($monto, 2, ',', '.')` de la vista. */
    function pesos(monto) {
        return `$ ${Number(monto ?? 0).toLocaleString('es-AR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        })}`;
    }

    function plural(cantidad, singular, muchos) {
        return `${cantidad} ${cantidad === 1 ? singular : muchos}`;
    }

    function debounce(fn, ms) {
        let reloj;

        return (...argumentos) => {
            clearTimeout(reloj);
            reloj = setTimeout(() => fn(...argumentos), ms);
        };
    }

    /**
     * Un renglón se puede pedir si tiene proveedor y ese proveedor está activo.
     *
     * Es la misma condición que valida el servidor: conviene que el usuario la
     * vea antes de mandar el pedido y que se lo rechacen.
     */
    function pedible(renglon) {
        return Boolean(renglon.proveedor && renglon.proveedor.activo);
    }

    // ------------------------------------------------------------------
    // Renglones
    // ------------------------------------------------------------------

    function dibujarRenglones() {
        if (renglones.length === 0) {
            const fila  = document.createElement('tr');
            const celda = document.createElement('td');

            celda.colSpan = 5;
            celda.className = 'text-center py-4 text-muted';
            celda.textContent = 'Todavía no cargaste productos. Buscá uno arriba para empezar.';

            fila.append(celda);
            cuerpo.replaceChildren(fila);
        } else {
            cuerpo.replaceChildren(...renglones.map(fila));
        }

        actualizarResumen();
    }

    function fila(renglon, indice) {
        const tr = document.createElement('tr');

        if (! pedible(renglon)) {
            tr.className = 'table-warning';
        }

        tr.append(
            celdaProducto(renglon),
            celdaMarca(renglon),
            celdaProveedor(renglon),
            celdaCantidad(renglon, indice),
            celdaQuitar(indice),
        );

        return tr;
    }

    function celdaProducto(renglon) {
        const celda = document.createElement('td');
        celda.className = 'text-start';

        const nombre = document.createElement('div');
        nombre.className = 'fw-semibold';
        nombre.textContent = renglon.nombre;

        const codigo = document.createElement('div');
        codigo.className = 'text-muted small';
        codigo.textContent = renglon.codigo;

        celda.append(nombre, codigo);

        return celda;
    }

    function celdaMarca(renglon) {
        const celda = document.createElement('td');
        celda.className = 'small';

        if (renglon.marca) {
            celda.textContent = renglon.marca;
        } else {
            const vacio = document.createElement('span');
            vacio.className = 'text-muted';
            vacio.textContent = '—';
            celda.append(vacio);
        }

        return celda;
    }

    function celdaProveedor(renglon) {
        const celda = document.createElement('td');

        if (! renglon.proveedor) {
            const insignia = document.createElement('span');

            insignia.className = 'badge text-bg-danger';
            insignia.textContent = 'Sin proveedor';
            celda.append(insignia);

            return celda;
        }

        const nombre = document.createElement('div');
        nombre.className = 'small';
        nombre.textContent = renglon.proveedor.razon_social;
        celda.append(nombre);

        if (! renglon.proveedor.activo) {
            const baja = document.createElement('span');

            baja.className = 'badge text-bg-warning mt-1';
            baja.textContent = 'Dado de baja';
            celda.append(baja);
        }

        return celda;
    }

    function celdaCantidad(renglon, indice) {
        const celda = document.createElement('td');

        const campo = document.createElement('input');

        campo.type = 'number';
        campo.className = 'form-control form-control-sm text-center';
        campo.value = String(renglon.cantidad);
        campo.min = '1';
        campo.max = '999999';
        campo.setAttribute('aria-label', `Unidades a pedir de ${renglon.nombre}`);

        campo.addEventListener('input', () => {
            const valor = Number.parseInt(campo.value, 10);

            renglones[indice].cantidad = Number.isNaN(valor) || valor < 1 ? 1 : valor;
            actualizarResumen();
        });

        celda.append(campo);

        return celda;
    }

    function celdaQuitar(indice) {
        const celda = document.createElement('td');

        const boton = document.createElement('button');

        boton.type = 'button';
        boton.className = 'btn btn-sm btn-outline-danger';
        boton.title = 'Quitar';
        boton.textContent = '×';

        boton.addEventListener('click', () => {
            renglones.splice(indice, 1);
            dibujarRenglones();
        });

        celda.append(boton);

        return celda;
    }

    function actualizarResumen() {
        const unidades = renglones.reduce((suma, renglon) => suma + Number(renglon.cantidad), 0);
        const estimado = renglones.reduce(
            (suma, renglon) => suma + (Number(renglon.costo) || 0) * Number(renglon.cantidad),
            0,
        );

        const huerfanos = renglones.filter((renglon) => ! pedible(renglon));

        totalRenglones.textContent = String(renglones.length);
        totalUnidades.textContent = String(unidades);
        totalEstimado.textContent = pesos(estimado);

        avisoSinProveedor.classList.toggle('d-none', huerfanos.length === 0);

        // Guardar con un renglón sin proveedor lo rechaza el servidor: no tiene
        // sentido dejar que mande el pedido y pierda el tiempo.
        btnGuardar.disabled = guardando || renglones.length === 0 || huerfanos.length > 0;

        dibujarResumenDeProveedores();
        dibujarDestino();
    }

    /** Los grupos por proveedor, que son las órdenes que se van a emitir. */
    function grupos() {
        const porProveedor = [];

        for (const renglon of renglones) {
            if (! renglon.proveedor) {
                continue;
            }

            let grupo = porProveedor.find((candidato) => candidato.id === renglon.proveedor.id);

            if (! grupo) {
                grupo = {
                    id: renglon.proveedor.id,
                    nombre: renglon.proveedor.razon_social,
                    activo: renglon.proveedor.activo,
                    items: 0,
                    unidades: 0,
                };

                porProveedor.push(grupo);
            }

            grupo.items += 1;
            grupo.unidades += Number(renglon.cantidad);
        }

        return porProveedor;
    }

    function dibujarResumenDeProveedores() {
        const porProveedor = grupos();

        if (porProveedor.length === 0) {
            const vacio = document.createElement('div');

            vacio.className = 'text-muted small';
            vacio.textContent = 'Cargá productos para ver a quiénes se les va a pedir.';
            resumen.replaceChildren(vacio);

            return;
        }

        const encabezado = document.createElement('div');
        encabezado.className = 'small text-muted';

        if (porProveedor.length === 1) {
            encabezado.textContent = 'Va a quedar una sola orden, dirigida a:';
        } else {
            encabezado.append(document.createTextNode('Al aprobarlo se van a emitir '));

            const cuantas = document.createElement('strong');
            cuantas.textContent = `${porProveedor.length} órdenes`;

            encabezado.append(cuantas, document.createTextNode(':'));
        }

        const filas = porProveedor.map((grupo) => {
            const caja = document.createElement('div');
            caja.className = 'd-flex justify-content-between align-items-center border rounded-3 bg-white p-2';

            const nombre = document.createElement('span');
            nombre.className = 'small fw-semibold';
            nombre.textContent = grupo.nombre;

            if (! grupo.activo) {
                const baja = document.createElement('span');
                baja.className = 'badge text-bg-warning ms-1';
                baja.textContent = 'baja';
                nombre.append(baja);
            }

            const cuenta = document.createElement('span');
            cuenta.className = 'text-muted small text-nowrap';
            cuenta.textContent = `${plural(grupo.items, 'ítem', 'ítems')} · ${grupo.unidades} u.`;

            caja.append(nombre, cuenta);

            return caja;
        });

        resumen.replaceChildren(encabezado, ...filas);
    }

    /**
     * El cartel de arriba de la edición: a quién le va a llegar este pedido.
     *
     * <b>Se redibuja con cada cambio, y antes no.</b> Era un dato que el
     * servidor imprimía al abrir la página y quedaba viejo en cuanto se
     * agregaba un producto de otro proveedor, que es exactamente la operación
     * que lo cambia.
     */
    function dibujarDestino() {
        if (! destino) {
            return;
        }

        const porProveedor = grupos();

        const alerta = document.createElement('div');
        alerta.className = 'alert alert-light border';

        if (porProveedor.length === 1) {
            alerta.append(
                linea('text-secondary small d-block', 'Por ahora va a un solo proveedor'),
                fuerte(porProveedor[0].nombre),
            );
        } else if (porProveedor.length > 1) {
            alerta.append(
                linea('text-secondary small d-block', `Al aprobarlo se van a emitir ${porProveedor.length} órdenes, una para`),
                fuerte(porProveedor.map((grupo) => grupo.nombre).join(' · ')),
            );
        } else {
            alerta.append(fuerte('Este pedido no tiene renglones.'));
        }

        alerta.append(linea(
            'text-secondary small d-block mt-1',
            'El destinatario sale de los productos: cada proveedor recibe por correo solamente los '
            + 'renglones que le corresponden.',
        ));

        destino.replaceChildren(alerta);
    }

    function linea(clases, texto) {
        const span = document.createElement('span');

        span.className = clases;
        span.textContent = texto;

        return span;
    }

    function fuerte(texto) {
        const elemento = document.createElement('strong');

        elemento.textContent = texto;

        return elemento;
    }

    /**
     * Suma el producto al detalle, o le sube uno a la cantidad si ya estaba.
     *
     * Sumar en vez de abrir otro renglón: el servidor rechaza productos
     * repetidos —`distinct` en OrdenCompraRequest—, y el proveedor no tiene por
     * qué recibir un remito con el mismo ítem dos veces.
     */
    function agregarProducto(producto) {
        const existente = renglones.find((renglon) => renglon.producto_id === producto.id);

        if (existente) {
            existente.cantidad += 1;
        } else {
            renglones.push({
                producto_id: producto.id,
                codigo: producto.codigo,
                nombre: producto.nombre,
                marca: producto.marca,
                costo: producto.costo,
                cantidad: 1,
                proveedor: producto.proveedor,
            });
        }

        buscador.value = '';
        ocultarResultados();
        dibujarRenglones();
    }

    // ------------------------------------------------------------------
    // Buscador del catálogo
    // ------------------------------------------------------------------

    function ocultarResultados() {
        resultados.classList.add('d-none');
        resultados.replaceChildren();
    }

    async function buscar(texto) {
        if (texto.trim().length < MINIMO_PARA_BUSCAR) {
            ocultarResultados();

            return;
        }

        try {
            // 'estado=activos' es lo que hacía el `where('activo', true)` de la
            // ruta vieja: un producto dado de baja no se pide.
            const respuesta = await obtener('gestion/productos', {
                q: texto.trim(),
                estado: 'activos',
                por_pagina: RESULTADOS,
            });

            pintarResultados(respuesta.data ?? []);

        } catch (error) {
            const mensaje = tratar(error);

            if (mensaje !== null) {
                ocultarResultados();
                avisos.mostrar(caja, 'error', mensaje);
            }
        }
    }

    function pintarResultados(productos) {
        if (productos.length === 0) {
            ocultarResultados();

            return;
        }

        resultados.replaceChildren(...productos.map(opcion));
        resultados.classList.remove('d-none');
    }

    function opcion(producto) {
        const boton = document.createElement('button');

        boton.type = 'button';
        boton.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-3';

        const izquierda = document.createElement('span');
        izquierda.className = 'text-start';

        const nombre = document.createElement('span');
        nombre.className = 'fw-semibold';
        nombre.textContent = producto.nombre;

        const codigo = document.createElement('span');
        codigo.className = 'text-muted small ms-2';
        codigo.textContent = producto.codigo;

        const detalle = document.createElement('span');
        detalle.className = 'd-block text-muted small';
        detalle.textContent = (producto.marca ? `${producto.marca} · ` : '')
            + (producto.proveedor ? producto.proveedor.razon_social : 'sin proveedor');

        izquierda.append(nombre, codigo, detalle);

        // El disponible va en el desplegable porque es el motivo por el que
        // alguien abre esta pantalla: se pide lo que falta.
        const falta = Number(producto.stock_disponible) <= Number(producto.punto_reposicion);

        const derecha = document.createElement('span');
        derecha.className = 'text-nowrap';

        const insignia = document.createElement('span');
        insignia.className = `badge text-bg-${falta ? 'danger' : 'secondary'}`;
        insignia.textContent = `${producto.stock_disponible} disp.`;

        derecha.append(insignia);

        boton.append(izquierda, derecha);
        boton.addEventListener('click', () => agregarProducto(producto));

        return boton;
    }

    // ------------------------------------------------------------------
    // Traer lo que falta pedir
    // ------------------------------------------------------------------

    function avisar(nodos, clase) {
        avisoSugerencias.className = `alert ${clase} small`;
        avisoSugerencias.replaceChildren(...nodos);
        avisoSugerencias.classList.remove('d-none');
    }

    /**
     * Mezcla lo sugerido con lo que ya está cargado.
     *
     * La cantidad SOLO SUBE, igual que OrdenCompraService::ampliar(). Si el
     * administrativo escribió un número mayor, es una decisión suya y el botón
     * no se la pisa; si escribió uno menor, la sugerencia lo corrige porque esa
     * es la cantidad que saca al producto del punto de reposición.
     */
    function fusionar(sugeridos) {
        let agregados = 0;
        let ajustados = 0;

        for (const sugerido of sugeridos) {
            const existente = renglones.find((renglon) => renglon.producto_id === sugerido.producto_id);

            if (! existente) {
                renglones.push({
                    producto_id: sugerido.producto_id,
                    codigo: sugerido.codigo,
                    nombre: sugerido.nombre,
                    marca: sugerido.marca,
                    costo: sugerido.costo,
                    cantidad: Number(sugerido.cantidad_sugerida),
                    proveedor: sugerido.proveedor,
                });

                agregados += 1;

                continue;
            }

            if (Number(sugerido.cantidad_sugerida) > Number(existente.cantidad)) {
                existente.cantidad = Number(sugerido.cantidad_sugerida);
                ajustados += 1;
            }
        }

        return { agregados, ajustados };
    }

    function resumenDeSugerencias({ agregados, ajustados }, omitidos) {
        const partes = [];

        if (agregados > 0) {
            partes.push(`Se agregaron ${plural(agregados, 'producto', 'productos')}.`);
        }

        if (ajustados > 0) {
            partes.push(`Se subió la cantidad de ${plural(ajustados, 'renglón', 'renglones')} que ya estaban cargados.`);
        }

        if (partes.length === 0) {
            partes.push(omitidos.length > 0
                ? 'No hay nada más para agregar.'
                : 'No hay productos en falta sin pedir: está todo cubierto.');
        }

        return partes.join(' ');
    }

    /**
     * Los que no se pueden pedir no se esconden: un producto en falta que nadie
     * va a reponer porque le falta el proveedor es justamente lo que hay que
     * ver.
     */
    function listaDeOmitidos(omitidos) {
        const bloque = document.createElement('div');
        bloque.className = 'mt-2';

        bloque.append(fuerte(`No se pudieron agregar ${plural(omitidos.length, 'producto', 'productos')}:`));

        const lista = document.createElement('ul');
        lista.className = 'mb-0 mt-2';

        for (const omitido of omitidos) {
            const item = document.createElement('li');

            item.append(document.createTextNode(omitido.nombre));

            const codigo = document.createElement('span');
            codigo.className = 'text-muted';
            codigo.textContent = ` (${omitido.codigo})`;

            item.append(codigo, document.createTextNode(` — ${omitido.motivo}`));
            lista.append(item);
        }

        bloque.append(lista);

        return bloque;
    }

    async function traerSugerencias() {
        btnSugerencias.disabled = true;
        btnSugerencias.textContent = 'Buscando…';
        avisoSugerencias.classList.add('d-none');

        try {
            const respuesta = await obtener('gestion/compras/sugerencias');

            const sugeridos = respuesta.data ?? [];
            const omitidos  = respuesta.omitidos ?? [];

            const resultado = fusionar(sugeridos);

            dibujarRenglones();

            const nodos = [document.createTextNode(resumenDeSugerencias(resultado, omitidos))];

            if (omitidos.length > 0) {
                nodos.push(listaDeOmitidos(omitidos));
            }

            avisar(nodos, omitidos.length > 0 ? 'alert-warning' : 'alert-info');

        } catch (error) {
            const mensaje = tratar(error);

            if (mensaje !== null) {
                avisar(
                    [document.createTextNode(
                        `${mensaje} Si hace falta, cargá los productos con el buscador.`
                    )],
                    'alert-danger',
                );
            }

        } finally {
            btnSugerencias.disabled = false;
            btnSugerencias.textContent = 'Traer lo que falta pedir';
        }
    }

    // ------------------------------------------------------------------
    // Traer el pedido que se edita
    // ------------------------------------------------------------------

    async function traerPedido() {
        try {
            const respuesta = await obtener(`gestion/compras/${id}`);
            const pedido = respuesta.data;

            // El proveedor de la línea manda sobre el del producto: es el
            // snapshot de a quién se le pidió, y puede no ser el que el
            // producto tiene hoy.
            renglones = (pedido.lineas ?? [])
                .filter((linea) => linea.producto !== null)
                .map((linea) => ({
                    producto_id: linea.producto.id,
                    codigo: linea.producto.codigo,
                    nombre: linea.producto.nombre,
                    marca: linea.producto.marca,
                    costo: linea.costo_unitario,
                    cantidad: linea.cantidad_pedida,
                    proveedor: linea.proveedor,
                }));

            observaciones.value = pedido.observaciones ?? '';

            mostrarNumero(pedido.numero);
            mostrarOrigenAutomatico(pedido);

            dibujarRenglones();

        } catch (error) {
            const mensaje = tratar(error);

            if (mensaje !== null) {
                avisos.mostrar(caja, 'error', mensaje);
            }
        }
    }

    function mostrarNumero(numero) {
        if (numeroPedido) {
            numeroPedido.textContent = numero;
        }

        if (numeroVolver) {
            numeroVolver.textContent = numero;
        }
    }

    /**
     * El aviso de los pedidos que generó la reposición automática.
     *
     * Se dibuja una sola vez, al abrir, con el origen y los destinatarios que
     * tenía el pedido guardado: es lo mismo que hacía la vista. El segundo
     * párrafo sólo aplica mientras el pedido siga siendo de un proveedor, que
     * es la condición para que el barrido pueda seguir ampliándolo.
     */
    function mostrarOrigenAutomatico(pedido) {
        if (! avisoAutomatico || pedido.origen !== 'automatica') {
            return;
        }

        const alerta = document.createElement('div');
        alerta.className = 'alert alert-info small';

        alerta.append(document.createTextNode(
            'Este pedido lo generó la reposición automática. Editarlo es normal —para ajustar '
            + 'cantidades antes de aprobarlo—, pero tené en cuenta que las cantidades sugeridas '
            + 'cubren el faltante hasta el punto de reposición más el lote de compra de cada producto.'
        ));

        if ((pedido.proveedores ?? []).length === 1) {
            alerta.append(
                document.createElement('br'),
                document.createTextNode(
                    'Mientras siga siendo de un solo proveedor, la reposición lo puede seguir ampliando '
                    + 'sola. Si le agregás productos de otro, deja de ser el borrador abierto de ese '
                    + 'proveedor y el automático va a abrir uno nuevo en lugar de tocar este.'
                ),
            );
        }

        avisoAutomatico.replaceChildren(alerta);
    }

    // ------------------------------------------------------------------
    // Guardar
    // ------------------------------------------------------------------

    /**
     * El cuerpo que se manda: los renglones y las observaciones.
     *
     * `cantidad_pedida` es el nombre que espera OrdenCompraRequest, el mismo
     * FormRequest que validaba el formulario Blade.
     */
    function cuerpoDelPedido() {
        return {
            lineas: renglones.map((renglon) => ({
                producto_id: renglon.producto_id,
                cantidad_pedida: renglon.cantidad,
            })),
            observaciones: observaciones.value,
        };
    }

    async function guardar() {
        if (guardando) {
            return;
        }

        guardando = true;
        btnGuardar.disabled = true;
        limpiarErrores();

        try {
            const respuesta = esEdicion
                ? await reemplazar(`gestion/compras/${id}`, cuerpoDelPedido())
                : await crear('gestion/compras', cuerpoDelPedido());

            // Al detalle, que es a donde redirigía el controlador web.
            //
            // <b>Sin aviso guardado.</b> El detalle todavía es Blade y no tiene
            // quién lo pinte —es la segunda entrega de la migración—, y dejar el
            // mensaje en sessionStorage haría que apareciera más tarde, en
            // cualquier otra pantalla migrada que lo consuma. Cuando el detalle
            // se migre, acá va un avisos.recordar() como en clientes.
            window.location.assign(urlDetalle.replace('__ID__', String(respuesta.data.id)));

        } catch (error) {
            guardando = false;

            if (error?.estado === 422) {
                mostrarErroresDeValidacion(error.errores);
                actualizarResumen();

                return;
            }

            const mensaje = tratar(error);

            if (mensaje !== null) {
                avisos.mostrar(caja, 'error', mensaje);
            }

            actualizarResumen();
        }
    }

    /**
     * Los mensajes del 422, en el mismo lugar donde los ponía Blade: la lista de
     * arriba del formulario, con el mismo encabezado.
     *
     * @param {Record<string, string[]>|null} porCampo
     */
    function mostrarErroresDeValidacion(porCampo) {
        const mensajes = Object.values(porCampo ?? {}).flat();

        if (mensajes.length === 0) {
            avisos.mostrar(caja, 'error', 'No pudimos guardar el pedido. Revisá los renglones.');

            return;
        }

        const alerta = document.createElement('div');
        alerta.className = 'alert alert-danger';

        const lista = document.createElement('ul');
        lista.className = 'mb-0 mt-2';

        for (const mensaje of mensajes) {
            const item = document.createElement('li');
            item.textContent = mensaje;
            lista.append(item);
        }

        alerta.append(fuerte('Revisá estos campos:'), lista);
        caja.replaceChildren(alerta);

        // Los de 'lineas' también van debajo de la tabla, que es donde estaba el
        // @error('lineas') de la vista.
        const deLineas = [
            ...(porCampo.lineas ?? []),
            ...Object.entries(porCampo)
                .filter(([clave]) => clave.startsWith('lineas.'))
                .flatMap(([, suyos]) => suyos),
        ];

        if (deLineas.length > 0 && errorLineas) {
            errorLineas.textContent = deLineas.join(' ');
            errorLineas.classList.remove('d-none');
        }

        caja.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function limpiarErrores() {
        avisos.limpiar(caja);

        if (errorLineas) {
            errorLineas.replaceChildren();
            errorLineas.classList.add('d-none');
        }
    }

    // ------------------------------------------------------------------
    // Arranque
    // ------------------------------------------------------------------

    buscador.addEventListener('input', debounce(() => buscar(buscador.value), 300));

    // Enter en el buscador no manda nada: lo normal es que el usuario esté
    // buscando, no terminando.
    buscador.addEventListener('keydown', (evento) => {
        if (evento.key === 'Enter') {
            evento.preventDefault();
        }
    });

    document.addEventListener('click', (evento) => {
        if (! evento.target.closest('#resultadosProducto') && evento.target !== buscador) {
            ocultarResultados();
        }
    });

    btnSugerencias?.addEventListener('click', () => traerSugerencias());
    btnGuardar.addEventListener('click', () => guardar());

    dibujarRenglones();

    if (esEdicion) {
        traerPedido();
    }
}
