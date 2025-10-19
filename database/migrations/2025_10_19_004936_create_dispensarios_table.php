<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('dispensarios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_planta');
            $table->string('clave_dispensario', 50);
            $table->string('descripcion')->nullable();
            $table->timestamps();

            $table->unique(['id_planta', 'clave_dispensario'], 'dispensarios_planta_clave_unique');
            // Si tienes catálogo de plantas, agrega FK aquí:
            //$table->foreign('id_planta')->references('id')->on('plantas')->onDelete('cascade');
        });
    }

    public function down(): void {
        Schema::dropIfExists('dispensarios');
    }
};
