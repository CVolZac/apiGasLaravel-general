<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComplementoTrasvase extends Model
{
    protected $table = 'complemento_trasvase';

    protected $fillable = [
        'evento_id',
        'nombre',
        'rfc',
        'permiso',
        'descripcion',
        'cfdi_uuid',
    ];

    public function evento()
    {
        return $this->belongsTo(EventoAlmacen::class, 'evento_id');
    }
}
