<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('mangueras', function (Blueprint $table) {
            $table->unsignedBigInteger('id_subproducto')->nullable()->after('identificador_manguera');
            // Si tienes una tabla subproductos, puedes activar la FK:
            // $table->foreign('id_subproducto')->references('id')->on('subproductos')->nullOnDelete();
        });
    }

    public function down(): void {
        Schema::table('mangueras', function (Blueprint $table) {
            // Si tienes FK, bórrala primero:
            // $table->dropForeign(['id_subproducto']);
            $table->dropColumn('id_subproducto');
        });
    }
};
