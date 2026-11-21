@extends('layouts.gestion')

@section('title', 'PowerTech')

@section('content')

{{--
    La ficha del cliente. <b>La página entrega la estructura y nada más.</b>

    Los datos del cliente, el estado de su cuenta, sus direcciones y su historial
    de compras los pide resources/js/gestion/clientes/ficha.js a la API:

      GET    /api/v1/gestion/clientes/{id}
      GET    /api/v1/gestion/clientes/{id}/ventas?page=
      PATCH  /api/v1/gestion/clientes/{id}/estado
      POST   /api/v1/gestion/clientes/{id}/invitacion
      POST   /api/v1/gestion/clientes/{id}/direcciones
      DELETE /api/v1/gestion/clientes/{id}/direcciones/{direccion}

    Los cuatro formularios que había acá —baja/reactivación, reenvío de la
    invitación, alta de dirección y borrado de dirección— eran los últimos
    consumidores de las rutas web de negocio y del grupo
    `gestion.direcciones.*`. Se retiraron las rutas y los formularios pasaron a
    ser llamadas a la API.
--}}

<!-- Título -->

<section class="mb-4">

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">

        <div>

            <h1 class="h3 fw-bold mb-2 d-flex flex-wrap align-items-center gap-2">

                <span id="fichaRazonSocial"></span>

                <span class="badge text-bg-secondary d-none" id="fichaInsigniaBaja">Dado de baja</span>

            </h1>

            {{-- «DNI 27123456 · Consumidor Final» --}}
            <p class="text-muted mb-0" id="fichaDocumento"></p>

        </div>


        <div class="d-flex flex-wrap gap-2">

            {{--
                Los tres botones arrancan escondidos y los muestra el JS según
                el permiso del rol y el estado del cliente. No se dibujan desde
                Blade porque el estado —y por lo tanto cuál de los dos de baja
                corresponde— llega con el detalle.

                Según la matriz de PermisoSeeder, el vendedor y el cajero NO
                tienen 'cliente.baja': con esos roles los dos últimos no
                aparecen nunca, y el endpoint los rechazaría igual con un 403.
            --}}
            <a href="#" class="btn btn-warning fw-semibold d-none" id="botonEditar">
                Editar
            </a>

            <button type="button" class="btn btn-outline-danger fw-semibold d-none" id="botonBaja">
                Dar de baja
            </button>

            <button type="button" class="btn btn-success fw-semibold d-none" id="botonReactivar">
                Reactivar
            </button>

            <a href="{{ route('gestion.clientes.index') }}" class="btn btn-outline-secondary">
                Volver al listado
            </a>

        </div>

    </div>

</section>


{{--
    Avisos de la pantalla. Antes era `@include('gestion.partials.alertas')` con
    el `session('status')` del redirect; ahora lo pinta avisos.js, que además
    muestra el que haya dejado el formulario de alta o de edición.
--}}
<div id="avisosCliente" aria-live="polite"></div>


<div class="alert alert-warning d-none" id="avisoFueraDeOperacion" role="alert">
    Este cliente está dado de baja: no se ofrece al cargar una venta nueva y su
    cuenta quedó inhabilitada, así que no puede ingresar ni comprar online. Sus
    compras anteriores lo siguen nombrando y la baja se puede revertir.
</div>


<div class="row g-4">

    <!-- Columna izquierda -->
    <div class="col-12 col-lg-8">

        <!-- Historial de compras -->
        <section class="mb-4" id="compras">

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">

                <h2 class="h5 fw-bold mb-0">Historial de compras</h2>

                {{-- «Mostrando 1–10 de 23» --}}
                <span class="text-muted small d-none" id="resumenVentas"></span>

            </div>

            <div class="table-responsive">

                <table class="table table-bordered table-hover align-middle text-center">

                    <thead class="table-dark">

                        <tr>
                            <th class="text-nowrap">Número</th>
                            <th class="text-nowrap">Fecha</th>
                            <th class="text-nowrap">Estado</th>
                            <th class="text-nowrap">Total</th>
                        </tr>

                    </thead>

                    {{--
                        Las filas las dibuja el JS con lo que devuelve
                        GET /clientes/{id}/ventas. El enlace de cada número
                        lleva al detalle de la venta, que sigue exigiendo
                        'venta.ver': que el enlace esté no significa que entre.
                    --}}
                    <tbody id="contenidoTablaVentas">

                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">
                                Cargando compras…
                            </td>
                        </tr>

                    </tbody>

                </table>

            </div>

            <div id="paginadoVentas" class="d-flex justify-content-center mt-3"></div>

        </section>


        <!-- Direcciones -->
        <section>

            <h2 class="h5 fw-bold mb-1">Direcciones</h2>

            <p class="text-muted small">
                La fiscal va en la factura; la de envío, en el remito. Un envío ya
                despachado no cambia de destino si se edita esto.
            </p>

            <p class="text-muted d-none" id="sinDirecciones">Sin direcciones cargadas.</p>

            {{-- La lista la dibuja el JS con `data.direcciones` del detalle. --}}
            <ul class="list-group mb-3 d-none" id="listaDirecciones"></ul>


            @if ($permisos['editar'])

            <!-- Alta de dirección -->
            <div class="accordion" id="acordeonDireccion">

                <div class="accordion-item">

                    <h3 class="accordion-header" id="encabezadoNuevaDireccion">

                        <button
                            class="accordion-button collapsed"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#nuevaDireccion"
                            aria-expanded="false"
                            aria-controls="nuevaDireccion">
                            Agregar una dirección
                        </button>

                    </h3>

                    <div
                        id="nuevaDireccion"
                        class="accordion-collapse collapse"
                        aria-labelledby="encabezadoNuevaDireccion"
                        data-bs-parent="#acordeonDireccion">

                        <div class="accordion-body">

                            {{-- Errores de validación que devuelve la API. --}}
                            <div id="erroresDireccion"></div>

                            {{--
                                Las reglas son las de siempre: el mismo
                                DireccionRequest, ahora invocado por
                                Api\V1\Gestion\ClienteDireccionController.
                            --}}
                            <form
                                class="row g-3"
                                autocomplete="off"
                                id="formularioDireccion">

                                <div class="col-12 col-md-4">

                                    <label for="tipo" class="form-label fw-semibold">Tipo</label>

                                    <select id="tipo" name="tipo" class="form-select rounded-3" required>
                                        <option value="fiscal">Fiscal</option>
                                        <option value="envio">Envío</option>
                                        <option value="particular">Particular</option>
                                    </select>

                                    <div class="invalid-feedback" id="error-direccion-tipo"></div>

                                </div>

                                <div class="col-12 col-md-6">

                                    <label for="calle" class="form-label fw-semibold">Calle</label>

                                    <input type="text" class="form-control rounded-3" id="calle" name="calle"
                                        maxlength="120" required>

                                    <div class="invalid-feedback" id="error-direccion-calle"></div>

                                </div>

                                <div class="col-6 col-md-2">

                                    <label for="numero" class="form-label fw-semibold">Número</label>

                                    <input type="text" class="form-control rounded-3" id="numero" name="numero"
                                        maxlength="10">

                                    <div class="invalid-feedback" id="error-direccion-numero"></div>

                                </div>

                                <div class="col-6 col-md-3">

                                    <label for="piso_depto" class="form-label fw-semibold">Piso / depto</label>

                                    <input type="text" class="form-control rounded-3" id="piso_depto" name="piso_depto"
                                        maxlength="20">

                                    <div class="invalid-feedback" id="error-direccion-piso_depto"></div>

                                </div>

                                <div class="col-12 col-md-3">

                                    <label for="localidad" class="form-label fw-semibold">Localidad</label>

                                    <input type="text" class="form-control rounded-3" id="localidad" name="localidad"
                                        maxlength="80" required>

                                    <div class="invalid-feedback" id="error-direccion-localidad"></div>

                                </div>

                                <div class="col-12 col-md-3">

                                    <label for="provincia" class="form-label fw-semibold">Provincia</label>

                                    <input type="text" class="form-control rounded-3" id="provincia" name="provincia"
                                        maxlength="60" required>

                                    <div class="invalid-feedback" id="error-direccion-provincia"></div>

                                </div>

                                <div class="col-6 col-md-3">

                                    <label for="codigo_postal" class="form-label fw-semibold">Código postal</label>

                                    <input type="text" class="form-control rounded-3" id="codigo_postal" name="codigo_postal"
                                        maxlength="10" required>

                                    <div class="invalid-feedback" id="error-direccion-codigo_postal"></div>

                                </div>

                                <div class="col-12">

                                    <div class="form-check form-switch">

                                        <input class="form-check-input" type="checkbox" role="switch"
                                            id="es_predeterminada" name="es_predeterminada" value="1">

                                        <label class="form-check-label fw-semibold" for="es_predeterminada">
                                            Usar como predeterminada de su tipo
                                        </label>

                                    </div>

                                </div>

                                <div class="col-12">

                                    <button type="submit" class="btn btn-success px-4 fw-semibold">
                                        Guardar dirección
                                    </button>

                                </div>

                            </form>

                        </div>

                    </div>

                </div>

            </div>

            @endif

        </section>

    </div>


    <!-- Columna derecha -->
    <div class="col-12 col-lg-4">

        <section class="p-4 rounded-4 bg-light shadow-sm border mb-4">

            <h2 class="h6 fw-bold mb-2">Contacto</h2>

            {{-- El cliente de mostrador sin cuenta no tiene correo: el JS
                 esconde la línea, como hacía el `@if ($cliente->user)`. --}}
            <p class="mb-1 d-none" id="fichaEmail"></p>

            <p class="mb-0" id="fichaTelefono"></p>

        </section>


        <section class="p-4 rounded-4 bg-light shadow-sm border">

            <h2 class="h6 fw-bold mb-3">Cuenta en la tienda</h2>

            @include('gestion.clientes.partials.acceso')

        </section>

    </div>

</div>

@endsection

@push('scripts')
<script type="module">
    import { iniciar } from '{{ Vite::asset('resources/js/gestion/clientes/ficha.js') }}';

    iniciar({
        // Sólo el id: los datos los pide la pantalla a la API.
        id: {{ $cliente->id }},

        // Los decide ClientePolicy en el servidor (ver el controlador): acá
        // sólo dicen qué se dibuja. El permiso de verdad lo exige la API.
        permisos: @json($permisos),

        urlEdicion: '{{ route('gestion.clientes.edit', $cliente) }}',

        // El detalle de la venta, con un hueco en lugar del id.
        urlVenta: '{{ route('gestion.ventas.show', ['venta' => '__ID__']) }}',

        // La vigencia del enlace de invitación, que la frase del estado de la
        // cuenta nombra. Es una constante del dominio, no un dato del cliente.
        horasDeVigencia: {{ \App\Notifications\InvitacionCliente::HORAS_DE_VIGENCIA }},
    });
</script>
@endpush
