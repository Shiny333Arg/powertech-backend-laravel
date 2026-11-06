<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Estado del checkout, guardado en el carrito.
 *
 * <b>Por que estas tres columnas no pueden vivir en la sesion.</b> El cliente
 * sale del sitio para pagar en Mercado Pago y la acreditacion vuelve por dos
 * caminos: el retorno del navegador, que si tiene sesion, y el webhook, que
 * llega desde los servidores de Mercado Pago sin sesion, sin cookies y sin
 * usuario autenticado. El webhook es el camino autoritativo —es el que llega
 * aunque el cliente cierre el navegador despues de pagar— asi que todo lo que
 * la creacion de la venta necesite tiene que estar en la base.
 *
 * Guardarlas en el carrito y no en una tabla nueva: son datos de un checkout
 * en curso, y el carrito ES el checkout en curso. Al convertirse en venta,
 * modo_entrega y observaciones se copian a `ventas` y estas quedan como
 * rastro de lo que el cliente eligio.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carritos', function (Blueprint $table) {

            // Preferencia de Mercado Pago vigente para este carrito. Permite
            // reconciliar desde el panel de Mercado Pago hacia el carrito, y
            // detectar que ya se genero un link de pago.
            $table->string('mp_preference_id', 60)->nullable()->after('session_token');

            // Lo que el cliente eligio en el checkout. Hoy solo 'retiro': el
            // circuito de envio no esta implementado.
            $table->enum('modo_entrega', ['retiro', 'envio'])->nullable()->after('mp_preference_id');

            $table->string('observaciones', 500)->nullable()->after('modo_entrega');
        });
    }

    public function down(): void
    {
        Schema::table('carritos', function (Blueprint $table) {
            $table->dropColumn(['mp_preference_id', 'modo_entrega', 'observaciones']);
        });
    }
};
