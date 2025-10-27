<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PedimentosComercializador extends Model
{
    protected $table = 'pedimentos_comercializadores';

    protected $fillable = [
        'evento_id','numero_pedimento','incoterm','medio_trans_aduana',
        'precio_import_export','volumen_documentado_valor','volumen_documentado_um',
        'pais_origen_destino','punto_internacion_extraccion',
    ];

    protected $casts = [
        'volumen_documentado_valor' => 'decimal:6',
        'precio_import_export' => 'decimal:6',
    ];

    public function evento()
    {
        return $this->belongsTo(EventosComercializacion::class, 'evento_id');
    }
}
