<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('totalizadores_manguera_diario', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_manguera');
            $table->date('fecha');
            $table->unsignedBigInteger('id_subproducto')->nullable(); // histórico útil
            $table->decimal('totalizador_inicial_dia', 18, 3);
            $table->decimal('totalizador_final_dia', 18, 3);
            $table->decimal('volumen_entregado_dia', 18, 3);
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->unique(['id_manguera', 'fecha'], 'manguera_fecha_unique');
            $table->index(['fecha']);
            $table->index(['id_subproducto']);

            $table->foreign('id_manguera')->references('id')->on('mangueras')->onDelete('cascade');
            // Si tienes subproductos:
            // $table->foreign('id_subproducto')->references('id')->on('subproductos');
        });
    }
    public function down(): void {
        Schema::dropIfExists('totalizadores_manguera_diario');
    }
};
