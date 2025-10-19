<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedidorDispensario extends Model
{
    protected $table = 'medidor_dispensarios';

    protected $fillable = [
        'id_dispensario',
        'sistema_medicion_dispensario',
        'descripcion_medidor',
        'vigencia_calibracion',
        'incertidumbre_medicion',
    ];

    protected $casts = [
        'id_dispensario' => 'integer',
        'vigencia_calibracion' => 'date:Y-m-d',
        'incertidumbre_medicion' => 'decimal:4',
    ];

    public function dispensario()
    {
        return $this->belongsTo(Dispensario::class, 'id_dispensario');
    }
}
