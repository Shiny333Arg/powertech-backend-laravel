<?php

namespace App\Support;

use App\Enums\TipoDireccion;
use App\Models\Direccion;
use Illuminate\Database\Eloquent\Model;

/**
 * El unico domicilio fiscal de una entidad con direcciones polimorficas.
 *
 * <b>Existe para que el panel y la API guarden el domicilio igual.</b> Las dos
 * reglas nacieron como metodos privados de gestion\ProveedorController
 * —guardarDireccion() y la lectura del tipo fiscal en edit()— y la API de la
 * entrega D necesita exactamente las mismas: un solo domicilio por proveedor,
 * reemplazado en lugar de acumulado, y marcado como predeterminado. Copiadas en
 * los dos controladores, el dia que la empresa acepte un segundo domicilio el
 * formulario Blade y la API quedarian guardando cosas distintas. Es el mismo
 * motivo por el que existe App\Support\CompraDelCliente.
 *
 * <b>La tabla admite varias direcciones y eso no cambia.</b> Es la misma que
 * usan clientes —que si necesitan varias de envio— y empleados. Lo que esta
 * clase fija es la regla del PROVEEDOR: le alcanza con uno, asi que
 * updateOrCreate sobre el tipo fiscal reemplaza el que haya.
 */
final class DomicilioFiscal
{
    /**
     * Guarda el domicilio fiscal, o no toca nada si no vino ninguno.
     *
     * `null` significa "no cargaron domicilio" y NO "borralo": un alta sin
     * domicilio es valida, y una edicion que no lo trae no tiene por que
     * destruir el que ya estaba cargado. Ver ProveedorRequest::datosDeLaDireccion().
     *
     * @param  Model  $entidad  cualquier modelo con la relacion direcciones()
     * @param  array<string, mixed>|null  $datos
     */
    public static function guardar(Model $entidad, ?array $datos): void
    {
        if ($datos === null) {
            return;
        }

        $entidad->direcciones()->updateOrCreate(
            ['tipo' => TipoDireccion::Fiscal->value],
            $datos + ['es_predeterminada' => true],
        );
    }

    /**
     * El domicilio fiscal cargado, o null si no tiene.
     *
     * @param  Model  $entidad  cualquier modelo con la relacion direcciones()
     */
    public static function de(Model $entidad): ?Direccion
    {
        return $entidad->direcciones()
            ->where('tipo', TipoDireccion::Fiscal->value)
            ->first();
    }

    /**
     * El domicilio fiscal de una coleccion YA CARGADA, sin volver a la base.
     *
     * Es la version para el Resource: el controlador trae las direcciones con
     * load() y aca se elige la fiscal en memoria. Con de() cada proveedor
     * serializado seria una consulta mas.
     *
     * @param  iterable<Direccion>  $direcciones
     */
    public static function entre(iterable $direcciones): ?Direccion
    {
        foreach ($direcciones as $direccion) {
            if ($direccion->tipo === TipoDireccion::Fiscal) {
                return $direccion;
            }
        }

        return null;
    }
}
