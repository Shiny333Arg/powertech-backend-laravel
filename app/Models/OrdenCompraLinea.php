<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * cantidad_recibida habilita recepciones parciales sin tabla de recepciones.
 *
 * proveedor_id es a quien se le pide ESTE renglon, y es un snapshot: se copia
 * del producto al guardar y no vuelve a mirarse. Si manana al producto le
 * cambian el proveedor, los pedidos viejos tienen que seguir diciendo a quien
 * se le pidieron. Cuando un item pueda tener varios proveedores, esta columna
 * pasa a ser la eleccion del operador sin que cambie nada mas.
 */
class OrdenCompraLinea extends Model
{
    protected $table = 'orden_compra_lineas';

    protected $fillable = [
        'orden_compra_id', 'producto_id', 'proveedor_id', 'cantidad_pedida',
        'cantidad_recibida', 'costo_unitario',
    ];

    protected $casts = [
        'cantidad_pedida'   => 'integer',
        'cantidad_recibida' => 'integer',
        'costo_unitario'    => 'decimal:2',
    ];

    public function ordenCompra(): BelongsTo
    {
        return $this->belongsTo(OrdenCompra::class, 'orden_compra_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    /**
     * Lo que todavia se espera de este renglon.
     *
     * Es un accessor y no un metodo para poder usarlo en las vistas y en sum()
     * sin parentesis, como el resto de los calculados del modelo. El max(0)
     * cubre la recepcion de mas: si el proveedor manda 12 sobre 10 pedidas, el
     * pendiente es cero, no -2.
     */
    protected function pendiente(): Attribute
    {
        return Attribute::get(fn () => max(0, $this->cantidad_pedida - $this->cantidad_recibida));
    }

    /**
     * Unidades recibidas por encima de lo pedido.
     *
     * Se acepta recibir de mas: la mercaderia esta en el deposito y negarse a
     * registrarla no la hace desaparecer, solo obliga al operador a cargar lo
     * pedido y despues un ajuste por la diferencia —un ajuste que no tiene
     * origen apuntando a la orden y deja el historico sin explicar de donde
     * salieron esas unidades. Lo que si hace falta es que el excedente se vea,
     * porque el total estimado se calculo sobre lo pedido y la factura del
     * proveedor va a venir por mas.
     */
    protected function excedente(): Attribute
    {
        return Attribute::get(fn () => max(0, $this->cantidad_recibida - $this->cantidad_pedida));
    }

    /** El estimado de este renglon: lo pedido al costo del catalogo. */
    protected function subtotalEstimado(): Attribute
    {
        return Attribute::get(fn () => $this->cantidad_pedida * (float) $this->costo_unitario);
    }

    public function estaCompleta(): bool
    {
        return $this->pendiente === 0;
    }

    public function tieneExcedente(): bool
    {
        return $this->excedente > 0;
    }

    /** Todavia no llego ni una unidad de este renglon. */
    public function sinRecibir(): bool
    {
        return $this->cantidad_recibida === 0;
    }

    /**
     * ¿Lo que falta recibir de este renglon todavia alcanza para sacar al
     * producto de su punto de reposicion?
     *
     * <b>Para que sirve.</b> El faltante de un producto crece con cada venta.
     * Mientras el pedido es borrador, la reposicion automatica lo amplia sola;
     * una vez aprobado, el documento queda congelado —es lo que una persona
     * autorizo y lo que el proveedor va a recibir— y si se sigue vendiendo, la
     * cantidad pedida se queda corta sin que nada lo diga.
     *
     * Ese es el caso que este metodo hace visible: el pedido va a llegar
     * completo y el disponible va a seguir en negativo, con preventas que
     * esperaban esa entrega y no se van a poder entregar. Verlo antes de que
     * llegue le da al administrativo la chance de emitir otro pedido a tiempo.
     *
     * Necesita la relacion 'producto' cargada; sin ella no se pronuncia.
     */
    public function cubreElFaltante(): bool
    {
        $producto = $this->producto;

        if ($producto === null) {
            return true;
        }

        // El disponible puede ser negativo por preventa, y entonces el faltante
        // incluye lo que ya se le debe a clientes. Es la misma cuenta que hace
        // ReposicionService::cantidadSugerida(), sin el lote de compra: acá la
        // pregunta es si alcanza para volver a cero de deuda, no si conviene
        // pedir de mas.
        $faltante = $producto->punto_reposicion - $producto->stock_disponible;

        return $this->pendiente >= $faltante;
    }

    /** Cuantas unidades quedarian sin cubrir. Cero si el renglon alcanza. */
    public function faltanteDescubierto(): int
    {
        $producto = $this->producto;

        if ($producto === null) {
            return 0;
        }

        $faltante = $producto->punto_reposicion - $producto->stock_disponible;

        return max(0, $faltante - $this->pendiente);
    }
}
