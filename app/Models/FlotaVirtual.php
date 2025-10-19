<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlotaVirtual extends Model
{
    protected $table = 'flota_virtual';

    protected $fillable = [
        // Base
        'clave_contenedor',
        'tipo_contenedor',
        'producto_clave',
        'capacidad_valor',
        'capacidad_um',
        'estatus',
        'descripcion',

        // Información Tanque (solo aplica si tipo_contenedor = TANQUE_VIRTUAL)
        'tanque_clave_identificacion',
        'tanque_descripcion',
        'tanque_vigencia_calibracion',
        'tanque_estado',
        'tanque_cap_total_valor',
        'tanque_cap_total_um',
        'tanque_cap_oper_valor',
        'tanque_cap_oper_um',
        'tanque_cap_util_valor',
        'tanque_cap_util_um',
        'tanque_cap_fondaje_valor',
        'tanque_cap_fondaje_um',
        'tanque_vol_min_oper_valor',
        'tanque_vol_min_oper_um',
    ];

    protected $casts = [
        'capacidad_valor'            => 'decimal:6',
        'tanque_vigencia_calibracion'=> 'date',
        'tanque_cap_total_valor'     => 'decimal:6',
        'tanque_cap_oper_valor'      => 'decimal:6',
        'tanque_cap_util_valor'      => 'decimal:6',
        'tanque_cap_fondaje_valor'   => 'decimal:6',
        'tanque_vol_min_oper_valor'  => 'decimal:6',
    ];
}
