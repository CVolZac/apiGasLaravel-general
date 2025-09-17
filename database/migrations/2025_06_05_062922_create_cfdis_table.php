<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla `cfdis` normalizada (snake_case) y alineada a la UI.
     * Relación 1:N con `eventos_almacen` vía `evento_id`.
     */
    public function up(): void
    {
        Schema::create('cfdis', function (Blueprint $table) {
            $table->id();

            // Relación al evento de almacén (1:N)
            $table->foreignId('evento_id')
                  ->constrained('eventos_almacen')
                  ->cascadeOnDelete();

            // Metadatos “generales” (opcionales, por compatibilidad con tu esquema previo)
            $table->string('tipo_complemento', 30)->default('CFDI'); // antes: TipoComplemento
            $table->string('version', 10)->default('1.0');           // antes: Version

            // Identificadores y emisor/receptor (normalizados)
            $table->uuid('uuid');                       // antes: UUID
            $table->string('rfc_emisor', 13);           // antes: RFCEmisorCFDI
            $table->string('nombre_emisor', 255)->nullable(); // antes: NombreEmisorCFDI
            $table->string('rfc_receptor', 13)->nullable();   // antes: RFCProveedorReceptor

            // Importe total y fecha/hora del CFDI
            $table->decimal('monto_total', 18, 6)->nullable(); // antes: MontoTotalOperacion (12,2) → 18,6 para más precisión
            $table->timestamp('fecha_hora')->nullable();       // antes: FechaCFDI (date) → timestamp

            // Campos capturados en tu UI de CFDIs
            $table->enum('tipo_cfdi', ['ingreso', 'traslado', 'egreso'])->nullable();
            $table->decimal('precio_compra', 18, 6)->nullable();
            $table->decimal('contraprestacion', 18, 6)->nullable();
            $table->decimal('volumen_documentado_valor', 18, 6)->nullable();
            $table->string('volumen_documentado_unidad', 12)->nullable(); // p.ej. L, L20C

            $table->timestamps();

            // Índices/constraints
            $table->unique('uuid', 'cfdis_uuid_unique');
            $table->index(['evento_id', 'fecha_hora'], 'cfdis_evento_fecha_idx');
        });
    }

    /**
     * Revierte la creación de la tabla.
     */
    public function down(): void
    {
        Schema::dropIfExists('cfdis');
    }
};
