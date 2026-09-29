<?php

namespace App\Services;

use App\Enums\TipoMovimientoStock;
use App\Events\MovimientoStockRegistrado;
use App\Exceptions\StockInsuficienteException;
use App\Models\MovimientoStock;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Unico punto por el que se tocan los saldos de stock.
 *
 * Tres reglas que este servicio existe para no romper:
 *
 * 1. Los saldos se mueven con UPDATE RELATIVO dentro de una transaccion, nunca
 *    leyendo en PHP y reescribiendo un valor absoluto. Dos ventas simultaneas
 *    del mismo producto se serializan en el lock de fila; con SELECT + UPDATE
 *    absoluto la segunda pisa a la primera y una reserva desaparece.
 *
 * 2. Todo cambio deja su movimiento. El saldo de productos es un cache; el
 *    historico de movimientos_stock es la fuente de verdad para auditar.
 *
 * 3. stock_disponible no se toca jamas: es una columna generada y la recalcula
 *    el motor.
 *
 * Desde la reposicion automatica hay una cuarta:
 *
 * 4. Todo movimiento emite MovimientoStockRegistrado. Es el unico enganche del
 *    restock con el resto del sistema: este servicio no sabe que existen las
 *    ordenes de compra, y no tiene por que saberlo.
 */
class StockService
{
    /**
     * @param  int  $cantidad  Positiva siempre, salvo en 'ajuste', donde el signo
     *                         lo trae la cantidad (±q).
     * @param  array{origen?: Model|null, usuario_id?: int|null, motivo?: string|null}  $opciones
     *
     * @throws StockInsuficienteException
     */
    public function registrar(
        Producto $producto,
        TipoMovimientoStock $tipo,
        int $cantidad,
        array $opciones = [],
    ): MovimientoStock {
        $this->validar($tipo, $cantidad, $opciones);

        // Los signos salen del enum, que replica la tabla de deltas de D.1.
        $deltaFisico       = $tipo->signoFisico() * $cantidad;
        $deltaComprometido = $tipo->signoComprometido() * $cantidad;

        $movimiento = DB::transaction(function () use ($producto, $tipo, $deltaFisico, $deltaComprometido, $opciones) {

            $columnas = [];

            if ($deltaFisico !== 0) {
                $columnas['stock_fisico'] = DB::raw(sprintf('stock_fisico + (%d)', $deltaFisico));
            }

            if ($deltaComprometido !== 0) {
                $columnas['stock_comprometido'] = DB::raw(sprintf('stock_comprometido + (%d)', $deltaComprometido));
            }

            $consulta = Producto::whereKey($producto->getKey());

            // La guarda de la salida: solo se condiciona lo que RESTA. Sumar
            // siempre es valido, y comprometer sin stock es la preventa.
            if ($deltaFisico < 0) {
                $consulta->where('stock_fisico', '>=', abs($deltaFisico));
            }

            if ($deltaComprometido < 0) {
                $consulta->where('stock_comprometido', '>=', abs($deltaComprometido));
            }

            // Cero filas afectadas = no habia saldo suficiente. Es defensa en
            // profundidad: la aplicacion ya deberia haber verificado antes,
            // pero es la unica capa que queda en pie cuando dos entregas
            // concurrentes compiten por las ultimas unidades.
            if ($consulta->update($columnas) === 0) {
                throw new StockInsuficienteException(
                    "No hay stock suficiente en «{$producto->nombre}» para registrar un movimiento de {$tipo->value}."
                );
            }

            // Releido DENTRO de la transaccion: devuelve el saldo que acabamos
            // de escribir, con stock_disponible ya recalculado por el motor.
            $producto->refresh();

            $origen = $opciones['origen'] ?? null;

            return MovimientoStock::create([
                'producto_id'                   => $producto->id,
                'tipo'                          => $tipo,
                'cantidad_fisico'               => $deltaFisico,
                'cantidad_comprometido'         => $deltaComprometido,
                'stock_fisico_resultante'       => $producto->stock_fisico,
                'stock_comprometido_resultante' => $producto->stock_comprometido,
                'origen_type'                   => $origen?->getMorphClass(),
                'origen_id'                     => $origen?->getKey(),
                'usuario_id'                    => $opciones['usuario_id'] ?? auth()->id(),
                'motivo'                        => $opciones['motivo'] ?? null,
            ]);
        });

        // FUERA de la transaccion a proposito, y ademas el listener esta marcado
        // afterCommit. Si esto corriera adentro y la venta que engloba a este
        // movimiento hiciera rollback, quedaria una orden de compra emitida por
        // un faltante que nunca existio. El evento anuncia hechos consumados.
        //
        // El producto viaja ya refrescado: quien escuche lee el saldo real sin
        // volver a consultar.
        $movimiento->setRelation('producto', $producto);

        MovimientoStockRegistrado::dispatch($movimiento);

        return $movimiento;
    }

    /** Conteo de puesta en marcha. El comprometido arranca en cero. */
    public function cargaInicial(Producto $producto, int $cantidad): MovimientoStock
    {
        return $this->registrar($producto, TipoMovimientoStock::Inicial, $cantidad);
    }

    /** Diferencias de conteo, roturas, mermas. Motivo obligatorio. */
    public function ajustar(Producto $producto, int $cantidad, string $motivo): MovimientoStock
    {
        return $this->registrar($producto, TipoMovimientoStock::Ajuste, $cantidad, ['motivo' => $motivo]);
    }

    /** Recepcion de una orden de compra, total o parcial. Suma solo al fisico. */
    public function recibirCompra(Producto $producto, int $cantidad, Model $ordenCompra): MovimientoStock
    {
        return $this->registrar($producto, TipoMovimientoStock::Compra, $cantidad, ['origen' => $ordenCompra]);
    }

    /** Venta confirmada. Nunca se rechaza: sin stock, es preventa. */
    public function comprometer(Producto $producto, int $cantidad, Model $venta): MovimientoStock
    {
        return $this->registrar($producto, TipoMovimientoStock::Reserva, $cantidad, ['origen' => $venta]);
    }

    /** Salida real del deposito: despacho o retiro. Exige fisico suficiente. */
    public function entregar(Producto $producto, int $cantidad, Model $venta): MovimientoStock
    {
        return $this->registrar($producto, TipoMovimientoStock::Entrega, $cantidad, ['origen' => $venta]);
    }

    /** Venta cancelada o presupuesto vencido antes de la entrega. */
    public function liberar(Producto $producto, int $cantidad, Model $venta): MovimientoStock
    {
        return $this->registrar($producto, TipoMovimientoStock::Liberacion, $cantidad, ['origen' => $venta]);
    }

    /** Nota de credito: reingresa unidades al fisico. */
    public function devolver(Producto $producto, int $cantidad, Model $comprobante): MovimientoStock
    {
        return $this->registrar($producto, TipoMovimientoStock::Devolucion, $cantidad, ['origen' => $comprobante]);
    }

    private function validar(TipoMovimientoStock $tipo, int $cantidad, array $opciones): void
    {
        if ($cantidad === 0) {
            throw new InvalidArgumentException('Un movimiento de stock de cantidad cero no registra nada.');
        }

        if ($tipo !== TipoMovimientoStock::Ajuste && $cantidad < 0) {
            throw new InvalidArgumentException(
                "La cantidad de un movimiento de {$tipo->value} es siempre positiva; el signo lo pone el tipo."
            );
        }

        if ($tipo->exigeMotivo() && blank($opciones['motivo'] ?? null)) {
            throw new InvalidArgumentException('Un ajuste de inventario necesita motivo.');
        }
    }
}
