<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiciosComercializador extends Model
{
    protected $table = 'servicios_comercializadores';

    protected $fillable = [
        'evento_id','tipo_servicio','rfc_prestador','permiso_prestador','descripcion',
    ];

    public function evento()
    {
        return $this->belongsTo(EventosComercializacion::class, 'evento_id');
    }
}
