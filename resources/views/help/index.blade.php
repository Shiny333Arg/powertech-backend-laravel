@extends('layouts.cliente')

@section('title', 'Ayuda - PowerTech')

@section('content')

<section class="mb-4 text-center">
    <h1 class="display-6 fw-bold">Preguntas frecuentes</h1>
</section>

<div class="container py-4">
    <div class="card shadow-sm rounded-4 mb-4">



        <div class="card-body p-0">

            <div class="accordion accordion-flush" id="accordionAyuda">

                <!-- Realizar un pedido -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingPedido">
                        <button
                            class="accordion-button collapsed"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#collapsePedido"
                            aria-expanded="false"
                            aria-controls="collapsePedido">
                            Realizar un pedido
                        </button>
                    </h2>

                    <div
                        id="collapsePedido"
                        class="accordion-collapse collapse"
                        aria-labelledby="headingPedido"
                        data-bs-parent="#accordionAyuda">

                        <div class="accordion-body">
                            Para realizar un pedido, seleccioná los productos que quieras
                            adquirir y agregalos al carrito. Luego revisá las cantidades,
                            verificá el total y confirmá la operación.
                        </div>
                    </div>
                </div>


                <!-- Precio -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingPrecio">
                        <button
                            class="accordion-button collapsed"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#collapsePrecio"
                            aria-expanded="false"
                            aria-controls="collapsePrecio">
                            Precio
                        </button>
                    </h2>

                    <div
                        id="collapsePrecio"
                        class="accordion-collapse collapse"
                        aria-labelledby="headingPrecio"
                        data-bs-parent="#accordionAyuda">

                        <div class="accordion-body">
                            Los precios publicados corresponden al valor actual de los
                            productos disponibles en PowerTech. El precio final puede
                            depender del medio de pago seleccionado.
                        </div>
                    </div>
                </div>


                <!-- Formas de pago -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingPago">
                        <button
                            class="accordion-button collapsed"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#collapsePago"
                            aria-expanded="false"
                            aria-controls="collapsePago">
                            Formas de pago
                        </button>
                    </h2>

                    <div
                        id="collapsePago"
                        class="accordion-collapse collapse"
                        aria-labelledby="headingPago"
                        data-bs-parent="#accordionAyuda">

                        <div class="accordion-body">
                            PowerTech permite seleccionar diferentes formas de pago
                            disponibles al momento de confirmar una compra. La opción
                            elegida se registra junto con la operación.
                        </div>
                    </div>
                </div>


                <!-- Envíos -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingEnvios">
                        <button
                            class="accordion-button collapsed"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#collapseEnvios"
                            aria-expanded="false"
                            aria-controls="collapseEnvios">
                            Envíos
                        </button>
                    </h2>

                    <div
                        id="collapseEnvios"
                        class="accordion-collapse collapse"
                        aria-labelledby="headingEnvios"
                        data-bs-parent="#accordionAyuda">

                        <div class="accordion-body">
                            Durante la compra podrás consultar las opciones de entrega
                            disponibles. El tiempo estimado dependerá del domicilio
                            ingresado y del método de envío seleccionado.
                        </div>
                    </div>
                </div>


                <!-- Facturación -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingFacturacion">
                        <button
                            class="accordion-button collapsed"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#collapseFacturacion"
                            aria-expanded="false"
                            aria-controls="collapseFacturacion">
                            Facturación
                        </button>
                    </h2>

                    <div
                        id="collapseFacturacion"
                        class="accordion-collapse collapse"
                        aria-labelledby="headingFacturacion"
                        data-bs-parent="#accordionAyuda">

                        <div class="accordion-body">
                            La facturación se genera de acuerdo con los datos fiscales
                            registrados en la operación. El comprobante correspondiente
                            queda asociado a la venta realizada.
                        </div>
                    </div>
                </div>


                <!-- Reintegro -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingReintegro">
                        <button
                            class="accordion-button collapsed"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#collapseReintegro"
                            aria-expanded="false"
                            aria-controls="collapseReintegro">
                            Solicitar el reintegro de un pago
                        </button>
                    </h2>

                    <div
                        id="collapseReintegro"
                        class="accordion-collapse collapse"
                        aria-labelledby="headingReintegro"
                        data-bs-parent="#accordionAyuda">

                        <div class="accordion-body">
                            Para solicitar un reintegro, contactá con el área de atención
                            al cliente indicando el número de la operación y el motivo
                            de la solicitud. El pedido será revisado antes de realizar
                            cualquier devolución.
                        </div>
                    </div>
                </div>


                <!-- Garantías -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingGarantias">
                        <button
                            class="accordion-button collapsed"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#collapseGarantias"
                            aria-expanded="false"
                            aria-controls="collapseGarantias">
                            Garantías
                        </button>
                    </h2>

                    <div
                        id="collapseGarantias"
                        class="accordion-collapse collapse"
                        aria-labelledby="headingGarantias"
                        data-bs-parent="#accordionAyuda">

                        <div class="accordion-body">
                            Los productos cuentan con garantía de acuerdo con las
                            condiciones correspondientes. Para iniciar una gestión,
                            comunicá el inconveniente y presentá los datos de la compra.
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
</div>

@endsection