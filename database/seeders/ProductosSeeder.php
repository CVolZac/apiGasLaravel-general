<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Producto;

class ProductosSeeder extends Seeder
{
    public function run(): void
    {
        $plantaId = (int) env('SEED_PLANTA_ID', 1);

        $rows = [
            [
                'id_planta'       => $plantaId,
                'clave_producto'  => 'PR06',
                'nombre'          => 'Gas LP',
                'unidad_medida'   => 'Litros',
                'tipo_combustible'=> 'Petrolífero',
                'descripcion'     => 'Gas licuado de petróleo',
                'activo'          => true,
            ],
            [
                'id_planta'       => $plantaId,
                'clave_producto'  => 'PR03',
                'nombre'          => 'Diésel',
                'unidad_medida'   => 'Litros',
                'tipo_combustible'=> 'Petrolífero',
                'descripcion'     => 'Diésel automotriz (UBA)',
                'activo'          => true,
            ],
            [
                'id_planta'       => $plantaId,
                'clave_producto'  => 'PR07',
                'nombre'          => 'Gasolina',
                'unidad_medida'   => 'Litros',
                'tipo_combustible'=> 'Petrolífero',
                'descripcion'     => 'Familia de gasolinas',
                'activo'          => true,
            ],
            [
                'id_planta'       => $plantaId,
                'clave_producto'  => 'PR08',
                'nombre'          => 'Turbosina',
                'unidad_medida'   => 'Litros',
                'tipo_combustible'=> 'Petrolífero',
                'descripcion'     => 'Turbosina/Jet A-1',
                'activo'          => true,
            ],
        ];

        foreach ($rows as $r) {
            // Evita duplicar por la unique (id_planta, clave_producto)
            Producto::firstOrCreate(
                ['id_planta' => $r['id_planta'], 'clave_producto' => $r['clave_producto']],
                $r
            );
        }
    }
}
