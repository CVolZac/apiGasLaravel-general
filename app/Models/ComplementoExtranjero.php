<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComplementoExtranjero extends Model
{
    protected $table = 'complemento_extranjero';

    protected $fillable = [
        'evento_id',
        'permiso_importacion',
    ];

    public function evento()
    {
        return $this->belongsTo(EventoAlmacen::class, 'evento_id');
    }
}
