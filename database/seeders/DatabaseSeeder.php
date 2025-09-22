<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Poblar la tabla de roles
        DB::table('roles_usuarios')->insert([
            ['rol' => 'Admin'],
            ['rol' => 'Capturista'],
            ['rol' => 'Analista'],
        ]);

        DB::table('planta_gas')->insert([
            [
                'nombre_planta' => "Gas Butano Zacatecas S.A de C.V",
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]
        ]);

        //\App\Models\User::factory(1)->create();

        \App\Models\User::factory()->create([
            'name' => 'Test User',
            'id_planta' => 1,
            'id_rol_usuario' => 1,
            'email' => 'test@example.com',
            'password' => 'test12345'
        ]);

        \App\Models\User::factory()->create([
            'name' => 'Tarjetita ESP8266',
            'id_planta' => 1,
            'id_rol_usuario' => 2,
            'email' => 'esp8266@gasbutano.com',
            'password' => 'esp8266butano'
        ]);



        // 1. Insertar maestro
        $infoGeneralId = DB::table('informacion_general_reporte')->insertGetId([
            'id_planta' => 1,
            'rfc_contribuyente' => 'XYZJ880326XXX',
            'rfc_representante_legal' => 'ABCD123456XXX',
            'rfc_proveedor' => 'PROV730123XXX',
            'rfc_proveedores' => json_encode(["XYZJ880326XXX", "ABCD123456XXX", "PROV730123XXX"]),
            'instalacion_almacen_gas' => 'Instalación 1',
            'clave_instalacion' => 'Clave1',
            'descripcion_instalacion' => 'Descripción de la instalación 1',
            'geolocalizacion_latitud' => 19.432608,
            'geolocalizacion_longitud' => -99.133209,
            'numero_pozos' => 5,
            'numero_tanques' => 10,
            'numero_ductos_entrada_salida' => 2,
            'numero_ductos_transporte' => 3,
            'numero_dispensarios' => 4,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // 2. Insertar caracteres (hijos)
        DB::table('tipo_caracter_planta')->insert([
            [
                'informacion_general_reporte_id' => $infoGeneralId,
                'tipo_caracter' => 'permisionario',
                'modalidad_permiso' => 'ALM',
                'numero_permiso' => 'PL/12345/ALM/2025',
                'numero_contrato_asignacion' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'informacion_general_reporte_id' => $infoGeneralId,
                'tipo_caracter' => 'contratista',
                'modalidad_permiso' => null,
                'numero_permiso' => null,
                'numero_contrato_asignacion' => 'CON-2025-001',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);

        DB::table('comercializador_instalacion')->insert([
            [
                'rfc_contribuyente'        => 'XYZJ880326XXX',
                'rfc_representante_legal'  => 'ABCD123456XXX',
                'caracter'                 => 'permisionario',
                'modalidad_permiso'        => 'ALM',
                'num_permiso'              => 'PL/12345/ALM/2025',
                'clave_instalacion'        => 'CMN-INS-0001',
                'descripcion_instalacion'  => 'Instalación principal (patio 1)',
                'geolat'                   => 22.7700000,
                'geolng'                   => -102.5700000,
                'created_at'               => Carbon::now(),
                'updated_at'               => Carbon::now(),
            ],
        ]);
    }
}
