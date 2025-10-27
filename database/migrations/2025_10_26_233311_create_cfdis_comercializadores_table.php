<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cfdis_comercializadores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('evento_id'); // FK a eventos_comercializacion

            $table->string('uuid', 40)->nullable()->unique();   // algunos traslados internos no llevan UUID
            $table->enum('tipo_cfdi', ['I','E','T','P']);       // Ingreso/Egreso/Traslado/Pago
            $table->timestamp('fecha_hora_cfdi');

            $table->decimal('volumen_documentado_valor', 18, 6);
            $table->string('volumen_documentado_um', 10);       // UM03, UM04, etc.

            $table->decimal('precio', 18, 6)->nullable();
            $table->decimal('contraprestacion', 18, 6)->nullable();
            $table->decimal('monto_total', 18, 6)->nullable();

            // snapshots
            $table->string('rfc_emisor', 13)->nullable();
            $table->string('rfc_receptor', 13)->nullable();

            $table->timestamps();

            $table->foreign('evento_id')->references('id')->on('eventos_comercializacion')->cascadeOnDelete();
            $table->index(['evento_id', 'fecha_hora_cfdi']);
            $table->index(['evento_id', 'tipo_cfdi']);
        });

        // Check simple de UM (ajusta si permitirás más)
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE cfdis_comercializadores
                ADD CONSTRAINT chk_cfdis_um CHECK (volumen_documentado_um IN ('UM01','UM03','UM04'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cfdis_comercializadores');
    }
};
