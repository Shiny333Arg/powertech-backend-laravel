<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * C.2 productos — tabla central del sistema.
 *
 * TRES VALORES DE STOCK
 *   stock_fisico       INT UNSIGNED  unidades realmente en deposito. Nunca < 0.
 *   stock_comprometido INT UNSIGNED  vendidas y pendientes de entrega
 *                                    (incluye la reserva de preventa). Nunca < 0.
 *   stock_disponible   INT SIGNED    GENERATED STORED = fisico - comprometido.
 *                                    Solo lectura, indexado, PUEDE ser < 0 (preventa).
 *
 * El CAST a SIGNED de la columna generada NO es adorno: en MySQL/MariaDB la
 * resta de dos UNSIGNED es UNSIGNED y falla por rango en el primer producto
 * en preventa (comprometido > fisico). Es el detalle exacto que rompe la
 * preventa en produccion si se omite.
 *
 * Los tipos UNSIGNED convierten las reglas de negocio en restricciones del
 * motor: cualquier UPDATE que dejaria un saldo bajo cero aborta la transaccion.
 * REQUIERE strict mode activo ('strict' => true en config/database.php).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('categoria_id')->constrained('categorias')->restrictOnDelete();
            $table->foreignId('alicuota_iva_id')->constrained('alicuotas_iva')->restrictOnDelete();
            // Un producto, un proveedor: es lo que el restock automatico necesita
            // para saber a quien emitir la orden.
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores')->restrictOnDelete();

            $table->string('codigo', 30)->unique();
            $table->string('nombre', 150);
            // Marca como columna indexada, no como entidad: alcanza para filtrar
            // y ahorra un join. Se mitiga "Logitech"/"logitech" con autocomplete.
            $table->string('marca', 80)->nullable()->index();
            $table->text('descripcion')->nullable();
            $table->string('imagen', 255)->nullable(); // una sola foto, por requerimiento

            // DECIMAL, nunca FLOAT: el error de FLOAT se acumula al multiplicar
            // por cantidades y sumar lineas.
            $table->decimal('precio', 12, 2);
            $table->decimal('costo', 12, 2)->nullable();

            // --- stock ---
            $table->unsignedInteger('stock_fisico')->default(0);
            $table->unsignedInteger('stock_comprometido')->default(0);
            $table->integer('stock_disponible')->storedAs(
                'CAST(stock_fisico AS SIGNED) - CAST(stock_comprometido AS SIGNED)'
            );

            // --- reposicion ---
            // El umbral se compara contra stock_disponible, no contra el fisico.
            $table->integer('punto_reposicion')->default(0);
            $table->integer('cantidad_reposicion')->default(0);
            $table->boolean('reposicion_automatica')->default(true);

            $table->unsignedInteger('peso_gramos')->nullable();
            $table->boolean('destacado')->default(false);   // lo escribe un job, no se edita a mano
            $table->boolean('publicado_app')->default(true);
            $table->boolean('activo')->default(true);

            $table->timestamps();
            $table->softDeletes();

            // Vitrina: WHERE stock_disponible > 0
            // Dashboard de reposicion: WHERE stock_disponible < punto_reposicion
            $table->index('stock_disponible');
            $table->index(['publicado_app', 'activo']);
            $table->index(['categoria_id', 'activo']);
            $table->index(['destacado', 'publicado_app']);
            $table->index('nombre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
