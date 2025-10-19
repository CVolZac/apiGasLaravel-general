<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BitacoraComercializacion extends Model
{
  protected $table = 'bitacora_comercializacion';

  protected $fillable = [
    'entidad','entidad_id','accion','descripcion','payload','usuario_id',
  ];

  protected $casts = [
    'payload' => 'array',
  ];
}
