<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cfdis extends Model
{
    use HasFactory;
    protected $fillable = [
        'evento_id',
        'TipoComplemento',
        'Version',
        'UUID',
        'RFCEmisorCFDI',
        'NombreEmisorCFDI',
        'RFCProveedorReceptor',
        'MontoTotalOperacion',
        'FechaCFDI'
    ];

    public function evento()
    {
        return $this->belongsTo(EventoAlmacen::class, 'evento_id');
    }
}
