<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('flota_virtual', function (Blueprint $table) {
      $table->id();
      $table->string('clave_contenedor', 80)->unique(); // Ej. "AUT-PR12-01"
      $table->enum('tipo_contenedor', ['AUTOTANQUE_VIRTUAL','DUCTO_VIRTUAL','TANQUE_VIRTUAL']);
      $table->string('producto_clave', 20);            // PR##
      $table->decimal('capacidad_valor', 16, 6)->nullable();
      $table->string('capacidad_um', 20)->nullable();  // litros/kg/bbl/etc.
      $table->enum('estatus', ['activo','inactivo'])->default('activo');
      $table->string('descripcion', 255)->nullable();
      $table->timestamps();

      $table->index(['tipo_contenedor','producto_clave']);
    });
  }

  public function down(): void {
    Schema::dropIfExists('flota_virtual');
  }
};
