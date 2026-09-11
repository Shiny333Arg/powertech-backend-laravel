<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * K.1 numeradores — contador de los correlativos INTERNOS del sistema.
 *
 * Dos documentos llevan numero propio: la venta y la orden de compra. Ninguno
 * puede depender del AUTO_INCREMENT, porque es un dato que el negocio lee,
 * dice por teléfono e imprime en un presupuesto, y una clave técnica salta
 * ante cualquier INSERT fallido.
 *
 * Nada que ver con la numeración FISCAL: esa es correlativa por (tipo de
 * comprobante, punto de venta), la autoriza ARCA y vive en `comprobantes`
 * con su propio índice único. Son dos series que corren en paralelo y jamás
 * se cruzan.
 *
 * CÓMO SE TOMA EL NÚMERO — dentro de la transacción que crea el documento:
 *
 *   SELECT ultimo_numero FROM numeradores
 *    WHERE serie = 'venta' FOR UPDATE;      -- bloquea la fila hasta el COMMIT
 *   UPDATE numeradores SET ultimo_numero = ultimo_numero + 1
 *    WHERE serie = 'venta';
 *
 * El bloqueo de fila serializa la asignación: serie estrictamente correlativa,
 * sin huecos y sin reintentos. Un ROLLBACK devuelve también el número. Ver
 * App\Models\Numerador::siguiente().
 *
 * Sin FK: es un contador, no una entidad del dominio. No lo referencia nadie.
 * Se carga por seeder con las dos series en cero.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('numeradores', function (Blueprint $table) {
            $table->id();
            // 'venta' | 'orden_compra'
            $table->string('serie', 30)->unique();
            // 'V' | 'OC'. De presentación: la columna del documento guarda el
            // entero y la pantalla arma V-0001204. Guardarlo ya formateado
            // rompería el orden, la comparación y el índice.
            $table->string('prefijo', 10)->nullable();
            $table->unsignedInteger('ultimo_numero')->default(0);
            // Sin created_at: la fila la crea el seeder y vive para siempre.
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('numeradores');
    }
};
