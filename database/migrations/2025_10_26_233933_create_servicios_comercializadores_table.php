<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('servicios_comercializadores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('evento_id'); // FK a eventos_comercializacion

            $table->enum('tipo_servicio', ['ALMACENAMIENTO','TRANSPORTE','LICUEFACCION','REGASIFICACION']);
            $table->string('rfc_prestador', 13)->nullable();
            $table->string('permiso_prestador', 50)->nullable();
            $table->string('descripcion', 255)->nullable();

            $table->timestamps();

            $table->foreign('evento_id')->references('id')->on('eventos_comercializacion')->cascadeOnDelete();
            $table->index(['evento_id', 'tipo_servicio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servicios_comercializadores');
    }
};
