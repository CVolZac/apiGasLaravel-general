<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('evento_cfdi_tanque_virutal', function (Blueprint $table) {
            $table->id();

            // Relación al evento del tanque virtual de comercializadores
            $table->foreignId('evento_id')
                ->constrained('evento_tanque_virtual')  // PK id
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // CFDI asociado al evento
            $table->uuid('uuid')->index();
            $table->enum('tipo_cfdi', ['Ingreso','Egreso','Traslado'])->nullable();

            // Económicos/fecha del CFDI
            $table->decimal('precio', 18, 6)->nullable();
            $table->dateTimeTz('fecha_hora_transaccion')->nullable();

            // Volumen documentado
            $table->decimal('vol_doc_valor', 18, 6)->nullable();
            $table->string('vol_doc_um', 20)->nullable();

            $table->timestamps();

            // No repetir el mismo CFDI en el mismo evento
            $table->unique(['evento_id','uuid'], 'uniq_evento_uuid_virutal');

            // Búsquedas típicas
            $table->index(['evento_id', 'fecha_hora_transaccion'], 'idx_evento_fecha_virutal');
        });
    }

    public function down(): void {
        Schema::dropIfExists('evento_cfdi_tanque_virutal');
    }
};
