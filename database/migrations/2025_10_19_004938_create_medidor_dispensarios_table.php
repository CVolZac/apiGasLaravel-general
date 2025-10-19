<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('medidor_dispensarios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_dispensario');
            $table->string('sistema_medicion_dispensario', 80);  // SMD-DISP-####
            $table->text('descripcion_medidor')->nullable();
            $table->date('vigencia_calibracion')->nullable();    // YYYY-MM-DD
            $table->decimal('incertidumbre_medicion', 8, 4)->nullable(); // 0.0100
            $table->timestamps();

            $table->foreign('id_dispensario')->references('id')->on('dispensarios')->onDelete('cascade');
        });
    }

    public function down(): void {
        Schema::dropIfExists('medidor_dispensarios');
    }
};
