<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('evento_tanque_virtual', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tanque_virtual_id')
                ->constrained('tanque_virtual')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // Tipos exactos que usaremos en el JSON SAT
            $table->enum('tipo_evento', ['recepcion','entrega','existenciaDiaria'])->index();

            // Recomendado guardar en UTC con tz
            $table->dateTimeTz('fecha_hora');

            // Snapshot de producto/subproducto (el JSON los repite por evento)
            $table->string('producto_clave', 20)->index();
            $table->string('subproducto_clave', 20)->nullable()->index();

            // Volumen del evento (UM base L a 20°C operativamente)
            $table->decimal('volumen_valor', 18, 6);
            $table->string('volumen_um', 20)->default('L');

            // Corte diario y marcador de consolidación (para reporteo interno)
            $table->boolean('es_cierre_diario')->default(false)->index();
            $table->boolean('es_consolidado_mensual')->default(false)->index();

            // Transporte puro: origen y destino (documental/operativo)
            $table->string('origen_rfc', 13)->nullable()->index();
            $table->string('origen_permiso', 100)->nullable();
            $table->string('origen_descripcion', 255)->nullable();
            $table->decimal('origen_geo_lat', 10, 7)->nullable();
            $table->decimal('origen_geo_lng', 10, 7)->nullable();

            $table->string('destino_rfc', 13)->nullable()->index();
            $table->string('destino_permiso', 100)->nullable();
            $table->string('destino_descripcion', 255)->nullable();
            $table->decimal('destino_geo_lat', 10, 7)->nullable();
            $table->decimal('destino_geo_lng', 10, 7)->nullable();

            // Idempotencia para evitar duplicados por reintentos
            $table->string('hash_evento', 64)->nullable()->unique();

            $table->string('observaciones', 500)->nullable();

            $table->timestamps();

            // Índices prácticos
            $table->index(['tanque_virtual_id','fecha_hora'], 'idx_tanque_fecha');
        });
    }

    public function down(): void {
        Schema::dropIfExists('evento_tanque_virtual');
    }
};
