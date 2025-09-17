<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('complemento_transporte', function (Blueprint $t) {
            $t->id();
            $t->foreignId('evento_id')->constrained('eventos_almacen')->cascadeOnDelete();
            $t->string('permiso', 100);
            $t->string('clave_vehiculo', 100)->nullable();
            $t->decimal('tarifa', 18, 6)->default(0);
            $t->decimal('cargo_capacidad', 18, 6)->nullable();
            $t->decimal('cargo_uso', 18, 6)->nullable();
            $t->decimal('cargo_volumetrico', 18, 6)->nullable();
            $t->timestamps();
            $t->unique('evento_id'); // 1:1
        });

        Schema::create('complemento_trasvase', function (Blueprint $t) {
            $t->id();
            $t->foreignId('evento_id')->constrained('eventos_almacen')->cascadeOnDelete();
            $t->string('nombre', 255);
            $t->string('rfc', 13);
            $t->string('permiso', 100)->nullable();
            $t->string('descripcion', 500)->nullable();
            $t->uuid('cfdi_uuid')->nullable();
            $t->timestamps();
            $t->unique('evento_id'); // 1:1
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complemento_trasvase');
        Schema::dropIfExists('complemento_transporte');
    }
};
