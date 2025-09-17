<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComplementoTransporte extends Model
{
    protected $table = 'complemento_transporte';

    protected $fillable = [
        'evento_id',
        'permiso',
        'clave_vehiculo',
        'tarifa',
        'cargo_capacidad',
        'cargo_uso',
        'cargo_volumetrico',
    ];

    public function evento()
    {
        return $this->belongsTo(EventoAlmacen::class, 'evento_id');
    }
}
