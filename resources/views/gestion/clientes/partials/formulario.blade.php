{{--
    Ficha del cliente. La comparten el alta y la edición.

    Variables que recibe:
      $tiposDocumento  Collection de TipoDocumento (necesita id, nombre, codigo_afip)
      $condicionesIva  Collection de CondicionIva  (necesita id, nombre, codigo_afip)

    <b>Ya no recibe el cliente ni su usuario.</b> El partial entrega la
    estructura del formulario y los desplegables, que son el catálogo de
    opciones y no datos del cliente; los valores los vuelca
    resources/js/gestion/clientes/formulario.js con lo que devuelve
    GET /api/v1/gestion/clientes/{cliente}, y el guardado va por POST o PUT
    contra la API.

    Con los valores se fueron el `old()`, los `@error` y la lista de `$errors`:
    no hay redirect del que volver con los datos escritos, porque la página no
    se recarga. Los mensajes del 422 los reparte el JavaScript en los mismos
    lugares: la lista de `#erroresCliente` y el `#error-{campo}` de cada
    control.

    Un cliente son DOS filas: `users` (con qué correo entra a la tienda) y
    `clientes` (a quién se le factura). De ahí los dos bloques del formulario.
    El cliente de mostrador viejo puede no tener cuenta, y en ese caso el bloque
    no corresponde: quién la tiene lo dice la API en `data.cuenta`, así que el
    JavaScript muestra `#bloqueCuenta` o `#avisoSinCuenta` al llegar el detalle.

    La contraseña NO está acá a propósito. El cliente nace sin ella y la elige
    él mismo desde el enlace que le llega por correo, igual que el empleado
    invitado: así nadie del mostrador la conoce, y que complete el enlace
    demuestra que la casilla que cargó el empleado es real.

    Obligatorio es lo que exige ReglasDatosCliente. Lo demás lleva "(opcional)"
    en la etiqueta: si no se marca, el empleado lo completa igual "por las
    dudas" y el alta se vuelve lenta.
--}}

@php
    // Las constantes viven en el trait, y un trait no expone sus constantes
    // por sí mismo: hay que leerlas desde una clase que lo use.
    // ProfileController hace lo mismo con esta misma clase.
    $reglas = \App\Http\Requests\Auth\RegistroClienteRequest::class;

    // Un cliente se identifica con DNI o con CUIT; el resto del catálogo
    // (pasaporte, CDI, etc.) es para otros usos y no se ofrece acá.
    //
    // Si el cliente que se está editando apunta a uno de los que no se ofrecen,
    // de cuando se aceptaban, el JavaScript agrega esa opción al desplegable
    // antes de elegirla: sin eso el navegador mostraría la primera como si
    // fuera la suya y guardar le cambiaría el documento sin que nadie lo haya
    // pedido. Es lo que hacía el `$documentos->push($suyo)` de esta vista,
    // movido a donde ahora se sabe qué documento tiene.
    $documentos = $tiposDocumento->whereIn('codigo_afip', $reglas::TIPOS_DOCUMENTO_CLIENTE);

    // Preselección del alta: el caso común en el mostrador es consumidor final
    // con DNI. En la edición la pisa el valor que traiga la API.
    $dniId             = $documentos->firstWhere('codigo_afip', $reglas::DOC_DNI)?->id;
    $consumidorFinalId = $condicionesIva->firstWhere('codigo_afip', $reglas::IVA_CONSUMIDOR_FINAL)?->id;
@endphp

{{-- Fallas de la API que no son de validación: sin permiso, sin red. --}}
<div id="avisosCliente" aria-live="polite"></div>

<form
    autocomplete="off"
    class="p-4 rounded-4 bg-light shadow-sm border"
    id="formularioCliente">

    {{-- Errores de validación que devuelve la API. --}}
    <div id="erroresCliente"></div>


    {{-- ===================== Datos de la cuenta ===================== --}}

    <section class="mb-4" id="bloqueCuenta">

        <h2 class="h6 fw-bold mb-1">Datos de la cuenta</h2>

        <p class="small text-muted">
            Con este nombre y este correo el cliente va a ingresar a la tienda.
        </p>

        <div class="row g-4">

            {{-- Nombres --}}
            <div class="col-md-6">

                <label for="nombres" class="form-label fw-semibold">
                    Nombres
                </label>

                <input
                    type="text"
                    class="form-control rounded-3"
                    id="nombres"
                    name="nombres"
                    maxlength="80"
                    placeholder="Ej: Juan Carlos"
                    autocomplete="off"
                    required
                    autofocus>

                <div class="invalid-feedback" id="error-nombres"></div>

            </div>


            {{-- Apellido --}}
            <div class="col-md-6">

                <label for="apellido" class="form-label fw-semibold">
                    Apellido
                </label>

                <input
                    type="text"
                    class="form-control rounded-3"
                    id="apellido"
                    name="apellido"
                    maxlength="80"
                    placeholder="Ej: Pérez"
                    autocomplete="off"
                    required>

                <div class="invalid-feedback" id="error-apellido"></div>

            </div>


            {{-- Correo electrónico --}}
            <div class="col-md-7">

                <label for="email" class="form-label fw-semibold">
                    Correo electrónico
                </label>

                <input
                    type="email"
                    class="form-control rounded-3"
                    id="email"
                    name="email"
                    maxlength="150"
                    placeholder="Ej: jperez@correo.com"
                    autocomplete="off"
                    aria-describedby="email-ayuda"
                    required>

                <div id="email-ayuda" class="form-text">
                    {{ $ayudaDelCorreo }}
                </div>

                <div class="invalid-feedback" id="error-email"></div>

            </div>


            {{-- Teléfono --}}
            <div class="col-md-5">

                <label for="telefono" class="form-label fw-semibold">
                    Teléfono
                    <span class="fw-normal text-muted">(opcional)</span>
                </label>

                <input
                    type="tel"
                    class="form-control rounded-3"
                    id="telefono"
                    name="telefono"
                    maxlength="30"
                    placeholder="Ej: 297 400 0000"
                    autocomplete="off">

                <div class="invalid-feedback" id="error-telefono"></div>

            </div>

        </div>

    </section>


    {{--
        Cliente de mostrador sin cuenta: no hay nada de `users` que editar.
        Arranca escondido y lo muestra el JavaScript si el detalle trae
        `cuenta` en null. En el alta nunca aparece: toda cuenta nueva nace con
        su fila en `users`.
    --}}
    <div class="alert alert-secondary d-flex align-items-start gap-2 d-none" id="avisoSinCuenta">

        <svg xmlns="http://www.w3.org/2000/svg"
            width="18"
            height="18"
            fill="currentColor"
            viewBox="0 0 16 16"
            class="flex-shrink-0 mt-1">

            <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2" />
        </svg>

        <div>
            Este cliente no tiene cuenta en la tienda, así que solo se editan sus
            datos de facturación.
        </div>

    </div>


    {{-- ===================== Datos para facturar ===================== --}}

    <section class="mb-2">

        <h2 class="h6 fw-bold mb-1">Datos para facturar</h2>

        <p class="small text-muted">
            El documento y la condición frente al IVA deciden si le corresponde
            factura A o B. Sin ellos no se le puede emitir el comprobante.
        </p>

        <div class="row g-4">

            {{-- Tipo de documento --}}
            <div class="col-md-4">

                <label for="tipo_documento_id" class="form-label fw-semibold">
                    Documento
                </label>

                <select
                    id="tipo_documento_id"
                    name="tipo_documento_id"
                    class="form-select rounded-3"
                    required>

                    @foreach ($documentos as $tipo)

                    <option
                        value="{{ $tipo->id }}"
                        @selected($dniId == $tipo->id)>

                        {{ $tipo->nombre }}

                    </option>

                    @endforeach

                </select>

                <div class="invalid-feedback" id="error-tipo_documento_id"></div>

            </div>


            {{-- Número de documento --}}
            <div class="col-md-8">

                <label for="nro_doc" class="form-label fw-semibold">
                    Número
                </label>

                <input
                    type="text"
                    class="form-control rounded-3"
                    id="nro_doc"
                    name="nro_doc"
                    maxlength="20"
                    inputmode="numeric"
                    placeholder="Sin puntos ni guiones"
                    autocomplete="off"
                    required>

                <div class="form-text">
                    DNI de 7 u 8 dígitos, o CUIT de 11.
                </div>

                <div class="invalid-feedback" id="error-nro_doc"></div>

            </div>


            {{-- Condición frente al IVA --}}
            <div class="col-md-6">

                <label for="condicion_iva_id" class="form-label fw-semibold">
                    Condición frente al IVA
                </label>

                <select
                    id="condicion_iva_id"
                    name="condicion_iva_id"
                    class="form-select rounded-3"
                    required>

                    @foreach ($condicionesIva as $condicion)

                    <option
                        value="{{ $condicion->id }}"
                        @selected($consumidorFinalId == $condicion->id)>

                        {{ $condicion->nombre }}

                    </option>

                    @endforeach

                </select>

                <div class="form-text">
                    Salvo consumidor final, todas exigen CUIT.
                </div>

                <div class="invalid-feedback" id="error-condicion_iva_id"></div>

            </div>


            {{-- Razón social --}}
            <div class="col-md-6">

                <label for="razon_social" class="form-label fw-semibold">
                    Razón social
                    <span class="fw-normal text-muted">(opcional)</span>
                </label>

                <input
                    type="text"
                    class="form-control rounded-3"
                    id="razon_social"
                    name="razon_social"
                    maxlength="150"
                    placeholder="Ej: Distribuidora del Sur S.R.L."
                    autocomplete="off"
                    aria-describedby="razon-social-ayuda">

                <div id="razon-social-ayuda" class="form-text">
                    Solo si factura a nombre de una empresa. Si queda vacía usamos
                    el apellido y los nombres.
                </div>

                <div class="invalid-feedback" id="error-razon_social"></div>

            </div>

        </div>

    </section>


    {{-- Botones --}}
    <div class="mt-5 d-flex flex-wrap gap-3">

        <button type="submit" id="botonGuardar" class="btn btn-success px-4 fw-semibold shadow-sm">
            {{ $textoDelBoton }}
        </button>

        <a
            href="{{ route('gestion.clientes.index') }}"
            class="btn btn-outline-secondary px-4 fw-semibold shadow-sm">
            Volver
        </a>

    </div>

</form>
