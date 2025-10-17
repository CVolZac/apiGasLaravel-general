<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_planta');        // planta propietaria
            $table->string('clave_producto', 10);           // PR##
            $table->string('nombre', 120);
            $table->string('unidad_medida', 20)->default('Litros'); // Litros|m3|kg|GJ|Otra
            $table->string('tipo_combustible', 30)->default('Petrolífero');
            $table->string('descripcion', 255)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Reglas
            $table->unique(['id_planta', 'clave_producto']);
            $table->index('id_planta');

            // Si tienes tabla plantas:
            // $table->foreign('id_planta')->references('id')->on('plantas')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
