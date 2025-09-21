<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BitacoraComercializador extends Model
{
    protected $table = 'bitacora_comercializador';

    protected $fillable = [
        'entidad',
        'entidad_id',
        'accion',
        'usuario_id',
        'antes_json',
        'despues_json',
        'ip',
        'user_agent',
    ];
}
