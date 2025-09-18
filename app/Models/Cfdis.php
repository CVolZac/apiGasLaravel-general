<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cfdis extends Model
{
    protected $table = 'cfdis';

    protected $fillable = [
        'evento_id',
        'tipo_complemento', // si decides conservarlo (ahora no lo toqué)
        'version',          // idem

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

    public function eventoCfdis()
    {
        return $this->hasMany(EventoCfdi::class, 'cfdi_id');
    }
}
