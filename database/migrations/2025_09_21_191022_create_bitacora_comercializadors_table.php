<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('bitacora_comercializador', function (Blueprint $table) {
            $table->id();
            $table->string('entidad', 100); // tanque_virtual, evento, cfdi, complemento, etc.
            $table->unsignedBigInteger('entidad_id')->index();
            $table->string('accion', 50); // crear, actualizar, eliminar, exportar
            $table->unsignedBigInteger('usuario_id')->nullable()->index();
            $table->json('antes_json')->nullable();
            $table->json('despues_json')->nullable();
            $table->string('ip', 64)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();

            $table->index(['entidad','entidad_id'], 'idx_entidad_entidadid');
        });
    }

    public function down(): void {
        Schema::dropIfExists('bitacora_comercializador');
    }
};
