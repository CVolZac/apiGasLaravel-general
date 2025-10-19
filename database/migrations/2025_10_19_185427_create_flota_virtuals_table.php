<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('flota_virtual', function (Blueprint $table) {
      $table->id();

      // ---- Base del contenedor virtual ----
      $table->string('clave_contenedor', 80)->unique(); // Ej. "AUT-PR12-01"
      $table->enum('tipo_contenedor', ['AUTOTANQUE_VIRTUAL','DUCTO_VIRTUAL','TANQUE_VIRTUAL']);
      $table->string('producto_clave', 20);             // PR##
      $table->decimal('capacidad_valor', 16, 6)->nullable();
      $table->string('capacidad_um', 20)->nullable();   // litros/kg/bbl/etc.
      $table->enum('estatus', ['activo','inactivo'])->default('activo');
      $table->string('descripcion', 255)->nullable();

      // ---- Información Tanque (solo aplica si tipo_contenedor = TANQUE_VIRTUAL) ----
      // Identificación / estado
      $table->string('tanque_clave_identificacion', 80)->nullable(); // "TQS-TDA-0001"
      $table->string('tanque_descripcion', 255)->nullable();         // "Tanque de almacenamiento virtual..."
      $table->date('tanque_vigencia_calibracion')->nullable();       // "2022-01-01"
      $table->enum('tanque_estado', ['O','F'])->nullable();          // O=Operativo, F=Fuera de operación

      // Capacidades y volúmenes (valor + UM)
      $table->decimal('tanque_cap_total_valor',    18, 6)->nullable();
      $table->string('tanque_cap_total_um',        10)->nullable();

      $table->decimal('tanque_cap_oper_valor',     18, 6)->nullable();
      $table->string('tanque_cap_oper_um',         10)->nullable();

      $table->decimal('tanque_cap_util_valor',     18, 6)->nullable();
      $table->string('tanque_cap_util_um',         10)->nullable();

      $table->decimal('tanque_cap_fondaje_valor',  18, 6)->nullable();
      $table->string('tanque_cap_fondaje_um',      10)->nullable();

      $table->decimal('tanque_vol_min_oper_valor', 18, 6)->nullable();
      $table->string('tanque_vol_min_oper_um',     10)->nullable();

      $table->timestamps();

      // Índices útiles
      $table->index(['tipo_contenedor','producto_clave']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('flota_virtual');
  }
};
