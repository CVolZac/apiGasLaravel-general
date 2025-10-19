<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contraparte extends Model
{
    use HasFactory;

    protected $table = 'contrapartes';

    protected $fillable = [
        'nombre',
        'rfc',
        'ambito',
        'rol',
        'permiso_contraparte',
        'pais',
        'correo',
        'telefono',
        'punto_internacion',
        'medio_aduana',
        'observaciones',
        'estatus',
    ];

    protected $casts = [
        // nada especial aquí por ahora
    ];

    // Relaciones
    public function contratos()
    {
        return $this->hasMany(Contrato::class, 'contraparte_id');
    }
}
