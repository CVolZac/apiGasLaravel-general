<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('complementos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evento_id')
                ->constrained('evento_tanque_virtual')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // Transporte
            $table->string('permiso_transporte', 100)->nullable();
            $table->string('clave_vehiculo', 100)->nullable();
            $table->decimal('tarifa_transporte', 18, 6)->nullable();
            $table->decimal('cargo_capacidad_trans', 18, 6)->nullable();
            $table->decimal('cargo_uso_trans', 18, 6)->nullable();
            $table->decimal('cargo_volumetrico_trans', 18, 6)->nullable();

            // Trasvase
            $table->string('trasvase_nombre', 255)->nullable();
            $table->string('trasvase_rfc', 13)->nullable();
            $table->string('trasvase_permiso', 100)->nullable();
            $table->string('trasvase_descripcion', 255)->nullable();
            $table->uuid('trasvase_cfdi')->nullable();

            // Dictamen
            $table->string('dictamen_rfc', 13)->nullable();
            $table->string('dictamen_lote', 100)->nullable();
            $table->string('dictamen_folio', 100)->nullable();
            $table->date('dictamen_fecha')->nullable();
            $table->string('dictamen_resultado', 100)->nullable();

            // Certificado
            $table->string('certificado_rfc', 13)->nullable();
            $table->string('certificado_folio', 100)->nullable();
            $table->date('certificado_fecha')->nullable();
            $table->string('certificado_resultado', 100)->nullable();

            // Aclaración
            $table->text('aclaracion_texto')->nullable();

            $table->timestamps();

            $table->index(['evento_id']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('complementos');
    }
};
