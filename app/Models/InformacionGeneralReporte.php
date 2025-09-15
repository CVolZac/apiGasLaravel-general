<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InformacionGeneralReporte extends Model
{
    use HasFactory;

    protected $table = 'informacion_general_reporte';

    protected $casts = [
    'rfc_proveedores' => 'array'
    ];


    protected $fillable = [
        'id_planta',
        'rfc_contribuyente',
        'rfc_representante_legal',
        'rfc_proveedor',
        'rfc_proveedores',
        'tipo_caracter',
        'modalidad_permiso',
        'numero_permiso',
        'numero_contrato_asignacion',
        'instalacion_almacen_gas',
        'clave_instalacion',
        'descripcion_instalacion',
        'geolocalizacion_latitud',
        'geolocalizacion_longitud',
        'numero_pozos',
        'numero_tanques',
        'numero_ductos_entrada_salida',
        'numero_ductos_transporte',
        'numero_dispensarios',
    ];
}
