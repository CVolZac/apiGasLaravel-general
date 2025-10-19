<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dispensario extends Model
{
    protected $table = 'dispensarios';

    protected $fillable = [
        'id_planta',
        'clave_dispensario',
        'descripcion',
    ];

    protected $casts = [
        'id_planta' => 'integer',
    ];

    public function mangueras()
    {
        return $this->hasMany(Manguera::class, 'id_dispensario');
    }

    public function medidores()
    {
        return $this->hasMany(MedidorDispensario::class, 'id_dispensario');
    }
}
