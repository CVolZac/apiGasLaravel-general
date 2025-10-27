<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CfdisComercializador extends Model
{
    protected $table = 'cfdis_comercializadores';

    protected $fillable = [
        'evento_id',
        'uuid',
        'tipo_cfdi',
        'fecha_hora_cfdi',
        'volumen_documentado_valor',
        'volumen_documentado_um',
        'precio',
        'contraprestacion',
        'monto_total',
        'rfc_emisor',
        'rfc_receptor',
    ];

    protected $casts = [
        'fecha_hora_cfdi' => 'datetime',
        'volumen_documentado_valor' => 'decimal:6',
        'precio' => 'decimal:6',
        'contraprestacion' => 'decimal:6',
        'monto_total' => 'decimal:6',
    ];

    public function evento()
    {
        return $this->belongsTo(EventosComercializacion::class, 'evento_id');
    }
}
