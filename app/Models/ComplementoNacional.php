<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComplementoNacional extends Model
{
    protected $table = 'complemento_nacional';

    protected $fillable = [
        'evento_id',
        'rfc_proveedor',
        'nombre_proveedor',
        'permiso_proveedor',
    ];

    public function evento()
    {
        return $this->belongsTo(EventoAlmacen::class, 'evento_id');
    }
}
