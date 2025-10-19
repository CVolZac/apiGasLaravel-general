<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AjusteTotalizadorManguera extends Model
{
    protected $table = 'ajustes_totalizador_manguera';

    protected $fillable = [
        'id_manguera',
        'fecha_hora_ajuste',
        'lectura_anterior',
        'lectura_nueva',
        'aplica_desde',
        'motivo',
    ];

    protected $casts = [
        'id_manguera' => 'integer',
        'fecha_hora_ajuste' => 'datetime',
        'lectura_anterior' => 'decimal:3',
        'lectura_nueva' => 'decimal:3',
        'aplica_desde' => 'date:Y-m-d',
    ];

    public function manguera() { return $this->belongsTo(\App\Models\Manguera::class, 'id_manguera'); }
}
