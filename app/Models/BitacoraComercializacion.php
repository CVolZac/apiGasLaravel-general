<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BitacoraComercializacion extends Model
{
  protected $table = 'bitacora_comercializacion';

  protected $fillable = [
    'fecha_hora_evento', 'tipo_evento', 'descripcion_evento'
  ];

  protected $casts = [
    'fecha_hora_evento' => 'datetime',
  ];
}
