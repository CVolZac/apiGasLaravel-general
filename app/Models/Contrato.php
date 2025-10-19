<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contrato extends Model
{
    use HasFactory;

    protected $table = 'contratos';

    protected $fillable = [
        'contraparte_id',
        'vigencia_inicio',
        'vigencia_fin',
        'moneda',
        'incoterm',
        'lugar_entrega',
        'clave_producto',
        'clave_subproducto',
        'um',
        'precio_base_formula',
        'descuento_pct',
        'origen_precio',
        'tipos_cfdi',
        'uuid_requerido',
        'validar_fecha_importe',
        'permiso_almacenamiento',
        'permiso_transporte',
        'estatus',
    ];

    protected $casts = [
        'vigencia_inicio' => 'date',
        'vigencia_fin' => 'date',
        'tipos_cfdi' => 'array',
        'uuid_requerido' => 'boolean',
        'validar_fecha_importe' => 'boolean',
        'descuento_pct' => 'decimal:4',
    ];

    // Relaciones
    public function contraparte()
    {
        return $this->belongsTo(Contraparte::class, 'contraparte_id');
    }
}
