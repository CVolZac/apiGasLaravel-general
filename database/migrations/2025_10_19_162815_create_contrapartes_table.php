<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contrapartes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');                      // Nombre / Razón social
            $table->string('rfc', 13)->nullable();         // RFC si ambito = nacional
            $table->enum('ambito', ['nacional','extranjero'])->default('nacional');
            $table->enum('rol', ['cliente','proveedor','mixto'])->default('mixto');
            $table->string('permiso_contraparte')->nullable(); // Permiso CRE/SENER de la contraparte (si aplica)

            $table->string('pais', 3)->default('MX');      // ISO-2/3; usa el que prefieras
            $table->string('correo')->nullable();
            $table->string('telefono')->nullable();

            // Campos base para extranjero (se usan luego en PEDIMENTOS)
            $table->string('punto_internacion')->nullable();
            $table->string('medio_aduana')->nullable();

            $table->text('observaciones')->nullable();
            $table->enum('estatus', ['activo','inactivo'])->default('activo');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contrapartes');
    }
};
