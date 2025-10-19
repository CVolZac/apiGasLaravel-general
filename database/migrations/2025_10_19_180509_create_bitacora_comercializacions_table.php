<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('bitacora_comercializacion', function (Blueprint $table) {
      $table->id(); // <- será tu NumeroRegistro
      $table->timestampTz('fecha_hora_evento');     // FechaYHoraEvento
      $table->unsignedInteger('tipo_evento');       // TipoEvento (catálogo estático)
      $table->string('descripcion_evento', 255);    // DescripcionEvento
      $table->timestamps();
      $table->index(['fecha_hora_evento','tipo_evento']);
    });
  }
  public function down(): void {
    Schema::dropIfExists('bitacora_comercializacion');
  }
};
