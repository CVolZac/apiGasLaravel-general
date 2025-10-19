<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('mangueras', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_dispensario');
            $table->string('identificador_manguera', 80);
            $table->enum('estado', ['O','F'])->default('O'); // O: operación, F: fuera de operación
            $table->timestamps();

            $table->foreign('id_dispensario')->references('id')->on('dispensarios')->onDelete('cascade');
            $table->unique(['id_dispensario','identificador_manguera'], 'mangueras_disp_mga_unique');
        });
    }

    public function down(): void {
        Schema::dropIfExists('mangueras');
    }
};
