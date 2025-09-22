<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('tanque_virtual', function (Blueprint $table) {
            $table->id();

            // Identificadores
            $table->string('clave_identificacion_tanque', 100)->index();
            $table->string('producto_clave', 20)->index();
            $table->string('subproducto_clave', 20)->nullable()->index();

            // Capacidades/parametría (opcionales en virtual)
            $table->decimal('cap_total', 18, 6)->nullable();
            $table->decimal('cap_operativa', 18, 6)->nullable();
            $table->decimal('cap_util', 18, 6)->nullable();
            $table->decimal('cap_fondaje', 18, 6)->nullable();
            $table->decimal('cap_gas_talon', 18, 6)->nullable();
            $table->decimal('vol_min_operacion', 18, 6)->nullable();

            $table->enum('estado', ['activo','inactivo'])->default('activo');
            $table->boolean('es_virtual')->default(true);

            $table->date('vigencia_calibracion')->nullable();
            $table->string('localizacion_descripcion', 255)->nullable();

            $table->timestamps();

            // Blindaje operativo para evitar duplicados de tanque por producto/subproducto
            $table->unique(['producto_clave','subproducto_clave','clave_identificacion_tanque'], 'uniq_prod_subprod_tanque');
        });
    }

    public function down(): void {
        Schema::dropIfExists('tanque_virtual');
    }
};
