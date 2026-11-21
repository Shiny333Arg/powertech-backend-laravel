{{--
    Detalle de un pedido a proveedores. Lo comparten el alta y la edición.

    <b>La página entrega la estructura y nada más.</b> Los renglones, las
    observaciones, el resumen por proveedor y los totales los dibuja
    resources/js/gestion/compras/formulario.js, que en el alta arranca vacío y
    en la edición pide el pedido a GET /api/v1/gestion/compras/{orden}. El
    guardado va a POST /api/v1/gestion/compras o a PUT .../{orden}, así que acá
    ya no hay action, ni @csrf, ni @method: no hay formulario que se mande al
    panel.

    El proveedor NO se elige: sale de los productos que se carguen. Si todos los
    renglones son del mismo, el pedido queda dirigido a él y se comporta como
    siempre; si hay varios, al aprobarlo se emite una orden por proveedor y cada
    uno recibe por correo solamente sus renglones. Por eso la columna de
    proveedor es informativa: muestra a quién se le va a pedir cada cosa.

    Cuando un producto pueda tener varios proveedores, esa celda pasa a ser un
    desplegable y no hay que tocar nada más: la línea ya guarda su proveedor_id.

    El costo unitario no se edita ni se muestra por renglón: sale del catálogo
    (producto->costo) y el dato tiene un solo dueño, que es la ficha del
    producto. Lo que sí se ve es el estimado del pedido, que es la referencia que
    el administrativo necesita para aprobar.

    El buscador va contra GET /api/v1/gestion/productos con `estado=activos`,
    que es el mismo endpoint que usa el listado del catálogo. Antes iba contra
    gestion.buscar.productos; esa ruta sigue publicada porque la usan ventas y
    promociones, pero esta pantalla ya no la necesita.

    El botón «traer lo que falta pedir» va contra
    GET /api/v1/gestion/compras/sugerencias y carga de una todos los productos
    bajo su punto de reposición que ningún proveedor tiene todavía. Los
    borradores automáticos que queden cubiertos se dan de baja solos cuando este
    pedido se apruebe, no antes: si el formulario se abandona, esos borradores
    siguen en pie.

    Espera:
      $volverA        URL del botón Volver (el listado en el alta, el detalle en
                      la edición)
      $textoGuardar   Etiqueta del botón de guardar
--}}

{{--
    Avisos de la pantalla: el error de la API y la lista de mensajes de
    validación. Antes era el bloque de `$errors->any()` que dejaba el redirect
    del controlador web; ahora no hay redirect y el cartel lo pinta
    resources/js/gestion/avisos.js, con el mismo marcado de Bootstrap.
--}}
<div id="avisosPedido" aria-live="polite"></div>

<div class="row g-4">

    <!-- Columna izquierda: detalle -->
    <div class="col-12 col-lg-8">

        <!-- Buscador y renglones -->
        <section class="p-4 rounded-4 bg-light shadow-sm border mb-4">

            <h2 class="h5 fw-bold mb-1">Productos a pedir</h2>

            <p class="text-muted small">
                Buscá por código, nombre o marca en todo el catálogo. El proveedor
                de cada renglón sale de la ficha del producto.
            </p>

            <div class="position-relative mb-3">

                <label for="buscadorProducto" class="form-label fw-semibold">
                    Agregar producto
                </label>

                <input
                    type="text"
                    class="form-control rounded-3"
                    id="buscadorProducto"
                    placeholder="Ej: RTX 3070"
                    maxlength="150"
                    autocomplete="off">

                <div
                    class="list-group position-absolute w-100 shadow d-none"
                    id="resultadosProducto"
                    style="z-index: 1050; max-height: 18rem; overflow-y: auto;">
                </div>

            </div>


            {{--
                El atajo del administrativo: en vez de buscar uno por uno los
                diez productos que están en falta, los trae todos.

                Deja afuera lo que ya está en manos de un proveedor —orden
                aprobada, enviada o recibida a medias—, porque eso ya se
                pidió. Lo que solo figura en un borrador SÍ entra: un
                borrador no es un compromiso y este pedido lo reemplaza.
            --}}
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">

                <button
                    type="button"
                    class="btn btn-outline-primary btn-sm fw-semibold"
                    id="btnSugerencias">
                    Traer lo que falta pedir
                </button>

                <span class="text-muted small" id="ayudaSugerencias">
                    Productos bajo su punto de reposición que todavía no le llegaron a ningún proveedor.
                </span>

            </div>

            <div class="alert alert-info small d-none" id="avisoSugerencias" role="status"></div>


            <div class="table-responsive">

                <table class="table table-bordered align-middle text-center mb-0" id="tablaRenglones">

                    <thead class="table-dark">

                        <tr>
                            <th class="text-nowrap">Producto</th>
                            <th class="text-nowrap" style="width: 10rem;">Marca</th>
                            <th class="text-nowrap" style="width: 14rem;">Proveedor</th>
                            <th class="text-nowrap" style="width: 9rem;">Cantidad</th>
                            <th class="text-nowrap" style="width: 5rem;">Quitar</th>
                        </tr>

                    </thead>

                    <tbody id="cuerpoRenglones">
                        {{-- Lo dibuja resources/js/gestion/compras/formulario.js --}}
                    </tbody>

                </table>

            </div>

            <div class="text-danger small mt-2 d-none" id="errorLineas"></div>

        </section>


        <!-- Observaciones -->
        <section class="p-4 rounded-4 bg-light shadow-sm border">

            <label for="observaciones" class="form-label fw-semibold">
                Observaciones
            </label>

            <textarea
                class="form-control rounded-3"
                id="observaciones"
                name="observaciones"
                rows="3"
                maxlength="2000"
                style="resize: none"
                placeholder="Notas internas sobre este pedido."></textarea>

            <div class="form-text">
                Son internas: no salen en el remito que recibe el proveedor.
            </div>

            <div class="invalid-feedback" id="errorObservaciones"></div>

        </section>

    </div>


    <!-- Columna derecha: resumen -->
    <div class="col-12 col-lg-4">

        <!-- Cómo se va a emitir -->
        <section class="p-4 rounded-4 bg-light shadow-sm border mb-4">

            <h2 class="h6 fw-bold mb-1">Cómo se va a emitir</h2>

            <p class="text-muted small mb-3">
                Un pedido con productos de varios proveedores se reparte al aprobarlo:
                cada uno recibe una orden con sus renglones y nada más.
            </p>

            <div id="resumenProveedores" class="vstack gap-2">
                {{-- Lo dibuja el módulo --}}
            </div>

        </section>


        <!-- Totales -->
        <section class="p-4 rounded-4 bg-white shadow-sm border">

            <div class="d-flex justify-content-between align-items-center">
                <span class="text-muted">Renglones</span>
                <span class="fw-semibold" id="totalRenglones">0</span>
            </div>

            <hr>

            <div class="d-flex justify-content-between align-items-center">
                <span class="text-muted">Unidades</span>
                <span class="fw-semibold" id="totalUnidades">0</span>
            </div>

            <hr>

            <div class="d-flex justify-content-between align-items-center">
                <span class="text-muted">Total estimado</span>
                <span class="h5 fw-bold mb-0" id="totalEstimado">$ 0,00</span>
            </div>

            <div class="form-text">
                Al costo del catálogo. El precio real lo fija la factura del proveedor
                cuando llega la mercadería.
            </div>

            <div class="alert alert-warning small mt-3 d-none" id="avisoSinProveedor">
                Hay renglones sin proveedor al que pedirles. Quitalos, o asignales un
                proveedor desde la ficha del producto.
            </div>

            <div class="mt-4 d-flex flex-wrap gap-2">

                <button
                    type="button"
                    class="btn btn-success px-4 fw-semibold shadow-sm"
                    id="btnGuardar"
                    disabled>
                    {{ $textoGuardar }}
                </button>

                <a
                    href="{{ $volverA }}"
                    class="btn btn-outline-secondary px-4 fw-semibold shadow-sm">
                    Volver
                </a>

            </div>

            <div class="form-text mt-3">
                Se guarda en borrador. Ninguna orden sale sin que un administrativo
                la apruebe.
            </div>

        </section>

    </div>

</div>
