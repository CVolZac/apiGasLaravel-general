<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('bitacora_dispensarios', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('id_planta');
            $table->timestamp('fecha_hora'); // ISO/UTC o local, decide a nivel app

            // Catálogo de eventos (puedes ajustar valores a tu catálogo definitivo)
            $table->string('tipo_evento', 50);

            // Severidad básica
            $table->enum('severidad', ['INFO','WARN','CRIT'])->default('INFO');

            // Componente afectado dentro del expendio al público
            $table->enum('componente_tipo', ['DISPENSARIO','MANGUERA','MEDIDOR']);

            // ID del componente (dispensario/manguera/medidor)
            $table->unsignedBigInteger('id_componente');

            // Por conveniencia para filtros/joins cuando el componente es hija del dispensario
            $table->unsignedBigInteger('id_dispensario')->nullable();

            $table->string('usuario_responsable', 120)->nullable();
            $table->text('descripcion')->nullable();

            $table->timestamps();

            $table->index(['id_planta', 'fecha_hora']);
            $table->index(['componente_tipo', 'id_componente']);
            $table->index(['id_dispensario']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('bitacora_dispensarios');
    }
};
