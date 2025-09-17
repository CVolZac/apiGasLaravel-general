<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla eventos_almacen con campos ampliados para VO/VC,
     * medición (estática/dinámica) y banderas de origen (nacional/extranjero).
     * Mantiene compatibilidad con tus columnas originales.
     */
    public function up(): void
    {
        Schema::create('eventos_almacen', function (Blueprint $table) {
            $table->id();

            // Relaciones base
            $table->unsignedBigInteger('id_almacen');                 // FK a almacen
            $table->unsignedBigInteger('id_tanque')->nullable();      // si manejas tanque específico
            $table->unsignedBigInteger('id_producto')->nullable();    // catálogo de productos (GLP, etc.)
            $table->string('lote', 100)->nullable();                  // lote (si aplica al evento)

            // Tipo de evento (como en tu UI): entrada | salida
            $table->enum('tipo_evento', ['entrada', 'salida']);

            // Volúmenes (compatibilidad + VO/VC explícitos)
            $table->decimal('volumen_inicial', 18, 6)->nullable();    // antes float(8,2)
            $table->decimal('volumen_movido', 18, 6)->nullable();     // compatibilidad: si no envías VO/VC, puedes seguir usando este
            $table->decimal('volumen_movido_vo', 18, 6)->nullable();  // volumen observado (VO)
            $table->decimal('volumen_movido_vc', 18, 6)->nullable();  // volumen compensado 20°C (VC)
            $table->decimal('volumen_final', 18, 6)->nullable();
            $table->string('unidad', 12)->nullable();                 // p.ej. "L" o "L20C"

            // Tiempos
            $table->dateTime('fecha_inicio_evento');
            $table->dateTime('fecha_fin_evento')->nullable();

            // Medición (bloque estática/dinámica)
            $table->enum('modo_medicion', ['estatica', 'dinamica'])->nullable();
            // ESTÁTICA
            $table->decimal('nivel', 12, 6)->nullable();
            $table->decimal('temperatura', 10, 4)->nullable();
            $table->decimal('presion_absoluta', 12, 6)->nullable();
            $table->decimal('densidad', 12, 6)->nullable();
            // DINÁMICA
            $table->decimal('caudal', 12, 6)->nullable();
            $table->decimal('tiempo', 12, 6)->nullable();
            $table->decimal('totalizador', 18, 6)->nullable();

            // Complemento: origen para decidir si capturas CFDIs (nacional) o pedimentos (extranjero)
            $table->enum('origen', ['nacional', 'extranjero'])->nullable();

            // Observaciones
            $table->text('observaciones')->nullable();

            $table->timestamps();

            // Foreign keys
            $table->foreign('id_almacen')
                  ->references('id')->on('almacen')   // ajusta a 'almacenes' si es tu nombre real
                  ->onUpdate('restrict')
                  ->onDelete('restrict');

            // Índices útiles
            $table->index(['id_almacen', 'id_tanque', 'fecha_inicio_evento']);
            $table->index(['lote']);
        });
    }

    /**
     * Reversa de la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('eventos_almacen');
    }
};
