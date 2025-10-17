<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('evento_tanque_virtual', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tanque_virtual_id')
                ->constrained('tanque_virtual')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->enum('tipo_evento', ['recepcion','entrega','existencia'])->index();
            $table->dateTimeTz('fecha_hora'); // se recomienda guardar en UTC con tz

            $table->decimal('volumen_valor', 18, 6);
            $table->string('volumen_um', 20)->default('L'); // o 'm3' según configuración

            $table->boolean('es_cierre_diario')->default(false)->index();
            $table->boolean('es_consolidado_mensual')->default(false)->index();

            $table->string('observaciones', 500)->nullable();

            $table->decimal('geo_lat', 10, 7)->nullable();
            $table->decimal('geo_lng', 10, 7)->nullable();

            $table->timestamps();

            $table->index(['tanque_virtual_id','fecha_hora'], 'idx_tanque_fecha');
        });
    }

    public function down(): void {
        Schema::dropIfExists('evento_tanque_virtual');
    }
};
