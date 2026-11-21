{{--
    Estado de la cuenta de un cliente en la tienda y, si corresponde, el botón
    para reenviarle la invitación.

      @include('gestion.clientes.partials.acceso')

    <b>Ya no recibe el cliente.</b> Entrega los dos huecos y los llena
    resources/js/gestion/clientes/ficha.js con lo que viaja en `data.cuenta` del
    detalle: si la cuenta existe, si está activa, si la invitación sigue
    pendiente y si el correo está verificado. Los cinco estados y sus frases son
    los mismos que escribía este partial —sin cuenta, acceso bloqueado,
    invitación pendiente, correo sin verificar, activa—, con la misma precedencia
    y en el mismo marcado; están en la función `estadoDeLaCuenta()` de ese
    módulo, que es donde ahora se sabe en qué estado está.

    El botón de reenviar sale sólo si la cuenta está activa, la invitación sigue
    pendiente y el rol tiene 'cliente.editar'. Es la misma condición que tenía el
    `@can('update', $cliente)` de acá, y el endpoint la vuelve a verificar: un
    cliente que ya eligió su contraseña recibe un 422.

    Lo que este bloque dice es SOLO sobre la tienda online. Ninguno de estos
    estados frena una venta, un cobro ni una factura de mostrador: para eso
    alcanza con que el cliente exista y tenga sus datos fiscales. Por eso el
    detalle lo aclara donde podría dar lugar a duda.
--}}

{{-- La insignia del estado y, si corresponde, el botón de reenviar. --}}
<div class="d-flex flex-wrap align-items-center gap-2" id="estadoCuenta"></div>

{{-- La línea que explica el estado. --}}
<p class="small text-muted mt-2 mb-0" id="detalleCuenta"></p>
