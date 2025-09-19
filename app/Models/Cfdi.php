<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cfdi extends Model
{
    protected $table = 'cfdis';

    protected $fillable = [
        'evento_id',
        'uuid',
        'rfc_emisor',
        'nombre_emisor',
        'rfc_receptor',
        'monto_total',
        'fecha_hora',
        'tipo_cfdi',
        'precio_compra',
        'contraprestacion',
        'volumen_documentado_valor',
        'volumen_documentado_unidad',
        'tipo_complemento',
        'version',
    ];

    protected $casts = [
        'fecha_hora' => 'datetime',
        'monto_total' => 'decimal:2',
        'precio_compra' => 'decimal:6',
        'contraprestacion' => 'decimal:6',
        'volumen_documentado_valor' => 'decimal:6',
    ];

    public function evento()
    {
        return $this->belongsTo(EventoAlmacen::class, 'evento_id');
    }

    // Si usas la tabla pivote enriquecida evento_cfdi:
    public function eventoCfdis()
    {
        return $this->hasMany(EventoCfdi::class, 'cfdi_id');
    }
}
