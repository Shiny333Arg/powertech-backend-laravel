<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Permite cargar un pedido con productos de varios proveedores y repartirlo en
 * una orden por proveedor al aprobarlo.
 *
 * Tres cambios:
 *
 *   ordenes_compra.proveedor_id  pasa a nullable. NULL significa "pedido
 *                                mezclado": lo que el administrativo cargo y
 *                                todavia no se repartio, o la orden madre de
 *                                las que si tienen destinatario. Con un solo
 *                                proveedor sigue seteado, asi que el restock,
 *                                el remito y la recepcion no se enteran del
 *                                cambio: todo lo que genera el automatico es
 *                                mono-proveedor por construccion.
 *
 *   ordenes_compra.orden_padre_id  apunta a la madre. Las hijas son las ordenes
 *                                reales —las que se envian, se reciben y
 *                                cuentan para la reposicion—; la madre es el
 *                                documento que alguien cargo y aprobo.
 *
 *   orden_compra_lineas.proveedor_id  a quien se le pide ESTE renglon. Hoy se
 *                                copia del producto al guardar; cuando un item
 *                                pueda tener varios proveedores pasa a ser la
 *                                eleccion del operador y la columna ya esta.
 *                                Es un snapshot a proposito: si manana al
 *                                producto le cambian el proveedor, los pedidos
 *                                viejos tienen que seguir diciendo a quien se
 *                                le pidieron.
 */
return new class extends Migration
{
    public function up(): void
    {
        // La FK se suelta antes de tocar la columna: MySQL no deja modificar
        // una columna referenciada con la restriccion puesta.
        Schema::table('ordenes_compra', function (Blueprint $table) {
            $table->dropForeign(['proveedor_id']);
        });

        Schema::table('ordenes_compra', function (Blueprint $table) {
            $table->unsignedBigInteger('proveedor_id')->nullable()->change();
        });

        Schema::table('ordenes_compra', function (Blueprint $table) {
            $table->foreign('proveedor_id')->references('id')->on('proveedores');

            $table->foreignId('orden_padre_id')
                ->nullable()
                ->after('id')
                ->constrained('ordenes_compra');
        });

        Schema::table('orden_compra_lineas', function (Blueprint $table) {
            $table->foreignId('proveedor_id')
                ->nullable()
                ->after('producto_id')
                ->constrained('proveedores');
        });

        // Las lineas que ya existen heredan el proveedor de su orden, que hasta
        // hoy era el unico posible. Sin esto quedan en NULL y el formulario las
        // muestra como renglones sin proveedor.
        DB::statement('
            UPDATE orden_compra_lineas AS l
            JOIN ordenes_compra AS o ON o.id = l.orden_compra_id
            SET l.proveedor_id = o.proveedor_id
            WHERE l.proveedor_id IS NULL
        ');
    }

    public function down(): void
    {
        Schema::table('orden_compra_lineas', function (Blueprint $table) {
            $table->dropForeign(['proveedor_id']);
            $table->dropColumn('proveedor_id');
        });

        // Las hijas pierden el vinculo pero sobreviven: son ordenes reales, con
        // su numero y su historial. Las madres no: sin la columna no hay forma
        // de representarlas, y son el unico caso de proveedor_id NULL.
        Schema::table('ordenes_compra', function (Blueprint $table) {
            $table->dropForeign(['orden_padre_id']);
            $table->dropColumn('orden_padre_id');
        });

        DB::table('ordenes_compra')->whereNull('proveedor_id')->delete();

        Schema::table('ordenes_compra', function (Blueprint $table) {
            $table->dropForeign(['proveedor_id']);
        });

        Schema::table('ordenes_compra', function (Blueprint $table) {
            $table->unsignedBigInteger('proveedor_id')->nullable(false)->change();
        });

        Schema::table('ordenes_compra', function (Blueprint $table) {
            $table->foreign('proveedor_id')->references('id')->on('proveedores');
        });
    }
};
