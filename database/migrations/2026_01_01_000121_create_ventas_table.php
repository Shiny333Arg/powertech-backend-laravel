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

            // Correlativo INTERNO: serie unica para ambos canales, tomado de
            // numeradores dentro de la transaccion. Nada que ver con el fiscal.
            $table->unsignedInteger('numero')->unique();

            $table->enum('canal', ['mostrador', 'online']);
            $table->foreignId('cliente_id')       // null: consumidor final anonimo
                ->nullable()
                ->constrained('clientes')
                ->restrictOnDelete();
            $table->foreignId('usuario_id')       // null: en la venta online no hay vendedor
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

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

            // Validacion del presupuesto
            $table->date('validez_hasta')->nullable();
            $table->foreignId('usuario_valido_id')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();
            $table->dateTime('fecha_validacion')->nullable();

            // Importes
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('descuento_promociones', 12, 2)->default(0);
            $table->decimal('costo_envio', 12, 2)->nullable();
            $table->decimal('neto_gravado', 12, 2)->default(0);
            $table->decimal('total_iva', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            $table->text('observaciones')->nullable();
            $table->dateTime('fecha');
            $table->timestamps();
            // SIN columna activo: el fin de vida de la venta es un estado.

            $table->index(['usuario_id', 'fecha']);   // historial del vendedor
            $table->index(['estado', 'fecha']);       // cola de pendiente_stock por antiguedad
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
