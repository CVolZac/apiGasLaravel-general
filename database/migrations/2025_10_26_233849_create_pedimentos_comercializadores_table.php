<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pedimentos_comercializadores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('evento_id'); // FK a eventos_comercializacion

            $table->string('numero_pedimento', 30);
            $table->string('incoterm', 10)->nullable();
            $table->string('medio_trans_aduana', 5)->nullable();        // catálogo SAT (1=marítimo, etc.)

            $table->decimal('precio_import_export', 18, 6)->nullable();
            $table->decimal('volumen_documentado_valor', 18, 6);
            $table->string('volumen_documentado_um', 10);

            $table->string('pais_origen_destino', 3);                    // ISO-3166-1 alpha-3
            $table->string('punto_internacion_extraccion', 20);

            $table->timestamps();

            $table->foreign('evento_id')->references('id')->on('eventos_comercializacion')->cascadeOnDelete();
            $table->index(['evento_id', 'numero_pedimento']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE pedimentos_comercializadores
                ADD CONSTRAINT chk_ped_um CHECK (volumen_documentado_um IN ('UM01','UM03','UM04'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pedimentos_comercializadores');
    }
};
