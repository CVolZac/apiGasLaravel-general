<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
         Schema::create('cfdis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evento_id')->constrained('eventos_almacen');
            $table->string('TipoComplemento')->default('CFDI');
            $table->string('Version')->default('1.0');
            $table->string('UUID');
            $table->string('RFCEmisorCFDI');
            $table->string('NombreEmisorCFDI')->nullable();
            $table->string('RFCProveedorReceptor')->nullable();
            $table->decimal('MontoTotalOperacion', 12, 2)->nullable();
            $table->date('FechaCFDI');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cfdis');
    }
};
