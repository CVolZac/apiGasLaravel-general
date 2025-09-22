<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BitacoraComercializador extends Model
{
    use HasFactory;

    protected $table = 'bitacora_comercializador';

    protected $fillable = [
        'FechaYHoraEvento',
        'TipoEvento',
        'DescripcionEvento',
    ];

    // Para exponer NumeroRegistro como alias de id
    protected $appends = ['NumeroRegistro'];

    public function getNumeroRegistroAttribute()
    {
        return $this->id;
    }
}
