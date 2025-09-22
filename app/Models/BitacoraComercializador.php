<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BitacoraTransporte extends Model
{
    use HasFactory;

    protected $table = 'bitacora_transporte';

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
