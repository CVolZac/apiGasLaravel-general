<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventoAlmacen extends Model
{
    use HasFactory;

    protected $table = 'eventos_almacen';

    protected $fillable = [
        'id_almacen',
        'tipo_evento',
        'volumen_inicial',
        'volumen_movido',
        'volumen_final',
        'fecha_inicio_evento',
        'fecha_fin_evento',
        'temperatura',
        'presion_absoluta',
        'observaciones'
    ];

    // Relación con el modelo Tanque (almacén)
    public function almacen()
    {
        return $this->belongsTo(Almacen::class, 'id_almacen', 'id');
    }

    public function cfdis()
    {
        return $this->hasMany(Cfdi::class, 'evento_id');
    }

}
