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

            $table->unsignedBigInteger('informacion_general_reporte_id');
            $table->foreign('informacion_general_reporte_id', 'tcarp_igr_fk')
                ->references('id')->on('informacion_general_reporte')
                ->onDelete('cascade');

            $table->string('tipo_caracter')->nullable();
            $table->string('modalidad_permiso')->nullable();
            $table->string('numero_permiso')->nullable();
            $table->string('numero_contrato_asignacion')->nullable();

            $table->timestamps();

            $table->unique([
                'informacion_general_reporte_id',
                'tipo_caracter',
                'modalidad_permiso',
                'numero_permiso',
                'numero_contrato_asignacion',
            ], 'tcarp_unq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipo_caracter_planta');
    }
};
