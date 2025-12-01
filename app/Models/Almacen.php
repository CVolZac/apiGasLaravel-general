<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Almacen extends Model
{
    use HasFactory;

    protected $table = 'almacen';

    protected $fillable = [

        // Comunes
        'id_planta',
        'tipo_tanque',
        'estado_tanque',

        // Campos PLANTA
        'clave_almacen',
        'localizacion_descripcion_almacen',
        'vigencia_calibracion_tanque',
        'capacidad_almacen',
        'capacidad_operativa',
        'capacidad_util',
        'capacidad_fondaje',
        'volumen_minimo_operacion',

        // Campos AUTOTANQUE
        'numero_economico',
        'placas',
        'permiso_cre',
        'descripcion_tanque'
    ];

    // Relaciones existentes
    public function registrosEntradasSalidasAlmacen()
    {
        return $this->hasMany(RegistroLlenadoAlmacen::class, 'id_almacen', 'id');
    }

    public function registrosEventos()
    {
        return $this->hasMany(EventoAlmacen::class, 'id_almacen', 'id');
    }

    public function registrosExistencia()
    {
        return $this->hasMany(ExistenciaAlmacen::class, 'id_almacen', 'id');
    }
}
