<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('bitacora_comercializacion', function (Blueprint $table) {
      $table->id();
      $table->string('entidad', 50);               // contrato | contraparte | evento | reporte | complemento
      $table->unsignedBigInteger('entidad_id')->nullable();
      $table->string('accion', 30);                // create | update | delete | validate | generate | send | error
      $table->string('descripcion', 255)->nullable();
      $table->json('payload')->nullable();         // snapshot (JSONB en Postgres)
      $table->unsignedBigInteger('usuario_id')->nullable();
      $table->timestamps();

      $table->index(['entidad', 'accion']);
      $table->index(['created_at']);
    });
  }

  public function down(): void {
    Schema::dropIfExists('bitacora_comercializacion');
  }
};
