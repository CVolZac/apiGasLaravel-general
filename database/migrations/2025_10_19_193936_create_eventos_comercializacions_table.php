<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('eventos_comercializacion', function (Blueprint $table) {
      $table->id();

      // Relaciones blandas
      $table->unsignedBigInteger('flota_virtual_id');           // contenedor origen/destino (tanque/ducto/autotanque virtual)
      $table->unsignedBigInteger('contraparte_id')->nullable(); // si aplica
      $table->unsignedBigInteger('contrato_id')->nullable();    // si aplica

      // Identificación del evento
      $table->enum('tipo_evento', ['RECEPCION','ENTREGA']);     // guía: "Recepciones"/"Entregas"
      $table->char('tipo_registro',1)->default('D');            // D=diario, M=mensual (guía)
      $table->string('producto_clave', 20);                     // PR##

      // Ventana de medición / operación
      $table->timestamp('fecha_hora_inicio')->nullable();
      $table->timestamp('fecha_hora_fin')->nullable();

      // Condiciones de referencia (guía: 20 °C y 101.325 kPa)
      $table->decimal('temperatura', 8, 3)->nullable();         // default en controlador = 20.000
      $table->decimal('presion_absoluta', 8, 3)->nullable();    // default en controlador = 101.325

      // Tanque (virtual) - valores operativos
      $table->decimal('volumen_inicial_valor', 18, 6)->nullable();
      $table->string('volumen_inicial_um', 10)->nullable();     // "UM03"
      $table->decimal('volumen_movido_valor', 18, 6)->nullable(); // VolumenRecepcion / VolumenEntregado
      $table->string('volumen_movido_um', 10)->nullable();        // "UM03"
      $table->decimal('volumen_final_tanque', 18, 6)->nullable();

      // Existencias del corte (opcional, para el JSON de "Existencias")
      $table->json('existencias')->nullable();  // { VolumenExistenciasAnterior, HoraRecepcionAcumulado, ... VolumenAcumOpsEntrega{ValorNumerico,UnidadDeMedida} }

      // Complemento (JSON fiel a guía: Nacional/Extranjero/Almacenamiento/Trasvase/Dictamen/CFDIs/Pedimentos)
      $table->json('complemento')->nullable();

      // Otros
      $table->text('observaciones')->nullable();

      $table->timestamps();

      $table->index(['tipo_evento','tipo_registro','producto_clave']);
      $table->foreign('flota_virtual_id')->references('id')->on('flota_virtual')->cascadeOnDelete();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('eventos_comercializacion');
  }
};
