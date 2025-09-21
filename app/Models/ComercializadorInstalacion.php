<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ComercializadorInstalacion extends Model
{
    protected $table = 'comercializador_instalacion';

    protected $fillable = [
        'rfc_contribuyente',
        'rfc_representante_legal',
        'caracter',
        'modalidad_permiso',
        'num_permiso',
        'clave_instalacion',
        'descripcion_instalacion',
        'geolat',
        'geolng',
    ];

    public function tanques(): HasMany {
        return $this->hasMany(TanqueVirtual::class, 'instalacion_id');
    }
}
