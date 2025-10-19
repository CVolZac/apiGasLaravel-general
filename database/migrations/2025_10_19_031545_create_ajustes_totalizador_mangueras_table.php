<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ajustes_totalizador_manguera', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_manguera');
            $table->timestamp('fecha_hora_ajuste');
            $table->decimal('lectura_anterior', 18, 3);
            $table->decimal('lectura_nueva', 18, 3);
            $table->date('aplica_desde'); // desde qué día considerar la nueva base
            $table->text('motivo')->nullable();
            $table->timestamps();

            $table->index(['id_manguera', 'aplica_desde']);
            $table->foreign('id_manguera')->references('id')->on('mangueras')->onDelete('cascade');
        });
    }
    public function down(): void {
        Schema::dropIfExists('ajustes_totalizador_manguera');
    }
};
