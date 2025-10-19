<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class BitacoraDispensario extends Model
{
    protected $table = 'bitacora_dispensarios';

    protected $fillable = [
        'id_planta',
        'fecha_hora',
        'tipo_evento',
        'severidad',
        'componente_tipo',
        'id_componente',
        'id_dispensario',
        'usuario_responsable',
        'descripcion',
    ];

    protected $casts = [
        'id_planta'      => 'integer',
        'fecha_hora'     => 'datetime',
        'id_componente'  => 'integer',
        'id_dispensario' => 'integer',
    ];

    /* === Scopes útiles === */
    public function scopePlanta(Builder $q, int $idPlanta): Builder
    {
        return $q->where('id_planta', $idPlanta);
    }

    public function scopeComponente(Builder $q, string $tipo, int $id): Builder
    {
        return $q->where('componente_tipo', $tipo)->where('id_componente', $id);
    }
}
