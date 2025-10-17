<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subproductos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_planta');        // por consistencia de filtrado
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->string('clave_subproducto', 15);        // SP...
            $table->string('nombre_subproducto', 150);
            $table->string('unidad_medida', 20)->default('Litros');
            $table->unsignedSmallInteger('octanaje')->nullable();
            $table->string('composicion', 255)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['producto_id', 'clave_subproducto']); // evita duplicados por producto
            $table->index(['id_planta', 'producto_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subproductos');
    }
};
