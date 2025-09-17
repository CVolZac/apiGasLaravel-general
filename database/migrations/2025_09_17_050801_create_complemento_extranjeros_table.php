<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('complemento_extranjero', function (Blueprint $t) {
            $t->id();
            $t->foreignId('evento_id')->constrained('eventos_almacen')->cascadeOnDelete();
            $t->string('permiso_importacion', 100)->nullable();
            $t->timestamps();
            $t->unique('evento_id'); // 1:1
        });

        Schema::create('evento_pedimento', function (Blueprint $t) {
            $t->id();
            $t->foreignId('evento_id')->constrained('eventos_almacen')->cascadeOnDelete();

            $t->string('pedimento', 50);
            $t->string('punto_internacion', 150)->nullable();
            $t->string('pais_origen', 100)->nullable();
            $t->string('medio_trans_aduana', 100)->nullable();
            $t->string('incoterms', 20)->nullable();
            $t->decimal('precio_importacion', 18, 6)->nullable();

            $t->decimal('volumen_documentado_valor', 18, 6)->nullable();
            $t->string('volumen_documentado_unidad', 12)->nullable();

            $t->timestamp('fecha_hora')->nullable();

            $t->timestamps();
            $t->unique(['evento_id','pedimento']);
            $t->index(['evento_id','fecha_hora']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evento_pedimento');
        Schema::dropIfExists('complemento_extranjero');
    }
};
