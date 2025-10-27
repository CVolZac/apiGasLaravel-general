<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('eventos_comercializacion', function (Blueprint $table) {
      $table->id();

      // Relaciones
      $table->unsignedBigInteger('flota_virtual_id');
      $table->unsignedBigInteger('contraparte_id')->nullable();
      $table->unsignedBigInteger('contrato_id')->nullable();

      // Identificación del evento
      $table->enum('tipo_evento', ['RECEPCION','ENTREGA']);   // físico: recepción/entrega
      $table->char('tipo_registro',1)->default('D');          // D=diario, M=mensual
      $table->string('producto_clave', 20);                   // PR##

      // Ámbito/complemento principal (clave para reglas)
      $table->enum('ambito', ['NACIONAL','EXTRANJERO','TRASLADO_INTERNO'])->nullable();

      // Ventanas/medición
      $table->timestamp('fecha_hora_inicio')->nullable();
      $table->timestamp('fecha_hora_fin')->nullable();
      $table->timestamp('fecha_hora_medicion')->nullable();   // corte de existencias

      // Condiciones (defaults 20 °C / 101.325 kPa en controlador)
      $table->decimal('temperatura', 8, 3)->nullable();
      $table->decimal('presion_absoluta', 8, 3)->nullable();

      // Tanque (virtual) – valores operativos
      $table->decimal('volumen_inicial_valor', 18, 6)->nullable();
      $table->string('volumen_inicial_um', 10)->nullable();     // UM03, etc.
      $table->decimal('volumen_movido_valor', 18, 6)->nullable(); // recepción o entrega
      $table->string('volumen_movido_um', 10)->nullable();
      $table->decimal('volumen_final_tanque', 18, 6)->nullable();

      // Existencias (opcional, bloque completo del ejemplo SAT)
      $table->json('existencias')->nullable();

      // Conciliación documentada (suma de CFDI o pedimentos)
      $table->decimal('volumen_documentado_total', 18, 6)->nullable();
      $table->string('volumen_documentado_um', 10)->nullable();

      // Snapshots de contraparte (evita reescritura histórica)
      $table->string('rfc_contraparte', 13)->nullable();
      $table->string('nombre_contraparte', 255)->nullable();
      $table->string('permiso_contraparte', 50)->nullable();

      // Complemento (DICTAMEN / INSTRUMENTO / ACLARACION y demás metadatos poco consultados)
      $table->json('complemento')->nullable();

      // Control de calidad / auditoría
      $table->enum('estatus_validacion', ['PENDIENTE','VALIDO','OBSERVADO','RECHAZADO'])->default('PENDIENTE');
      $table->text('motivo_observacion')->nullable();
      $table->string('version_esquema', 40)->default('RMF2025-Anexo30');

      // Observaciones generales
      $table->text('observaciones')->nullable();

      $table->timestamps();

      // Índices/FKs
      $table->index(['tipo_evento','tipo_registro','producto_clave']);
      $table->index(['ambito','fecha_hora_inicio']);
      $table->index(['flota_virtual_id','producto_clave']);

      $table->foreign('flota_virtual_id')->references('id')->on('flota_virtual')->cascadeOnDelete();
      // contraparte_id/contrato_id: si tienes tablas definidas, agrega sus FKs aquí.
    });

    // CHECKs útiles (solo PostgreSQL; si usas MySQL, omite esto)
    if (DB::getDriverName() === 'pgsql') {
      DB::statement("
        ALTER TABLE eventos_comercializacion
        ADD CONSTRAINT chk_ev_um_consistente
        CHECK (
          (volumen_inicial_um IS NULL AND volumen_movido_um IS NULL)
          OR (volumen_inicial_um = volumen_movido_um)
        )
      ");
      DB::statement("
        ALTER TABLE eventos_comercializacion
        ADD CONSTRAINT chk_ev_ambito_recepcion
        CHECK (
          tipo_evento <> 'RECEPCION'
          OR ambito IN ('NACIONAL','EXTRANJERO')
        )
      ");
    }
  }

  public function down(): void
  {
    Schema::dropIfExists('eventos_comercializacion');
  }
};
