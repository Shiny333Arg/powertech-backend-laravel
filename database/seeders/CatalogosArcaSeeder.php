<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\UpsertsMasterData;
use Illuminate\Database\Seeder;

/**
 * Catálogos fiscales de ARCA (sección B del modelo de datos).
 *
 * Los cuatro catálogos comparten la misma naturaleza: valores definidos por el
 * organismo, identificados por su `codigo_afip`, que es lo que viaja al web
 * service. Por eso van en un solo seeder y no en cuatro clases de diez líneas.
 *
 * La clave natural de todos es `codigo_afip`: si el organismo renombra una
 * etiqueta, la corrida siguiente actualiza el nombre sin duplicar la fila ni
 * romper las FK de los comprobantes ya emitidos.
 *
 * IMPORTANTE: verificar los códigos vigentes contra la tabla de parámetros del
 * WSFEv1 antes del go-live. El organismo los cambia sin aviso.
 */
class CatalogosArcaSeeder extends Seeder
{
    use UpsertsMasterData;

    public function run(): void
    {
        $this->tiposDocumento();
        $this->condicionesIva();
        $this->alicuotasIva();
        $this->tiposComprobante();
    }

    /**
     * Tipos de documento del receptor. Los usan clientes, empleados y comprobantes.
     */
    private function tiposDocumento(): void
    {
        $tipos = [
            ['codigo_afip' => 80, 'nombre' => 'CUIT'],
            ['codigo_afip' => 86, 'nombre' => 'CUIL'],
            ['codigo_afip' => 96, 'nombre' => 'DNI'],
            ['codigo_afip' => 99, 'nombre' => 'Consumidor final'],
        ];

        foreach ($tipos as $tipo) {
            $this->upsertPorClave(
                'tipos_documento',
                ['codigo_afip' => $tipo['codigo_afip']],
                ['nombre' => $tipo['nombre']]
            );
        }

        $this->command?->info('tipos_documento: '.count($tipos).' filas.');
    }

    /**
     * Condición del receptor frente al IVA.
     *
     * Determina la letra del comprobante: responsable inscripto recibe A,
     * el resto B (Requerimientos 7).
     */
    private function condicionesIva(): void
    {
        $condiciones = [
            ['codigo_afip' => 1, 'nombre' => 'IVA Responsable Inscripto'],
            ['codigo_afip' => 4, 'nombre' => 'IVA Sujeto Exento'],
            ['codigo_afip' => 5, 'nombre' => 'Consumidor Final'],
            ['codigo_afip' => 6, 'nombre' => 'Responsable Monotributo'],
        ];

        foreach ($condiciones as $condicion) {
            $this->upsertPorClave(
                'condiciones_iva',
                ['codigo_afip' => $condicion['codigo_afip']],
                ['nombre' => $condicion['nombre']]
            );
        }

        $this->command?->info('condiciones_iva: '.count($condiciones).' filas.');
    }

    /**
     * Alícuotas de IVA. PowerTech es responsable inscripto: el IVA va discriminado
     * y la alícuota se informa con el código que espera el web service, no con el
     * porcentaje.
     *
     * La operatoria normal del rubro es 21 %; el resto queda cargado para no
     * necesitar un deploy si aparece un producto con otra alícuota.
     */
    private function alicuotasIva(): void
    {
        $alicuotas = [
            ['codigo_afip' => 3, 'porcentaje' => '0.00', 'nombre' => '0%'],
            ['codigo_afip' => 9, 'porcentaje' => '2.50', 'nombre' => '2,5%'],
            ['codigo_afip' => 8, 'porcentaje' => '5.00', 'nombre' => '5%'],
            ['codigo_afip' => 4, 'porcentaje' => '10.50', 'nombre' => '10,5%'],
            ['codigo_afip' => 5, 'porcentaje' => '21.00', 'nombre' => '21%'],
            ['codigo_afip' => 6, 'porcentaje' => '27.00', 'nombre' => '27%'],
        ];

        foreach ($alicuotas as $alicuota) {
            $this->upsertPorClave(
                'alicuotas_iva',
                ['codigo_afip' => $alicuota['codigo_afip']],
                ['porcentaje' => $alicuota['porcentaje'], 'nombre' => $alicuota['nombre']]
            );
        }

        $this->command?->info('alicuotas_iva: '.count($alicuotas).' filas.');
    }

    /**
     * Tipos de comprobante habilitados: factura, nota de débito y nota de crédito,
     * en sus dos letras.
     *
     * `signo` (+1 / −1) permite sumar comprobantes para obtener el saldo de una
     * cuenta sin condicionales repartidos por el código: la nota de crédito resta.
     */
    private function tiposComprobante(): void
    {
        $tipos = [
            ['codigo_afip' => 1, 'nombre' => 'Factura A', 'letra' => 'A', 'signo' => 1],
            ['codigo_afip' => 2, 'nombre' => 'Nota de Débito A', 'letra' => 'A', 'signo' => 1],
            ['codigo_afip' => 3, 'nombre' => 'Nota de Crédito A', 'letra' => 'A', 'signo' => -1],
            ['codigo_afip' => 6, 'nombre' => 'Factura B', 'letra' => 'B', 'signo' => 1],
            ['codigo_afip' => 7, 'nombre' => 'Nota de Débito B', 'letra' => 'B', 'signo' => 1],
            ['codigo_afip' => 8, 'nombre' => 'Nota de Crédito B', 'letra' => 'B', 'signo' => -1],
        ];

        foreach ($tipos as $tipo) {
            $this->upsertPorClave(
                'tipos_comprobante',
                ['codigo_afip' => $tipo['codigo_afip']],
                [
                    'nombre' => $tipo['nombre'],
                    'letra' => $tipo['letra'],
                    'signo' => $tipo['signo'],
                ]
            );
        }

        $this->command?->info('tipos_comprobante: '.count($tipos).' filas.');
    }
}
