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

            $table->foreignId('categoria_id')
                ->constrained('categorias')
                ->restrictOnDelete(); // implementa la regla de borrado de categorias
            $table->foreignId('alicuota_iva_id')
                ->constrained('alicuotas_iva')
                ->restrictOnDelete();
            $table->foreignId('proveedor_id')
                ->nullable()
                ->constrained('proveedores')
                ->restrictOnDelete();

            $table->string('codigo', 30)->unique();
            $table->string('nombre', 150);
            $table->string('marca', 80)->nullable();
            $table->text('descripcion')->nullable();
            $table->string('imagen', 255)->nullable();

            $table->decimal('precio', 12, 2);
            $table->decimal('costo', 12, 2)->nullable();

            // --- Stock: dos valores registrados + uno generado ---
            $table->unsignedInteger('stock_fisico')->default(0);
            $table->unsignedInteger('stock_comprometido')->default(0);
            // El CAST a SIGNED es obligatorio: la resta de dos UNSIGNED es UNSIGNED
            // y falla por rango en el primer producto en preventa.
            $table->integer('stock_disponible')->storedAs(
                'CAST(stock_fisico AS SIGNED) - CAST(stock_comprometido AS SIGNED)'
            );

            // --- Reposicion ---
            $table->integer('punto_reposicion')->default(0);
            $table->integer('cantidad_reposicion')->default(0);
            $table->boolean('reposicion_automatica')->default(true);

            $table->integer('peso_gramos')->nullable();
            $table->boolean('destacado')->default(false);
            $table->boolean('publicado_app')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('marca');
            // vitrina (stock_disponible > 0) y dashboard de reposicion
            // (stock_disponible < punto_reposicion)
            $table->index('stock_disponible');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
