<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('dispensarios', function (Blueprint $table) {
            // Nuevo campo para guardar el número de permiso tal cual
            $table->string('numero_permiso_textual', 100)
                  ->nullable()
                  ->after('descripcion');
        });
    }

    public function down(): void
    {
        Schema::table('dispensarios', function (Blueprint $table) {
            $table->dropColumn('numero_permiso_textual');
        });
    }
};
