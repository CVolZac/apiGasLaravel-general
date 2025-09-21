<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoCaracterPlanta extends Model
{
    protected $table = 'tipo_caracter_planta';

    protected $fillable = [
        'informacion_general_reporte_id',
        'tipo_caracter',
        'modalidad_permiso',
        'numero_permiso',
        'numero_contrato_asignacion',
    ];

    public function informacionGeneral()
    {
        return $this->belongsTo(InformacionGeneralReporte::class, 'informacion_general_reporte_id');
    }
}