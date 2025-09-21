// database/migrations/2025_09_21_000000_create_tipo_caracter_planta_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipo_caracter_planta', function (Blueprint $table) {
            $table->id();

            // Relación 1:N con informacion_general_reporte
            $table->unsignedBigInteger('informacion_general_reporte_id');
            $table->foreign('informacion_general_reporte_id', 'tcarp_igr_fk')
                  ->references('id')->on('informacion_general_reporte')
                  ->onDelete('cascade');

            // Campos solicitados
            $table->string('tipo_caracter')->nullable();              // “Carácter”
            $table->string('modalidad_permiso')->nullable();          // “Modalidad” (aplica si hay permiso)
            $table->string('numero_permiso')->nullable();             // “Núm. permiso” (exclusivo con contrato)
            $table->string('numero_contrato_asignacion')->nullable(); // “Contrato/Asignación” (exclusivo con permiso)

            $table->timestamps();

            // Índice único para evitar el mismo renglón duplicado por maestro
            $table->unique([
                'informacion_general_reporte_id',
                'tipo_caracter',
                'modalidad_permiso',
                'numero_permiso',
                'numero_contrato_asignacion',
            ], 'tcarp_unq');

            // CHECK (si tu motor lo soporta: MySQL 8.0.16+ / Postgres OK):
            // - No pueden existir simultáneamente permiso y contrato
            // - Si hay contrato, modalidad también debe ser NULL
            // Nota: Si tu motor no aplica CHECK, esto lo haremos también en validación del controlador.
            $table->check("
                (
                    (numero_permiso IS NOT NULL AND numero_contrato_asignacion IS NULL)
                 OR (numero_permiso IS NULL AND numero_contrato_asignacion IS NOT NULL)
                 OR (numero_permiso IS NULL AND numero_contrato_asignacion IS NULL)
                )
            ");

            $table->check("
                (numero_contrato_asignacion IS NULL) OR (modalidad_permiso IS NULL)
            ");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipo_caracter_planta');
    }
};
