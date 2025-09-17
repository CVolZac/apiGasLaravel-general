<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventoCfdi extends Model
{
    protected $table = 'evento_cfdi';

    protected $fillable = [
        'evento_id',
        'cfdi_id',
        'tipo_cfdi',
        'fecha_hora',
        'precio_compra',
        'contraprestacion',
        'volumen_documentado_valor',
        'volumen_documentado_unidad',
        'monto_total',
        'rfc_emisor',
        'nombre_emisor',
        'rfc_receptor',
    ];

    protected $casts = [
        'fecha_hora' => 'datetime',
        'precio_compra' => 'decimal:6',
        'contraprestacion' => 'decimal:6',
        'volumen_documentado_valor' => 'decimal:6',
        'monto_total' => 'decimal:6',
    ];

    public function evento()
    {
        return $this->belongsTo(EventoAlmacen::class, 'evento_id');
    }

    public function cfdi()
    {
        return $this->belongsTo(Cfdi::class, 'cfdi_id');
    }
}
