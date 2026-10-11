<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            // Lo entrega el numerador de serie 'venta'
            $table->unsignedInteger('numero')->unique();
            $table->enum('canal', ['mostrador', 'online']);
            $table->foreignId('cliente_id')->nullable()->constrained('clientes');
            // Vendedor que la genero (null si la genero el cliente desde la app)
            $table->foreignId('usuario_id')->nullable()->constrained('users');
            $table->enum('estado', [
                'presupuesto',
                'confirmada',
                'pagada',
                'pendiente_stock',
                'en_preparacion',
                'despachada',
                'lista_retiro',
                'entregada',
                'devuelta_parcial',
                'devuelta',
                'vencida',
                'cancelada',
            ]);
            $table->enum('modo_entrega', ['retiro', 'envio'])->default('retiro');
            // Solo para presupuestos
            $table->date('validez_hasta')->nullable();
            $table->foreignId('usuario_valido_id')->nullable()->constrained('users');
            $table->dateTime('fecha_validacion')->nullable();

            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('descuento_promociones', 12, 2)->default(0);
            $table->decimal('costo_envio', 12, 2)->nullable();
            $table->decimal('neto_gravado', 12, 2)->default(0);
            $table->decimal('total_iva', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            $table->text('observaciones')->nullable();
            $table->dateTime('fecha');
            $table->timestamps();

            $table->index(['usuario_id', 'fecha']);
            $table->index(['estado', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
