<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contratos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('contraparte_id')
                  ->constrained('contrapartes')
                  ->cascadeOnUpdate()
                  ->restrictOnDelete();

            // Vigencia
            $table->date('vigencia_inicio')->nullable();
            $table->date('vigencia_fin')->nullable();

            // Condiciones comerciales
            $table->enum('moneda', ['MXN','USD','EUR'])->default('MXN');
            $table->string('incoterm')->nullable();     // Si aplica import/export
            $table->string('lugar_entrega')->nullable();

            // Producto/subproducto/UM
            $table->string('clave_producto');           // PR##
            $table->string('clave_subproducto')->nullable();
            $table->string('um');                       // litros, kg, etc.

            // Precio / contraprestación
            $table->text('precio_base_formula')->nullable();
            $table->decimal('descuento_pct', 10, 4)->default(0);
            $table->enum('origen_precio', ['contrato','evento'])->default('contrato');

            // Políticas CFDI
            $table->json('tipos_cfdi')->nullable();     // ['I','E','T','P']
            $table->boolean('uuid_requerido')->default(true);
            $table->boolean('validar_fecha_importe')->default(true);

            // Servicios preacordados (opcional)
            $table->string('permiso_almacenamiento')->nullable();
            $table->string('permiso_transporte')->nullable();

            $table->enum('estatus', ['activo','inactivo'])->default('activo');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contratos');
    }
};
