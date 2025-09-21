<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('comercializador_instalacion', function (Blueprint $table) {
            $table->id();
            $table->string('rfc_contribuyente', 13)->index();
            $table->string('rfc_representante_legal', 13)->nullable()->index();
            $table->string('caracter', 50)->nullable(); // permisionario, etc.
            $table->string('modalidad_permiso', 100)->nullable();
            $table->string('num_permiso', 100)->nullable()->index();
            $table->string('clave_instalacion', 100)->nullable()->index();
            $table->string('descripcion_instalacion', 255)->nullable();
            $table->decimal('geolat', 10, 7)->nullable();
            $table->decimal('geolng', 10, 7)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('comercializador_instalacion');
    }
};
