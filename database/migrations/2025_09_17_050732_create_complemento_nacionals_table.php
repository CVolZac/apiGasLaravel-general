<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('complemento_nacional', function (Blueprint $t) {
            $t->id();
            $t->foreignId('evento_id')->constrained('eventos_almacen')->cascadeOnDelete();
            $t->string('rfc_proveedor', 13)->nullable();
            $t->string('nombre_proveedor', 255)->nullable();
            $t->string('permiso_proveedor', 100)->nullable();
            $t->timestamps();
            $t->unique('evento_id'); // 1:1
        });

        Schema::create('evento_cfdi', function (Blueprint $t) {
            $t->id();
            $t->foreignId('evento_id')->constrained('eventos_almacen')->cascadeOnDelete();
            $t->foreignId('cfdi_id')->constrained('cfdis')->cascadeOnDelete();

            // Metadatos capturados en tu UI
            $t->enum('tipo_cfdi', ['ingreso','traslado','egreso'])->nullable();
            $t->timestamp('fecha_hora')->nullable();
            $t->decimal('precio_compra', 18, 6)->nullable();
            $t->decimal('contraprestacion', 18, 6)->nullable();
            $t->decimal('volumen_documentado_valor', 18, 6)->nullable();
            $t->string('volumen_documentado_unidad', 12)->nullable();
            $t->decimal('monto_total', 18, 6)->nullable();
            $t->string('rfc_emisor', 13)->nullable();
            $t->string('nombre_emisor', 255)->nullable();
            $t->string('rfc_receptor', 13)->nullable();

            $t->timestamps();
            $t->unique(['evento_id','cfdi_id']);
            $t->index(['evento_id','fecha_hora']);
        });

        // 🔕 Quitado el intento de volver a crear cfdis_uuid_unique
    }

    public function down(): void
    {
        Schema::dropIfExists('evento_cfdi');
        Schema::dropIfExists('complemento_nacional');
    }
};
