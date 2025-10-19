<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlotaVirtual extends Model
{
  protected $table = 'flota_virtual';

  protected $fillable = [
    'clave_contenedor',
    'tipo_contenedor',
    'producto_clave',
    'capacidad_valor',
    'capacidad_um',
    'estatus',
    'descripcion',
  ];
}
