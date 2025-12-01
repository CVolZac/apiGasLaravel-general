<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('almacen', function (Blueprint $table) {

            // ==============
            // NUEVO TIPO
            // ==============
            if (!Schema::hasColumn('almacen', 'tipo_tanque')) {
                $table->enum('tipo_tanque', ['planta', 'autotanque'])
                      ->default('planta')
                      ->after('id_planta');
            }

            // ==============
            // CAMPOS PARA AUTOTANQUE
            // ==============
            if (!Schema::hasColumn('almacen', 'numero_economico')) {
                $table->string('numero_economico')->nullable()->after('tipo_tanque');
            }

            if (!Schema::hasColumn('almacen', 'placas')) {
                $table->string('placas')->nullable()->after('numero_economico');
            }

            if (!Schema::hasColumn('almacen', 'permiso_cre')) {
                $table->string('permiso_cre')->nullable()->after('placas');
            }

            if (!Schema::hasColumn('almacen', 'descripcion_tanque')) {
                $table->string('descripcion_tanque')->nullable()->after('permiso_cre');
            }

            // Nota:
            // Tus campos existentes siguen siendo válidos para tanques planta.
            // Estos campos nuevos permiten registrar pipas sin romper estructura.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('almacen', function (Blueprint $table) {

            // Para revertir: eliminar columnas
            if (Schema::hasColumn('almacen', 'tipo_tanque')) {
                $table->dropColumn('tipo_tanque');
            }
            if (Schema::hasColumn('almacen', 'numero_economico')) {
                $table->dropColumn('numero_economico');
            }
            if (Schema::hasColumn('almacen', 'placas')) {
                $table->dropColumn('placas');
            }
            if (Schema::hasColumn('almacen', 'permiso_cre')) {
                $table->dropColumn('permiso_cre');
            }
            if (Schema::hasColumn('almacen', 'descripcion_tanque')) {
                $table->dropColumn('descripcion_tanque');
            }

        });
    }
};
