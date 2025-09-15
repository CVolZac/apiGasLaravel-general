<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BitacoraEventos extends Model
{
    use HasFactory;

    protected $table = 'bitacora_eventos';

    protected $fillable = [
        'id_planta',
        'NumeroRegistro',
        'FechaYHoraEvento',
        'UsuarioResponsable',
        'TipoEvento',
        'DescripcionEvento',
        'IdentificacionComponenteAlarma',
        'TipoEquipo',
        'EquipoRelacionado'
    ];

    protected $casts = [
        'FechaYHoraEvento' => 'datetime',
    ];


}
