<?php

namespace App\Http\Resources\Gestion;

use App\Models\Proveedor;
use App\Support\DomicilioFiscal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Proveedor visto desde el panel.
 *
 * <b>Solo existe para gestion y no tiene hermano en la tienda, a proposito.</b>
 * El cliente no compra a un proveedor ni tiene por que saber a quien le compra
 * la empresa: el costo de reposicion, el plazo de entrega y el portal de
 * pedidos son datos de la operacion interna. La API de la tienda
 * (routes/api.php) no publica proveedores en ningun endpoint, y de hecho el
 * ProductoResource de la tienda ni siquiera incluye el proveedor del producto
 * —el de gestion si—. Este resource vive bajo el prefijo /gestion y no se
 * reutiliza fuera de el.
 *
 * Los campos se enumeran uno por uno, nunca toArray() del modelo: es la misma
 * regla que CategoriaResource y ProductoResource, y lo que impide que una
 * columna agregada manana se publique sin que nadie lo decida.
 *
 * <b>Los enums viajan con valor y etiqueta</b>, igual que
 * MovimientoStockResource: el valor es el que la pantalla manda de vuelta en el
 * filtro, y la etiqueta la define CanalPedido, asi que el panel Blade y la API
 * nombran cada canal igual.
 *
 * @mixin Proveedor
 */
class ProveedorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'razon_social' => $this->razon_social,

            // Los 11 digitos tal como estan en la columna. El formato con
            // guiones lo arma la pantalla: ProveedorRequest normaliza al
            // guardar justamente para que el indice unico sirva.
            'cuit' => $this->cuit,

            'email'    => $this->email,
            'telefono' => $this->telefono,
            'contacto' => $this->contacto,

            // Como se le hace llegar la orden de compra.
            'canal_pedido'          => $this->canal_pedido?->value,
            'canal_pedido_etiqueta' => $this->canal_pedido?->etiqueta(),

            // Solo tiene sentido con el canal 'portal_externo'. En los otros
            // dos queda null: el request lo limpia al cambiar de canal, para no
            // arrastrar un dato muerto.
            'portal_url' => $this->portal_url,

            // Alimenta la fecha estimada de entrega de la orden de compra.
            'plazo_entrega_dias' => $this->plazo_entrega_dias,

            'activo' => (bool) $this->activo,

            'condicion_iva' => $this->condicionIva ? [
                'id'     => $this->condicionIva->id,
                'nombre' => $this->condicionIva->nombre,
            ] : null,

            // ----------------------------------------------------------
            // Los dos conteos que se miran antes de dar de baja
            // ----------------------------------------------------------
            //
            // Son los mismos que muestra el listado del panel y responden las
            // dos preguntas de la baja: cuantos productos se quedan sin quien
            // los reponga, y cuantas ordenes siguen su curso. Viajan solo
            // cuando la consulta hizo el withCount(); sin whenCounted, una
            // respuesta sin el conteo emitiria null y la pantalla no podria
            // distinguir "no vino" de "no tiene ninguno".
            'productos_count'        => $this->whenCounted('productos'),
            'ordenes_abiertas_count' => $this->whenCounted('ordenes_abiertas'),

            // ----------------------------------------------------------
            // Domicilio fiscal
            // ----------------------------------------------------------
            //
            // Viaja en el detalle y en las respuestas de alta y edicion —donde
            // confirma lo que se guardo— y NO en el listado: son 15 filas mas
            // por pagina de una tabla polimorfica que la grilla no muestra. La
            // clave falta cuando no se cargo la relacion, y es null cuando el
            // proveedor no tiene domicilio: no son lo mismo.
            'domicilio_fiscal' => $this->whenLoaded('direcciones', function () {
                $domicilio = DomicilioFiscal::entre($this->direcciones);

                return $domicilio ? [
                    'calle'         => $domicilio->calle,
                    'numero'        => $domicilio->numero,
                    'piso_depto'    => $domicilio->piso_depto,
                    'codigo_postal' => $domicilio->codigo_postal,
                    'localidad'     => $domicilio->localidad,
                    'provincia'     => $domicilio->provincia,
                ] : null;
            }),
        ];
    }
}
