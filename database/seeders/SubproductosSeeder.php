<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Producto;
use App\Models\Subproducto;

class SubproductosSeeder extends Seeder
{
    public function run(): void
    {
        $plantaId = (int) env('SEED_PLANTA_ID', 1);

        // Obtenemos productos por clave para relacionar subproductos
        $pr06 = Producto::where('id_planta', $plantaId)->where('clave_producto', 'PR06')->first(); // Gas LP
        $pr03 = Producto::where('id_planta', $plantaId)->where('clave_producto', 'PR03')->first(); // Diesel
        $pr07 = Producto::where('id_planta', $plantaId)->where('clave_producto', 'PR07')->first(); // Gasolina

        $rows = [
            // Gas LP
            [
                'id_planta'         => $plantaId,
                'producto_id'       => optional($pr06)->id,
                'clave_subproducto' => 'SP06A',
                'nombre_subproducto'=> 'GLP Doméstico',
                'unidad_medida'     => 'Litros',
                'octanaje'          => null,
                'composicion'       => 'Propano/Butano (doméstico)',
                'activo'            => true,
            ],
            [
                'id_planta'         => $plantaId,
                'producto_id'       => optional($pr06)->id,
                'clave_subproducto' => 'SP06B',
                'nombre_subproducto'=> 'GLP Industrial',
                'unidad_medida'     => 'Litros',
                'octanaje'          => null,
                'composicion'       => 'Propano/Butano (industrial)',
                'activo'            => true,
            ],

            // Diésel
            [
                'id_planta'         => $plantaId,
                'producto_id'       => optional($pr03)->id,
                'clave_subproducto' => 'SP03U',
                'nombre_subproducto'=> 'Diésel UBA',
                'unidad_medida'     => 'Litros',
                'octanaje'          => null,
                'composicion'       => 'Ultra bajo azufre',
                'activo'            => true,
            ],

            // Gasolina
            [
                'id_planta'         => $plantaId,
                'producto_id'       => optional($pr07)->id,
                'clave_subproducto' => 'SP07R',
                'nombre_subproducto'=> 'Gasolina Regular',
                'unidad_medida'     => 'Litros',
                'octanaje'          => 87,
                'composicion'       => null,
                'activo'            => true,
            ],
            [
                'id_planta'         => $plantaId,
                'producto_id'       => optional($pr07)->id,
                'clave_subproducto' => 'SP07P',
                'nombre_subproducto'=> 'Gasolina Premium',
                'unidad_medida'     => 'Litros',
                'octanaje'          => 91,
                'composicion'       => null,
                'activo'            => true,
            ],
        ];

        foreach ($rows as $r) {
            // Si no existe el producto padre (por ejemplo, borrado), salta ese subproducto
            if (empty($r['producto_id'])) {
                continue;
            }

            // Evita duplicar por la unique (producto_id, clave_subproducto)
            Subproducto::firstOrCreate(
                ['producto_id' => $r['producto_id'], 'clave_subproducto' => $r['clave_subproducto']],
                $r
            );
        }
    }
}
