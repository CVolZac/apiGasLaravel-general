<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventoComercializacion extends Model
{
    protected $table = 'eventos_comercializacion';

    protected $fillable = [
        'flota_virtual_id',
        'contraparte_id',
        'contrato_id',
        'tipo_evento',
        'tipo_registro',
        'producto_clave',
        'fecha_hora_inicio',
        'fecha_hora_fin',
        'temperatura',
        'presion_absoluta',
        'volumen_inicial_valor',
        'volumen_inicial_um',
        'volumen_movido_valor',
        'volumen_movido_um',
        'volumen_final_tanque',
        'existencias',
        'complemento',
        'observaciones',
    ];

    protected $casts = [
        'fecha_hora_inicio'   => 'datetime',
        'fecha_hora_fin'      => 'datetime',
        'temperatura'         => 'decimal:3',
        'presion_absoluta'    => 'decimal:3',
        'volumen_inicial_valor' => 'decimal:6',
        'volumen_movido_valor'  => 'decimal:6',
        'volumen_final_tanque'  => 'decimal:6',
        'existencias'         => 'array',
        'complemento'         => 'array',
    ];
}
