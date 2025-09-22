<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bitacora_comercializador', function (Blueprint $table) {
            $table->id(); // servirá como NumeroRegistro
            $table->dateTime('FechaYHoraEvento'); 
            $table->unsignedBigInteger('TipoEvento'); 
            $table->string('DescripcionEvento', 500);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bitacora_comercializador');
    }
};
