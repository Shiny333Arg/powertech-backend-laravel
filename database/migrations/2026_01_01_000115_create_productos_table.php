<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_id')->constrained('categorias');
            $table->foreignId('alicuota_iva_id')->constrained('alicuotas_iva');
            // Proveedor habitual: se usa para armar la OC automatica de reposicion
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores');
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 150);
            $table->string('marca', 80)->nullable();
            $table->text('descripcion')->nullable();
            $table->string('imagen', 255)->nullable();
            $table->decimal('precio', 12, 2);
            $table->decimal('costo', 12, 2)->nullable();

            // Stock: solo lo tocan los movimientos_stock, nunca se edita a mano
            $table->unsignedInteger('stock_fisico')->default(0);
            $table->unsignedInteger('stock_comprometido')->default(0);
            // Columna generada STORED: disponible = fisico - comprometido
            $table->integer('stock_disponible')
                ->storedAs('CAST(stock_fisico AS SIGNED) - CAST(stock_comprometido AS SIGNED)');

            // Disparadores de la reposicion automatica
            $table->integer('punto_reposicion')->default(0);
            $table->integer('cantidad_reposicion')->default(0);
            $table->boolean('reposicion_automatica')->default(true);

            $table->integer('peso_gramos')->nullable();
            $table->boolean('destacado')->default(false);
            $table->boolean('publicado_app')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('marca');
            $table->index('stock_disponible');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
